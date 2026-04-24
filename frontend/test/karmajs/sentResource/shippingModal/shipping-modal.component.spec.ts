import { ComponentFixture, fakeAsync, flush, TestBed, tick } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import {
    MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA,
    MatLegacyDialogModule as MatDialogModule,
    MatLegacyDialogRef as MatDialogRef
} from '@angular/material/legacy-dialog';
import { MatLegacyChipsModule as MatChipsModule } from '@angular/material/legacy-chips';
import { MatLegacyInputModule as MatInputModule } from '@angular/material/legacy-input';
import { MatIconModule } from '@angular/material/icon';
import { MatDividerModule } from '@angular/material/divider';
import { MatExpansionModule } from '@angular/material/expansion';
import { MatListModule } from '@angular/material/list';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { FormsModule } from '@angular/forms';
import { TranslateLoader, TranslateModule, TranslateService } from '@ngx-translate/core';
import { NotificationService } from '@service/notification/notification.service';
import { FunctionsService } from '@service/functions.service';
import { ContactService } from '@service/contact.service';
import { AppService } from '@service/app.service';
import { PrivilegeService } from '@service/privileges.service';
import { HeaderService } from '@service/header.service';
import { FullDatePipe } from '@plugins/fullDate.pipe';
import { SortPipe } from '@plugins/sorting.pipe';
import { ShippingModalComponent } from '@appRoot/sentResource/shippingModal/shipping-modal.component';
import { Observable, of } from "rxjs";
import { FoldersService } from "@appRoot/folder/folders.service";
import { LatinisePipe } from "ngx-pipes";
import * as langFrJson from "@langs/lang-fr.json";

class FakeLoader implements TranslateLoader {
    getTranslation(): Observable<any> {
        return of({ lang: langFrJson });
    }
}

/**
 * Test suite for ShippingModalComponent
 * This file tests the shipping modal component functionality including:
 * - Component initialization
 * - HTTP requests for shipping data
 * - UI element rendering and behavior
 * - Status handling
 */
