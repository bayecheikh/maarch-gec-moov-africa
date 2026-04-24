import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { FunctionsService } from './functions.service';
import { Router } from '@angular/router';
import { AddressContactInterface } from "@models/contact.model";
import { HeaderService } from "@service/header.service";

@Injectable()
export class ContactService {

    addressFields: string[] = ['addressNumber', 'addressStreet', 'addressPostcode', 'addressTown', 'addressCountry', 'addressAdditional1', 'addressAdditional2', 'sector'];

    readonly confidentialFields: string[] = [
        'email',
        'phone',
        ...this.addressFields
    ];

    private readonly hiddenConfidentialValue: string = '*'.repeat(30);

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        public functions: FunctionsService,
        public router: Router,
        public headerService: HeaderService
    ) {
    }

    getAdminMenu() {
        let route: any = this.router.url.split('?')[0].split('/');
        route = route[route.length - 1];
        return [
            {
                icon: 'fa fa-book',
                route: '/administration/contacts',
                label: this.translate.instant('lang.contactsList'),
                current: route === 'contacts'
            },
            {
                icon: 'fa fa-code',
                route: '/administration/contacts/contactsCustomFields',
                label: this.translate.instant('lang.customFieldsAdmin'),
                current: route === 'contactsCustomFields'
            },
            {
                icon: 'fa fa-cog',
                route: '/administration/contacts/contacts-parameters',
                label: this.translate.instant('lang.contactsParameters'),
                current: route === 'contacts-parameters'
            },
            {
                icon: 'fas fa-atlas',
                route: '/administration/contacts/contacts-groups',
                label: this.translate.instant('lang.contactsGroups'),
                current: route === 'contacts-groups'
            },
            {
                icon: 'fas fa-magic',
                route: '/administration/contacts/duplicates',
                label: this.translate.instant('lang.duplicatesContactsAdmin'),
                current: route === 'duplicates'
            },
        ];
    }

    goTo(route: string) {
        this.router.navigate([route]);
    }

    getFillingColor(thresholdLevel: 'first' | 'second' | 'third') {
        if (thresholdLevel === 'first') {
            return '#E81C2B';
        } else if (thresholdLevel === 'second') {
            return '#F4891E';
        } else if (thresholdLevel === 'third') {
            return '#0AA34F';
        } else {
            return '';
        }
    }

    formatCivilityObject(civility: any) {
        if (!this.empty(civility)) {
            return civility;
        } else {
            return {
                label: '',
                abbreviation: ''
            };
        }
    }

    formatFillingObject(filling: any) {
        if (!this.empty(filling)) {
            return {
                rate: filling.rate,
                color: this.getFillingColor(filling.thresholdLevel)
            };
        } else {
            return {
                rate: '',
                color: ''
            };
        }
    }

    empty(value: any) {
        return !(value !== null && value !== '' && value !== undefined);
    }

    formatContact(contact: any) {
        if (this.functions.empty(contact.firstname) && this.functions.empty(contact.lastname)) {
            return contact.company;

        } else {
            const arrInfo = [];
            arrInfo.push(contact.firstname);
            arrInfo.push(contact.lastname);
            if (!this.functions.empty(contact.company)) {
                arrInfo.push('(' + contact.company + ')');
            }

            return arrInfo.filter(info => !this.functions.empty(info)).join(' ');
        }
    }

    formatContactAddress(contact: any) {
        const arrInfo: string[] = [];
        arrInfo.push(contact.addressNumber);
        arrInfo.push(contact.addressStreet);
        arrInfo.push(contact.addressPostcode);
        arrInfo.push(contact.addressTown);
        arrInfo.push(contact.addressCountry);

        return arrInfo.filter(info => !this.functions.empty(info)).join(' ');
    }

    isConfidentialField(field: { confidential: boolean }) {
        return field.confidential ?? false;
    }

    /**
     * Sets the contact address properties based on the provided AddressContactInterface object.
     *
     * @param {AddressContactInterface} element - The object containing the contact address fields to be set.
     * @return {AddressContactInterface} The updated contact address object with the provided values.
     */
    setContactAddress(element: AddressContactInterface): AddressContactInterface {
        const contactAddress: AddressContactInterface = {
            addressNumber: { confidential: false, value: '' },
            addressStreet: { confidential: false, value: '' },
            addressPostcode: { confidential: false, value: '' },
            addressTown: { confidential: false, value: '' },
            addressCountry: { confidential: false, value: '' },
            addressAdditional1: { confidential: false, value: '' },
            addressAdditional2: { confidential: false, value: '' },
            sector: { confidential: false, value: '' }
        };

        this.addressFields.forEach((field: string) => {
            if (element[field]) {
                contactAddress[field] = element[field];
            }
        });

        return contactAddress;
    }

    /**
     * Extracts and returns a mapping of address field keys to their corresponding values from the provided contact address object.
     *
     * @param {AddressContactInterface} contactAddress - The contact address object containing address field details.
     * @return {{ [key: string]: string }} A key-value mapping of address fields to their values.
     */
    getAddressValues(contactAddress: AddressContactInterface): { [key: string]: string } {
        const addressOnlyValues: { [key: string]: string } = {};

        Object.entries(contactAddress).forEach(([key, field]) => {
            addressOnlyValues[key] = field.value;
        });

        return addressOnlyValues
    }

    getConfidentialValue(): string {
        return this.hiddenConfidentialValue;
    }

    formatConfidentialFields(contact: any) {
        Object.keys(contact).forEach((element: string) => {
            if (this.confidentialFields.indexOf(element) > -1) {
                if (contact[element]) {
                    const value: string | null | undefined = contact[element].value;
                    contact[element] = {
                        confidential: contact[element].confidential,
                        value: contact[element].confidential && (!this.headerService.user.privileges.includes('view_confidential_contact_information') || !this.headerService.user.privileges.includes('admin_contacts')) ? this.getConfidentialValue() : value
                    }
                }
            }
        });

        return contact;
    }

    getContactValueByType(contactField, type: string): string {
        if (type === 'contact') {
            return contactField.confidential && (!this.headerService.user.privileges.includes('view_confidential_contact_information') || !this.headerService.user.privileges.includes('admin_contacts')) ? this.translate.instant('lang.confidentialData') : contactField.value;
        }
        return contactField;
    }

    checkAddressValidity(contact: { [key: string]: any }): boolean {
        const getValue = (field: any): any => contact.type === 'contact' && field?.value !== undefined ? field.value : field;

        const addressFields: any[] = [
            getValue(contact.addressNumber),
            getValue(contact.addressStreet),
            getValue(contact.addressAdditional2),
            getValue(contact.addressPostcode),
            getValue(contact.addressTown),
            getValue(contact.addressCountry),
        ];

        return addressFields.some(field => !this.functions.empty(field));
    }

    isConfidentialAddress(contact): boolean {
        if (contact.type !== 'contact') return false;

        const array: { confidential: boolean, value: string }[] = [];
        Object.keys(contact).forEach(element => {
            if (this.addressFields.indexOf(element) > -1 && contact[element]) {
                array.push(contact[element]);
            }
        });

        return array.every((field: {
            confidential: boolean,
            value: string | null | undefined
        }) => field.confidential && (field.value === undefined || field.value === this.getConfidentialValue()));
    }

    isConfidentialFieldWithoutValue(contact, field: string): boolean {
        return contact.type === 'contact' &&
            contact[field]?.confidential &&
            (!contact[field]?.value || contact[field]?.value === this.getConfidentialValue());
    }

    getRecipientsByCorrespondantTarget(array: {
        id?: number;
        type: string;
        email?: { confidential: boolean; value?: string } | string;
        labelToDisplay?: string;
    }[]): string[] {
        const arrayToReturn: string[] = [];

        array.forEach(element => {
            if (['email', 'user'].includes(element.type)) {
                if (typeof element.email === 'string') {
                    arrayToReturn.push(element.email);
                }
            } else if (element.type === 'contact') {
                if (this.isConfidentialFieldWithoutValue(element, 'email')) {
                    arrayToReturn.push(element.labelToDisplay);
                } else if (typeof element.email === 'object' && element.email?.value) {
                    arrayToReturn.push(element.email.value);
                }
            }
        });

        return arrayToReturn;
    }

    formatEmail(element: {
        type: string;
        email: string | { value: string, confidential: boolean };
        labelToDisplay: string;
    }): string {
        if (['email', 'user', 'entity'].includes(element.type)) {
            return typeof element.email === 'string' ? element.email : element.labelToDisplay;
        } else if (element.type === 'contact' && typeof element.email === 'object') {
            if (element.email.confidential) {
                return this.functions.empty(element.email.value)
                    ? null
                    : element.email.value;
            } else {
                return element.email.value;
            }
        }
        return '';
    }
}
