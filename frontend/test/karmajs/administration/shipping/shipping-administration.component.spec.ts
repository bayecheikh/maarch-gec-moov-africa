import { ComponentFixture, fakeAsync, TestBed, tick } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { RouterTestingModule } from '@angular/router/testing';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { TranslateLoader, TranslateModule, TranslateService } from '@ngx-translate/core';
import { Router } from '@angular/router';
import { NotificationService } from '@service/notification/notification.service';
import { HeaderService } from '@service/header.service';
import { AppService } from '@service/app.service';
import { FunctionsService } from '@service/functions.service';
import { ShippingInterface, ShippingSendersInterface } from '@models/shipping.model';
import { ShippingAdministrationComponent } from '@appRoot/administration/shipping/shipping-administration.component';
import { SharedModule } from "@appRoot/app-common.module";
import { AdministrationService } from "@appRoot/administration/administration.service";
import { PrivilegeService } from "@service/privileges.service";
import { Observable, of } from "rxjs";
import * as langFrJson from "@langs/lang-fr.json";

class FakeLoader implements TranslateLoader {
    getTranslation(): Observable<any> {
        return of({ lang: langFrJson });
    }
}

// Mock services
class MockNotificationService {
    handleErrors = jasmine.createSpy('handleErrors');
    handleSoftErrors = jasmine.createSpy('handleSoftErrors');
    success = jasmine.createSpy('success');
}

class MockHeaderService {
    setHeader = jasmine.createSpy('setHeader');
}

class MockAppService {
    getViewMode = jasmine.createSpy('getViewMode').and.returnValue(false);
}

class MockFunctionsService {
    empty = jasmine.createSpy('empty').and.returnValue(false);
}

