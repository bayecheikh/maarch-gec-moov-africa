import { ProConnectAdministrationComponent, ProConnectConfigInterface } from '@appRoot/administration/connection/proConnect/proconnect-administration.component';
import { ComponentFixture, TestBed, fakeAsync, tick } from '@angular/core/testing';
import { HttpClientTestingModule } from '@angular/common/http/testing';
import { TranslateLoader, TranslateModule, TranslateService } from '@ngx-translate/core';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { FormsModule, NgForm } from '@angular/forms';
import { MatLegacyCardModule as MatCardModule } from '@angular/material/legacy-card';
import { MatLegacyFormFieldModule as MatFormFieldModule } from '@angular/material/legacy-form-field';
import { MatIconModule } from '@angular/material/icon'; // Celui-ci reste inchangé
import { MatLegacyInputModule as MatInputModule } from '@angular/material/legacy-input';
import { MatLegacyListModule as MatListModule } from '@angular/material/legacy-list';
import { MatLegacyProgressSpinnerModule as MatProgressSpinnerModule } from '@angular/material/legacy-progress-spinner';
import { MatSidenavModule } from '@angular/material/sidenav'; // Celui-ci reste inchangé
import { MatLegacySlideToggleModule as MatSlideToggleModule } from '@angular/material/legacy-slide-toggle';
import {  MatLegacyDialogModule as MatDialogModule } from "@angular/material/legacy-dialog";
import { RouterTestingModule } from '@angular/router/testing';
import { Observable, of, throwError } from 'rxjs';
import { FunctionsService } from '@service/functions.service';
import { HttpClient } from '@angular/common/http';
import { NotificationService } from '@service/notification/notification.service';
import { HeaderService } from '@service/header.service';
import { AppService } from '@service/app.service';
import { AdministrationService } from '@appRoot/administration/administration.service';
import { DebugElement } from '@angular/core';
import { By } from '@angular/platform-browser';
import * as langFrJson from '@langs/lang-fr.json';
import { PrivilegeService } from "@service/privileges.service";

class FakeLoader implements TranslateLoader {
    getTranslation(): Observable<any> {
        return of({ lang: langFrJson });
    }
}

/**
 * Mock for the NgForm class
 */
class MockNgForm {
    form = {
        valid: true
    };
    controls = {
        'proConnectUrl': {
            hasError: () => false,
            markAsTouched: () => {},
            touched: false
        },
        'clientId': {
            hasError: () => false,
            markAsTouched: () => {},
            touched: false
        },
        'clientSecret': {
            hasError: () => false,
            markAsTouched: () => {},
            touched: false
        }
    };
}

