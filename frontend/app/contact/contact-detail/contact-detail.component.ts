import { Component, EventEmitter, Input, OnInit, Output } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { NotificationService } from '@service/notification/notification.service';
import { ContactService } from '@service/contact.service';
import { catchError, finalize, tap } from 'rxjs/operators';
import { TranslateService } from '@ngx-translate/core';
import { FunctionsService } from '@service/functions.service';
import { of } from 'rxjs';

@Component({
    selector: 'app-contact-detail',
    templateUrl: './contact-detail.component.html',
    styleUrls: ['./contact-detail.component.scss'],
    providers: [ContactService]
})
export class ContactDetailComponent implements OnInit {

    /**
     * [Id of contact to load a specific resource]
     * DO NOT USE with @resId
     * ex : {id: 1, type: 'contact'}
     */
    @Input() contact: any = {};

    @Input() selectable: boolean = false;

    @Output() afterSelectedEvent = new EventEmitter<any>();
    @Output() afterDeselectedEvent = new EventEmitter<any>();


    loading: boolean = true;

    contactClone: any = {};
    customFields: any[] = [];

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        public contactService: ContactService,
        public functionsService: FunctionsService,
        private notify: NotificationService
    ) {
    }

    async ngOnInit(): Promise<void> {
        await this.getCustomFields();

        if (Object.keys(this.contact).length === 2) {
            this.loadContact(this.contact.id, this.contact.type);
        } else if (Object.keys(this.contact).length > 2) {
            this.contactClone = JSON.parse(JSON.stringify(this.contact));
            this.loading = false;
        }
    }

    getCustomFields() {
        return new Promise((resolve) => {
            this.http.get('../rest/contactsCustomFields').pipe(
                tap((data: any) => {
                    this.customFields = data.customFields.map((custom: any) => ({
                        id: custom.id,
                        label: custom.label
                    }));
                    resolve(true);
                })
            ).subscribe();
        });
    }

    loadContact(contactId: number, type: string) {

        if (type === 'contact') {
            const queryParam: string = this.selectable ? '?resourcesCount=true' : '';
            this.http.get('../rest/contacts/' + contactId + queryParam).pipe(
                tap((contact: any) => {
                    this.contact = {
                        ...contact,
                        civility: this.contactService.formatCivilityObject(contact.civility),
                        fillingRate: this.contactService.formatFillingObject(contact.fillingRate),
                        customFields: !this.functionsService.empty(contact.customFields) ? this.formatCustomField(contact.customFields) : [],
                        type: 'contact'
                    };

                    this.contact = this.contactService.formatConfidentialFields(this.contact);
                    this.contactClone = JSON.parse(JSON.stringify(this.contact));
                }),
                finalize(() => this.loading = false),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
        } else if (type === 'user') {
            this.http.get('../rest/users/' + contactId).pipe(
                tap((data: any) => {
                    this.contact = {
                        type: 'user',
                        civility: this.contactService.formatCivilityObject(null),
                        fillingRate: this.contactService.formatFillingObject(null),
                        customFields: [],
                        firstname: data.firstname,
                        lastname: data.lastname,
                        email: data.mail,
                        department: data.department,
                        phone: data.phone,
                        enabled: data.enabled
                    };
                    this.contactClone = JSON.parse(JSON.stringify(this.contact));
                }),
                finalize(() => this.loading = false),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
        } else if (type === 'entity') {
            this.http.get('../rest/entities/' + contactId).pipe(
                tap((data: any) => {
                    this.contact = {
                        ...data,
                        type: 'entity',
                        civility: this.contactService.formatCivilityObject(null),
                        fillingRate: this.contactService.formatFillingObject(null),
                        customFields: [],
                        lastname: data.short_label,
                        enabled: data.enabled === 'Y'
                    };
                    this.contactClone = JSON.parse(JSON.stringify(this.contact));
                }),
                finalize(() => this.loading = false),
                catchError((err: any) => {
                    this.notify.error(err.error.errors);
                    return of(false);
                })
            ).subscribe();
        }
    }

    formatCustomField(data: any) {
        const arrCustomFields: any[] = [];

        Object.keys(data).forEach(element => {
            arrCustomFields.push({
                label: this.customFields.filter(custom => custom.id == element).length > 0 ? this.customFields.filter(custom => custom.id == element)[0].label : element,
                value: data[element]
            });
        });

        return arrCustomFields;
    }

    goTo(contact: any) {
        if (!this.contactService.isConfidentialAddress(this.contact)) {
            const addressNumber: string = this.contactService.getContactValueByType(contact.addressNumber, contact.type);
            const addressStreet: string = this.contactService.getContactValueByType(contact.addressStreet, contact.type);
            const addressPostcode: string = this.contactService.getContactValueByType(contact.addressPostcode, contact.type);
            const addressTown: string = this.contactService.getContactValueByType(contact.addressTown, contact.type);
            const addressCountry: string = this.contactService.getContactValueByType(contact.addressCountry, contact.type);
            window.open(`https://www.google.com/maps/search/${addressNumber}+${addressStreet},+${addressPostcode}+${addressTown},+${addressCountry}`, '_blank');
        }
    }

    emptyOtherInfo(contact: any) {
        return !(contact.type === 'contact' && (!this.functionsService.empty(contact.notes) || !this.functionsService.empty(contact.communicationMeans) || !this.functionsService.empty(contact.customFields)));
    }

    toggleContact(contact: any) {
        contact.selected = !contact.selected;

        if (contact.selected) {
            this.afterSelectedEvent.emit(contact);
        } else {
            this.afterDeselectedEvent.emit(contact);
        }
    }

    getContactInfo() {
        return this.contact;
    }

    resetContact() {
        this.contact = JSON.parse(JSON.stringify(this.contactClone));
    }

    setContactInfo(identifier: string, value: string) {
        if (!this.functionsService.empty(value)) {
            if (identifier === 'customFields') {
                this.contact[identifier].push(value);
            } else {
                this.contact[identifier] = value;
            }
        }
    }

    isNewValue(identifier: any) {
        const isCustomField = typeof identifier === 'object' && identifier !== 'civility';

        if (isCustomField) {
            return this.contactClone['customFields'].filter((custom: any) => custom.label === identifier.value.label).length === 0;
        } else if (identifier === 'civility') {
            return JSON.stringify(this.contact[identifier]) !== JSON.stringify(this.contactClone[identifier]);
        } else {
            return this.contact[identifier] !== this.contactClone[identifier];
        }
    }

    getHref(contact: { type: string; confidential: any; email: string | { value: string; }; }): string {
        let href: string = '';
        if (contact.type === 'contact') {
            if (typeof contact.email === 'object') {
                href = contact.confidential && contact.email.value === this.translate.instant('lang.confidentialData') ? href : `mailto:${contact.email.value}`;
            }
        } else {
            href = `mailto:${contact.email}`;
        }

        return href;
    }
}
