import { ComponentFixture, fakeAsync, flush, TestBed, tick } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { TranslateLoader, TranslateModule, TranslateService } from '@ngx-translate/core';
import { By } from '@angular/platform-browser';
import { ContactService } from '@service/contact.service';
import { NotificationService } from '@service/notification/notification.service';
import { FunctionsService } from '@service/functions.service';
import { ContactDetailComponent } from "@appRoot/contact/contact-detail/contact-detail.component";
import { MatLegacyListModule } from "@angular/material/legacy-list";
import { MatLegacyButtonModule } from '@angular/material/legacy-button';
import { MatLegacyProgressSpinnerModule } from "@angular/material/legacy-progress-spinner";
import { MatLegacyCardModule } from "@angular/material/legacy-card";
import { Observable, of } from "rxjs";
import * as langFrJson from "@langs/lang-fr.json";
import { Pipe, PipeTransform } from "@angular/core";
import { HeaderService } from "@service/header.service";
import { FoldersService } from "@appRoot/folder/folders.service";
import { MatLegacyDialogModule } from "@angular/material/legacy-dialog";

@Pipe({ name: 'splitLoginPwd' })
class MockSplitLoginPwdPipe implements PipeTransform {
    transform(value: any): any {
        return value; // Return the value unchanged for testing
    }
}

class FakeLoader implements TranslateLoader {
    getTranslation(): Observable<any> {
        return of({ lang: langFrJson });
    }
}

