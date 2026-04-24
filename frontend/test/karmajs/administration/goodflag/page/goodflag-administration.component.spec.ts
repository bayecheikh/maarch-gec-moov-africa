import { ComponentFixture, fakeAsync, flush, TestBed, tick } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { RouterTestingModule } from '@angular/router/testing';
import { FormsModule } from '@angular/forms';
import { NoopAnimationsModule } from '@angular/platform-browser/animations';
import { ActivatedRoute, Router } from '@angular/router';
import { TranslateLoader, TranslateModule, TranslateService } from '@ngx-translate/core';
import { Observable, of } from 'rxjs';
import { Component, Input } from '@angular/core';
import { By } from '@angular/platform-browser';
import { MatLegacyCardModule as MatCardModule } from '@angular/material/legacy-card';
import { MatLegacyFormFieldModule as MatFormFieldModule } from '@angular/material/legacy-form-field';
import { MatLegacyInputModule as MatInputModule } from '@angular/material/legacy-input';
import { MatLegacySelectModule as MatSelectModule } from '@angular/material/legacy-select';
import { MatLegacyButtonModule as MatButtonModule } from '@angular/material/legacy-button';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatSidenavModule } from '@angular/material/sidenav';

import { FunctionsService } from "@service/functions.service";
import { NotificationService } from "@service/notification/notification.service";
import { AppService } from '@service/app.service';
import { HeaderService } from '@service/header.service';
import { ConsentPageInterface, GoodFlagTemplateInterface, SignatureProfileInterface } from '@models/goodflag.model';
import { GoodflagAdministrationComponent } from "@appRoot/administration/goodflag/page/goodflag-administration.component";
import * as langFrJson from "@langs/lang-fr.json";

@Component({ selector: 'app-header-left', template: '' })
class MockHeaderLeftComponent {
}

@Component({ selector: 'app-header-right', template: '' })
class MockHeaderRightComponent {
}

@Component({ selector: 'app-consent-page', template: '<div>Consent Page Component</div>' })
class MockConsentPageComponent {
    @Input() consentPage: ConsentPageInterface;
}

@Component({ selector: 'app-signature-profile', template: '<div>Signature Profile Component</div>' })
class MockSignatureProfileComponent {
    @Input() signatureProfile: SignatureProfileInterface;
}

class FakeLoader implements TranslateLoader {
    getTranslation(): Observable<any> {
        return of({ lang: langFrJson });
    }
}

