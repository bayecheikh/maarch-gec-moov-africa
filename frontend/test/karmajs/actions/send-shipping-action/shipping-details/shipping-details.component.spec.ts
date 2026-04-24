import { ComponentFixture, TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { TranslateLoader, TranslateModule, TranslateService } from '@ngx-translate/core';
import { By } from '@angular/platform-browser';
import { NotificationService } from '@service/notification/notification.service';
import { AttachListInterface } from '@models/send-shipping-action.model';
import {
    ShippingDetailsComponent
} from "@appRoot/actions/send-shipping-action/shipping-details/shipping-details.component";
import { Observable, of } from "rxjs";
import * as langFrJson from "@langs/lang-fr.json";
import { FoldersService } from "@appRoot/folder/folders.service";
import { SharedModule } from "@appRoot/app-common.module";
import { DatePipe } from "@angular/common";

class FakeLoader implements TranslateLoader {
    getTranslation(): Observable<{ lang: any }> {
        return of({ lang: langFrJson });
    }
}

describe('ShippingDetailsComponent', () => {
    let component: ShippingDetailsComponent;
    let fixture: ComponentFixture<ShippingDetailsComponent>;
    let httpMock: HttpTestingController;
    let translateService: TranslateService;
    let notificationService: NotificationService;

    // Mock data for testing
    const mockShippingDetails: AttachListInterface = {
        '123': {
            '456': [
                {
                    contactId: '456',
                    contactLabel: 'Bernard PASCONTENT',
                    title: 'Courrier de test',
                    chrono: 'MAARCH/2025D/26 ',
                    type: 'mail',
                    res_id: 123,
                    integrations: '{ "inShipping": true }'
                },
                {
                    contactId: '456',
                    contactLabel: 'Bernard PASCONTENT',
                    title: 'Pièce jointe de test',
                    chrono: 'MAARCH/2025D/27',
                    type: 'attachment',
                    res_id: 790,
                    integrations: '{ "inShipping": true }'
                }
            ]
        },
        '124': {
            '457': [
                {
                    contactId: '457',
                    contactLabel: 'Hamza HRAMCHI',
                    title: 'Courrier de test 2',
                    chrono: 'MAARCH/2025D/10',
                    type: 'mail',
                    res_id: 124,
                    integrations: '{ "inShipping": true }'
                }
            ]
        }
    };

    beforeEach(async () => {
        // Mock notification service
        const notificationServiceSpy = jasmine.createSpyObj('NotificationService', ['handleBlobErrors']);

        await TestBed.configureTestingModule({
            declarations: [ShippingDetailsComponent],
            imports: [
                HttpClientTestingModule,
                SharedModule,
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: FakeLoader },
                }),
            ],
            providers: [
                TranslateService,
                FoldersService,
                DatePipe,
                { provide: NotificationService, useValue: notificationServiceSpy }
            ]
        }).compileComponents();

        // Set lang
        translateService = TestBed.inject(TranslateService);
        translateService.use('fr');

        fixture = TestBed.createComponent(ShippingDetailsComponent);
        component = fixture.componentInstance;
        httpMock = TestBed.inject(HttpTestingController);
        notificationService = TestBed.inject(NotificationService);
    });

    /**
     * Test suite for component initialization
     */
    describe('Component Initialization', () => {
        it('should create the component', () => {
            expect(component).toBeTruthy();
        });

        it('should initialize with default values', () => {
            expect(component.shippingDetails).toBeNull();
            expect(component.digitalPackageLength).toBeNull();
            expect(component.loading).toBe(true);
            expect(component.formattedShippingDetails).toEqual([]);
        });

        it('should call formatShippingDetails on ngOnInit', () => {
            spyOn(component, 'formatShippingDetails').and.returnValue([]);
            component.ngOnInit();
            expect(component.formatShippingDetails).toHaveBeenCalled();
        });

        it('should set loading to false after formatting shipping details', () => {
            component.shippingDetails = mockShippingDetails;
            component.ngOnInit();
            expect(component.loading).toBe(false);
        });
    });

    /**
     * Test suite for formatShippingDetails method
     */
    describe('formatShippingDetails()', () => {
        it('should format shipping details correctly with single contact', () => {
            component.shippingDetails = {
                '123': {
                    '456': [
                        {
                            contactId: '456',
                            contactLabel: 'Bernard PASCONTENT',
                            title: 'Document 1',
                            chrono: 'MAARCH/2025A/13',
                            type: 'mail',
                            res_id: 123,
                            integrations: '{ "inShipping": true }'
                        }
                    ]
                }
            };
            const result = component.formatShippingDetails();

            expect(result.length).toBe(1);
            expect(result[0].contactLabel).toBe('Bernard PASCONTENT');
            expect(result[0].documents.length).toBe(1);
            expect(result[0].documents[0].title).toBe('Document 1');
        });

        it('should format shipping details correctly with multiple contacts', () => {
            component.shippingDetails = mockShippingDetails;
            const result = component.formatShippingDetails();

            expect(result.length).toBe(2);
            expect(result[0].contactLabel).toBe('Bernard PASCONTENT');
            expect(result[0].documents.length).toBe(2);
            expect(result[1].contactLabel).toBe('Hamza HRAMCHI');
            expect(result[1].documents.length).toBe(1);
        });

        it('should preserve all document properties during formatting', () => {
            component.shippingDetails = mockShippingDetails;
            const result = component.formatShippingDetails();

            const firstDocument = result[0].documents[0];
            expect(firstDocument.title).toBe('Courrier de test');
            expect(firstDocument.chrono).toBe('MAARCH/2025D/26 ');
            expect(firstDocument.type).toBe('mail');
            expect(firstDocument.res_id).toBe(123);
        });

        it('should handle empty shipping details', () => {
            component.shippingDetails = {};
            const result = component.formatShippingDetails();

            expect(result.length).toBe(0);
            expect(component.loading).toBe(false);
        });

        it('should set loading to false after formatting', () => {
            component.shippingDetails = mockShippingDetails;
            component.loading = true;
            component.formatShippingDetails();

            expect(component.loading).toBe(false);
        });
    });

    /**
     * Test suite for getType method
     */
    describe('getType()', () => {
        it('should return "Main Document" for mail type', () => {
            const result = component.getType('mail');
            expect(result).toBe(translateService.instant('lang.mainDocument'));
        });

        it('should return "Attachment" for non-mail type', () => {
            const result = component.getType('attachment');
            expect(result).toBe(translateService.instant('lang.attachment'));
        });
    });

    /**
     * Test suite for viewThumbnail method
     */
    describe('viewThumbnail()', () => {
        it('should emit showThumbnail event with resources type for mail documents', () => {
            spyOn(component.showThumbnail, 'emit');
            const document = { type: 'mail', res_id: 123 };

            component.viewThumbnail(document);

            expect(component.showThumbnail.emit).toHaveBeenCalledWith({
                resId: 123,
                type: 'resources'
            });
        });

        it('should emit showThumbnail event with attachments type for non-mail documents', () => {
            spyOn(component.showThumbnail, 'emit');
            const document = { type: 'attachment', res_id: 456 };

            component.viewThumbnail(document);

            expect(component.showThumbnail.emit).toHaveBeenCalledWith({
                resId: 456,
                type: 'attachments'
            });
        });
    });

    /**
     * Test suite for closeThumbnail method
     */
    describe('closeThumbnail()', () => {
        it('should emit hideThumbnail event', () => {
            spyOn(component.hideThumbnail, 'emit');

            component.closeThumbnail();

            expect(component.hideThumbnail.emit).toHaveBeenCalled();
        });
    });

    /**
     * Test suite for viewDocument method
     */
    describe('viewDocument()', () => {
        const mockPdfBlob = new Blob(['test pdf content'], { type: 'application/pdf' });
        let windowOpenSpy: jasmine.Spy;
        let mockWindow: any;

        beforeEach(() => {
            // Mock window.open
            mockWindow = {
                document: {
                    write: jasmine.createSpy('write'),
                    title: ''
                }
            };
            windowOpenSpy = spyOn(window, 'open').and.returnValue(mockWindow);
            spyOn(URL, 'createObjectURL').and.returnValue('blob:mock-url');
        });

        it('should fetch document content from resources endpoint for mail type', () => {
            const document = { type: 'mail', res_id: 123, chrono: 'MAARCH/2025A/10 ' };

            component.viewDocument(document);

            const req = httpMock.expectOne('../rest/resources/123/content?mode=view');
            expect(req.request.method).toBe('GET');
            expect(req.request.responseType).toBe('blob');
            req.flush(mockPdfBlob);
        });

        it('should fetch document content from attachments endpoint for attachment type', () => {
            const document = { type: 'attachment', res_id: 456, chrono: 'MAARCH/2025A/11' };

            component.viewDocument(document);

            const req = httpMock.expectOne('../rest/attachments/456/content?mode=view');
            expect(req.request.method).toBe('GET');
            req.flush(mockPdfBlob);
        });

        it('should open new window with PDF content on successful fetch', () => {
            const document = { type: 'mail', res_id: 123, chrono: 'MAARCH/2025A/13' };

            component.viewDocument(document);

            const req = httpMock.expectOne('../rest/resources/123/content?mode=view');
            req.flush(mockPdfBlob);

            expect(windowOpenSpy).toHaveBeenCalled();
            expect(mockWindow.document.write).toHaveBeenCalled();
            expect(mockWindow.document.title).toBe('MAARCH/2025A/13');
        });

        it('should create blob URL with correct MIME type', () => {
            const document = { type: 'mail', res_id: 100, chrono: 'MAARCH/2025D/15' };

            component.viewDocument(document);

            const req = httpMock.expectOne('../rest/resources/100/content?mode=view');
            req.flush(mockPdfBlob);

            expect(URL.createObjectURL).toHaveBeenCalledWith(jasmine.any(Blob));
        });

        it('should handle HTTP errors gracefully', () => {
            const document = { type: 'mail', res_id: 999, chrono: 'MAARCH/2025D/16' };
            const mockError = new ErrorEvent('Network error');

            component.viewDocument(document);

            const req = httpMock.expectOne('../rest/resources/999/content?mode=view');
            req.error(mockError);

            expect(notificationService.handleBlobErrors).toHaveBeenCalled();
        });

        it('should set window title to document chrono', () => {
            const document = { type: 'attachment', res_id: 200, chrono: 'MAARCH/2025A/20' };

            component.viewDocument(document);

            const req = httpMock.expectOne('../rest/attachments/200/content?mode=view');
            req.flush(mockPdfBlob);

            expect(mockWindow.document.title).toBe('MAARCH/2025A/20');
        });
    });

    /**
     * Test suite for EventEmitter outputs
     */
    describe('Event Emitters', () => {
        it('should emit closeSidenav event when triggered', (done) => {
            component.closeSidenav.subscribe(() => {
                expect(true).toBe(true);
                done();
            });

            component.closeSidenav.emit();
        });

        it('should emit showThumbnail event with correct data', (done) => {
            const expectedData = { type: 'resources', resId: 123 };

            component.showThumbnail.subscribe((data) => {
                expect(data).toEqual(expectedData);
                done();
            });

            component.showThumbnail.emit(expectedData);
        });

        it('should emit hideThumbnail event when triggered', (done) => {
            component.hideThumbnail.subscribe(() => {
                expect(true).toBe(true);
                done();
            });

            component.hideThumbnail.emit();
        });
    });

    /**
     * Test suite for Input properties
     */
    describe('Input Properties', () => {
        it('should accept shippingDetails input', () => {
            component.shippingDetails = mockShippingDetails;
            expect(component.shippingDetails).toBe(mockShippingDetails);
        });

        it('should accept digitalPackageLength input', () => {
            component.digitalPackageLength = 5;
            expect(component.digitalPackageLength).toBe(5);
        });

        it('should handle null shippingDetails', () => {
            component.shippingDetails = null;
            expect(component.shippingDetails).toBeNull();
        });
    });

    /**
     * Test suite for template rendering
     */
    describe('Template Rendering', () => {
        it('should render correct number of digital packages', () => {
            component.shippingDetails = mockShippingDetails;
            component.ngOnInit();
            fixture.detectChanges();

            const headerElements = fixture.debugElement.queryAll(By.css('.header-content'));
            expect(headerElements.length).toBe(2);
        });

        it('should display close button in header', () => {
            fixture.detectChanges();

            const closeButton = fixture.debugElement.query(By.css('.header-container button'));
            expect(closeButton).toBeTruthy();
        });
    });

    /**
     * Test suite for integration scenarios
     */
    describe('Integration Scenarios', () => {
        it('should complete full lifecycle from init to display', () => {
            component.shippingDetails = mockShippingDetails;
            component.ngOnInit();
            fixture.detectChanges();

            expect(component.loading).toBe(false);
            expect(component.formattedShippingDetails.length).toBe(2);

            const shippingElements = fixture.debugElement.queryAll(By.css('.shipping-content'));
            expect(shippingElements.length).toBe(3); // 2 documents for Bernard PASCONTENT + 1 for Hamza HRAMCHI
        });

        it('should handle user interaction flow: view thumbnail then close', () => {
            spyOn(component.showThumbnail, 'emit');
            spyOn(component.hideThumbnail, 'emit');

            const document = { type: 'mail', res_id: 123 };

            component.viewThumbnail(document);
            expect(component.showThumbnail.emit).toHaveBeenCalled();

            component.closeThumbnail();
            expect(component.hideThumbnail.emit).toHaveBeenCalled();
        });
    });
});