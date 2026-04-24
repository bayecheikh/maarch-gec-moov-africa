import { ComponentFixture, fakeAsync, TestBed, tick } from '@angular/core/testing';
import { ConsentPageComponent } from "@appRoot/administration/goodflag/consent-page/consent-page.component";
import { TranslateLoader, TranslateModule, TranslateService } from "@ngx-translate/core";
import { Observable, of } from "rxjs";
import * as langFrJson from "@langs/lang-fr.json";
import { FunctionsService } from "@service/functions.service";
import { ConsentPageInterface } from "@models/goodflag.model";

class FakeLoader implements TranslateLoader {
    getTranslation(): Observable<any> {
        return of({ lang: langFrJson });
    }
}

describe('ConsentPageComponent', () => {
    let component: ConsentPageComponent;
    let fixture: ComponentFixture<ConsentPageComponent>;
    let translateService: TranslateService;
    let mockFunctionsService: jasmine.SpyObj<FunctionsService>;

    const mockConsentPage: ConsentPageInterface = {
        id: 'cp_XJDHJH',
        name: 'Consent page',
        created: '01/01/2024 01:00',
        updated: '2024-01-02T00:00:00Z',
        stepType: 'signature',
        signingMode: 'server',
        authenticateUser: true,
        allowOrganization: false,
        strictCertificateControl: true,
        keystoreTypes: ['PKCS12', 'JKS', 'PKCS11']
    };

    beforeEach(async () => {
        mockFunctionsService = jasmine.createSpyObj('FunctionsService', ['booleanToYesNo']);
        mockFunctionsService.booleanToYesNo.and.callFake((value: boolean) => {
            return value ? 'Oui' : 'Non';
        });

        await TestBed.configureTestingModule({
            declarations: [ConsentPageComponent],
            imports: [
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: FakeLoader },
                }),
            ],
            providers: [
                { provide: FunctionsService, useValue: mockFunctionsService },
                TranslateService,
            ]
        }).compileComponents();

        translateService = TestBed.inject(TranslateService);
        translateService.use('fr');

        fixture = TestBed.createComponent(ConsentPageComponent);
        component = fixture.componentInstance;

        component.consentPage = mockConsentPage;

        fixture.detectChanges();
    });

    describe('Create component', () => {
        it('should create', () => {
            expect(component).toBeTruthy();
        });
    });

    describe('should display consent page information when page loaded', () => {
        it('check if information are correctly displayed', fakeAsync(() => {
            expect(component.consentPage).toEqual(mockConsentPage);

            fixture.detectChanges();
            tick();

            const nativeElement = fixture.nativeElement;
            const container = nativeElement.querySelector('.items-container');
            const itemSection = nativeElement.querySelectorAll('.item-section');
            const items = container.querySelectorAll('.item');

            expect(container).toBeDefined();
            expect(itemSection.length).toBe(2);
            expect(items.length).toBe(9);

            const labelSection = container.querySelector('#label').querySelectorAll('span');
            const creationDateSection = container.querySelector('#creationDate').querySelectorAll('span');
            const authenticateUserSection = container.querySelector('#authenticateUser').querySelectorAll('span');
            const keystoreTypesSection = container.querySelector('#keystoreTypes').querySelectorAll('span');

            expect(labelSection).toBeDefined();
            expect(creationDateSection).toBeDefined();
            expect(authenticateUserSection).toBeDefined();
            expect(keystoreTypesSection).toBeDefined();

            expect(labelSection[0].title.trim()).toBe(translateService.instant('lang.label'));
            expect(creationDateSection[0].title.trim()).toBe(translateService.instant('lang.creationDate'));
            expect(authenticateUserSection[0].title.trim()).toBe(translateService.instant('lang.authenticateUser'));
            expect(keystoreTypesSection[0].title.trim()).toBe(translateService.instant('lang.keystoreTypes'));

            expect(labelSection[1].textContent.trim()).toBe('Consent page');
            expect(creationDateSection[1].textContent.trim()).toBe('01/01/2024 01:00');
            expect(authenticateUserSection[1].textContent.trim()).toBe('Oui');
            expect(keystoreTypesSection[1].textContent.trim()).toBe('PKCS12,JKS,PKCS11');
        }));

        it('should not display keystore types if not defined', fakeAsync(() => {
            mockConsentPage.keystoreTypes = undefined;
            component.consentPage = mockConsentPage;

            fixture.detectChanges();
            tick();

            const nativeElement = fixture.nativeElement;
            const keystoreTypesSection = nativeElement.querySelector('#keystoreTypes');

            expect(keystoreTypesSection).toBeNull();

        }))
    });
})