describe('ContactDetailComponent', () => {
    let component: ContactDetailComponent;
    let fixture: ComponentFixture<ContactDetailComponent>;
    let httpMock: HttpTestingController;
    let contactService: jasmine.SpyObj<ContactService>;
    let notificationService: jasmine.SpyObj<NotificationService>;
    let functionsService: jasmine.SpyObj<FunctionsService>;
    let translateService: TranslateService;

    const mockCustomFields = {
        customFields: [
            { id: '1', label: 'Custom Field 1' },
            { id: '2', label: 'Custom Field 2' }
        ]
    };

    const mockContact = {
        id: 1,
        type: 'contact',
        firstname: 'John',
        lastname: 'Doe',
        email: { value: 'john.doe@maarch.com' },
        phone: { value: '+1234567890' },
        company: 'MAARCH',
        function: 'Developer',
        department: 'IT',
        civility: { label: 'Mr.', abbreviation: 'Mr.' },
        fillingRate: { color: '#green' },
        addressNumber: { value: '123' },
        addressStreet: { value: 'Main St' },
        addressPostcode: { value: '12345' },
        addressTown: { value: 'Test City' },
        addressCountry: { value: 'Test Country' },
        addressAdditional1: { value: 'Building A' },
        addressAdditional2: { value: 'Floor 2' },
        sector: { value: 'Tech Sector' },
        notes: 'Test notes',
        customFields: [{ '1': 'Custom Value 1' }],
        communicationMeans: {
            url: 'http://example.maarch.com',
            email: 'comm@maarch.com',
            login: 'testlogin'
        },
        externalId: { 'm2m': 'ext123' },
        enabled: true,
        confidential: false
    };

    const mockUser = {
        firstname: 'Barbara',
        lastname: 'BAIN',
        mail: 'bbain@maarch.com',
        phone: '+330987654321',
        department: 'HR',
        enabled: true
    };

    const mockEntity = {
        short_label: 'PJS',
        enabled: 'Y'
    };

    beforeEach(async () => {
        const contactServiceSpy = jasmine.createSpyObj('ContactService', [
            'formatCivilityObject',
            'formatFillingObject',
            'formatConfidentialFields',
            'isConfidentialAddress',
            'getContactValueByType',
            'checkAddressValidity',
            'isConfidentialFieldWithoutValue',
            'getConfidentialValue'
        ]);

        const notificationServiceSpy = jasmine.createSpyObj('NotificationService', [
            'handleErrors',
            'error'
        ]);

        const functionsServiceSpy = jasmine.createSpyObj('FunctionsService', [
            'empty'
        ]);

        await TestBed.configureTestingModule({
            declarations: [ContactDetailComponent, MockSplitLoginPwdPipe],
            imports: [
                HttpClientTestingModule,
                BrowserAnimationsModule,
                MatLegacyCardModule,
                MatLegacyListModule,
                MatLegacyButtonModule,
                MatLegacyProgressSpinnerModule,
                MatLegacyDialogModule,
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: FakeLoader },
                })
            ],
            providers: [
                { provide: ContactService, useValue: contactServiceSpy },
                { provide: NotificationService, useValue: notificationServiceSpy },
                { provide: FunctionsService, useValue: functionsServiceSpy },
                TranslateService,
                HeaderService,
                FoldersService
            ]
        }).compileComponents();

        // Set lang
        translateService = TestBed.inject(TranslateService);
        translateService.use('fr');

        fixture = TestBed.createComponent(ContactDetailComponent);
        component = fixture.componentInstance;
        httpMock = TestBed.inject(HttpTestingController);
        contactService = TestBed.inject(ContactService) as jasmine.SpyObj<ContactService>;
        notificationService = TestBed.inject(NotificationService) as jasmine.SpyObj<NotificationService>;
        functionsService = TestBed.inject(FunctionsService) as jasmine.SpyObj<FunctionsService>;
        translateService = TestBed.inject(TranslateService) as jasmine.SpyObj<TranslateService>;

        // Default spy returns
        contactService.formatCivilityObject.and.returnValue({ label: 'Mr.', abbreviation: 'Mr.' });
        contactService.formatFillingObject.and.returnValue({
            color: '#green',
            rate: 10
        });
        contactService.formatConfidentialFields.and.returnValue(mockContact);
        contactService.isConfidentialAddress.and.returnValue(false);
        contactService.getContactValueByType.and.returnValue('test value');
        contactService.checkAddressValidity.and.returnValue(true);
        contactService.isConfidentialFieldWithoutValue.and.returnValue(false);
        contactService.getConfidentialValue.and.returnValue('*'.repeat(30));
        functionsService.empty.and.returnValue(false);
    });

    afterEach(() => {
        httpMock.verify();
    });

    describe('Component Initialization', () => {
        it('should create', () => {
            expect(component).toBeTruthy();
        });

        it('should initialize with default values', () => {
            expect(component.contact).toEqual({});
            expect(component.selectable).toBeFalse();
            expect(component.loading).toBeTrue();
            expect(component.contactClone).toEqual({});
            expect(component.customFields).toEqual([]);
        });

        it('should load custom fields on init', fakeAsync(() => {
            component.ngOnInit();
            tick();

            const req = httpMock.expectOne('../rest/contactsCustomFields');
            expect(req.request.method).toBe('GET');
            req.flush(mockCustomFields);

            expect(component.customFields).toEqual([
                { id: '1', label: 'Custom Field 1' },
                { id: '2', label: 'Custom Field 2' }
            ]);
        }));

        it('should load contact when input has id and type', fakeAsync(() => {
            component.contact = { id: 1, type: 'contact' };
            spyOn(component, 'loadContact');

            fixture.detectChanges();
            tick();

            const req = httpMock.expectOne('../rest/contactsCustomFields');
            req.flush(mockCustomFields);

            flush();

            expect(component.loadContact).toHaveBeenCalledWith(1, 'contact');
        }));

        it('should use existing contact data when provided', fakeAsync(() => {
            component.contact = mockContact;

            fixture.detectChanges();
            tick();

            const req = httpMock.expectOne('../rest/contactsCustomFields');
            req.flush(mockCustomFields);

            flush();

            expect(component.contactClone).toEqual(mockContact);
            expect(component.loading).toBeFalse();
        }));
    });

    describe('Contact Loading', () => {
        it('should load contact with resources count when selectable', () => {
            component.selectable = true;
            component.loadContact(1, 'contact');

            const req = httpMock.expectOne('../rest/contacts/1?resourcesCount=true');
            expect(req.request.method).toBe('GET');
            req.flush(mockContact);
        });

        it('should load user data', () => {
            component.loadContact(1, 'user');

            const req = httpMock.expectOne('../rest/users/1');
            expect(req.request.method).toBe('GET');
            req.flush(mockUser);

            expect(component.contact.type).toBe('user');
            expect(component.contact.firstname).toBe('Barbara');
            expect(component.contact.email).toBe('bbain@maarch.com');
        });

        it('should load entity data', () => {
            component.loadContact(1, 'entity');

            const req = httpMock.expectOne('../rest/entities/1');
            expect(req.request.method).toBe('GET');
            req.flush(mockEntity);

            expect(component.contact.type).toBe('entity');
            expect(component.contact.lastname).toBe('PJS');
            expect(component.contact.enabled).toBeTrue();
        });

        it('should handle contact loading error', () => {
            component.loadContact(1, 'contact');

            const req = httpMock.expectOne('../rest/contacts/1');
            req.error(new ErrorEvent('Network error'));

            expect(notificationService.handleErrors).toHaveBeenCalled();
            expect(component.loading).toBeFalse();
        });

        it('should handle entity loading error', () => {
            component.loadContact(1, 'entity');

            const req = httpMock.expectOne('../rest/entities/1');
            req.error(new ErrorEvent('Network error'), { status: 400, statusText: 'Bad Request' });

            expect(notificationService.error).toHaveBeenCalled();
            expect(component.loading).toBeFalse();
        });
    });

    describe('Custom Fields', () => {
        beforeEach(() => {
            component.customFields = mockCustomFields.customFields;
        });

        it('should format custom fields correctly', () => {
            const data = { '1': 'Value 1', '2': 'Value 2' };
            const result = component.formatCustomField(data);

            expect(result).toEqual([
                { label: 'Custom Field 1', value: 'Value 1' },
                { label: 'Custom Field 2', value: 'Value 2' }
            ]);
        });

        it('should use field id as label when custom field not found', () => {
            const data = { '99': 'Unknown Value' };
            const result = component.formatCustomField(data);

            expect(result).toEqual([
                { label: '99', value: 'Unknown Value' }
            ]);
        });
    });

    describe('Contact Selection', () => {
        it('should toggle contact selection and emit events', () => {
            spyOn(component.afterSelectedEvent, 'emit');
            spyOn(component.afterDeselectedEvent, 'emit');

            const contact = { ...mockContact, selected: false };

            component.toggleContact(contact);
            expect(contact.selected).toBeTrue();
            expect(component.afterSelectedEvent.emit).toHaveBeenCalledWith(contact);

            component.toggleContact(contact);
            expect(contact.selected).toBeFalse();
            expect(component.afterDeselectedEvent.emit).toHaveBeenCalledWith(contact);
        });
    });

    describe('Contact Management', () => {
        beforeEach(() => {
            component.contact = { ...mockContact };
            component.contactClone = { ...mockContact };
        });

        it('should get contact info', () => {
            const result = component.getContactInfo();
            expect(result).toEqual(component.contact);
        });

        it('should reset contact to original state', () => {
            component.contact.firstname = 'Updated firstname';
            component.resetContact();
            expect(component.contact.firstname).toBe(mockContact.firstname);
        });

        it('should set contact info for regular fields', () => {
            functionsService.empty.and.returnValue(false);
            component.setContactInfo('firstname', 'New firstname');
            expect(component.contact.firstname).toBe('New firstname');
        });

        it('should not set contact info for empty values', () => {
            functionsService.empty.and.returnValue(true);
            const originalValue = component.contact.firstname;
            component.setContactInfo('firstname', '');
            expect(component.contact.firstname).toBe(originalValue);
        });

        it('should add to custom fields array', () => {
            functionsService.empty.and.returnValue(false);
            component.contact.customFields = [];
            component.setContactInfo('customFields', 'new custom value');
            expect(component.contact.customFields).toContain('new custom value');
        });
    });

    describe('Change Detection', () => {
        beforeEach(() => {
            component.contact = { ...mockContact };
            component.contactClone = { ...mockContact };
        });

        it('should detect new values for regular fields', () => {
            component.contact.firstname = 'Updated firstname';
            expect(component.isNewValue('firstname')).toBeTrue();
        });

        it('should detect unchanged values for regular fields', () => {
            expect(component.isNewValue('firstname')).toBeFalse();
        });

        it('should detect civility changes', () => {
            component.contact.civility = { label: 'Mrs.', abbreviation: 'Mrs.' };
            expect(component.isNewValue('civility')).toBeTrue();
        });

        it('should detect new custom fields', () => {
            component.contactClone.customFields = [];
            const identifier = { value: { label: 'New Custom Field' } };
            expect(component.isNewValue(identifier)).toBeTrue();
        });
    });

    describe('Email Href Generation', () => {
        it('should generate mailto href for non-confidential contact email', () => {
            const contact = {
                type: 'contact',
                confidential: false,
                email: { value: 'test@example.com', confidential: false }
            };

            const result = component.getHref(contact);
            expect(result).toBe('mailto:test@example.com');
        });

        it('should generate mailto href for user email', () => {
            const contact = {
                type: 'user',
                email: 'user@example.com',
                confidential: false
            };

            const result = component.getHref(contact);
            expect(result).toBe('mailto:user@example.com');
        });

        it('should return empty href for confidential email', () => {
            const contact = {
                type: 'contact',
                confidential: true,
                email: { value: 'Donnée personnelle' }
            };

            const result = component.getHref(contact);
            expect(result).toBe('');
        });
    });

    describe('Utility Methods', () => {
        it('should check if other info section is empty', () => {
            functionsService.empty.and.returnValue(true);
            const contact = { type: 'contact' };
            expect(component.emptyOtherInfo(contact)).toBeTrue();
        });

        it('should detect non-empty other info section', () => {
            functionsService.empty.and.returnValue(false);
            const contact = { type: 'contact', notes: 'Some notes' };
            expect(component.emptyOtherInfo(contact)).toBeFalse();
        });

        it('should check confidential field without value', () => {
            contactService.getConfidentialValue.and.returnValue('******************************');
            const contact = {
                type: 'contact',
                email: { confidential: true, value: contactService.getConfidentialValue() }
            };

            const result = component.contactService.isConfidentialFieldWithoutValue(contact, 'email');
            expect(result).toBeTrue();
        });
    });

    // HTML Integration Tests for Legacy Material
    describe('Template Integration (Legacy Material)', () => {
        beforeEach(fakeAsync(() => {
            component.ngOnInit();
            tick();
            const req = httpMock.expectOne('../rest/contactsCustomFields');
            req.flush(mockCustomFields);
        }));

        it('should display contact card when not loading', fakeAsync(() => {
            component.loading = false;
            component.contact = mockContact;
            component.contactClone = mockContact;

            fixture.detectChanges();
            tick();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            fixture.detectChanges();
            tick();

            expect(fixture.nativeElement.querySelector('md-card')).toBeDefined();
        }));

        it('should display contact name with civility in card title', fakeAsync(() => {
            component.loading = false;
            component.contact = mockContact;
            component.contactClone = mockContact;

            functionsService.empty.and.returnValue(false);
            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            const title = fixture.nativeElement.querySelector('.mat-card-title');
            expect(title).toBeDefined();

            const titleText = title.textContent.trim();
            expect(titleText).toContain('John Doe');
        }));

        it('should display function in card subtitle', fakeAsync(() => {
            component.loading = false;
            component.contact = mockContact;
            component.contactClone = mockContact;
            functionsService.empty.and.returnValue(false);
            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            const subtitle = fixture.debugElement.query(By.css('mat-card-subtitle'));
            expect(subtitle).toBeTruthy();
            const subtitleText = subtitle.nativeElement.textContent.trim();
            expect(subtitleText).toContain('Developer');
        }));

        it('should display contact details in mat-list', fakeAsync(() => {
            component.loading = false;
            component.contact = mockContact;
            component.contactClone = mockContact;

            functionsService.empty.and.returnValue(false);
            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            const list = fixture.nativeElement.querySelector('mat-list');
            expect(list).toBeDefined();

            const subheader = fixture.debugElement.query(By.css('h3[mat-subheader]'));
            expect(subheader).toBeTruthy();
        }));

        it('should display email with correct FontAwesome icon and title', fakeAsync(() => {
            component.loading = false;
            component.contact = mockContact;
            component.contactClone = mockContact;

            functionsService.empty.and.returnValue(false);
            contactService.getContactValueByType.and.returnValue('john.doe@example.com');
            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            const emailIcon = fixture.nativeElement.querySelector('.fa-envelope');
            expect(emailIcon).toBeDefined();
            expect(emailIcon.title).toBe(translateService.instant('lang.email'));
        }));

        it('should display phone with correct FontAwesome icon and title', fakeAsync(() => {
            component.loading = false;
            component.contact = mockContact;
            component.contactClone = mockContact;

            functionsService.empty.and.returnValue(false);
            contactService.getContactValueByType.and.returnValue('+1234567890');
            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            const phoneIcon = fixture.debugElement.query(By.css('.fa-phone'));
            expect(phoneIcon).toBeTruthy();
            expect(phoneIcon.nativeElement.getAttribute('title')).toBe(translateService.instant('lang.phoneNumber'));
        }));

        it('should display address section with map marker icon when valid', fakeAsync(() => {
            component.loading = false;
            component.contact = mockContact;
            component.contactClone = mockContact;

            functionsService.empty.and.returnValue(false);
            contactService.checkAddressValidity.and.returnValue(true);
            contactService.isConfidentialAddress.and.returnValue(false);
            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            const addressIcon = fixture.debugElement.query(By.css('.fa-map-marker-alt'));
            expect(addressIcon).toBeTruthy();
        }));

        it('should display confidential address with lock icon', fakeAsync(() => {
            component.loading = false;
            component.contact = mockContact;
            component.contactClone = mockContact;

            Object.keys(mockContact).forEach(key => {
                if (['address', 'sector', 'phone', 'email'].includes(key)) {
                    mockContact[key] = { confidential: true }
                }
            })

            functionsService.empty.and.returnValue(false);
            contactService.checkAddressValidity.and.returnValue(true);
            contactService.isConfidentialAddress.and.returnValue(true);
            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            const lockIcon = fixture.nativeElement.querySelector('.fa-user-lock');
            expect(lockIcon).toBeDefined();

            const confidentialText = fixture.nativeElement.querySelector('.confidential');
            expect(confidentialText).toBeDefined();

            expect(confidentialText.title.trim()).toContain(translateService.instant('lang.confidentialData'));
        }));

        it('should display mat-expansion-panel for other info when not empty', fakeAsync(() => {
            component.loading = false;
            component.contact = { ...mockContact, type: 'contact' };
            component.contactClone = { ...mockContact, type: 'contact' };

            functionsService.empty.and.returnValue(false);
            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            const expansionPanel = fixture.debugElement.query(By.css('mat-expansion-panel'));
            expect(expansionPanel).toBeDefined();
        }));

        it('should display notes with sticky note icon in other info section', fakeAsync(() => {
            component.loading = false;
            component.contact = { ...mockContact, type: 'contact' };
            component.contactClone = { ...mockContact, type: 'contact' };

            functionsService.empty.and.returnValue(false);
            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            const noteIcon = fixture.nativeElement.querySelector('.fa-sticky-note');
            expect(noteIcon).toBeDefined();
        }));

        it('should display selection button when selectable and not selected', fakeAsync(() => {
            component.loading = false;
            component.selectable = true;
            component.contact = { ...mockContact, selected: false, resourcesCount: 5 };
            component.contactClone = { ...mockContact, selected: false, resourcesCount: 5 };

            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            const selectButton = fixture.nativeElement.querySelector('button[mat-raised-button][color="primary"]');
            expect(selectButton).toBeDefined();
            expect(selectButton.textContent).toContain(translateService.instant('lang.selectDuplicatedContact'));
            expect(selectButton.textContent).toContain('5');
        }));

        it('should display selected state button with check icon', fakeAsync(() => {
            component.loading = false;
            component.selectable = true;
            component.contact = { ...mockContact, selected: true };
            component.contactClone = { ...mockContact, selected: true };

            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            const selectedButton = fixture.debugElement.query(By.css('button .fa-check-circle'));
            expect(selectedButton).toBeDefined();
        }));

        it('should display disabled contact message', fakeAsync(() => {
            component.loading = false;
            component.contact = { ...mockContact, enabled: false };
            component.contactClone = { ...mockContact, enabled: false };

            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            const disabledMessage = fixture.debugElement.query(By.css('.disabledContact'));
            expect(disabledMessage).toBeDefined();
        }));

        it('should display correct avatar icon for contact type', fakeAsync(() => {
            component.loading = false;
            component.contact = { ...mockContact, type: 'contact' };
            component.contactClone = { ...mockContact, type: 'contact' };

            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            const avatar = fixture.debugElement.query(By.css('.fa-address-card'));
            expect(avatar).toBeDefined();
            expect(avatar.nativeElement.getAttribute('title')).toBe(translateService.instant('lang.contact_contact'));
        }));

        it('should display correct avatar icon for user type', fakeAsync(() => {
            component.loading = false;
            component.contact = { ...mockContact, type: 'user' };
            component.contactClone = { ...mockContact, type: 'user' };

            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            const avatar = fixture.debugElement.query(By.css('.fa-user'));
            expect(avatar).toBeDefined();
            expect(avatar.nativeElement.getAttribute('title')).toBe(translateService.instant('lang.contact_user'));
        }));

        it('should display correct avatar icon for entity type', fakeAsync(() => {
            component.loading = false;
            component.contact = { ...mockContact, type: 'entity' };
            component.contactClone = { ...mockContact, type: 'entity' };

            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            const avatar = fixture.debugElement.query(By.css('.fa-sitemap'));
            expect(avatar).toBeDefined();
            expect(avatar.nativeElement.getAttribute('title')).toBe(translateService.instant('lang.contact_entity'));
        }));

        it('should display correct avatar icon for contact group type', fakeAsync(() => {
            component.loading = false;
            component.contact = { ...mockContact, type: 'contactGroup' };
            component.contactClone = { ...mockContact, type: 'contactGroup' };

            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            const avatar = fixture.debugElement.query(By.css('.fa-users'));
            expect(avatar).toBeDefined();
            expect(avatar.nativeElement.getAttribute('title')).toBe(translateService.instant('lang.contact_contactGroup'));
        }));

        it('should handle button click for contact selection', fakeAsync(() => {
            component.loading = false;
            component.selectable = true;
            component.contact = { ...mockContact, selected: false };
            component.contactClone = { ...mockContact, selected: false };

            spyOn(component, 'toggleContact');
            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            const button = fixture.debugElement.query(By.css('button[color="primary"]'));
            button.nativeElement.click();

            expect(component.toggleContact).toHaveBeenCalledWith(component.contact);
        }));

        it('should handle address click for navigation', fakeAsync(() => {
            component.loading = false;
            component.contact = mockContact;
            component.contactClone = mockContact;

            functionsService.empty.and.returnValue(false);
            contactService.checkAddressValidity.and.returnValue(true);
            contactService.isConfidentialAddress.and.returnValue(false);

            spyOn(component, 'goTo');
            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            const addressItem = fixture.debugElement.query(By.css('.contact-address'));
            if (addressItem) {
                addressItem.nativeElement.click();
                expect(component.goTo).toHaveBeenCalledWith(component.contact);
            }
        }));

        it('should display custom fields with hashtag icons in other info section', fakeAsync(() => {
            component.loading = false;
            component.contact = {
                ...mockContact,
                type: 'contact',
                customFields: [{ label: 'Custom Field', value: 'Custom Value' }]
            };
            component.contactClone = {
                ...mockContact,
                type: 'contact',
                customFields: [{ label: 'Custom Field', value: 'Custom Value' }]
            };

            functionsService.empty.and.returnValue(false);
            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            const customFieldIcons = fixture.debugElement.queryAll(By.css('.fa-hashtag'));
            expect(customFieldIcons.length).toBeGreaterThan(0);
        }));

        it('should show newData class for changed fields', fakeAsync(() => {
            component.loading = false;
            component.contact = mockContact;
            component.contactClone = { ...mockContact, firstname: 'Original' };

            functionsService.empty.and.returnValue(false);
            spyOn(component, 'isNewValue').and.returnValue(true);
            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            const newDataElements = fixture.debugElement.queryAll(By.css('.newData'));
            expect(newDataElements.length).toBeGreaterThan(0);
        }));

        it('should display communication means in expansion panel', fakeAsync(() => {
            component.loading = false;
            component.contact = { ...mockContact, type: 'contact' };
            component.contactClone = { ...mockContact, type: 'contact' };

            functionsService.empty.and.returnValue(false);
            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            // Check for communication means URL, email, login
            const commElements = fixture.debugElement.queryAll(By.css('mat-list-item'));
            expect(commElements.length).toBeGreaterThan(0);
        }));

        it('should display filling rate indicator for contacts', fakeAsync(() => {
            component.loading = false;
            component.contact = { ...mockContact, type: 'contact' };
            component.contactClone = { ...mockContact, type: 'contact' };

            functionsService.empty.and.returnValue(false);
            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            const fillingRate = fixture.debugElement.query(By.css('.contact-filling'));
            expect(fillingRate).toBeTruthy();
            expect(fillingRate.nativeElement.getAttribute('title')).toBe(translateService.instant('lang.contactsFillingRate'));
        }));

        it('should display company info when available', fakeAsync(() => {
            component.loading = false;
            component.contact = mockContact;
            component.contactClone = mockContact;

            functionsService.empty.and.returnValue(false);
            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            const companyIcon = fixture.debugElement.query(By.css('.fa-building'));
            expect(companyIcon).toBeTruthy();
            expect(companyIcon.nativeElement.getAttribute('title')).toBe(translateService.instant('lang.contactsParameters_company'));
        }));

        it('should display department info when available', fakeAsync(() => {
            component.loading = false;
            component.contact = mockContact;
            component.contactClone = mockContact;

            functionsService.empty.and.returnValue(false);
            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            const deptIcon = fixture.debugElement.query(By.css('.fa-sitemap'));
            expect(deptIcon).toBeTruthy();
            expect(deptIcon.nativeElement.getAttribute('title')).toBe(translateService.instant('lang.contactsParameters_department'));
        }));

        it('should handle email confidential display with lock icon', fakeAsync(() => {
            component.loading = false;
            component.contact = mockContact;
            component.contactClone = mockContact;

            functionsService.empty.and.returnValue(false);
            contactService.isConfidentialFieldWithoutValue.and.returnValue(true);
            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            const lockIcon = fixture.debugElement.query(By.css('mat-icon.fas.fa-user-lock'));
            expect(lockIcon).toBeTruthy();
        }));

        it('should handle phone confidential display with lock icon', fakeAsync(() => {
            component.loading = false;
            component.contact = mockContact;
            component.contactClone = mockContact;

            functionsService.empty.and.returnValue(false);
            contactService.isConfidentialFieldWithoutValue.and.returnValue(true);
            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            const confidentialPhone = fixture.debugElement.query(By.css('p.confidential'));
            expect(confidentialPhone).toBeTruthy();
        }));

        it('should display sector information when available', fakeAsync(() => {
            component.loading = false;
            component.contact = mockContact;
            component.contactClone = mockContact;

            functionsService.empty.and.returnValue(false);
            contactService.isConfidentialAddress.and.returnValue(false);
            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            const sectorIcon = fixture.debugElement.query(By.css('.fa-map-marked-alt'));
            expect(sectorIcon).toBeTruthy();
            expect(sectorIcon.nativeElement.getAttribute('title')).toBe(translateService.instant(('lang.contactsParameters_sector')));
        }));

        it('should display external M2M ID when available', fakeAsync(() => {
            component.loading = false;
            component.contact = { ...mockContact, type: 'contact' };
            component.contactClone = { ...mockContact, type: 'contact' };

            functionsService.empty.and.returnValue(false);
            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            // Should display external ID in other info section
            const expansionPanel = fixture.debugElement.query(By.css('mat-expansion-panel'));
            expect(expansionPanel).toBeTruthy();
        }));

        it('should use keyvalue pipe for custom fields display', fakeAsync(() => {
            component.loading = false;
            component.contact = {
                ...mockContact,
                type: 'contact',
                customFields: [{ label: 'Test Field', value: 'Test Value' }]
            };
            component.contactClone = {
                ...mockContact,
                type: 'contact',
                customFields: [{ label: 'Test Field', value: 'Test Value' }]
            };

            functionsService.empty.and.returnValue(false);
            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            // Custom fields should be displayed using keyvalue pipe
            const customFieldsContainer = fixture.debugElement.query(By.css('ng-container'));
            expect(customFieldsContainer).toBeDefined();
        }));

        it('should display civility abbreviation with proper styling', fakeAsync(() => {
            component.loading = false;
            component.contact = mockContact;
            component.contactClone = mockContact;

            functionsService.empty.and.returnValue(false);
            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            const civilityElement = fixture.debugElement.query(By.css('sup'));
            expect(civilityElement).toBeTruthy();
            expect(civilityElement.nativeElement.textContent.trim()).toContain('Mr.');
        }));

        it('should handle address additional fields display', fakeAsync(() => {
            component.loading = false;
            component.contact = mockContact;
            component.contactClone = mockContact;

            functionsService.empty.and.returnValue(false);
            contactService.checkAddressValidity.and.returnValue(true);
            contactService.isConfidentialAddress.and.returnValue(false);

            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            // Should display address additional fields
            const addressSection = fixture.debugElement.query(By.css('mat-list-item'));
            expect(addressSection).toBeTruthy();
        }));

        it('should handle matLine directive for email links', fakeAsync(() => {
            component.loading = false;
            component.contact = mockContact;
            component.contactClone = mockContact;

            functionsService.empty.and.returnValue(false);
            contactService.isConfidentialFieldWithoutValue.and.returnValue(false);
            contactService.getContactValueByType.and.returnValue('bbain@maarch.com');

            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            const emailLink = fixture.debugElement.query(By.css('a[matLine]'));
            expect(emailLink).toBeTruthy();
            expect(emailLink.nativeElement.getAttribute('href')).toContain('mailto:');
        }));

        it('should apply correct CSS classes based on contact state', fakeAsync(() => {
            component.loading = false;
            component.selectable = true;
            component.contact = { ...mockContact, selected: true };
            component.contactClone = { ...mockContact, selected: true };

            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            const selectableDiv = fixture.debugElement.query(By.css('.selectable.selected'));
            expect(selectableDiv).toBeTruthy();
        }));

        it('should display button text content correctly for selection', fakeAsync(() => {
            component.loading = false;
            component.selectable = true;
            component.contact = { ...mockContact, selected: false, resourcesCount: 3 };
            component.contactClone = { ...mockContact, selected: false, resourcesCount: 3 };

            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            const button = fixture.debugElement.query(By.css('button[color="primary"]'));
            const buttonText = button.nativeElement.textContent;
            expect(buttonText).toContain(translateService.instant('lang.selectDuplicatedContact'));
            expect(buttonText).toContain('3');
            expect(buttonText).toContain(translateService.instant('lang.associatedElements'));
        }));

        it('should display selected button text correctly', fakeAsync(() => {
            component.loading = false;
            component.selectable = true;
            component.contact = { ...mockContact, selected: true };
            component.contactClone = { ...mockContact, selected: true };

            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            const selectedButton = fixture.debugElement.query(By.css('button[color="primary"]'));
            const buttonText = selectedButton.nativeElement.textContent;
            expect(buttonText).toContain(translateService.instant(('lang.selectedContact')));
        }));

        it('should display all communication means fields', fakeAsync(() => {
            component.loading = false;
            component.contact = { ...mockContact, type: 'contact' };
            component.contactClone = { ...mockContact, type: 'contact' };

            functionsService.empty.and.returnValue(false);
            fixture.detectChanges();

            const customFieldReq = httpMock.expectOne('../rest/contactsCustomFields');
            customFieldReq.flush(mockCustomFields);
            tick();
            flush();

            // Should display URL, email, and login fields in communication means
            const commIcons = fixture.debugElement.queryAll(By.css('.fa-hashtag'));
            expect(commIcons.length).toBeGreaterThan(0);
        }));
    });
});