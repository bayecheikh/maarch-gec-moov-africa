import { ComponentFixture, fakeAsync, TestBed, tick } from '@angular/core/testing';
import { TranslateLoader, TranslateModule, TranslateService } from "@ngx-translate/core";
import { Observable, of } from "rxjs";
import * as langFrJson from "@langs/lang-fr.json";
import { FunctionsService } from "@service/functions.service";
import { SignatureProfileInterface } from "@models/goodflag.model";
import {
    SignatureProfileComponent
} from "@appRoot/administration/goodflag/signature-profile/signature-profile.component";
import { NgPipesModule } from "ngx-pipes";

class FakeLoader implements TranslateLoader {
    getTranslation(): Observable<any> {
        return of({ lang: langFrJson });
    }
}

describe('SignatureProfileComponent', () => {
    let component: SignatureProfileComponent;
    let fixture: ComponentFixture<SignatureProfileComponent>;
    let translateService: TranslateService;
    let mockFunctionsService: jasmine.SpyObj<FunctionsService>;

    const mockSignatureProfile: SignatureProfileInterface = {
        id: 'sp_XF4To1',
        name: 'PDF Signature Profile',
        created: '2024-01-10T08:00:00Z',
        updated: '2024-01-18T12:30:00Z',
        documentType: 'PDF',
        signatureType: 'PADES',
        pdfSignatureImageText: 'Digitally signed by {signer}',
        forceScrollDocument: true
    };

    beforeEach(async () => {
        mockFunctionsService = jasmine.createSpyObj('FunctionsService', ['booleanToYesNo']);
        mockFunctionsService.booleanToYesNo.and.callFake((value: boolean) => {
            return value ? 'Oui' : 'Non';
        });

        await TestBed.configureTestingModule({
            declarations: [SignatureProfileComponent],
            imports: [
                NgPipesModule,
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

        fixture = TestBed.createComponent(SignatureProfileComponent);
        component = fixture.componentInstance;

        component.signatureProfile = mockSignatureProfile;

        fixture.detectChanges();
    });

    describe('Create component', () => {
        it('should create', () => {
            expect(component).toBeTruthy();
        });
    });

    describe('should display signature profile information when page loaded', () => {
        it('check if information are correctly displayed', fakeAsync(() => {
            expect(component.signatureProfile).toEqual(mockSignatureProfile);

            fixture.detectChanges();
            tick();

            const nativeElement = fixture.nativeElement;
            const container = nativeElement.querySelector('.items-container');
            const itemSection = nativeElement.querySelectorAll('.item-section');
            const items = container.querySelectorAll('.item');

            expect(container).toBeDefined();
            expect(itemSection.length).toBe(2);
            expect(items.length).toBe(7);

            const labelSection = container.querySelector('#label').querySelectorAll('span');
            const creationDateSection = container.querySelector('#creationDate').querySelectorAll('span');
            const forceScrollDocumentSection = container.querySelector('#forceScrollDocument').querySelectorAll('span');

            expect(labelSection).toBeDefined();
            expect(creationDateSection).toBeDefined();
            expect(forceScrollDocumentSection).toBeDefined();

            expect(labelSection[0].title.trim()).toBe(translateService.instant('lang.label'));
            expect(creationDateSection[0].title.trim()).toBe(translateService.instant('lang.creationDate'));
            expect(forceScrollDocumentSection[0].title.trim()).toBe(translateService.instant('lang.forceScrollDocument'));

            expect(labelSection[1].textContent.trim()).toBe('PDF Signature Profile');
            expect(creationDateSection[1].textContent.trim()).toBe('10/01/2024 08:00');
            expect(forceScrollDocumentSection[1].textContent.trim()).toBe(translateService.instant('lang.yes'));
        }));
    });
})