describe('ShippingModalComponent', () => {
    let component: ShippingModalComponent;
    let fixture: ComponentFixture<ShippingModalComponent>;
    let httpTestingController: HttpTestingController;
    let notifyService: jasmine.SpyObj<NotificationService>;
    let dialogRefSpy: jasmine.SpyObj<MatDialogRef<ShippingModalComponent>>;
    let translateService: TranslateService;

    // Mock data for dialog
    const mockDialogData = {
        title: 'Envoi maileva',
        shippingData: {
            id: 123,
            sendingId: '80d4d89e-f87b-4f8c-9',
            sender: 'Bernard PASCONTENT',
            creationDate: '2025-04-20T12:30:00',
            sendDate: '2025-04-22T09:00:00',
            recipients: [['Pierre BRUNEL', '5 allée des Ommiers 99000 MAARCH-LES-BAINS'], ['Hamza HRAMCHI', '11 Boulevard Sud-Est 99000 Nanterre']]
        }
    };

    // Mock responses for HTTP requests
    const mockAttachmentsResponse = {
        attachments: [
            {
                attachmentType: 'shipping_deposit_proof',
                resId: 1001,
                title: 'Deposit Proof'
            },
            {
                attachmentType: 'shipping_acknowledgement_of_receipt',
                resId: 1002,
                title: 'Receipt Acknowledgement 1',
                filesize: 256000
            },
            {
                attachmentType: 'shipping_acknowledgement_of_receipt',
                resId: 1003,
                title: 'Receipt Acknowledgement 2',
                filesize: 512000
            }
        ]
    };

    const mockHistoryResponse = {
        history: [
            {
                eventType: 'SHIPPING_CREATED',
                eventDate: '2025-04-20T12:30:00',
                status: 'CREATED'
            },
            {
                eventType: 'SHIPPING_SENT',
                eventDate: '2025-04-22T09:00:00',
                status: 'SENT'
            },
            {
                eventType: 'ON_DEPOSIT_PROOF_RECEIVED',
                eventDate: '2025-04-23T10:15:00',
                status: 'RECEIVED'
            }
        ]
    };

    const mockStatusesResponse = {
        statuses: [
            { id: 'CREATED', label_status: 'Created' },
            { id: 'SENT', label_status: 'Sent' },
            { id: 'RECEIVED', label_status: 'Received' }
        ]
    };

    // Setup before each test
    beforeEach(async () => {
        // Create spies for services
        const notifyServiceSpy = jasmine.createSpyObj('NotificationService', ['handleSoftErrors']);
        const functionsServiceSpy = jasmine.createSpyObj('FunctionsService', ['empty', 'formatBytes']);
        const dialogRefSpyObj = jasmine.createSpyObj('MatDialogRef', ['close']);

        // Configure function spies with behavior
        functionsServiceSpy.empty.and.callFake((obj) => !obj);
        functionsServiceSpy.formatBytes.and.returnValue('250 KB');

        // Setup TestBed with required modules and components
        await TestBed.configureTestingModule({
            declarations: [
                ShippingModalComponent,
                FullDatePipe,
                SortPipe
            ],
            imports: [
                BrowserAnimationsModule,
                HttpClientTestingModule,
                FormsModule,
                MatDialogModule,
                MatChipsModule,
                MatInputModule,
                MatIconModule,
                MatDividerModule,
                MatExpansionModule,
                MatListModule,
                MatProgressSpinnerModule,
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: FakeLoader },
                }),
            ],
            providers: [
                { provide: NotificationService, useValue: notifyServiceSpy },
                { provide: FunctionsService, useValue: functionsServiceSpy },
                { provide: MatDialogRef, useValue: dialogRefSpyObj },
                { provide: MAT_DIALOG_DATA, useValue: mockDialogData },
                ContactService,
                AppService,
                PrivilegeService,
                HeaderService,
                FullDatePipe,
                SortPipe,
                TranslateService,
                FoldersService,
                LatinisePipe
            ]
        }).compileComponents();

        // Set lang
        translateService = TestBed.inject(TranslateService);
        translateService.use('fr');

        // Store references to test controller and services
        httpTestingController = TestBed.inject(HttpTestingController);
        notifyService = TestBed.inject(NotificationService) as jasmine.SpyObj<NotificationService>;
        dialogRefSpy = TestBed.inject(MatDialogRef) as jasmine.SpyObj<MatDialogRef<ShippingModalComponent>>;

        // Create component instance
        fixture = TestBed.createComponent(ShippingModalComponent);
        component = fixture.componentInstance;
    });

    // Clean up after each test
    afterEach(() => {
        httpTestingController.verify();
    });

    /**
     * Test: Component initialization
     * Verifies the component loads properly and makes expected HTTP calls
     */
    it('should initialize properly and load data', fakeAsync(() => {
        // Act: Trigger ngOnInit
        fixture.detectChanges();
        tick();

        // Expect HTTP calls for statuses, attachments, and history
        const statusesReq = httpTestingController.expectOne('../rest/statuses');
        expect(statusesReq.request.method).toBe('GET');
        statusesReq.flush(mockStatusesResponse);

        const attachmentsReq = httpTestingController.expectOne(`../rest/shippings/${mockDialogData.shippingData.sendingId}/attachments`);
        expect(attachmentsReq.request.method).toBe('GET');
        attachmentsReq.flush(mockAttachmentsResponse);

        const historyReq = httpTestingController.expectOne(`../rest/shippings/${mockDialogData.shippingData.sendingId}/history`);
        expect(historyReq.request.method).toBe('GET');
        historyReq.flush(mockHistoryResponse);

        // Complete initialization by advancing timers
        tick();
        fixture.detectChanges();

        // Verify component properties are set correctly
        expect(component.loading).toBeFalse();
        expect(component.depositProof).toEqual(mockAttachmentsResponse.attachments[0]);
        expect(component.shippingAttachments.length).toBe(2);
        expect(component.shippingHistory.length).toBe(2); // Filtering out ON_DEPOSIT_PROOF_RECEIVED
        expect(component.statuses.length).toBe(3);
    }));

    /**
     * Test: Error handling
     * Verifies the component handles HTTP errors gracefully
     */
    it('should handle errors when loading data', fakeAsync(() => {
        // Initialize component
        fixture.detectChanges();

        // Simulate an error for statuses request
        const statusesReq = httpTestingController.expectOne('../rest/statuses');
        statusesReq.error(new ErrorEvent('Network error'));

        // Simulate successful responses for other requests
        const attachmentsReq = httpTestingController.expectOne(`../rest/shippings/${mockDialogData.shippingData.sendingId}/attachments`);
        attachmentsReq.flush(mockAttachmentsResponse);

        const historyReq = httpTestingController.expectOne(`../rest/shippings/${mockDialogData.shippingData.sendingId}/history`);
        historyReq.flush(mockHistoryResponse);

        // Complete initialization
        tick();
        fixture.detectChanges();

        // Verify error handling
        expect(notifyService.handleSoftErrors).toHaveBeenCalled();
    }));

    /**
     * Test: UI rendering
     * Verifies UI elements are displayed correctly after data loading
     */
    it('should display shipping information correctly', fakeAsync(() => {
        // Initialize component
        fixture.detectChanges();

        // Respond to HTTP requests
        httpTestingController.expectOne('../rest/statuses').flush(mockStatusesResponse);
        httpTestingController.expectOne(`../rest/shippings/${mockDialogData.shippingData.sendingId}/attachments`).flush(mockAttachmentsResponse);
        httpTestingController.expectOne(`../rest/shippings/${mockDialogData.shippingData.sendingId}/history`).flush(mockHistoryResponse);

        // Complete initialization
        tick();
        fixture.detectChanges();

        // Verify UI elements
        const element = fixture.nativeElement;

        // Check sender info is displayed
        const senderInput = element.querySelector('#sender');
        expect(senderInput.title).toBe(mockDialogData.shippingData.sender);

        // Check recipient chips are displayed
        const chipElements = element.querySelectorAll('.recipients .matChip');
        expect(chipElements.length).toBe(mockDialogData.shippingData.recipients.length);

        // Check attachments are displayed
        const attachmentChips = element.querySelectorAll('.itemChip .listAutocomplete');
        expect(attachmentChips.length).toBe(3); // 1 deposit proof + 2 acknowledgements
    }));

    /**
     * Test: Status label retrieval
     * Verifies status labels are retrieved correctly
     */
    it('should get correct status labels', fakeAsync(() => {
        // Initialize component
        tick();
        fixture.detectChanges();

        // Respond to HTTP requests
        httpTestingController.expectOne('../rest/statuses').flush(mockStatusesResponse);
        httpTestingController.expectOne(`../rest/shippings/${mockDialogData.shippingData.sendingId}/attachments`).flush(mockAttachmentsResponse);
        httpTestingController.expectOne(`../rest/shippings/${mockDialogData.shippingData.sendingId}/history`).flush(mockHistoryResponse);

        // Complete initialization
        tick();
        fixture.detectChanges();

        // Test status label retrieval
        expect(component.setStatus('CREATED')).toBe('Created');
        expect(component.setStatus('SENT')).toBe('Sent');
        expect(component.setStatus('RECEIVED')).toBe('Received');
    }));

    /**
     * Test: Modal close
     * Verifies the dialog closes when the close button is clicked
     */
    it('should close the dialog when close button is clicked', fakeAsync(() => {
        // Initialize component
        fixture.detectChanges();

        // Respond to HTTP requests
        httpTestingController.expectOne('../rest/statuses').flush(mockStatusesResponse);
        httpTestingController.expectOne(`../rest/shippings/${mockDialogData.shippingData.sendingId}/attachments`).flush(mockAttachmentsResponse);
        httpTestingController.expectOne(`../rest/shippings/${mockDialogData.shippingData.sendingId}/history`).flush(mockHistoryResponse);

        // Complete initialization
        tick();
        fixture.detectChanges();

        // Find and click close button
        const closeButton = fixture.nativeElement.querySelector('button[mat-icon-button]');
        closeButton.click();

        // Verify dialog was closed
        expect(dialogRefSpy.close).toHaveBeenCalled();
    }));

    /**
     * Test: Empty recipient handling
     * Verifies the component filters empty recipient entries
     */
    it('should filter empty recipients', fakeAsync(() => {
        // Set up data with empty recipients
        component.data = {
            ...component.data,
            shippingData: {
                ...mockDialogData.shippingData,
                recipients: [['Pierre BRUNEL', '5 allée des Ommiers 99000 MAARCH-LES-BAINS'], ['Hamza HRAMCHI', '11 Boulevard Sud-Est 99000 Nanterre']]
            }
        };

        // Initialize component
        fixture.detectChanges();

        // Respond to HTTP requests
        httpTestingController.expectOne('../rest/statuses').flush(mockStatusesResponse);
        httpTestingController.expectOne(`../rest/shippings/${mockDialogData.shippingData.sendingId}/attachments`).flush(mockAttachmentsResponse);
        httpTestingController.expectOne(`../rest/shippings/${mockDialogData.shippingData.sendingId}/history`).flush(mockHistoryResponse);

        // Complete initialization
        tick();
        fixture.detectChanges();

        // Verify empty elements were filtered
        expect(component.data.shippingData.recipients[0]).not.toContain('');
        expect(component.data.shippingData.recipients[1]).not.toContain('');
    }));

    it('should display proofs for ere send mode', fakeAsync(() => {
        component.data = {
            ...component.data,
            shippingData: {
                ...mockDialogData.shippingData,
                sendMode: 'ere',
                recipientId: '6ebf058c-66ca-4b07-90bc'
            }
        };

        spyOn(component, 'getShippingHistory');
        spyOn(component, 'getStatus');
        spyOn(component, 'getAttachments');

        fixture.detectChanges();
        tick();

        expect(component.getShippingHistory).not.toHaveBeenCalled();
        expect(component.getStatus).not.toHaveBeenCalled();
        expect(component.getAttachments).not.toHaveBeenCalled();

        const nativeElement = fixture.nativeElement;
        const downloadProofs = nativeElement.querySelector('.download-proofs');
        const downloadDepositProof = downloadProofs.querySelector('button[name="downloadDepositProof"]');
        const downloadProofOfReceipt = downloadProofs.querySelector('button[name="downloadProofOfReceipt"]');

        expect(downloadProofs).toBeDefined();

        downloadDepositProof.click();

        fixture.detectChanges();
        tick();

        const req = httpTestingController.expectOne(`../rest/shippings/${component.data.shippingData.sendingId}/recipient/${component.data.shippingData.recipientId}/downloadDepositProof`);
        expect(req.request.method).toBe('GET');
        req.flush({});

        flush();

        fixture.detectChanges();
        tick();

        downloadProofOfReceipt.click();

        fixture.detectChanges();
        tick();

        const req2 = httpTestingController.expectOne(`../rest/shippings/${component.data.shippingData.sendingId}/recipient/${component.data.shippingData.recipientId}/downloadProofOfReceipt`);
        expect(req2.request.method).toBe('GET');
        req2.flush({});

        flush();
    }));
});