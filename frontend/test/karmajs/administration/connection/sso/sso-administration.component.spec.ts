import { HttpClientTestingModule } from '@angular/common/http/testing';
import { ComponentFixture, TestBed, fakeAsync, tick } from '@angular/core/testing';
import { FormsModule, NgForm } from '@angular/forms';
import { MatLegacyDialog as MatDialog } from '@angular/material/legacy-dialog';
import { NoopAnimationsModule } from '@angular/platform-browser/animations';
import { RouterTestingModule } from '@angular/router/testing';
import { TranslateLoader, TranslateModule, TranslateService } from '@ngx-translate/core';
import { Observable, of, throwError } from 'rxjs';
import { AppService } from '@service/app.service';
import { AuthService } from '@service/auth.service';
import { HeaderService } from '@service/header.service';
import { NotificationService } from '@service/notification/notification.service';
import { HttpClient } from '@angular/common/http';
import { ConfirmComponent } from '@plugins/modal/confirm.component';
import { SsoAdministrationComponent } from '@appRoot/administration/connection/sso/sso-administration.component';
import { AdministrationService } from '@appRoot/administration/administration.service';
import { DatePipe } from '@angular/common';
import { PrivilegeService } from '@service/privileges.service';
import * as langFrJson from '@langs/lang-fr.json';
import { LatinisePipe } from 'ngx-pipes';
import { MatExpansionModule } from '@angular/material/expansion';
import { MatLegacyFormFieldModule } from '@angular/material/legacy-form-field';
import { MatLegacyInputModule } from '@angular/material/legacy-input';
import { MatLegacyListModule } from '@angular/material/legacy-list';
import { MatLegacyProgressSpinnerModule } from '@angular/material/legacy-progress-spinner';


class FakeLoader implements TranslateLoader {
    getTranslation(): Observable<any> {
        return of({ lang: langFrJson });
    }
}

