import { fakeAsync, flush, TestBed, tick } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { SignatureBookService } from "@appRoot/signatureBook/signature-book.service";
import { NotificationService } from "@service/notification/notification.service";
import { SharedModule } from "@appRoot/app-common.module";
import { TranslateLoader, TranslateModule, TranslateService } from "@ngx-translate/core";
import { Observable, of } from "rxjs";
import * as langFrJson from "@langs/lang-fr.json";
import { FiltersListService } from "@service/filtersList.service";
import { FoldersService } from "@appRoot/folder/folders.service";
import { DatePipe } from "@angular/common";
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';

class FakeLoader implements TranslateLoader {
    getTranslation(): Observable<any> {
        return of({ lang: langFrJson });
    }
}

const resMock = {
    "resourcesToSign": [
        {
            "resId": 221,
            "resIdMaster": 100,
            "title": "Attachment resId 221",
            "chrono": "MAARCH/2024D/32",
            "creator": {
                "id": 21,
                "label": "Bernard BLIER"
            },
            "signedResId": 2,
            "type": "response_project",
            "typeLabel": "Projet de réponse",
            "isConverted": true,
            "canModify": false,
            "canDelete": false,
            "hasDigitalSignature": false,
            "externalDocumentId": 167,
            "originalFormat": "pdf",
            "version": 2,
            "creationDate": "2024-06-28T17:27:16+02:00",
            "modificationDate": "2024-06-28T17:27:16+02:00",
            "versions": [
                {
                    resId: 222,
                    relation: 1
                }
            ]
        },
        {
            "resId": 100,
            "resIdMaster": null,
            "title": "Main document resId 244",
            "chrono": "MAARCH/2024A/11",
            "creator": {
                "id": 21,
                "label": "Bernard BLIER"
            },
            "signedResId": null,
            "type": "main_document",
            "typeLabel": "Document Principal",
            "isConverted": true,
            "canModify": true,
            "canDelete": false,
            "hasDigitalSignature": false,
            "externalDocumentId": null,
            "originalFormat": "pdf",
            "version": 1,
            "creationDate": "2024-06-28T17:13:23+02:00",
            "modificationDate": "2024-06-28T17:34:00+02:00",
            "versions": []
        }
    ],
    "resourcesAttached": [],
    "canSignResources": true,
    "canUpdateResources": false,
    "hasActiveWorkflow": false,
    "isCurrentWorkflowUser": false,
    "currentWorkflowRole": "sign"
};