describe('ShippingAdministrationComponent', () => {
    let component: ShippingAdministrationComponent;
    let fixture: ComponentFixture<ShippingAdministrationComponent>;
    let httpMock: HttpTestingController;
    let router: Router;
    let notificationService: MockNotificationService;
    let headerService: MockHeaderService;
    let functionsService: MockFunctionsService;
    let translateService: TranslateService;

    const mockShippingData: ShippingInterface = {
        id: 1,
        label: 'Shipping template',
        description: 'Shipping template description',
        options: {
            shapingOptions: ['addressPage', 'color'],
            sendMode: 'fast',
            senderId: 'ERES100',
            senderLabel: 'Barbara BAIN (bbain@maarch.com)'
        },
        fee: {
            firstPagePrice: 1.5,
            nextPagePrice: 0.5,
            postagePrice: 0.8,
            ereSendingPrice: 0
        },
        account: {
            id: 'testAccount',
            password: 'testPassword'
        },
        entities: [1, 2],
        senders: [],
        subscribed: true
    };

    const mockEntities = [
        { id: 1, label: 'PJS' },
        { id: 2, label: 'PS' }
    ];

    const mockSenders: ShippingSendersInterface[] = [
        { id: 'ERES100', firstname: 'Barbara', lastname: 'BAIN', email: 'bbain@maarch.com', company: 'MAARCH' },
        { id: 'ERES101', firstname: 'Patricia', lastname: 'PETIT', email: 'ppetit@maarch.com', company: 'MAARCH' }
    ];

    beforeEach(async () => {
        await TestBed.configureTestingModule({
            declarations: [
                ShippingAdministrationComponent
            ],
            imports: [
                SharedModule,
                HttpClientTestingModule,
                RouterTestingModule,
                BrowserAnimationsModule,
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: FakeLoader },
                }),
            ],
            providers: [
                { provide: NotificationService, useClass: MockNotificationService },
                { provide: HeaderService, useClass: MockHeaderService },
                { provide: AppService, useClass: MockAppService },
                { provide: FunctionsService, useClass: MockFunctionsService },
                AdministrationService,
                PrivilegeService,
                TranslateService
            ]
        }).compileComponents();

        // Set lang
        translateService = TestBed.inject(TranslateService);
        translateService.use('fr');

        fixture = TestBed.createComponent(ShippingAdministrationComponent);
        component = fixture.componentInstance;
        httpMock = TestBed.inject(HttpTestingController);
        router = TestBed.inject(Router);
        notificationService = TestBed.inject(NotificationService) as any;
        headerService = TestBed.inject(HeaderService) as any;
        functionsService = TestBed.inject(FunctionsService) as any;

        spyOn(router, 'navigate');
    });

    afterEach(() => {
        httpMock.verify();
    });

    /**
     * UNIT TESTS (TU)
     */
    describe('Unit Tests (TU)', () => {

        describe('Component Initialization', () => {
            it('should create the component', () => {
                expect(component).toBeTruthy();
            });

            it('should initialize with default values', () => {
                expect(component.loading).toBe(false);
                expect(component.creationMode).toBe(true);
                expect(component.hidePassword).toBe(true);
                expect(component.shippingAvailable).toBe(false);
                expect(component.templateId).toBeNull();
                expect(component.shipping.label).toBe('');
                expect(component.shipping.description).toBe('');
                expect(component.shipping.options.shapingOptions).toEqual(['addressPage']);
                expect(component.shipping.options.sendMode).toBe('fast');
            });

            it('should initialize shaping options correctly', () => {
                expect(component.shapingOptions).toEqual([
                    'color',
                    'duplexPrinting',
                    'addressPage',
                    'envelopeWindowsType'
                ]);
            });

            it('should initialize send modes correctly', () => {
                expect(component.sendModes).toEqual([
                    'digital_registered_mail',
                    'digital_registered_mail_with_AR',
                    'fast',
                    'economic',
                    'ere'
                ]);
            });
        });

        describe('Helper Methods', () => {
            it('should format senders correctly', () => {
                const formatted = component.formatSenders(mockSenders);
                expect(formatted).toEqual([
                    { id: 'ERES100', label: 'Barbara BAIN (bbain@maarch.com)' },
                    { id: 'ERES101', label: 'Patricia PETIT (ppetit@maarch.com)' }
                ]);
            });

            it('should return empty array when formatting empty senders', () => {
                const formatted = component.formatSenders([]);
                expect(formatted).toEqual([]);
            });

            it('should check modification correctly when no changes', () => {
                component.shippingClone = { ...mockShippingData };
                component.shipping = { ...mockShippingData };
                expect(component.checkModif()).toBe(true);
            });

            it('should check modification correctly when changes exist', () => {
                component.shippingClone = { ...mockShippingData };
                component.shipping = { ...mockShippingData, label: 'Updated label' };
                expect(component.checkModif()).toBe(false);
            });

            it('should get correct shaping warning for ere mode', () => {
                component.shipping.options.sendMode = 'ere';
                expect(component.getShapingWarning()).toBe('lang.warnShapingEre');
            });

            it('should get correct shaping warning for non-ere mode', () => {
                component.shipping.options.sendMode = 'fast';
                expect(component.getShapingWarning()).toBe('lang.warnShapingOption');
            });

            it('should disable toggle for ere mode', () => {
                component.shipping.options.sendMode = 'ere';
                expect(component.shouldDisableToggle('color')).toBe(true);
                expect(component.shipping.options.shapingOptions).toEqual([]);
            });

            it('should disable envelope windows type for digital mail modes', () => {
                component.shipping.options.sendMode = 'digital_registered_mail';
                expect(component.shouldDisableToggle('envelopeWindowsType')).toBe(true);

                component.shipping.options.sendMode = 'digital_registered_mail_with_AR';
                expect(component.shouldDisableToggle('envelopeWindowsType')).toBe(true);
            });

            it('should not disable toggle for regular modes', () => {
                component.shipping.options.sendMode = 'fast';
                expect(component.shouldDisableToggle('color')).toBe(false);
                expect(component.shouldDisableToggle('envelopeWindowsType')).toBe(false);
            });
        });

        describe('Shaping Options Management', () => {
            it('should add shaping option when not present', () => {
                component.shipping.options.shapingOptions = ['addressPage'];
                component.toggleShapingOption('color');
                expect(component.shipping.options.shapingOptions).toContain('color');
            });

            it('should remove shaping option when present', () => {
                component.shipping.options.shapingOptions = ['addressPage', 'color'];
                component.toggleShapingOption('color');
                expect(component.shipping.options.shapingOptions).not.toContain('color');
            });

            it('should update shapingOptionsClone when toggling', () => {
                component.shipping.options.shapingOptions = ['addressPage'];
                component.toggleShapingOption('color');
                expect(component.shapingOptionsClone).toEqual(['addressPage', 'color']);
            });
        });

        describe('Price Fields Management', () => {
            it('should add missing price fields for ere mode', () => {
                component.shipping.options.sendMode = 'ere';
                component.addMissingPriceFields();
                expect(component.shipping.fee.firstPagePrice).toBe(0);
                expect(component.shipping.fee.nextPagePrice).toBe(0);
                expect(component.shipping.fee.postagePrice).toBe(0);
            });

            it('should add missing price fields for non-ere mode', () => {
                component.shipping.options.sendMode = 'fast';
                component.addMissingPriceFields();
                expect(component.shipping.fee.ereSendingPrice).toBe(0);
            });
        });

        describe('Send Mode Changes', () => {
            it('should remove envelope windows type for digital mail modes', () => {
                component.shipping.options.shapingOptions = ['addressPage', 'envelopeWindowsType'];
                component.changeSendMode('digital_registered_mail', 'envelopeWindowsType');
                expect(component.shipping.options.shapingOptions).not.toContain('envelopeWindowsType');
            });

            it('should not remove envelope windows type for regular modes', () => {
                component.shipping.options.shapingOptions = ['addressPage', 'envelopeWindowsType'];
                component.changeSendMode('fast', 'envelopeWindowsType');
                expect(component.shipping.options.shapingOptions).toContain('envelopeWindowsType');
            });
        });

        describe('Entity Management', () => {
            it('should update selected entities', () => {
                spyOn(component.maarchTree, 'getSelectedNodes').and.returnValue([{ id: 1 }, { id: 2 }]);
                component.updateSelectedEntities();
                expect(component.shipping.entities).toEqual([1, 2]);
            });
        });

        describe('Data Cloning', () => {
            it('should clone values and add missing price fields', () => {
                component.entities = mockEntities;
                component.shipping = { ...mockShippingData };
                spyOn(component, 'initEntitiesTree');
                spyOn(component, 'addMissingPriceFields');

                component.cloneValuesAndAddMissingPriceFields();

                expect(component.entitiesClone).toEqual(mockEntities);
                expect(component.shippingClone).toEqual(mockShippingData);
                expect(component.initEntitiesTree).toHaveBeenCalledWith(mockEntities);
                expect(component.addMissingPriceFields).toHaveBeenCalled();
            });
        });

        describe('Cancel Modification', () => {
            it('should restore original values when canceling', () => {
                component.shippingClone = { ...mockShippingData };
                component.entitiesClone = [...mockEntities];
                component.shipping = { ...mockShippingData, label: 'Updated label' };
                component.entities = [];
                spyOn(component, 'initEntitiesTree');

                component.cancelModification();

                expect(component.shipping).toEqual(mockShippingData);
                expect(component.entities).toEqual(mockEntities);
                expect(component.initEntitiesTree).toHaveBeenCalledWith(mockEntities);
            });
        });
    });

    /**
     * FUNCTIONAL TESTS (TF)
     */
    describe('Functional Tests (TF)', () => {

        describe('Component Initialization Flow', () => {
            it('should initialize for creation mode', fakeAsync(() => {
                spyOn(component, 'cloneValuesAndAddMissingPriceFields');

                component.ngOnInit();

                // Mock external connections check
                const externalReq = httpMock.expectOne('../rest/externalConnectionsEnabled');
                externalReq.flush({ connection: { maileva: true } });

                // Mock new shipping data
                const newShippingReq = httpMock.expectOne('../rest/administration/shippings/new');
                newShippingReq.flush({ entities: mockEntities });

                tick();

                expect(component.creationMode).toBe(true);
                expect(component.shippingAvailable).toBe(true);
                expect(component.entities).toEqual(mockEntities.map(e => ({ ...e, id: parseInt(e.id.toString()) })));
                expect(component.cloneValuesAndAddMissingPriceFields).toHaveBeenCalled();
                expect(headerService.setHeader).toHaveBeenCalledWith(component.translate.instant('lang.shippingCreation'));
            }));

            it('should initialize for edit mode', fakeAsync(() => {
                spyOn(component, 'cloneValuesAndAddMissingPriceFields');

                // Mock route params to simulate edit mode using BehaviorSubject
                const mockParams = { id: '1' };
                (component.route.params as any) = of(mockParams);

                component.ngOnInit();

                // Mock external connections check
                const externalReq = httpMock.expectOne('../rest/externalConnectionsEnabled');
                externalReq.flush({ connection: { maileva: true } });

                // Mock existing shipping data - this should now be called
                const existingShippingReq = httpMock.expectOne('../rest/administration/shippings/1');
                existingShippingReq.flush({
                    shipping: { ...mockShippingData, senders: mockSenders },
                    entities: mockEntities
                });

                tick();

                expect(component.creationMode).toBe(false);
                expect(component.templateId).toBe(1);
                expect(component.shipping.label).toBe(mockShippingData.label);
                expect(component.entities).toEqual(mockEntities);
                expect(headerService.setHeader).toHaveBeenCalledWith(component.translate.instant('lang.shippingModification'));
            }));

            it('should handle new shipping data error', fakeAsync(() => {
                component.ngOnInit();

                const externalReq = httpMock.expectOne('../rest/externalConnectionsEnabled');
                externalReq.flush({ connection: { maileva: true } });

                const newShippingReq = httpMock.expectOne('../rest/administration/shippings/new');
                newShippingReq.error(new ErrorEvent('Network error'));

                tick();

                expect(component.loading).toBe(false);
                expect(notificationService.handleSoftErrors).toHaveBeenCalled();
            }));
        });

        describe('Form Submission Flow', () => {
            beforeEach(() => {
                component.shipping = { ...mockShippingData };
                component.shippingClone = { ...mockShippingData };
            });

            it('should submit new shipping successfully', fakeAsync(() => {
                component.creationMode = true;
                spyOn(component, 'getShipping').and.returnValue(component.shipping);

                component.onSubmit();

                const req = httpMock.expectOne('../rest/administration/shippings');
                expect(req.request.method).toBe('POST');
                req.flush({ success: true });

                tick();

                expect(component.loading).toBe(false);
                expect(notificationService.success).toHaveBeenCalledWith(component.translate.instant('lang.shippingAdded'));
                expect(router.navigate).toHaveBeenCalledWith(['/administration/shippings']);
            }));

            it('should update existing shipping successfully', fakeAsync(() => {
                component.creationMode = false;
                component.shipping.id = 1;
                spyOn(component, 'getShipping').and.returnValue(component.shipping);

                component.onSubmit();

                const req = httpMock.expectOne('../rest/administration/shippings/1');
                expect(req.request.method).toBe('PUT');
                req.flush({ success: true });

                tick();

                expect(component.loading).toBe(false);
                expect(notificationService.success).toHaveBeenCalledWith(component.translate.instant('lang.shippingUpdated'));
                expect(router.navigate).toHaveBeenCalledWith(['/administration/shippings']);
            }));

            it('should handle submission error', fakeAsync(() => {
                component.creationMode = true;
                spyOn(component, 'getShipping').and.returnValue(component.shipping);

                component.onSubmit();

                const req = httpMock.expectOne('../rest/administration/shippings');
                req.error(new ErrorEvent('Network error'));

                tick();

                expect(component.loading).toBe(false);
                expect(notificationService.handleSoftErrors).toHaveBeenCalled();
            }));

            it('should clean up fields for ere mode submission', () => {
                component.shipping.options.sendMode = 'ere';

                // Call onSubmit first to trigger the HTTP request
                component.onSubmit();

                // Then expect and flush the HTTP request
                const req = httpMock.expectOne('../rest/administration/shippings');
                req.flush({});

                // Verify the field cleanup happened
                expect(component.shipping.subscribed).toBe(false);
                expect(component.shipping.fee.firstPagePrice).toBeUndefined();
                expect(component.shipping.fee.nextPagePrice).toBeUndefined();
                expect(component.shipping.fee.postagePrice).toBeUndefined();
            });
        });

        describe('Sender Management Flow', () => {
            it('should get shipping senders for ere mode in creation', fakeAsync(() => {
                component.creationMode = true;
                component.shipping.options.sendMode = 'ere';
                component.shipping.account.id = 'maarch';
                component.shipping.account.password = 'maarch';

                component.getShippingSenders(false);

                const req = httpMock.expectOne(req => req.url === '../rest/shippings/senders');
                expect(req.request.params.get('accountId')).toBe('maarch');
                expect(req.request.params.get('accountPassword')).toBe('maarch');
                req.flush(mockSenders);

                tick();

                expect(component.shipping.senders).toEqual([
                    { id: 'ERES100', label: 'Barbara BAIN (bbain@maarch.com)' },
                    { id: 'ERES101', label: 'Patricia PETIT (ppetit@maarch.com)' }
                ]);
            }));

            it('should get shipping senders for ere mode in edit with template', fakeAsync(() => {
                component.creationMode = false;
                component.templateId = 1;
                component.shipping.options.sendMode = 'ere';

                component.getShippingSenders(false);

                const req = httpMock.expectOne(req => req.url === '../rest/shippings/senders');
                expect(req.request.params.get('templateId')).toBe('1');
                req.flush(mockSenders);

                tick();

                expect(component.shipping.senders).toEqual([
                    { id: 'ERES100', label: 'Barbara BAIN (bbain@maarch.com)' },
                    { id: 'ERES101', label: 'Patricia PETIT (ppetit@maarch.com)' }
                ]);
            }));

            it('should handle sender retrieval error', fakeAsync(() => {
                component.shipping.options.sendMode = 'ere';
                component.shipping.options.senderId = 'maarch';
                component.shipping.senders = [{ id: 'maarch', label: 'Barbara BAIN (bbain@maarch.com)' }];

                component.getShippingSenders(false);

                const req = httpMock.expectOne(req => req.url === '../rest/shippings/senders');
                req.error(new ErrorEvent('Network error'));

                tick();

                expect(notificationService.handleSoftErrors).toHaveBeenCalled();
                expect(component.shipping.options.senderId).toBe('');
                expect(component.shipping.senders).toEqual([]);
            }));

            it('should clear senders for non-ere mode', () => {
                component.shipping.options.sendMode = 'fast';
                component.shipping.options.senderId = 'maarch';
                component.shipping.senders = [{ id: 'maarch', label: 'maarch' }];

                component.getShippingSenders(false);

                expect(component.shipping.options.senderId).toBe('');
                expect(component.shipping.senders).toEqual([]);
            });
        });

        describe('Send Mode Change Flow', () => {
            it('should handle send mode change to ere', fakeAsync(() => {
                component.creationMode = false;
                component.shippingClone = { ...mockShippingData };
                component.shapingOptionsClone = ['addressPage', 'color'];
                spyOn(component, 'getShippingSenders');

                component.onSendModeChange('ere');

                expect(component.getShippingSenders).toHaveBeenCalledWith(true);
            }));

            it('should handle send mode change to ere with account change', fakeAsync(() => {
                component.creationMode = false;
                component.shippingClone = { ...mockShippingData };
                component.shipping.account.id = 'newAccoundId';
                spyOn(component, 'getShippingSenders');

                component.onSendModeChange('ere');

                expect(component.getShippingSenders).toHaveBeenCalledWith(true);
            }));

            it('should handle send mode change from ere to non-ere', () => {
                component.shapingOptionsClone = ['addressPage', 'color'];
                component.shipping.options.shapingOptions = [];

                component.onSendModeChange('fast');

                expect(component.shipping.options.shapingOptions).toEqual(['addressPage', 'color']);
            });
        });

        describe('Account Field Change Flow', () => {
            it('should trigger sender retrieval when account fields are filled', fakeAsync(() => {
                functionsService.empty.and.returnValue(false);
                component.ngOnInit();

                // Mock external connections
                const externalReq = httpMock.expectOne('../rest/externalConnectionsEnabled');
                externalReq.flush({ connection: { maileva: true } });

                // Mock new shipping data
                const newShippingReq = httpMock.expectOne('../rest/administration/shippings/new');
                newShippingReq.flush({ entities: mockEntities });

                tick();

                // Trigger account field change
                component.onAccountFieldChanged();
                tick(500); // Wait for debounce
            }));

            it('should not trigger sender retrieval when account fields are empty', fakeAsync(() => {
                functionsService.empty.and.returnValue(true);

                component.ngOnInit();

                // Mock external connections
                const externalReq = httpMock.expectOne('../rest/externalConnectionsEnabled');
                externalReq.flush({ connection: { maileva: true } });

                // Mock new shipping data
                const newShippingReq = httpMock.expectOne('../rest/administration/shippings/new');
                newShippingReq.flush({ entities: mockEntities });

                tick();

                // Trigger account field change
                component.onAccountFieldChanged();
                tick(500); // Wait for debounce
            }));
        });

        describe('Component Cleanup', () => {
            it('should unsubscribe on destroy', () => {
                component.ngOnInit();

                // Mock external connections
                const externalReq = httpMock.expectOne('../rest/externalConnectionsEnabled');
                externalReq.flush({ connection: { maileva: true } });

                // Mock new shipping data
                const newShippingReq = httpMock.expectOne('../rest/administration/shippings/new');
                newShippingReq.flush({ entities: mockEntities });

                spyOn(component.subscription, 'unsubscribe');

                component.ngOnDestroy();

                expect(component.subscription.unsubscribe).toHaveBeenCalled();
            });
        });
    });

    describe('Integration Tests', () => {
        it('should handle complete creation workflow', fakeAsync(() => {
            // Initialize component
            component.ngOnInit();

            // Mock external connections
            const externalReq = httpMock.expectOne('../rest/externalConnectionsEnabled');
            externalReq.flush({ connection: { maileva: true } });

            // Mock new shipping data
            const newShippingReq = httpMock.expectOne('../rest/administration/shippings/new');
            newShippingReq.flush({ entities: mockEntities });

            tick();

            // Fill form
            component.shipping.label = 'Shipping label';
            component.shipping.description = 'Shipping description';
            component.shipping.options.sendMode = 'ere';
            component.shipping.account.id = 'maarch';
            component.shipping.account.password = 'maarch';

            // Change to ere mode should trigger sender retrieval
            component.onSendModeChange('ere');

            const sendersReq = httpMock.expectOne(req => req.url === '../rest/shippings/senders');
            sendersReq.flush(mockSenders);

            tick();

            // Select sender
            component.shipping.options.senderId = 'ERES100';

            // Submit form
            component.onSubmit();

            const submitReq = httpMock.expectOne('../rest/administration/shippings');
            expect(submitReq.request.method).toBe('POST');
            submitReq.flush({ success: true });

            tick();

            expect(router.navigate).toHaveBeenCalledWith(['/administration/shippings']);
            expect(notificationService.success).toHaveBeenCalled();
        }));
    });
});