describe('ProConnectAdministrationComponent', () => {
    let component: ProConnectAdministrationComponent;
    let fixture: ComponentFixture<ProConnectAdministrationComponent>;
    let httpClient: jasmine.SpyObj<HttpClient>;
    let notificationService: jasmine.SpyObj<NotificationService>;
    let functionsService: jasmine.SpyObj<FunctionsService>;
    let headerService: jasmine.SpyObj<HeaderService>;
    let appService: jasmine.SpyObj<AppService>;
    let administrationService: jasmine.SpyObj<AdministrationService>;
    let debugElement: DebugElement;
    let translateService: TranslateService;

    // Sample ProConnect configuration for testing
    const mockProConnectConfig: ProConnectConfigInterface = {
        clientId: 'test-client-id',
        clientSecret: 'test-client-secret',
        url: 'https://test-proconnect.fr',
        enabled: true
    };

    // Setup before each test
    beforeEach(async () => {
        // Create spy objects for services
        const httpSpy = jasmine.createSpyObj('HttpClient', ['get', 'put']);
        const notificationSpy = jasmine.createSpyObj('NotificationService', ['success', 'handleSoftErrors']);
        const functionsSpy = jasmine.createSpyObj('FunctionsService', ['empty']);
        const headerSpy = jasmine.createSpyObj('HeaderService', ['injectInSideBarLeft', 'setHeader']);
        const appSpy = jasmine.createSpyObj('AppService', ['getViewMode']);
        const adminSpy = jasmine.createSpyObj('AdministrationService', ['getAdminConnectionsSubMenus']);
        const privilegeSpy = jasmine.createSpyObj('PrivilegeService', ['hasPrivilege', 'getCurrentUserMenus']);

        // Configure TestBed with required modules and services
        await TestBed.configureTestingModule({
            declarations: [
                ProConnectAdministrationComponent
            ],
            imports: [
                HttpClientTestingModule,
                RouterTestingModule,
                BrowserAnimationsModule,
                FormsModule,
                MatCardModule,
                MatFormFieldModule,
                MatIconModule,
                MatInputModule,
                MatListModule,
                MatProgressSpinnerModule,
                MatSidenavModule,
                MatSlideToggleModule,
                MatDialogModule,
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: FakeLoader },
                })
            ],
            providers: [
                { provide: HttpClient, useValue: httpSpy },
                { provide: NotificationService, useValue: notificationSpy },
                { provide: FunctionsService, useValue: functionsSpy },
                { provide: HeaderService, useValue: headerSpy },
                { provide: AppService, useValue: appSpy },
                { provide: AdministrationService, useValue: adminSpy },
                { provide: PrivilegeService, useValue: privilegeSpy }
            ]
        }).compileComponents();

        // Set lang
        translateService = TestBed.inject(TranslateService);
        translateService.use('fr');

        // Initialize component and services
        fixture = TestBed.createComponent(ProConnectAdministrationComponent);
        component = fixture.componentInstance;
        httpClient = TestBed.inject(HttpClient) as jasmine.SpyObj<HttpClient>;
        notificationService = TestBed.inject(NotificationService) as jasmine.SpyObj<NotificationService>;
        functionsService = TestBed.inject(FunctionsService) as jasmine.SpyObj<FunctionsService>;
        headerService = TestBed.inject(HeaderService) as jasmine.SpyObj<HeaderService>;
        appService = TestBed.inject(AppService) as jasmine.SpyObj<AppService>;
        administrationService = TestBed.inject(AdministrationService) as jasmine.SpyObj<AdministrationService>;
        debugElement = fixture.debugElement;

        // Setup default behavior for spies
        functionsService.empty.and.callFake((value) => !value || value === '');
        appService.getViewMode.and.returnValue(false);
        administrationService.getAdminConnectionsSubMenus.and.returnValue([]);

        // Mock the HTTP response for getting config
        httpClient.get.and.returnValue(of({
            configuration: {
                value: mockProConnectConfig
            }
        }));
    });

    /**
    * Test component initialization
    */
    it('should create the component', () => {
        expect(component).toBeTruthy();
    });

    /**
    * Test ngOnInit method and verify services are called correctly
    */
    it('should initialize component and load configuration on ngOnInit', fakeAsync(() => {
        // Spy on component method
        spyOn(component, 'getProConnectConfig').and.callThrough();

        // Call ngOnInit
        component.ngOnInit();
        tick();

        // Check if services were called with correct parameters
        expect(headerService.injectInSideBarLeft).toHaveBeenCalled();
        expect(headerService.setHeader).toHaveBeenCalled();
        expect(component.getProConnectConfig).toHaveBeenCalled();
        expect(httpClient.get).toHaveBeenCalledWith('../rest/configurations/admin_proconnect');

        // Verify component properties were updated correctly
        expect(component.loading).toBeFalse();
        expect(component.proConnectConfig).toEqual(mockProConnectConfig);
        expect(component.proConnectConfigClone).toEqual(mockProConnectConfig);
    }));

    /**
    * Test getProConnectConfig method error handling
    */
    it('should handle errors during configuration loading', fakeAsync(() => {
        // Mock error response
        httpClient.get.and.returnValue(throwError(() => new Error('Test error')));

        // Call method
        component.getProConnectConfig();
        tick();

        // Verify error handling
        expect(notificationService.handleSoftErrors).toHaveBeenCalled();
        expect(component.loading).toBeFalse();
    }));

    /**
    * Test onSubmit method - successful case
    */
    it('should save configuration when onSubmit is called', fakeAsync(() => {
        // Setup test data
        httpClient.put.and.returnValue(of({ success: true }));
        component.proConnectConfig = { ...mockProConnectConfig };
        component.proConnectConfigClone = { ...mockProConnectConfig, url: 'https://test-proconnect.fr' };

        // Call the method
        component.onSubmit();
        tick();

        // Verify HTTP call and notifications
        expect(httpClient.put).toHaveBeenCalledWith('../rest/configurations/admin_proconnect', component.proConnectConfig);
        expect(notificationService.success).toHaveBeenCalled();
        expect(component.loading).toBeFalse();
        expect(component.proConnectConfigClone).toEqual(component.proConnectConfig);
    }));

    /**
    * Test onSubmit method - error case
    */
    it('should handle errors during configuration saving', fakeAsync(() => {
        // Mock error response
        httpClient.put.and.returnValue(throwError(() => new Error('Test error')));
        component.proConnectConfig = { ...mockProConnectConfig };

        // Call the method
        component.onSubmit();
        tick();

        // Verify error handling
        expect(notificationService.handleSoftErrors).toHaveBeenCalled();
        expect(component.loading).toBeFalse();
    }));

    /**
    * Test cancel method
    */
    it('should restore original configuration when cancel is called', () => {
        // Setup test data with differences
        component.proConnectConfig = {
            ...mockProConnectConfig,
            url: 'changed-url',
            clientId: 'changed-id'
        };
        component.proConnectConfigClone = { ...mockProConnectConfig };

        // Call the method
        component.cancel();

        // Verify state is restored
        expect(component.proConnectConfig).toEqual(mockProConnectConfig);
    });

    /**
    * Test isValid method with different scenarios
    */
    it('should correctly validate the form', () => {
        // Setup mock form
        const mockForm = new MockNgForm() as unknown as NgForm;

        // Test case 1: Valid form with changes
        component.proConnectConfig = { ...mockProConnectConfig, url: 'https://new-test-proconnect.fr' };
        component.proConnectConfigClone = { ...mockProConnectConfig };
        expect(component.isValid(mockForm)).toBeTrue();

        // Test case 2: Valid form but no changes
        component.proConnectConfig = { ...mockProConnectConfig };
        component.proConnectConfigClone = { ...mockProConnectConfig };
        expect(component.isValid(mockForm)).toBeFalse();

        // Test case 3: Invalid form
        // Mock the valid property using a getter
        Object.defineProperty(mockForm.form, 'valid', { get: () => false });
        expect(component.isValid(mockForm)).toBeFalse();
    });

    /**
    * Test template rendering - verify all form fields are present
    */
    it('should render all form fields', fakeAsync(() => {
        component.loading = false;

        fixture.detectChanges();
        tick();

        // Check for the main form elements
        const formElement = debugElement.query(By.css('form'));
        expect(formElement).toBeTruthy();

        // Check for specific form fields
        expect(debugElement.query(By.css('input[name="proConnectUrl"]'))).toBeTruthy();
        expect(debugElement.query(By.css('input[name="clientId"]'))).toBeTruthy();
        expect(debugElement.query(By.css('input[name="clientSecret"]'))).toBeTruthy();
        expect(debugElement.query(By.css('mat-slide-toggle[ng-reflect-name="toggleProConnectConnection"]'))).toBeTruthy();

        // Check for buttons
        expect(debugElement.query(By.css('button[name="validate"]'))).toBeTruthy();
        expect(debugElement.query(By.css('button[name="cancel"]'))).toBeTruthy();
    }));

    /**
    * Test loading state rendering
    */
    it('should show spinner when loading', fakeAsync(() => {
        fixture.detectChanges();
        tick();

        component.loading = true;

        fixture.detectChanges();
        tick();

        // Check for spinner presence
        const spinner = debugElement.query(By.css('mat-spinner'));
        expect(spinner).toBeTruthy();

        // Form should not be visible
        const form = debugElement.query(By.css('form'));
        expect(form).toBeFalsy();
    }));

    /**
    * Test form disabled state for submit button
    */
    it('should disable submit button when form is invalid', () => {
        // Setup component
        component.loading = false;
        component.proConnectConfig = { ...mockProConnectConfig };
        component.proConnectConfigClone = { ...mockProConnectConfig };
        fixture.detectChanges();

        // Get the button element
        const submitButton = debugElement.query(By.css('button[color="primary"]')).nativeElement;

        // Button should be disabled when no changes
        expect(submitButton.disabled).toBeTrue();

        // Make a change to enable the button
        component.proConnectConfig.url = 'changed-url';
        fixture.detectChanges();

        spyOn(component, 'isValid').and.returnValue(true);
        fixture.detectChanges();

        // Now manually trigger change detection
        fixture.detectChanges();
    });

    /**
    * Test slide toggle functionality
    */
    it('should update enabled state when toggle is clicked', () => {
        // Setup component with initial state
        component.loading = false;
        component.proConnectConfig = { ...mockProConnectConfig, enabled: false };
        component.proConnectConfigClone = { ...mockProConnectConfig, enabled: false };
        fixture.detectChanges();

        // Get the slide toggle and verify initial state
        const slideToggle = debugElement.query(By.css('mat-slide-toggle')).nativeElement;
        expect(slideToggle).toBeTruthy();

        slideToggle.click();
        fixture.detectChanges();
        expect(component.proConnectConfig.enabled).toBeTrue();
    });
});
