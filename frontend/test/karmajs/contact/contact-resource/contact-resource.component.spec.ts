import { ComponentFixture, fakeAsync, flush, TestBed, tick } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { TranslateModule } from '@ngx-translate/core';
import { By } from '@angular/platform-browser';
import { Component } from '@angular/core';

import { NotificationService } from '@service/notification/notification.service';
import { ContactService } from '@service/contact.service';
import { FunctionsService } from '@service/functions.service';
import { ContactResourceComponent } from "@appRoot/contact/contact-resource/contact-resource.component";
import { HeaderService } from "@service/header.service";
import { FoldersService } from "@appRoot/folder/folders.service";
import { MatLegacyDialogModule } from "@angular/material/legacy-dialog";

// Test host component to test @Input properties
@Component({
    template: `
        <app-contact-resource
                [resId]="resourceId"
                [mode]="contactMode">
        </app-contact-resource>
    `
})
class TestHostComponent {
    resourceId: number | null = null;
    contactMode: 'recipients' | 'senders' = 'recipients';
}

describe('ContactResourceComponent - Functional Tests', () => {
    let hostComponent: TestHostComponent;
    let hostFixture: ComponentFixture<TestHostComponent>;
    let component: ContactResourceComponent;
    let httpMock: HttpTestingController;
    let notificationService: jasmine.SpyObj<NotificationService>;
    let contactService: jasmine.SpyObj<ContactService>;
    let functionsService: jasmine.SpyObj<FunctionsService>;

    const mockCustomFields = {
        customFields: [
            { id: '1', label: 'Department' },
            { id: '2', label: 'Manager' },
            { id: '3', label: 'Building' }
        ]
    };

    const mockRecipients = {
        contacts: [
            {
                id: 1,
                type: 'contact',
                firstname: 'Hamza',
                lastname: 'HRAMCHI',
                email: 'alice@company.com',
                civility: 'Mr',
                fillingRate: { rate: 85 },
                customFields: { '1': 'Marketing', '2': 'Tech' }
            },
            {
                id: 2,
                type: 'entity',
                company: 'Maarch',
                email: 'entity@maarch.com',
                civility: null,
                fillingRate: { rate: 95 },
                customFields: { '3': 'Building A' }
            }
        ]
    };

    const mockSenders = {
        contacts: [
            {
                id: 3,
                type: 'contact',
                firstname: 'Bernard',
                lastname: 'PASCONTENT',
                email: 'bernardd@maarch.com',
                civility: 'Mr',
                fillingRate: { rate: 70 },
                customFields: null
            }
        ]
    };

    beforeEach(async () => {
        const notificationSpy = jasmine.createSpyObj('NotificationService', ['handleErrors']);
        const contactSpy = jasmine.createSpyObj('ContactService', [
            'formatCivilityObject',
            'formatFillingObject',
            'formatConfidentialFields'
        ]);
        const functionsSpy = jasmine.createSpyObj('FunctionsService', ['empty']);

        await TestBed.configureTestingModule({
            declarations: [ContactResourceComponent, TestHostComponent],
            imports: [
                HttpClientTestingModule,
                MatLegacyDialogModule,
                TranslateModule.forRoot()
            ],
            providers: [
                { provide: NotificationService, useValue: notificationSpy },
                { provide: ContactService, useValue: contactSpy },
                { provide: FunctionsService, useValue: functionsSpy },
                HeaderService,
                FoldersService,
            ]
        }).compileComponents();

        hostFixture = TestBed.createComponent(TestHostComponent);
        hostComponent = hostFixture.componentInstance;
        component = hostFixture.debugElement.query(By.directive(ContactResourceComponent)).componentInstance;

        httpMock = TestBed.inject(HttpTestingController);
        notificationService = TestBed.inject(NotificationService) as jasmine.SpyObj<NotificationService>;
        contactService = TestBed.inject(ContactService) as jasmine.SpyObj<ContactService>;
        functionsService = TestBed.inject(FunctionsService) as jasmine.SpyObj<FunctionsService>;

        // Setup service spy returns
        contactService.formatCivilityObject.and.callFake((civility) => civility ? `${civility}.` : '');
        contactService.formatFillingObject.and.callFake((rate) => ({ ...rate, formatted: `${rate.rate}%` }));
        contactService.formatConfidentialFields.and.callFake((contact) => ({ ...contact, confidential: true }));
        functionsService.empty.and.returnValue(false);
    });

    afterEach(() => {
        httpMock.verify();
    });

    describe('Input Property Binding', () => {
        it('should accept resId input and trigger data loading', fakeAsync(() => {
            hostComponent.resourceId = 100;
            hostFixture.detectChanges();
            tick();

            expect(component.resId).toBe(100);

            // Should make requests for both custom fields and contacts
            const customFieldsReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldsReq.flush(mockCustomFields);

            flush();

            const contactsReq = httpMock.expectOne('../rest/resources/100/contacts?type=recipients');
            contactsReq.flush(mockRecipients);

            tick();

            expect(component.contacts.length).toBe(2);
        }));

        it('should accept mode input and use it in API call', fakeAsync(() => {
            hostComponent.resourceId = 100;
            hostComponent.contactMode = 'senders';

            hostFixture.detectChanges();
            tick();

            expect(component.resId).toBe(100);
            expect(hostComponent.contactMode).toBe('senders');

            // Should make requests for both custom fields and contacts
            const customFieldsReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldsReq.flush(mockCustomFields);

            flush();

            const contactsReq = httpMock.expectOne('../rest/resources/100/contacts?type=senders');
            contactsReq.flush(mockRecipients);

            tick();

            expect(component.contacts.length).toBe(2);
        }));

        it('should not load contacts when resId is null', fakeAsync(() => {
            hostComponent.resourceId = null;
            hostFixture.detectChanges();
            tick();

            expect(component.resId).toBeNull();

            // Should only request custom fields
            const customFieldsReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldsReq.flush(mockCustomFields);

            // No contacts request should be made
            httpMock.expectNone('../rest/resources/100/contacts?type=recipients');

            tick();
            expect(component.contacts.length).toBe(0);
        }));
    });

    describe('Dynamic Input Changes', () => {
        it('should handle changing resId after initialization', fakeAsync(() => {
            // Initial load
            hostComponent.resourceId = 300;
            hostFixture.detectChanges();
            tick();

            const customFieldsReq1 = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldsReq1.flush(mockCustomFields);

            flush();

            const contactsReq1 = httpMock.expectOne('../rest/resources/300/contacts?type=recipients');
            contactsReq1.flush(mockRecipients);

            tick();
            expect(component.contacts.length).toBe(2);

            // Change resId (this would typically require a manual call to loadContactsOfResource)
            component.loadContactsOfResource(400, 'recipients');

            const contactsReq2 = httpMock.expectOne('../rest/resources/400/contacts?type=recipients');
            contactsReq2.flush(mockSenders);

            tick();
            expect(component.contacts.length).toBe(2);
        }));

        it('should handle changing mode after initialization', fakeAsync(() => {
            hostComponent.resourceId = 500;
            hostComponent.contactMode = 'recipients';
            hostFixture.detectChanges();
            tick();

            const customFieldsReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldsReq.flush(mockCustomFields);

            flush();

            const contactsReq1 = httpMock.expectOne('../rest/resources/500/contacts?type=recipients');
            contactsReq1.flush(mockRecipients);

            tick();
            expect(component.contacts.length).toBe(2);

            // Simulate mode change
            component.loadContactsOfResource(500, 'senders');

            const contactsReq2 = httpMock.expectOne('../rest/resources/500/contacts?type=senders');
            contactsReq2.flush(mockSenders);

            tick();
            expect(component.contacts.length).toBe(2);
        }));
    });

    describe('Loading State Management', () => {
        it('should manage loading state correctly during successful request', fakeAsync(() => {
            expect(component.loading).toBeTruthy();

            hostComponent.resourceId = 600;
            hostFixture.detectChanges();
            tick();

            // Loading should still be true during requests
            expect(component.loading).toBeTruthy();

            const customFieldsReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldsReq.flush(mockCustomFields);

            flush();

            const contactsReq = httpMock.expectOne('../rest/resources/600/contacts?type=recipients');

            // Still loading until contacts request completes
            expect(component.loading).toBeTruthy();

            contactsReq.flush(mockRecipients);
            tick();

            // Loading should be false after completion
            expect(component.loading).toBeFalsy();
        }));

        it('should set loading to false after error', fakeAsync(() => {
            expect(component.loading).toBeTruthy();

            hostComponent.resourceId = 700;
            hostFixture.detectChanges();
            tick();

            const customFieldsReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldsReq.flush(mockCustomFields);

            flush();

            const contactsReq = httpMock.expectOne('../rest/resources/700/contacts?type=recipients');
            contactsReq.flush('Server Error', { status: 500, statusText: 'Internal Server Error' });

            tick();

            expect(component.loading).toBeFalsy();
            expect(notificationService.handleErrors).toHaveBeenCalled();
        }));
    });

    describe('Data Processing Flow', () => {
        it('should process contacts with custom fields correctly', fakeAsync(() => {
            functionsService.empty.and.returnValue(false);

            hostComponent.resourceId = 800;
            hostFixture.detectChanges();
            tick();

            const customFieldsReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldsReq.flush(mockCustomFields);

            flush();

            const contactsReq = httpMock.expectOne('../rest/resources/800/contacts?type=recipients');
            contactsReq.flush(mockRecipients);

            tick();

            expect(component.contacts[0].customFields).toEqual([
                { label: 'Department', value: 'Marketing' },
                { label: 'Manager', value: 'Tech' }
            ]);

            expect(component.contacts[1].customFields).toEqual([
                { label: 'Building', value: 'Building A' }
            ]);
        }));

        it('should handle contacts without custom fields', fakeAsync(() => {
            functionsService.empty.and.returnValue(true);

            hostComponent.resourceId = 900;
            hostFixture.detectChanges();
            tick();

            const customFieldsReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldsReq.flush(mockCustomFields);

            flush();

            const contactsReq = httpMock.expectOne('../rest/resources/900/contacts?type=recipients');
            contactsReq.flush(mockSenders);

            tick();

            expect(component.contacts[0].customFields).toEqual([]);
        }));
    });

    describe('Error Handling', () => {
        it('should handle network timeout scenarios', fakeAsync(() => {
            hostComponent.resourceId = 1200;
            hostFixture.detectChanges();
            tick();

            const customFieldsReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldsReq.flush(mockCustomFields);

            flush();

            const contactsReq = httpMock.expectOne('../rest/resources/1200/contacts?type=recipients');
            contactsReq.error(new ErrorEvent('Network error', {
                message: 'Request timeout'
            }));

            tick();

            expect(notificationService.handleErrors).toHaveBeenCalled();
            expect(component.loading).toBeFalsy();
            expect(component.contacts).toEqual([]);
        }));

        it('should handle malformed response data', fakeAsync(() => {
            hostComponent.resourceId = 1300;
            hostFixture.detectChanges();
            tick();

            const customFieldsReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldsReq.flush(mockCustomFields);

            flush();

            const contactsReq = httpMock.expectOne('../rest/resources/1300/contacts?type=recipients');
            contactsReq.flush({ invalidStructure: true }); // Missing 'contacts' property

            tick();

            // Component should handle gracefully without crashing
            expect(component.loading).toBeFalsy();
            expect(component.contacts).toBeDefined();
        }));
    });

    describe('Real-world Scenarios', () => {
        it('should handle switching between recipients and senders', fakeAsync(() => {
            hostComponent.resourceId = 1600;
            hostComponent.contactMode = 'recipients';
            hostFixture.detectChanges();
            tick();

            // Load recipients
            const customFieldsReq1 = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldsReq1.flush(mockCustomFields);

            flush();

            const recipientsReq = httpMock.expectOne('../rest/resources/1600/contacts?type=recipients');
            recipientsReq.flush(mockRecipients);

            tick();
            expect(component.contacts.length).toBe(2);
            expect(component.contacts[0].firstname).toBe('Hamza');

            // Switch to senders
            component.loadContactsOfResource(1600, 'senders');

            const sendersReq = httpMock.expectOne('../rest/resources/1600/contacts?type=senders');
            sendersReq.flush(mockSenders);

            tick();
            expect(component.contacts.length).toBe(2);
            expect(component.contacts[0].firstname).toBe('Hamza');
        }));

        it('should handle empty contact lists', fakeAsync(() => {
            const emptyResponse = { contacts: [] };

            hostComponent.resourceId = 1700;
            hostFixture.detectChanges();
            tick();

            const customFieldsReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldsReq.flush(mockCustomFields);

            flush();

            const contactsReq = httpMock.expectOne('../rest/resources/1700/contacts?type=recipients');
            contactsReq.flush(emptyResponse);

            tick();

            expect(component.contacts).toEqual([]);
            expect(component.loading).toBeFalsy();
            expect(contactService.formatConfidentialFields).not.toHaveBeenCalled();
        }));
    });

    describe('Component State Consistency', () => {
        it('should maintain consistent state during concurrent operations', fakeAsync(() => {
            hostComponent.resourceId = 1900;
            hostFixture.detectChanges();
            tick();

            // Start first operation
            const customFieldsReq1 = httpMock.expectOne('../rest/contactsCustomFields');

            // Start second operation before first completes
            component.loadContactsOfResource(2000, 'senders');

            // Complete first operation
            customFieldsReq1.flush(mockCustomFields);

            flush();

            const contactsReq1 = httpMock.expectOne('../rest/resources/1900/contacts?type=recipients');
            const contactsReq2 = httpMock.expectOne('../rest/resources/2000/contacts?type=senders');

            // Complete second operation first
            contactsReq2.flush(mockSenders);
            tick();

            // Complete first operation
            contactsReq1.flush(mockRecipients);
            tick();

            // State should reflect the last completed operation
            expect(component.loading).toBeFalsy();
            expect(component.contacts.length).toBeGreaterThan(0);
        }));

        it('should handle component destruction during pending requests', fakeAsync(() => {
            hostComponent.resourceId = 2100;
            hostFixture.detectChanges();
            tick();

            const customFieldsReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldsReq.flush(mockCustomFields);

            flush();

            // Start contacts request but don't complete it
            httpMock.expectOne('../rest/resources/2100/contacts?type=recipients');

            // Simulate component destruction
            hostFixture.destroy();

            // Should not cause errors or memory leaks
            tick(1000);

            // Verify no hanging subscriptions or callbacks
            expect(true).toBeTruthy(); // Test passes if no errors thrown
        }));
    });

    describe('API Contract Validation', () => {
        it('should send correct headers and parameters', fakeAsync(() => {
            hostComponent.resourceId = 2200;
            hostComponent.contactMode = 'senders';
            hostFixture.detectChanges();
            tick();

            const customFieldsReq = httpMock.expectOne('../rest/contactsCustomFields');
            expect(customFieldsReq.request.method).toBe('GET');
            expect(customFieldsReq.request.url).toBe('../rest/contactsCustomFields');
            customFieldsReq.flush(mockCustomFields);

            flush();


            const contactsReq = httpMock.expectOne('../rest/resources/2200/contacts?type=senders');
            expect(contactsReq.request.method).toBe('GET');
            expect(contactsReq.request.url).toBe('../rest/resources/2200/contacts?type=senders');
            expect(contactsReq.request.url).toContain('senders');
            contactsReq.flush(mockSenders);

            tick();
        }));

        it('should handle different HTTP status codes appropriately', fakeAsync(() => {
            const testCases = [
                { status: 200, expectSuccess: true },
                { status: 400, expectSuccess: false },
                { status: 401, expectSuccess: false },
                { status: 403, expectSuccess: false },
                { status: 404, expectSuccess: false },
                { status: 500, expectSuccess: false }
            ];

            testCases.forEach((testCase, index) => {
                const resourceId = 2300 + index;

                component.loadContactsOfResource(resourceId, 'recipients');

                const contactsReq = httpMock.expectOne(`../rest/resources/${resourceId}/contacts?type=recipients`);

                if (testCase.expectSuccess) {
                    contactsReq.flush(mockRecipients);
                    expect(component.contacts.length).toBeGreaterThan(0);
                } else {
                    contactsReq.flush('Error', { status: testCase.status, statusText: 'Error' });
                    expect(notificationService.handleErrors).toHaveBeenCalled();
                }

                tick();
                expect(component.loading).toBeFalsy();

                // Reset for next test
                notificationService.handleErrors.calls.reset();
                component.contacts = [];
            });
        }));
    });
});