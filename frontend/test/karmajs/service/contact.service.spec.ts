import { TranslateService } from '@ngx-translate/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { ContactService } from "@service/contact.service";
import { FunctionsService } from "@service/functions.service";
import createSpyObj = jasmine.createSpyObj;
import { HeaderService } from "@service/header.service";

describe('ContactService', () => {
    let service: ContactService;
    let translate: jasmine.SpyObj<TranslateService>;
    let http: jasmine.SpyObj<HttpClient>;
    let functions: jasmine.SpyObj<FunctionsService>;
    let router: jasmine.SpyObj<Router>;
    let headerService: jasmine.SpyObj<HeaderService>;

    beforeEach(() => {
        translate = createSpyObj('TranslateService', ['instant']);
        http = createSpyObj('HttpClient', ['get', 'post']);
        functions = createSpyObj('FunctionsService', ['empty']);
        router = createSpyObj('Router', ['navigate'], { url: '/administration/contacts' });
        headerService = createSpyObj('HeaderService', [], {
            user: {
                privileges: ['view_confidential_contact_information', 'admin_contacts']
            }
        });

        translate.instant.and.callFake((key) => key);
        service = new ContactService(translate, http, functions, router, headerService);
    });

    describe('TU: formatCivilityObject', () => {
        it('should return civility if not empty', () => {
            const civility = { label: 'M.', abbreviation: 'M' };
            spyOn(service, 'empty').and.returnValue(false);
            expect(service.formatCivilityObject(civility)).toEqual(civility);
        });

        it('should return empty civility object if input is empty', () => {
            spyOn(service, 'empty').and.returnValue(true);
            expect(service.formatCivilityObject(null)).toEqual({ label: '', abbreviation: '' });
        });
    });

    describe('TU: formatContact', () => {
        it('should return company if firstname and lastname are empty', () => {
            functions.empty.and.returnValue(true);
            const contact = { firstname: '', lastname: '', company: 'CompanyX' };
            expect(service.formatContact(contact)).toBe('CompanyX');
        });

        it('should return fullname with company in brackets', () => {
            functions.empty.and.callFake((v) => !v); // tous les champs sont non vides
            const contact = { firstname: 'Jane', lastname: 'Doe', company: 'CompanyX' };
            expect(service.formatContact(contact)).toBe('Jane Doe (CompanyX)');
        });
    });

    describe('TU: getFillingColor', () => {
        it('should return correct color for each threshold', () => {
            expect(service.getFillingColor('first')).toBe('#E81C2B');
            expect(service.getFillingColor('second')).toBe('#F4891E');
            expect(service.getFillingColor('third')).toBe('#0AA34F');
            expect(service.getFillingColor('invalid' as any)).toBe('');
        });
    });

    describe('TU: isConfidentialFieldWithoutValue', () => {
        it('should return true for contact with confidential email and no value', () => {
            const contact = {
                type: 'contact',
                email: { confidential: true, value: service.getConfidentialValue() }
            };
            expect(service.isConfidentialFieldWithoutValue(contact, 'email')).toBeTrue();
        });

        it('should return false if not a contact', () => {
            const contact = {
                type: 'user',
                email: { confidential: true, value: 'email@test.com' }
            };
            expect(service.isConfidentialFieldWithoutValue(contact, 'email')).toBeFalse();
        });
    });

    describe('TU: formatEmail', () => {
        it('should return string email if type is email or user', () => {
            const el = { type: 'email', email: 'test@test.com', labelToDisplay: 'label' };
            expect(service.formatEmail(el)).toBe('test@test.com');
        });

        it('should return null if email confidential and value empty', () => {
            functions.empty.and.returnValue(true);
            const el = {
                type: 'contact',
                email: { confidential: true, value: '' },
                labelToDisplay: 'label'
            };
            expect(service.formatEmail(el)).toBe(null);
        });
    });

    describe('TF: getRecipientsByCorrespondantTarget', () => {
        it('should return array of emails and labels', () => {
            const array = [
                { type: 'email', email: 'email1@test.com' },
                { type: 'user', email: 'user@test.com' },
                {
                    type: 'contact',
                    email: { confidential: true },
                    labelToDisplay: 'Confidential Contact'
                },
                {
                    type: 'contact',
                    email: { confidential: false, value: 'contact@test.com' }
                }
            ];
            spyOn(service, 'isConfidentialFieldWithoutValue').and.callFake((c) => c.email.confidential && !c.email.value);
            const result = service.getRecipientsByCorrespondantTarget(array);
            expect(result).toEqual(['email1@test.com', 'user@test.com', 'Confidential Contact', 'contact@test.com']);
        });
    });

    describe('TF: getAdminMenu', () => {
        it('should return admin menu with correct route detection', () => {
            const menu = service.getAdminMenu();
            expect(menu.length).toBeGreaterThan(0);
            const currentItem = menu.find((item) => item.current);
            expect(currentItem?.route).toBe('/administration/contacts');
        });
    });

    describe('TF: goTo', () => {
        it('should navigate to given route', () => {
            service.goTo('/home');
            expect(router.navigate).toHaveBeenCalledWith(['/home']);
        });
    });

    describe('TU: checkAddressValidity', () => {
        it('should return true if any address field is not empty', () => {
            functions.empty.and.callFake((v) => v === null || v === '');
            const contact = {
                type: 'contact',
                addressNumber: { value: '12' },
                addressStreet: { value: '' },
                addressPostcode: { value: null },
                addressTown: { value: null },
                addressCountry: { value: null },
                addressAdditional2: { value: null }
            };
            expect(service.checkAddressValidity(contact)).toBeTrue();
        });
    });
});
