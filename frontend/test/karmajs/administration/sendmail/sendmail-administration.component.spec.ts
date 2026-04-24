import { ComponentFixture, TestBed, fakeAsync, tick } from '@angular/core/testing';
import { HttpClientTestingModule } from '@angular/common/http/testing';
import { TranslateService, TranslateModule, TranslateLoader } from '@ngx-translate/core';
import { NotificationService } from '@service/notification/notification.service';
import { HeaderService } from '@service/header.service';
import { AppService } from '@service/app.service';
import { FormsModule } from '@angular/forms';
import { MatLegacyDialogModule as MatDialogModule, MatLegacyDialog as MatDialog } from '@angular/material/legacy-dialog';
import { MatLegacyCardModule as MatCardModule } from '@angular/material/legacy-card';
import { MatLegacyFormFieldModule as MatFormFieldModule } from '@angular/material/legacy-form-field';
import { MatLegacyInputModule as MatInputModule } from '@angular/material/legacy-input';
import { MatLegacySelectModule as MatSelectModule } from '@angular/material/legacy-select';
import { MatLegacySlideToggleModule as MatSlideToggleModule } from '@angular/material/legacy-slide-toggle';
import { MatLegacyTabsModule as MatTabsModule } from '@angular/material/legacy-tabs';
import { MatLegacyProgressSpinnerModule as MatProgressSpinnerModule } from '@angular/material/legacy-progress-spinner';
import { MatIconModule } from '@angular/material/icon';
import { NoopAnimationsModule } from '@angular/platform-browser/animations';
import { Observable, of, throwError } from 'rxjs';
import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { SendmailAdministrationComponent, SendMailConfigInterface } from '@appRoot/administration/sendmail/sendmail-administration.component';
import { CheckMailServerModalComponent } from '@appRoot/administration/sendmail/checkMailServer/check-mail-server-modal.component';
import * as langFrJson from '@langs/lang-fr.json';

class FakeLoader implements TranslateLoader {
    getTranslation(): Observable<any> {
        return of({ lang: langFrJson });
    }
}

