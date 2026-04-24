import { ComponentFixture, TestBed, fakeAsync, tick } from '@angular/core/testing';
import { HttpClientTestingModule } from '@angular/common/http/testing';
import { MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA, MatLegacyDialogRef as MatDialogRef } from '@angular/material/legacy-dialog';
import { TranslateService, TranslateModule, TranslateLoader } from '@ngx-translate/core';
import { AuthService } from '@service/auth.service';
import { Observable, of, throwError } from 'rxjs';
import { NO_ERRORS_SCHEMA } from '@angular/core';
import { CheckMailServerModalComponent } from '@appRoot/administration/sendmail/checkMailServer/check-mail-server-modal.component';
import { HttpClient } from '@angular/common/http';
import * as langFrJson from '@langs/lang-fr.json';

class FakeLoader implements TranslateLoader {
    getTranslation(): Observable<any> {
        return of({ lang: langFrJson });
    }
}

describe('CheckMailServerModalComponent', () => {
    let component: CheckMailServerModalComponent;
    let fixture: ComponentFixture<CheckMailServerModalComponent>;
    let httpClientSpy: { post: jasmine.Spy };
    let translateServiceSpy: jasmine.SpyObj<TranslateService>;
    let authServiceSpy: jasmine.SpyObj<AuthService>;
    let dialogRefSpy: jasmine.SpyObj<MatDialogRef<CheckMailServerModalComponent>>;

    const mockData = {
        serverConf: {
            from: 'test@example.com'
        },
        recipient: 'recipient@example.com'
    };

    beforeEach(() => {
        httpClientSpy = jasmine.createSpyObj('HttpClient', ['post']);
        translateServiceSpy = jasmine.createSpyObj('TranslateService', ['instant']);
        authServiceSpy = jasmine.createSpyObj('AuthService', [], { mailServerOnline: false });
        dialogRefSpy = jasmine.createSpyObj('MatDialogRef', ['close']);

        // Configure translate spy
        translateServiceSpy.instant.and.callFake((key: string, params?: any) => {
            if (key === 'lang.emailSendInProgressShort') return 'Sending email...';
            if (key === 'lang.emailSendInProgress') return `Sending email to ${params[0]}...`;
            if (key === 'lang.doNotReply') return 'DO NOT REPLY';
            if (key === 'lang.emailSendTest') return 'Test email';
            if (key === 'lang.emailSendSuccess') return `Email sent successfully to ${params[0]}`;
            if (key === 'lang.emailSendFailed')
                return `Failed to send email from ${params.sender} to ${params.recipient}`;
            return key;
        });

        TestBed.configureTestingModule({
            imports: [
                HttpClientTestingModule,
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: FakeLoader },
                }),
            ],
            declarations: [CheckMailServerModalComponent],
            providers: [
                { provide: HttpClient, useValue: httpClientSpy },
                { provide: TranslateService, useValue: translateServiceSpy },
                { provide: MAT_DIALOG_DATA, useValue: mockData },
                { provide: MatDialogRef, useValue: dialogRefSpy },
                { provide: AuthService, useValue: authServiceSpy }
            ],
            schemas: [NO_ERRORS_SCHEMA] // Ignore unrecognized elements for app-maarch-message
        }).compileComponents();

        fixture = TestBed.createComponent(CheckMailServerModalComponent);
        component = fixture.componentInstance;

        httpClientSpy.post.and.returnValue(of({}));
    });

    it('should create', () => {
        expect(component).toBeTruthy();
    });

    it('should initialize with the correct data from MAT_DIALOG_DATA', () => {
        component.ngOnInit();
        expect(component.serverConf).toEqual(mockData.serverConf);
        expect(component.recipient).toEqual(mockData.recipient);
        expect(component.statusMsg).toEqual('Email sent successfully to recipient@example.com');
    });

    it('should call testEmailServer on initialization', () => {
        component.serverConf = mockData.serverConf;
        component.recipient = mockData.recipient;
        spyOn(component, 'testEmailServer');
        component.ngOnInit();
        expect(component.testEmailServer).toHaveBeenCalled();
    });

    it('should send a test email with correct parameters', () => {
        httpClientSpy.post.and.returnValue(of({}));
        component.serverConf = mockData.serverConf;
        component.recipient = mockData.recipient;

        component.testEmailServer();

        expect(httpClientSpy.post).toHaveBeenCalledWith('../rest/emails', {
            'sender': { 'email': 'test@example.com' },
            'recipients': ['recipient@example.com'],
            'object': '[DO NOT REPLY] Test email',
            'status': 'EXPRESS',
            'body': 'Test email',
            'isHtml': false
        });
    });

    it('should handle successful email test', fakeAsync(() => {
        httpClientSpy.post.and.returnValue(of({}));
        component.serverConf = mockData.serverConf;
        component.recipient = mockData.recipient;

        component.testEmailServer();

        // fixture.detectChanges();
        tick(300);

        expect(component.loading).toBeFalse();
        expect(component.statusMsg).toEqual('Email sent successfully to recipient@example.com');
        // expect(authServiceSpy.mailServerOnline).toBeTrue();

        // Check if dialog closes after timeout
        tick(2000);
        expect(dialogRefSpy.close).toHaveBeenCalledWith('success');
    }));

    it('should handle failed email test', () => {
        const errorResponse = {
            error: {
                errors: 'SMTP connection failed'
            }
        };

        httpClientSpy.post.and.returnValue(throwError(errorResponse));
        component.serverConf = mockData.serverConf;
        component.recipient = mockData.recipient;

        component.testEmailServer();

        expect(component.loading).toBeFalse();
        expect(component.statusMsg).toEqual('Failed to send email from test@example.com to recipient@example.com');
        expect(component.error).toEqual('SMTP connection failed');
        expect(authServiceSpy.mailServerOnline).toBeFalse();
    });

    it('should set loading to false in all scenarios', () => {
        // Test with success case
        component.serverConf = mockData.serverConf;
        component.recipient = mockData.recipient;
        httpClientSpy.post.and.returnValue(of({}));
        component.testEmailServer();
        expect(component.loading).toBeFalse();

        // Reset loading
        component.loading = true;

        // Test with error case
        httpClientSpy.post.and.returnValue(throwError({ error: { errors: 'Error' } }));
        component.testEmailServer();
        expect(component.loading).toBeFalse();
    });
});