describe('GoodflagAdministrationComponent', () => {
    let component: GoodflagAdministrationComponent;
    let fixture: ComponentFixture<GoodflagAdministrationComponent>;
    let httpMock: HttpTestingController;
    let router: Router;
    let activatedRoute: ActivatedRoute;
    let translateService: TranslateService;

    // Mock services
    let mockFunctionsService: jasmine.SpyObj<FunctionsService>;
    let mockNotificationService: jasmine.SpyObj<NotificationService>;
    let mockAppService: jasmine.SpyObj<AppService>;
    let mockHeaderService: jasmine.SpyObj<HeaderService>;

    // Test data
    const mockConsentPages: ConsentPageInterface[] = [
        {
            id: '1',
            name: 'Consent Page 1',
            created: '2024-01-15T10:30:00Z',
            updated: '2024-01-20T14:45:00Z',
            stepType: 'signature',
            signingMode: 'server',
            authenticateUser: true,
            allowOrganization: false,
            strictCertificateControl: true,
            keystoreTypes: ['PKCS12', 'JKS']
        },
        {
            id: '2',
            name: 'Consent Page 2',
            created: '2024-01-16T09:15:00Z',
            updated: '2024-01-21T16:20:00Z',
            stepType: 'approval',
            signingMode: 'collective',
            authenticateUser: false,
            allowOrganization: true,
            strictCertificateControl: false,
            keystoreTypes: ['PKCS11']
        }
    ];

    const mockSignatureProfiles: SignatureProfileInterface[] = [
        {
            id: '1',
            name: 'PDF Signature Profile',
            created: '2024-01-10T08:00:00Z',
            updated: '2024-01-18T12:30:00Z',
            documentType: 'PDF',
            signatureType: 'PADES',
            pdfSignatureImageText: 'Digitally signed by {signer}',
            forceScrollDocument: true
        },
        {
            id: '2',
            name: 'XML Signature Profile',
            created: '2024-01-12T11:45:00Z',
            updated: '2024-01-19T15:10:00Z',
            documentType: 'XML',
            signatureType: 'XADES',
            pdfSignatureImageText: '',
            forceScrollDocument: false
        }
    ];

    const mockGoodflagTemplate: GoodFlagTemplateInterface = {
        id: '1',
        label: 'Test Template',
        description: 'Test Description',
        signatureProfileId: '1',
        consentPageId: '1'
    };

    beforeEach(async () => {
        // Create spy objects for services
        mockFunctionsService = jasmine.createSpyObj('FunctionsService', ['empty']);
        mockNotificationService = jasmine.createSpyObj('NotificationService', ['handleErrors', 'handleSoftErrors', 'success']);
        mockAppService = jasmine.createSpyObj('AppService', ['getViewMode']);
        mockHeaderService = jasmine.createSpyObj('HeaderService', ['setHeader']);

        // Set up default return values
        mockFunctionsService.empty.and.returnValue(false);
        mockAppService.getViewMode.and.returnValue(false);

        await TestBed.configureTestingModule({
            declarations: [
                GoodflagAdministrationComponent,
                MockHeaderLeftComponent,
                MockHeaderRightComponent,
                MockConsentPageComponent,
                MockSignatureProfileComponent
            ],
            imports: [
                HttpClientTestingModule,
                RouterTestingModule.withRoutes([]),
                FormsModule,
                NoopAnimationsModule,
                // Material Design modules (Legacy)
                MatCardModule,
                MatFormFieldModule,
                MatInputModule,
                MatSelectModule,
                MatButtonModule,
                MatProgressSpinnerModule,
                MatSidenavModule,
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: FakeLoader },
                }),
            ],
            providers: [
                { provide: FunctionsService, useValue: mockFunctionsService },
                { provide: NotificationService, useValue: mockNotificationService },
                { provide: AppService, useValue: mockAppService },
                { provide: HeaderService, useValue: mockHeaderService },
                {
                    provide: ActivatedRoute,
                    useValue: {
                        params: of({ id: undefined })
                    }
                },
                TranslateService
            ]
        }).compileComponents();

        // Set lang
        translateService = TestBed.inject(TranslateService);
        translateService.use('fr');

        fixture = TestBed.createComponent(GoodflagAdministrationComponent);
        component = fixture.componentInstance;
        httpMock = TestBed.inject(HttpTestingController);
        router = TestBed.inject(Router);
        activatedRoute = TestBed.inject(ActivatedRoute);

        // Spy on router navigation
        spyOn(router, 'navigate').and.returnValue(Promise.resolve(true));
    });

    afterEach(() => {
        httpMock.verify(); // Verify that no unmatched requests are outstanding
    });

    describe('Component Initialization', () => {
        it('should create component', () => {
            expect(component).toBeTruthy();
        });

        it('should initialize with default values', () => {
            expect(component.loading).toBeFalse();
            expect(component.creationMode).toBeTrue();
            expect(component.goodflagTemplate).toEqual({
                id: null,
                label: '',
                description: '',
                signatureProfileId: '',
                consentPageId: ''
            });
        });
    });

    describe('ngOnInit - Creation Mode', () => {
        beforeEach(() => {
            // Mock route params for creation mode (no id parameter)
            activatedRoute.params = of({});
        });

        it('should initialize in creation mode when no id parameter is provided', fakeAsync(() => {
            component.ngOnInit();

            // Mock HTTP responses
            const consentReq = httpMock.expectOne('../rest/goodflag/consentPages');
            expect(consentReq.request.method).toBe('GET');
            consentReq.flush(mockConsentPages);

            const profilesReq = httpMock.expectOne('../rest/goodflag/signatureProfiles');
            expect(profilesReq.request.method).toBe('GET');
            profilesReq.flush(mockSignatureProfiles);

            tick();

            expect(component.creationMode).toBeTrue();
            expect(component.loading).toBeFalse();
            expect(mockHeaderService.setHeader).toHaveBeenCalledWith(translateService.instant('lang.goodflagCreation'));
            expect(component.consentPages).toEqual(mockConsentPages);
            expect(component.signatureProfiles).toEqual(mockSignatureProfiles);

            // Should select first items by default in creation mode
            expect(component.selectedConentPage).toEqual(mockConsentPages[0]);
            expect(component.selectedSignatureProfile).toEqual(mockSignatureProfiles[0]);
        }));
    });

    describe('ngOnInit - Edit Mode', () => {
        beforeEach(() => {
            // Mock route params for edit mode (with id parameter)
            activatedRoute.params = of({ id: '1' });
        });

        it('should initialize in edit mode when id parameter is provided', fakeAsync(() => {
            fixture.detectChanges();

            // Mock HTTP responses for consent pages and signature profiles
            const consentReq = httpMock.expectOne('../rest/goodflag/consentPages');
            consentReq.flush(mockConsentPages);

            const profilesReq = httpMock.expectOne('../rest/goodflag/signatureProfiles');
            profilesReq.flush(mockSignatureProfiles);

            tick();

            // Mock HTTP response for getting specific template
            const templateReq = httpMock.expectOne('../rest/goodflag/templates/1');
            templateReq.flush(mockGoodflagTemplate);

            tick();
            flush();

            expect(component.creationMode).toBeFalse();
            expect(component.loading).toBeFalse();
            expect(component.goodflagTemplate).toEqual(mockGoodflagTemplate);
            expect(component.selectedSignatureProfile).toEqual(mockSignatureProfiles[0]);
            expect(component.selectedConentPage).toEqual(mockConsentPages[0]);
        }));

        it('should handle error when fetching template fails', fakeAsync(() => {
            fixture.detectChanges();

            // Mock successful responses for consent pages and signature profiles
            const consentReq = httpMock.expectOne('../rest/goodflag/consentPages');
            consentReq.flush(mockConsentPages);

            const profilesReq = httpMock.expectOne('../rest/goodflag/signatureProfiles');
            profilesReq.flush(mockSignatureProfiles);

            tick();

            // Mock error response for template
            const templateReq = httpMock.expectOne('../rest/goodflag/templates/1');
            templateReq.error(new ErrorEvent('Network error'));

            tick();
            flush();

            expect(mockNotificationService.handleErrors).toHaveBeenCalled();
            expect(component.loading).toBeFalse();
        }));
    });

    describe('HTTP Service Methods', () => {
        it('should fetch consent pages successfully', fakeAsync(() => {
            const promise = component.getConsentPages();

            const req = httpMock.expectOne('../rest/goodflag/consentPages');
            expect(req.request.method).toBe('GET');
            req.flush(mockConsentPages);

            tick();
            promise.then(() => {
                expect(component.consentPages).toEqual(mockConsentPages);
                expect(component.consentPagesClone).toEqual(mockConsentPages);
            });
        }));

        it('should handle consent pages fetch error', fakeAsync(() => {
            component.getConsentPages();

            const req = httpMock.expectOne('../rest/goodflag/consentPages');
            req.error(new ErrorEvent('Network error'));

            tick();

            expect(mockNotificationService.handleErrors).toHaveBeenCalled();
            expect(router.navigate).toHaveBeenCalledWith(['/administration/goodflag']);
        }));

        it('should fetch signature profiles successfully', fakeAsync(() => {
            const promise = component.getSignatureProfiles();

            const req = httpMock.expectOne('../rest/goodflag/signatureProfiles');
            expect(req.request.method).toBe('GET');
            req.flush(mockSignatureProfiles);

            tick();
            promise.then(() => {
                expect(component.signatureProfiles).toEqual(mockSignatureProfiles);
                expect(component.signatureProfilesClone).toEqual(mockSignatureProfiles);
            });
        }));
    });

    describe('Form Submission', () => {
        it('should submit new template in creation mode', fakeAsync(() => {
            component.goodflagTemplate = { ...mockGoodflagTemplate, id: null };

            component.onSubmit();

            expect(component.creationMode).toBeTrue();

            const req = httpMock.expectOne('../rest/goodflag/templates');
            expect(req.request.method).toBe('POST');
            expect(req.request.body).toEqual(component.goodflagTemplate);
            req.flush({});

            tick();

            expect(router.navigate).toHaveBeenCalledWith(['/administration/goodflag']);
            expect(mockNotificationService.success).toHaveBeenCalled();
        }));

        it('should update existing template in edit mode', fakeAsync(() => {
            component.creationMode = false;
            component.goodflagTemplate = mockGoodflagTemplate;

            component.onSubmit();

            const req = httpMock.expectOne(`../rest/goodflag/templates/${mockGoodflagTemplate.id}`);
            expect(req.request.method).toBe('PUT');
            expect(req.request.body).toEqual(mockGoodflagTemplate);
            req.flush({});

            tick();

            expect(router.navigate).toHaveBeenCalledWith(['/administration/goodflag']);
            expect(mockNotificationService.success).toHaveBeenCalled();
        }));

        it('should handle submission error', fakeAsync(() => {
            component.onSubmit();

            const req = httpMock.expectOne('../rest/goodflag/templates');
            req.error(new ErrorEvent('Network error'));

            tick();

            expect(mockNotificationService.handleSoftErrors).toHaveBeenCalled();
            expect(component.loading).toBeFalse();
        }));
    });

    describe('Component Methods', () => {
        beforeEach(() => {
            component.consentPages = mockConsentPages;
            component.signatureProfiles = mockSignatureProfiles;
            component.goodflagTemplate = { ...mockGoodflagTemplate };
            component.goodflagTemplateClone = { ...mockGoodflagTemplate };
            component.consentPagesClone = [...mockConsentPages];
            component.signatureProfilesClone = [...mockSignatureProfiles];
        });

        it('should detect modifications correctly', () => {
            // Initially no modifications
            expect(component.isModified()).toBeFalse();

            // Modify template
            component.goodflagTemplate.label = 'New label';
            expect(component.isModified()).toBeTrue();

            // Reset modifications
            component.goodflagTemplate.label = mockGoodflagTemplate.label;
            expect(component.isModified()).toBeFalse();
        });

        it('should not show modifications when loading', () => {
            component.loading = true;
            component.goodflagTemplate.label = 'New label';
            expect(component.isModified()).toBeFalse();
        });

        it('should cancel modifications correctly', () => {
            // Make some changes
            component.goodflagTemplate.label = 'New label';
            component.goodflagTemplate.description = 'New description';

            component.cancelModification();

            expect(component.goodflagTemplate).toEqual(mockGoodflagTemplate);
            expect(component.consentPages).toEqual(mockConsentPages);
            expect(component.signatureProfiles).toEqual(mockSignatureProfiles);
        });

        it('should change signature profile correctly', () => {
            component.changeSignatureProfile('2');
            expect(component.selectedSignatureProfile).toEqual(mockSignatureProfiles[1]);
        });

        it('should change consent page correctly', () => {
            component.changeConsentPage('2');
            expect(component.selectedConentPage).toEqual(mockConsentPages[1]);
        });
    });

    describe('HTML Template Integration', () => {
        beforeEach(fakeAsync(() => {
            // Initialize component with mock data
            component.consentPages = mockConsentPages;
            component.signatureProfiles = mockSignatureProfiles;
            component.goodflagTemplate = { ...mockGoodflagTemplate };
            component.selectedConentPage = mockConsentPages[0];
            component.selectedSignatureProfile = mockSignatureProfiles[0];
            component.loading = false;
            fixture.detectChanges();
            tick();
        }));

        it('should render form fields correctly', fakeAsync(() => {
            // Mock HTTP responses for consent pages and signature profiles
            const consentReq = httpMock.expectOne('../rest/goodflag/consentPages');
            consentReq.flush(mockConsentPages);

            const profilesReq = httpMock.expectOne('../rest/goodflag/signatureProfiles');
            profilesReq.flush(mockSignatureProfiles);

            tick();
            flush();

            fixture.detectChanges();

            // Check if label input exists
            const labelInput = fixture.debugElement.query(By.css('input[name="label"]'));
            expect(labelInput).toBeTruthy();

            fixture.detectChanges();

            expect(labelInput.nativeElement.value).toBe(component.goodflagTemplate.label);

            // Check if description input exists
            const descriptionInput = fixture.debugElement.query(By.css('input[name="description"]'));
            expect(descriptionInput).toBeTruthy();

            fixture.detectChanges();

            expect(descriptionInput.nativeElement.value).toBe(component.goodflagTemplate.description);
        }));

        it('should render consent page select with options', fakeAsync(() => {
            // Mock HTTP responses for consent pages and signature profiles
            const consentReq = httpMock.expectOne('../rest/goodflag/consentPages');
            consentReq.flush(mockConsentPages);

            const profilesReq = httpMock.expectOne('../rest/goodflag/signatureProfiles');
            profilesReq.flush(mockSignatureProfiles);

            tick();
            flush();

            fixture.detectChanges();

            const consentSelect = fixture.debugElement.query(By.css('mat-select[name="currentConsentPage"]'));
            expect(consentSelect).toBeTruthy();

            // Trigger the select to open
            consentSelect.nativeElement.click();
            fixture.detectChanges();


            const options = fixture.debugElement.queryAll(By.css('mat-option'));
            expect(options.length).toBe(mockConsentPages.length);
            expect(options[0].nativeElement.textContent.trim()).toContain(mockConsentPages[0].name);
        }));

        it('should render signature profile select with options', fakeAsync(() => {
            // Mock HTTP responses for consent pages and signature profiles
            const consentReq = httpMock.expectOne('../rest/goodflag/consentPages');
            consentReq.flush(mockConsentPages);

            const profilesReq = httpMock.expectOne('../rest/goodflag/signatureProfiles');
            profilesReq.flush(mockSignatureProfiles);

            tick();
            flush();

            fixture.detectChanges();

            const profileSelect = fixture.debugElement.query(By.css('mat-select[name="currentSignatureProfile"]'));
            expect(profileSelect).toBeTruthy();

            // Trigger the select to open
            profileSelect.nativeElement.click();
            fixture.detectChanges();

            const options = fixture.debugElement.queryAll(By.css('mat-option'));
            expect(options.length).toBe(mockSignatureProfiles.length);
            expect(options[0].nativeElement.textContent.trim()).toContain(mockSignatureProfiles[0].name);
        }));

        it('should show loading spinner when loading is true', fakeAsync(() => {
            // Mock HTTP responses for consent pages and signature profiles
            const consentReq = httpMock.expectOne('../rest/goodflag/consentPages');
            consentReq.flush(mockConsentPages);

            const profilesReq = httpMock.expectOne('../rest/goodflag/signatureProfiles');
            profilesReq.flush(mockSignatureProfiles);

            tick();
            flush();

            fixture.detectChanges();


            component.loading = true;
            fixture.detectChanges();

            const spinner = fixture.debugElement.query(By.css('mat-spinner'));
            expect(spinner).toBeTruthy();
        }));

        it('should hide loading spinner when loading is false', fakeAsync(() => {
            // Mock HTTP responses for consent pages and signature profiles
            const consentReq = httpMock.expectOne('../rest/goodflag/consentPages');
            consentReq.flush(mockConsentPages);

            const profilesReq = httpMock.expectOne('../rest/goodflag/signatureProfiles');
            profilesReq.flush(mockSignatureProfiles);

            tick();
            flush();

            component.loading = false;
            fixture.detectChanges();

            const spinner = fixture.debugElement.query(By.css('mat-spinner'));
            expect(spinner).toBeFalsy();
        }));

        it('should render child components when data is loaded', fakeAsync(() => {
            // Mock HTTP responses for consent pages and signature profiles
            const consentReq = httpMock.expectOne('../rest/goodflag/consentPages');
            consentReq.flush(mockConsentPages);

            const profilesReq = httpMock.expectOne('../rest/goodflag/signatureProfiles');
            profilesReq.flush(mockSignatureProfiles);

            tick();
            flush();

            fixture.detectChanges();

            const consentPageComponent = fixture.debugElement.query(By.css('app-consent-page'));
            expect(consentPageComponent).toBeTruthy();
            expect(consentPageComponent.componentInstance.consentPage).toEqual(component.selectedConentPage);

            const signatureProfileComponent = fixture.debugElement.query(By.css('app-signature-profile'));
            expect(signatureProfileComponent).toBeTruthy();
            expect(signatureProfileComponent.componentInstance.signatureProfile).toEqual(component.selectedSignatureProfile);
        }));

        it('should enable/disable save button based on form validity and modifications', fakeAsync(() => {
            // Mock HTTP responses for consent pages and signature profiles
            const consentReq = httpMock.expectOne('../rest/goodflag/consentPages');
            consentReq.flush(mockConsentPages);

            const profilesReq = httpMock.expectOne('../rest/goodflag/signatureProfiles');
            profilesReq.flush(mockSignatureProfiles);

            tick();
            flush();

            fixture.detectChanges();

            const saveButton = fixture.debugElement.query(By.css('button[type="submit"]'));

            // Initially should be disabled (no modifications)
            expect(saveButton.nativeElement.disabled).toBeTruthy();

            // Make a modification
            component.goodflagTemplate.label = 'New label';
            fixture.detectChanges();
            expect(saveButton.nativeElement.disabled).toBeFalsy();

            // Test with loading state
            component.loading = true;
            fixture.detectChanges();
            expect(saveButton.nativeElement.disabled).toBeTruthy();
        }));

        it('should enable/disable cancel button based on modifications', fakeAsync(() => {
            // Mock HTTP responses for consent pages and signature profiles
            const consentReq = httpMock.expectOne('../rest/goodflag/consentPages');
            consentReq.flush(mockConsentPages);

            const profilesReq = httpMock.expectOne('../rest/goodflag/signatureProfiles');
            profilesReq.flush(mockSignatureProfiles);

            tick();
            flush();

            fixture.detectChanges();

            const cancelButton = fixture.debugElement.query(By.css('button[type="button"]'));

            // Initially should be disabled (no modifications)
            expect(cancelButton.nativeElement.disabled).toBeTruthy();

            // Make a modification
            component.goodflagTemplate.label = 'New label';
            fixture.detectChanges();
            expect(cancelButton.nativeElement.disabled).toBeFalsy();
        }));

        it('should trigger form submission when save button is clicked', fakeAsync(() => {
            // Mock HTTP responses for consent pages and signature profiles
            const consentReq = httpMock.expectOne('../rest/goodflag/consentPages');
            consentReq.flush(mockConsentPages);

            const profilesReq = httpMock.expectOne('../rest/goodflag/signatureProfiles');
            profilesReq.flush(mockSignatureProfiles);

            tick();
            flush();

            fixture.detectChanges();

            spyOn(component, 'onSubmit');

            // Make form valid and modified
            component.goodflagTemplate.label = 'New label';
            fixture.detectChanges();

            const form = fixture.debugElement.query(By.css('form'));
            form.triggerEventHandler('ngSubmit', null);

            tick();
            expect(component.onSubmit).toHaveBeenCalled();
        }));

        it('should trigger cancel modification when cancel button is clicked', fakeAsync(() => {
            // Mock HTTP responses for consent pages and signature profiles
            const consentReq = httpMock.expectOne('../rest/goodflag/consentPages');
            consentReq.flush(mockConsentPages);

            const profilesReq = httpMock.expectOne('../rest/goodflag/signatureProfiles');
            profilesReq.flush(mockSignatureProfiles);

            tick();
            flush();

            fixture.detectChanges();

            spyOn(component, 'cancelModification');

            // Make a modification to enable cancel button
            component.goodflagTemplate.label = 'New label';
            fixture.detectChanges();

            const cancelButton = fixture.debugElement.query(By.css('button[type="button"]'));
            cancelButton.nativeElement.click();

            expect(component.cancelModification).toHaveBeenCalled();
        }));

        it('should update model when input values change', fakeAsync(() => {
            // Mock HTTP responses for consent pages and signature profiles
            const consentReq = httpMock.expectOne('../rest/goodflag/consentPages');
            consentReq.flush(mockConsentPages);

            const profilesReq = httpMock.expectOne('../rest/goodflag/signatureProfiles');
            profilesReq.flush(mockSignatureProfiles);

            tick();
            flush();

            fixture.detectChanges();

            const labelInput = fixture.debugElement.query(By.css('input[name="label"]'));

            // Simulate user input
            labelInput.nativeElement.value = 'New Label';
            labelInput.nativeElement.dispatchEvent(new Event('input'));

            tick();
            fixture.detectChanges();

            expect(component.goodflagTemplate.label).toBe('New Label');
        }));

        it('should trigger change methods when selects change', fakeAsync(() => {
            // Mock HTTP responses for consent pages and signature profiles
            const consentReq = httpMock.expectOne('../rest/goodflag/consentPages');
            consentReq.flush(mockConsentPages);

            const profilesReq = httpMock.expectOne('../rest/goodflag/signatureProfiles');
            profilesReq.flush(mockSignatureProfiles);

            tick();
            flush();

            fixture.detectChanges();

            spyOn(component, 'changeConsentPage');
            spyOn(component, 'changeSignatureProfile');

            const consentSelect = fixture.debugElement.query(By.css('mat-select[name="currentConsentPage"]'));
            const profileSelect = fixture.debugElement.query(By.css('mat-select[name="currentSignatureProfile"]'));

            // Trigger select change events
            consentSelect.triggerEventHandler('ngModelChange', '2');
            profileSelect.triggerEventHandler('ngModelChange', '2');

            expect(component.changeConsentPage).toHaveBeenCalledWith('2');
            expect(component.changeSignatureProfile).toHaveBeenCalledWith('2');
        }));

        it('should apply correct CSS classes based on view mode', fakeAsync(() => {
            // Mock HTTP responses for consent pages and signature profiles
            const consentReq = httpMock.expectOne('../rest/goodflag/consentPages');
            consentReq.flush(mockConsentPages);

            const profilesReq = httpMock.expectOne('../rest/goodflag/signatureProfiles');
            profilesReq.flush(mockSignatureProfiles);

            tick();
            flush();

            fixture.detectChanges();

            // Test when view mode is false
            mockAppService.getViewMode.and.returnValue(false);
            fixture.detectChanges();

            let customContainer = fixture.debugElement.query(By.css('.customContainerRight'));
            let fullContainer = fixture.debugElement.query(By.css('.fullContainer'));

            expect(customContainer).toBeFalsy();
            expect(fullContainer).toBeFalsy();

            // Test when view mode is true
            mockAppService.getViewMode.and.returnValue(true);
            fixture.detectChanges();

            customContainer = fixture.debugElement.query(By.css('.customContainerRight'));
            fullContainer = fixture.debugElement.query(By.css('.fullContainer'));

            expect(customContainer).toBeTruthy();
            expect(fullContainer).toBeTruthy();
        }));
    });

    describe('Interface Array Handling', () => {
        it('should handle consent pages array with all properties', fakeAsync(() => {
            const complexConsentPages: ConsentPageInterface[] = [
                {
                    id: '1',
                    name: 'Consent Page 1',
                    created: '2024-01-01T00:00:00Z',
                    updated: '2024-01-01T00:00:00Z',
                    stepType: 'signature',
                    signingMode: 'server',
                    authenticateUser: false,
                    allowOrganization: true,
                    strictCertificateControl: false,
                    keystoreTypes: ['PKCS12']
                },
                {
                    id: '2',
                    name: 'Consent Page 2',
                    created: '2024-01-02T00:00:00Z',
                    updated: '2024-01-03T00:00:00Z',
                    stepType: 'signature',
                    signingMode: 'server',
                    authenticateUser: true,
                    allowOrganization: false,
                    strictCertificateControl: true,
                    keystoreTypes: ['PKCS11', 'JKS', 'PKCS12']
                }
            ];

            component.getConsentPages();

            const req = httpMock.expectOne('../rest/goodflag/consentPages');
            req.flush(complexConsentPages);

            tick();

            expect(component.consentPages.length).toBe(2);
            expect(component.consentPages[0].keystoreTypes).toEqual(['PKCS12']);
            expect(component.consentPages[1].keystoreTypes).toEqual(['PKCS11', 'JKS', 'PKCS12']);
            expect(component.consentPages[1].authenticateUser).toBeTrue();
            expect(component.consentPages[0].allowOrganization).toBeTrue();
        }));

        it('should handle signature profiles array with all properties', fakeAsync(() => {
            const complexSignatureProfiles: SignatureProfileInterface[] = [
                {
                    id: '1',
                    name: 'PDF Advanced Profile',
                    created: '2024-01-01T10:00:00Z',
                    updated: '2024-01-01T11:00:00Z',
                    documentType: 'PDF',
                    signatureType: 'PADES',
                    pdfSignatureImageText: 'Digitally signed by {signer} at {timestamp}',
                    forceScrollDocument: true
                },
                {
                    id: '2',
                    name: 'XML Basic Profile',
                    created: '2024-01-02T12:00:00Z',
                    updated: '2024-01-02T13:00:00Z',
                    documentType: 'XML',
                    signatureType: 'XADES',
                    pdfSignatureImageText: '',
                    forceScrollDocument: false
                }
            ];

            component.getSignatureProfiles();

            const req = httpMock.expectOne('../rest/goodflag/signatureProfiles');
            req.flush(complexSignatureProfiles);

            tick();

            expect(component.signatureProfiles.length).toBe(2);
            expect(component.signatureProfiles[0].documentType).toBe('PDF');
            expect(component.signatureProfiles[0].signatureType).toBe('PADES');
            expect(component.signatureProfiles[0].forceScrollDocument).toBeTrue();
            expect(component.signatureProfiles[1].documentType).toBe('XML');
            expect(component.signatureProfiles[1].signatureType).toBe('XADES');
            expect(component.signatureProfiles[1].forceScrollDocument).toBeFalse();
        }));
    });

    describe('Interface Validation and Error Handling', () => {
        it('should handle null/undefined interface properties', () => {
            const templateWithNulls: Partial<GoodFlagTemplateInterface> = {
                id: '1',
                label: null,
                description: undefined,
                signatureProfileId: '',
                consentPageId: ''
            };

            component.goodflagTemplate = templateWithNulls as GoodFlagTemplateInterface;

            expect(component.goodflagTemplate.label).toBeNull();
            expect(component.goodflagTemplate.description).toBeUndefined();
            expect(component.isModified()).toBeTruthy(); // Should detect changes even with null values
        });

        it('should properly serialize and deserialize complex interfaces', () => {
            const originalConsentPage: ConsentPageInterface = {
                id: 'serialize-test',
                name: 'Serialization Test Page',
                created: '2024-01-01T00:00:00Z',
                updated: '2024-01-02T00:00:00Z',
                stepType: 'signature',
                signingMode: 'server',
                authenticateUser: true,
                allowOrganization: false,
                strictCertificateControl: true,
                keystoreTypes: ['PKCS12', 'JKS', 'PKCS11']
            };

            // Test JSON serialization/deserialization (used in cloning)
            const serialized = JSON.stringify(originalConsentPage);
            const deserialized: ConsentPageInterface = JSON.parse(serialized);

            expect(deserialized).toEqual(originalConsentPage);
            expect(deserialized.keystoreTypes).toEqual(['PKCS12', 'JKS', 'PKCS11']);
            expect(deserialized.authenticateUser).toBeTrue();
            expect(deserialized.allowOrganization).toBeFalse();
        });
    });

    it('should handle empty consent pages array', fakeAsync(() => {
        component.getConsentPages();

        const req = httpMock.expectOne('../rest/goodflag/consentPages');
        req.flush([]); // Empty array

        tick();

        expect(component.consentPages).toEqual([]);
        expect(component.selectedConentPage).toBeNull();
    }));

    it('should handle empty signature profiles array', fakeAsync(() => {
        component.getSignatureProfiles();

        const req = httpMock.expectOne('../rest/goodflag/signatureProfiles');
        req.flush([]); // Empty array

        tick();

        expect(component.signatureProfiles).toEqual([]);
        expect(component.selectedSignatureProfile).toBeNull();
    }));

    describe('Form Validation Integration', () => {
        it('should require label field', fakeAsync(() => {
            TestBed.resetTestingModule();

            component.goodflagTemplate.label = '';
            fixture.detectChanges();
            tick();

            const labelInput = fixture.nativeElement.querySelector('input[name="label"]');
            expect(labelInput.required).toBeTruthy();
        }));
    });
});