describe('SendmailAdministrationComponent', () => {
    let component: SendmailAdministrationComponent;
    let fixture: ComponentFixture<SendmailAdministrationComponent>;
    let httpClientSpy: { get: jasmine.Spy, put: jasmine.Spy };
    let notifyServiceSpy: { success: jasmine.Spy, handleErrors: jasmine.Spy, handleSoftErrors: jasmine.Spy };
    let headerServiceSpy: { setHeader: jasmine.Spy, injectInSideBarLeft: jasmine.Spy, user: any };
    let appServiceSpy: { getViewMode: jasmine.Spy };
    let dialogSpy: { open: jasmine.Spy };
    let translateService: TranslateService;

    const mockConfig: SendMailConfigInterface = {
        type: 'smtp',
        host: 'smtp.example.com',
        auth: true,
        user: 'test@example.com',
        password: 'password123',
        secure: 'ssl',
        port: '465',
        charset: 'utf-8',
        from: 'noreply@example.com',
        tenantId: '',
        clientId: '',
        clientSecret: '',
        smarthost: '',
        useSMTPAuth: false,
        passwordAlreadyExists: true
    };

    beforeEach(async () => {
        httpClientSpy = jasmine.createSpyObj('HttpClient', ['get', 'put']);
        notifyServiceSpy = jasmine.createSpyObj('NotificationService', ['success', 'handleErrors', 'handleSoftErrors']);
        headerServiceSpy = jasmine.createSpyObj('HeaderService', ['setHeader', 'injectInSideBarLeft']);
        headerServiceSpy.user = { mail: 'test@example.com' };
        appServiceSpy = jasmine.createSpyObj('AppService', ['getViewMode']);
        dialogSpy = jasmine.createSpyObj('MatDialog', ['open']);

        await TestBed.configureTestingModule({
            declarations: [SendmailAdministrationComponent],
            imports: [
                HttpClientTestingModule,
                FormsModule,
                NoopAnimationsModule,
                MatDialogModule,
                MatCardModule,
                MatFormFieldModule,
                MatInputModule,
                MatSelectModule,
                MatSlideToggleModule,
                MatTabsModule,
                MatProgressSpinnerModule,
                MatIconModule,
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: FakeLoader },
                }),
            ],
            providers: [
                { provide: HttpClient, useValue: httpClientSpy },
                { provide: NotificationService, useValue: notifyServiceSpy },
                { provide: HeaderService, useValue: headerServiceSpy },
                { provide: AppService, useValue: appServiceSpy },
                { provide: MatDialog, useValue: dialogSpy },
                TranslateService
            ],
            schemas: [CUSTOM_ELEMENTS_SCHEMA]
        }).compileComponents();

        // Set lang
        translateService = TestBed.inject(TranslateService);
        translateService.use('fr');
    });

    beforeEach(() => {
        fixture = TestBed.createComponent(SendmailAdministrationComponent);
        component = fixture.componentInstance;

        // Mock HTTP responses
        httpClientSpy.get.and.returnValue(of({
            configuration: {
                value: mockConfig
            }
        }));

        httpClientSpy.put.and.returnValue(of({}));

        // Initialize spies
        appServiceSpy.getViewMode.and.returnValue(false);
    });

    it('should create the component', () => {
        expect(component).toBeTruthy();
    });

    it('should initialize component with existing configuration', fakeAsync(() => {
        component.ngOnInit();
        tick();

        expect(component.loading).toBeFalse();
        expect(component.sendmail).toEqual(mockConfig);
        expect(component.sendmailClone).toEqual(mockConfig);
        expect(component.recipientTest).toEqual('test@example.com');
        expect(headerServiceSpy.setHeader).toHaveBeenCalled();
        expect(headerServiceSpy.injectInSideBarLeft).toHaveBeenCalled();
    }));

    it('should handle errors during initialization', fakeAsync(() => {
        httpClientSpy.get.and.returnValue(throwError(() => new Error('Test error')));

        component.ngOnInit();
        fixture.detectChanges();
        tick();

        expect(component.loading).toBeFalse();
        expect(notifyServiceSpy.handleSoftErrors).toHaveBeenCalled();
    }));

    it('should cancel modifications', () => {
        // Set initial state
        component.sendmail = { ...mockConfig };
        component.sendmailClone = { ...mockConfig };

        // Modify something
        component.sendmail.host = 'new-host.example.com';

        // Verify it's different
        expect(component.sendmail).not.toEqual(component.sendmailClone);

        // Cancel modifications
        component.cancelModification();

        // Verify it's back to initial state
        expect(component.sendmail).toEqual(component.sendmailClone);
    });

    it('should submit the configuration', fakeAsync(() => {
        component.sendmail = { ...mockConfig };

        const result: Promise<boolean> = component.onSubmit();

        fixture.detectChanges();
        tick();

        expect(httpClientSpy.put).toHaveBeenCalledWith('../rest/configurations/admin_email_server', component.sendmail);
        expect(notifyServiceSpy.success).toHaveBeenCalled();
        expect(component.sendmailClone).toEqual(component.sendmail);

        result.then(success => {
            expect(success).toBeTrue();
        });
    }));

    it('should handle errors during submission', fakeAsync(() => {
        httpClientSpy.put.and.returnValue(throwError(() => new Error('Test error')));

        component.sendmail = { ...mockConfig };

        const result: Promise<boolean> = component.onSubmit();

        fixture.detectChanges();
        tick();

        expect(httpClientSpy.put).toHaveBeenCalled();
        expect(notifyServiceSpy.handleErrors).toHaveBeenCalled();
        expect(component.savingConfig).toBeFalse();

        result.then(success => {
            expect(success).toBeFalse();
        });
    }));

    it('should check if there are modifications', () => {
        // Set identical initial state
        component.sendmail = { ...mockConfig };
        component.sendmailClone = { ...mockConfig };

        // No difference
        expect(component.checkModif()).toBeTrue();

        // Modification
        component.sendmail.host = 'new-host.example.com';

        // Now there's a difference
        expect(component.checkModif()).toBeFalse();
    });

    it('should clean authentication information', () => {
        component.sendmail = {
            ...mockConfig,
            user: 'test@example.com',
            password: 'password123',
            passwordAlreadyExists: true
        };

        component.cleanAuthInfo();

        expect(component.sendmail.user).toBe('');
        expect(component.sendmail.password).toBe('');
        expect(component.sendmail.passwordAlreadyExists).toBeFalse();
    });

    it('should open mail server test modal after successful submission', fakeAsync(() => {
        spyOn(component, 'onSubmit').and.returnValue(Promise.resolve(true));

        component.openMailServerTest();

        fixture.detectChanges();
        tick();

        expect(component.onSubmit).toHaveBeenCalled();
        expect(dialogSpy.open).toHaveBeenCalledWith(CheckMailServerModalComponent, {
            panelClass: 'maarch-modal',
            disableClose: true,
            width: '500px',
            data: {
                serverConf: component.sendmail,
                recipient: component.recipientTest,
                sender: component.emailSendResult
            }
        });
        expect(component.savingConfig).toBeFalse();
    }));

    it('should not open test modal if submission fails', fakeAsync(() => {
        spyOn(component, 'onSubmit').and.returnValue(Promise.resolve(false));

        component.openMailServerTest();

        fixture.detectChanges();
        tick();

        expect(component.onSubmit).toHaveBeenCalled();
        expect(dialogSpy.open).not.toHaveBeenCalled();
        expect(component.savingConfig).toBeFalse();
    }));

    it('should correctly format config for Microsoft OAuth', () => {
        component.sendmail = {
            ...mockConfig,
            type: 'microsoftOAuth',
            tenantId: 'tenant123',
            clientId: 'client123',
            clientSecret: 'secret123',
            useSMTPAuth: false
        };

        const formattedConfig = component.formatSendMailConfig();

        expect(formattedConfig.user).toBeUndefined();
        expect(formattedConfig.charset).toBeUndefined();
        expect(formattedConfig.host).toBeUndefined();
        expect(formattedConfig.port).toBeUndefined();
        expect(formattedConfig.secure).toBeUndefined();
        expect(formattedConfig.password).toBeUndefined();
        expect(formattedConfig.passwordAlreadyExists).toBeUndefined();

        expect(formattedConfig.type).toBe('microsoftOAuth');
        expect(formattedConfig.tenantId).toBe('tenant123');
        expect(formattedConfig.clientId).toBe('client123');
        expect(formattedConfig.clientSecret).toBe('secret123');
        expect(formattedConfig.useSMTPAuth).toBe(false);
    });
});