describe('SignatureBook Service', () => {
    let signatureBookService: SignatureBookService, httpCtl: HttpTestingController;
    let translateService: TranslateService;

    beforeEach(() => {
        TestBed.configureTestingModule({
            imports: [
                BrowserAnimationsModule,
                SharedModule,
                TranslateModule,
                HttpClientTestingModule,
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: FakeLoader },
                }),
            ],
            providers: [
                FiltersListService,
                FoldersService,
                DatePipe,
                SignatureBookService,
                NotificationService,
                TranslateService
            ]
        });
        signatureBookService = TestBed.inject(SignatureBookService);
        httpCtl = TestBed.inject(HttpTestingController);
        // Set lang
        translateService = TestBed.inject(TranslateService);
        translateService.use('fr');
    });

    it('select a mail store resources to sign in service', fakeAsync(() => {
        signatureBookService.resetSelection();
        signatureBookService.toggleSelection(true, 1, 1, 1, 100);

        const req = httpCtl.expectOne('../rest/signatureBook/users/1/groups/1/baskets/1/resources/100');
        req.flush(resMock);

        tick(600);

        expect(signatureBookService.selectedResources.length).toEqual(2);
        expect(signatureBookService.selectedMailsCount).toEqual(1);

        flush();
    }));

    it('deselect a mail remove resources to sign stored in service', fakeAsync(() => {
        signatureBookService.resetSelection();
        signatureBookService.toggleSelection(true, 1, 1, 1, 100);

        const req = httpCtl.expectOne('../rest/signatureBook/users/1/groups/1/baskets/1/resources/100');
        req.flush(resMock);

        tick();

        signatureBookService.toggleSelection(false, 1, 1, 1, 100);

        tick();

        expect(signatureBookService.selectedResources.length).toEqual(0);
        expect(signatureBookService.selectedMailsCount).toEqual(0);

        flush();
    }));

    it('should check version consistency correctly', () => {
        // Spy on the displayVersionWarning method before calling the function
        spyOn(signatureBookService, 'displayVersionWarning');

        // Call the function with test data
        const result = signatureBookService.checkVersionsConsistency(
            '24.5.1', {
                fortify: '24.5.0',
                pdftron: '2301.0.0',
                mpApi: '24.4.0'
            });

        // Check that the function returns the expected result
        expect(result.isConsistent).toBeFalse();
        expect(result.maarchVersion).toBe('24.5.1');
        expect(Object.keys(result.inconsistentPlugins).length).toBe(2);

        // Check that pdftron is identified as inconsistent
        expect(result.inconsistentPlugins.pdftron).toBeDefined();
        expect(result.inconsistentPlugins.pdftron.version).toBe('2301.0.0');

        // Check that mpApi is identified as inconsistent
        expect(result.inconsistentPlugins.mpApi).toBeDefined();
        expect(result.inconsistentPlugins.mpApi.version).toBe('24.4.0');

        // Verify that displayVersionWarning was called with the result
        expect(signatureBookService.displayVersionWarning).toHaveBeenCalledWith(result);
    });

    it('should report consistent versions correctly', () => {
        // Spy on the displayVersionWarning method
        spyOn(signatureBookService, 'displayVersionWarning');

        // Call the function with matching versions
        const result = signatureBookService.checkVersionsConsistency('24.5.1', {
            fortify: '24.5.0',
            pdftron: '24.5.0',
            mpApi: '24.5.0'
        });

        // Check that the result indicates consistency
        expect(result.isConsistent).toBeTrue();
        expect(result.maarchVersion).toBe('24.5.1');
        expect(Object.keys(result.inconsistentPlugins).length).toBe(0);

        // Verify that displayVersionWarning was called with the result
        expect(signatureBookService.displayVersionWarning).toHaveBeenCalledWith(result);
    });

    it('should handle invalid Maarch Courrier version', () => {
        expect(() => {
            signatureBookService.checkVersionsConsistency('', {});
        }).toThrowError('Maarch Courrier version required');

        expect(() => {
            signatureBookService.checkVersionsConsistency('24', {});
        }).toThrowError('Maarch Courrier version invalid');
    });

    it('should handle undefined plugin versions', () => {
        spyOn(signatureBookService, 'displayVersionWarning');

        const result = signatureBookService.checkVersionsConsistency('24.5.1', {
            fortify: signatureBookService.translate.instant('lang.undefined'),
            pdftron: signatureBookService.translate.instant('lang.undefined'),
            mpApi: '24.5.1'
        });

        expect(result.isConsistent).toBeTrue();
        expect(Object.keys(result.inconsistentPlugins).length).toBe(0);
        expect(signatureBookService.displayVersionWarning).toHaveBeenCalledWith(result);
    });


    describe('displayVersionWarning', () => {
        beforeEach(() => {
            sessionStorage.clear();
        });

        it('should clear warnings when versions are consistent', () => {
            // Call the function with a consistent version result
            signatureBookService.displayVersionWarning({
                isConsistent: true,
                maarchVersion: '24.5.1',
                inconsistentPlugins: {}
            });

            // Check that the warning message is cleared
            expect(signatureBookService.pluginsVersionsWarning).toBe('');
            expect(sessionStorage.getItem('ignorePluginsWarning')).toBeNull();
        });

        it('should format warnings for inconsistent versions', () => {
            // Call the function with an inconsistent version result
            const mcVersion: string = '24.5.1';
            signatureBookService.displayVersionWarning({
                isConsistent: false,
                maarchVersion: mcVersion,
                inconsistentPlugins: {
                    'fortify': {
                        version: '24.4.0',
                        reason: `${signatureBookService.translate.instant('lang.inconsistentPluginMsg')} ${mcVersion}`
                    },
                    'pdftron': {
                        version: '2301.0.0',
                        reason: `${signatureBookService.translate.instant('lang.inconsistentPluginMsg')} ${mcVersion}`
                    },
                    'mpApi': {
                        version: '24.3.0',
                        reason: `${signatureBookService.translate.instant('lang.inconsistentPluginMsg')} ${mcVersion}`
                    }
                }
            });

            // Check that the warning message is correctly formatted
            const expectedMessage =
                `<b>${signatureBookService.translate.instant('lang.inconsistentPluginMsg')} ${mcVersion} :</b><br><br>- Fortify :  24.4.0<br>- PDFTRON (Apryse) :  2301.0.0<br>- MP API :  24.3.0<br><br>${signatureBookService.translate.instant('lang.inconsistentPluginsWarning')}`;

            expect(signatureBookService.pluginsVersionsWarning).toBe(expectedMessage);
        });
    });
});