describe('SsoAdministrationComponent', () => {
    let component: SsoAdministrationComponent;
    let fixture: ComponentFixture<SsoAdministrationComponent>;
    let httpClient: jasmine.SpyObj<HttpClient>;
    let notificationService: jasmine.SpyObj<NotificationService>;
    let headerService: jasmine.SpyObj<HeaderService>;
    let authService: jasmine.SpyObj<AuthService>;
    let dialog: jasmine.SpyObj<MatDialog>;
    let ngForm: NgForm;
    let translateService: TranslateService;

    const mockSsoConfig = {
        configuration: {
            value: {
                url: 'https://sso.maarchcourrier/dist/index.html#/login',
                ssoLogoutUrl: 'https://sso.maarchcourrier/dist/index.html#/logout',
                mapping: [
                    {
                        maarchId: 'login',
                        ssoId: 'userId',
                        desc: 'User ID description'
                    }
                ]
            }
        }
    };

    beforeEach(async () => {
        const httpClientSpy = jasmine.createSpyObj('HttpClient', ['get', 'put']);
        const notificationServiceSpy = jasmine.createSpyObj('NotificationService', ['success', 'handleSoftErrors']);
        const headerServiceSpy = jasmine.createSpyObj('HeaderService', ['setHeader', 'injectInSideBarLeft']);
        const authServiceSpy = jasmine.createSpyObj('AuthService', [], { authMode: 'standard' });
        const dialogSpy = jasmine.createSpyObj('MatDialog', ['open']);

        await TestBed.configureTestingModule({
            declarations: [SsoAdministrationComponent],
            imports: [
                HttpClientTestingModule,
                RouterTestingModule,
                FormsModule,
                MatLegacyListModule,
                MatLegacyProgressSpinnerModule,
                MatLegacyFormFieldModule,
                MatLegacyInputModule,
                MatExpansionModule,
                NoopAnimationsModule,
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: FakeLoader },
                }),
            ],
            providers: [
                { provide: HttpClient, useValue: httpClientSpy },
                { provide: NotificationService, useValue: notificationServiceSpy },
                { provide: HeaderService, useValue: headerServiceSpy },
                { provide: AuthService, useValue: authServiceSpy },
                { provide: MatDialog, useValue: dialogSpy },
                AppService,
                AdministrationService,
                TranslateService,
                DatePipe,
                PrivilegeService,
                LatinisePipe
            ]
        }).compileComponents();

        // Set lang
        translateService = TestBed.inject(TranslateService);
        translateService.use('fr');

        httpClient = TestBed.inject(HttpClient) as jasmine.SpyObj<HttpClient>;
        notificationService = TestBed.inject(NotificationService) as jasmine.SpyObj<NotificationService>;
        headerService = TestBed.inject(HeaderService) as jasmine.SpyObj<HeaderService>;
        authService = TestBed.inject(AuthService) as jasmine.SpyObj<AuthService>;
        dialog = TestBed.inject(MatDialog) as jasmine.SpyObj<MatDialog>;
    });

    beforeEach(() => {
        httpClient.get.and.returnValue(of(mockSsoConfig));
        httpClient.put.and.returnValue(of({}));

        const dialogRefSpyObj = jasmine.createSpyObj({ afterClosed: of('ok') });
        dialog.open.and.returnValue(dialogRefSpyObj);

        fixture = TestBed.createComponent(SsoAdministrationComponent);
        component = fixture.componentInstance;
        ngForm = new NgForm([], []);

        fixture.detectChanges();
    });

    it('should create', () => {
        expect(component).toBeTruthy();
    });

    it('should initialize the component and load SSO configuration', fakeAsync(() => {
        component.ngOnInit();
        fixture.detectChanges();
        tick();

        component.sso = mockSsoConfig.configuration.value;

        fixture.detectChanges();
        tick(100);

        expect(headerService.injectInSideBarLeft).toHaveBeenCalled();
        expect(headerService.setHeader).toHaveBeenCalled();
        expect(httpClient.get).toHaveBeenCalledWith('../rest/configurations/admin_sso');
        expect(component.sso).toEqual(mockSsoConfig.configuration.value);
        expect(component.ssoClone).toEqual(mockSsoConfig.configuration.value);
        expect(component.loading).toBe(false);

        ngForm.form.markAsTouched();

        fixture.detectChanges();
        tick(100);

        const nativeElement = fixture.nativeElement;

        expect(nativeElement.querySelector('input[name="ssoUrl"]').value).toEqual('https://sso.maarchcourrier/dist/index.html#/login');
        expect(nativeElement.querySelector('input[name="ssoLogoutUrl"]').value).toEqual('https://sso.maarchcourrier/dist/index.html#/logout');
        expect(nativeElement.querySelector('input[name="input_0"]').value).toEqual('userId');
    }));

    it('should handle error when loading SSO configuration', fakeAsync(() => {
        const error = { error: 'error message' };
        httpClient.get.and.returnValue(throwError(error));

        component.getConnection();
        fixture.detectChanges();
        tick();

        expect(notificationService.handleSoftErrors).toHaveBeenCalledWith(error);
    }));

    it('should validate form when data has changed and form is valid', fakeAsync(() => {
        component.sso = {
            url: 'https://sso.maarchcourrier/dist/index.html#/login',
            ssoLogoutUrl: 'https://sso.maarchcourrier/dist/index.html#/logout',
            mapping: [
                {
                    maarchId: 'login',
                    ssoId: 'userId',
                    desc: 'User ID description'
                }
            ]
        };

        component.ssoClone = {
            url: 'https://ssomaarchcourrier/dist/index.html#/login',
            ssoLogoutUrl: 'https://sso.maarchcourrier/dist/index.html#/logout',
            mapping: [
                {
                    maarchId: 'login',
                    ssoId: 'userId',
                    desc: 'User ID description'
                }
            ]
        };

        ngForm.form.markAsTouched();

        fixture.detectChanges();
        tick();

        const result = component.isValid(ngForm);

        expect(result).toBeTrue();

        fixture.detectChanges();
        tick();

        const nativeElement = fixture.nativeElement;

        const validBtn = nativeElement.querySelector('button[name="submit"]');
        expect(validBtn.disabled).toBeFalse();

        const cancelBtn = nativeElement.querySelector('button[name="cancel"]');
        expect(cancelBtn.disabled).toBeFalse();
    }));

    it('should not validate form when data has not changed', fakeAsync(() => {
        component.sso = {
            url: 'https://sso.maarchcourrier/dist/index.html#/login',
            ssoLogoutUrl: 'https://sso.maarchcourrier/dist/index.html#/logout',
            mapping: [
                {
                    maarchId: 'login',
                    ssoId: 'userId',
                    desc: 'User ID description'
                }
            ]
        };

        component.ssoClone = {
            url: 'https://sso.maarchcourrier/dist/index.html#/login',
            ssoLogoutUrl: 'https://sso.maarchcourrier/dist/index.html#/logout',
            mapping: [
                {
                    maarchId: 'login',
                    ssoId: 'userId',
                    desc: 'User ID description'
                }
            ]
        };

        fixture.detectChanges();
        tick();

        const result = component.isValid(ngForm);

        expect(result).toBeFalse();

        const nativeElement = fixture.nativeElement;

        const validBtn = nativeElement.querySelector('button[name="submit"]');
        expect(validBtn.disabled).toBeTruthy();

        const cancelBtn = nativeElement.querySelector('button[name="cancel"]');
        expect(cancelBtn.disabled).toBeTruthy();
    }));

    it('should not validate form when form is invalid', fakeAsync(() => {
        component.sso = {
            url: '',
            ssoLogoutUrl: 'https://sso.maarchcourrier/dist/index.html#/logout',
            mapping: [
                {
                    maarchId: 'login',
                    ssoId: 'userId',
                    desc: 'User ID description'
                }
            ]
        };

        component.ssoClone = {
            url: '',
            ssoLogoutUrl: 'https://sso.maarchcourrier/dist/index.html#/logout',
            mapping: [
                {
                    maarchId: 'login',
                    ssoId: 'userId',
                    desc: 'User ID description'
                }
            ]
        };

        fixture.detectChanges();
        tick();

        const result = component.isValid(ngForm);

        expect(result).toBeFalse();

        const nativeElement = fixture.nativeElement;

        const validBtn = nativeElement.querySelector('button[name="submit"]');
        expect(validBtn.disabled).toBeTruthy();
    }));

    it('should cancel changes and revert to clone', fakeAsync(() => {
        component.sso = {
            url: 'https://sso.maarchcourrier/dist/index.html#/',
            ssoLogoutUrl: 'https://sso.maarchcourrier/dist/index.html#/logout',
            mapping: [
                {
                    maarchId: 'login',
                    ssoId: 'userId',
                    desc: 'User ID description'
                }
            ]
        };

        component.ssoClone = {
            url: 'https://sso.maarchcourrier/dist/index.html#/login',
            ssoLogoutUrl: 'https://sso.maarchcourrier/dist/index.html#/logout',
            mapping: [
                {
                    maarchId: 'login',
                    ssoId: 'userId',
                    desc: 'User ID description'
                }
            ]
        };

        component.cancel();

        fixture.detectChanges();
        tick();

        expect(component.sso).toEqual(component.ssoClone);
        expect(component.sso.url).toEqual('https://sso.maarchcourrier/dist/index.html#/login');

        const nativeElement = fixture.nativeElement;

        const validBtn = nativeElement.querySelector('button[name="submit"]');
        expect(validBtn.disabled).toBeTruthy();

        const cancelBtn = nativeElement.querySelector('button[name="cancel"]');
        expect(cancelBtn.disabled).toBeTruthy();
    }));

    it('should format data correctly by removing desc field from mapping', () => {
        component.sso = {
            url: 'https://sso.maarchcourrier/dist/index.html#/login',
            ssoLogoutUrl: 'https://sso.maarchcourrier/dist/index.html#/logout',
            mapping: [
                {
                    maarchId: 'login',
                    ssoId: 'userId',
                    desc: 'User ID description'
                }
            ]
        };

        const result = component.formatData();

        expect(result.url).toEqual('https://sso.maarchcourrier/dist/index.html#/login');
        expect(result.ssoLogoutUrl).toEqual('https://sso.maarchcourrier/dist/index.html#/logout');
        expect(result.mapping[0].maarchId).toEqual('login');
        expect(result.mapping[0].ssoId).toEqual('userId');
        expect(result.mapping[0].desc).toBeUndefined();
    });

    it('should call confirmAndUpdateConfiguration when authMode is not SSO', fakeAsync(() => {
        spyOn(component, 'formatData').and.returnValue({
            url: 'https://sso.maarchcourrier/dist/index.html#/login',
            ssoLogoutUrl: 'https://sso.maarchcourrier/dist/index.html#/logout',
            mapping: [
                {
                    maarchId: 'login',
                    ssoId: 'userId',
                    desc: 'User ID description'
                }
            ]
        });

        spyOn<any>(component, 'confirmAndUpdateConfiguration');

        authService.authMode = 'standard';

        fixture.detectChanges();
        tick();

        component.onSubmit();

        fixture.detectChanges();
        tick();

        expect(component.formatData).toHaveBeenCalled();
        expect(component['confirmAndUpdateConfiguration']).toHaveBeenCalled();
    }));

    it('should call updateConfiguration when authMode is SSO', fakeAsync(() => {
        spyOn(component, 'formatData').and.returnValue({
            url: 'https://sso.maarchcourrier/dist/index.html#/login',
            ssoLogoutUrl: 'https://sso.maarchcourrier/dist/index.html#/logout',
            mapping: [
                {
                    maarchId: 'login',
                    ssoId: 'userId',
                    desc: 'User ID description'
                }
            ]
        });

        authService.authMode = 'sso';

        fixture.detectChanges();
        tick();

        component.onSubmit();

        spyOn<any>(component, 'updateConfiguration');


        fixture.detectChanges();
        tick();

        expect(component.formatData).toHaveBeenCalled();
    }));

    it('should open confirmation dialog when calling confirmAndUpdateConfiguration', () => {
        const mockDialogRef = {
            afterClosed: () => of('ok')
        };

        dialog.open.and.returnValue(mockDialogRef as any);
        spyOn<any>(component, 'updateConfigurationRequest').and.returnValue(of({}));
        const formattedData = {
            url: 'https://sso.maarchcourrier/dist/index.html#/login',
            ssoLogoutUrl: 'https://sso.maarchcourrier/dist/index.html#/logout',
            mapping: [
                {
                    maarchId: 'login',
                    ssoId: 'userId',
                    desc: 'User ID description'
                }
            ]
        };

        component['confirmAndUpdateConfiguration'](formattedData);

        expect(dialog.open).toHaveBeenCalledWith(ConfirmComponent, jasmine.any(Object));
        expect(component['updateConfigurationRequest']).toHaveBeenCalledWith(formattedData);
    });

    it('should not call updateConfigurationRequest when dialog is cancelled', () => {
        const mockDialogRef = {
            afterClosed: () => of('cancel')
        };
        dialog.open.and.returnValue(mockDialogRef as any);
        spyOn<any>(component, 'updateConfigurationRequest');
        const formattedData = {
            url: 'https://sso.maarchcourrier/dist/index.html#/login',
            ssoLogoutUrl: 'https://sso.maarchcourrier/dist/index.html#/logout',
            mapping: [
                {
                    maarchId: 'login',
                    ssoId: 'userId',
                    desc: 'User ID description'
                }
            ]
        };

        component['confirmAndUpdateConfiguration'](formattedData);

        expect(dialog.open).toHaveBeenCalled();
        expect(component['updateConfigurationRequest']).not.toHaveBeenCalled();
    });

    it('should update configuration and show success notification', fakeAsync(() => {
        httpClient.put.and.returnValue(of({ success: true }));
        const formattedData = {
            url: 'https://sso.maarchcourrier/dist/index.html#/login',
            ssoLogoutUrl: 'https://sso.maarchcourrier/dist/index.html#/logout',
            mapping: [
                {
                    maarchId: 'login',
                    ssoId: 'userId',
                    desc: 'User ID description'
                }
            ]
        };

        component.sso = formattedData;

        component['updateConfigurationRequest'](formattedData).subscribe();
        fixture.detectChanges();
        tick();

        expect(httpClient.put).toHaveBeenCalledWith('../rest/configurations/admin_sso', formattedData);
        expect(notificationService.success).toHaveBeenCalled();
        expect(component.ssoClone).toEqual(component.sso);
    }));

    it('should handle errors when updating configuration', fakeAsync(() => {
        const error = { error: 'error message' };
        httpClient.put.and.returnValue(throwError(error));
        const formattedData = {
            url: 'https://sso.maarchcourrier/dist/index.html#/login',
            ssoLogoutUrl: 'https://sso.maarchcourrier/dist/index.html#/logout',
            mapping: [
                {
                    maarchId: 'login',
                    ssoId: 'userId',
                    desc: 'User ID description'
                }
            ]
        };

        component['updateConfigurationRequest'](formattedData).subscribe();
        tick();

        expect(httpClient.put).toHaveBeenCalledWith('../rest/configurations/admin_sso', formattedData);
        expect(notificationService.handleSoftErrors).toHaveBeenCalledWith(error);
    }));
});
