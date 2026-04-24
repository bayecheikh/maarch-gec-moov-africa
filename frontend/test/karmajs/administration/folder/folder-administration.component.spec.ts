import { ComponentFixture, fakeAsync, flush, TestBed, tick } from '@angular/core/testing';
import { FolderAdministrationComponent } from '@appRoot/administration/folder/folder-administration.component';
import { TranslateLoader, TranslateModule, TranslateService } from '@ngx-translate/core';
import { Observable, of } from 'rxjs';
import * as langFrJson from '@langs/lang-fr.json';
import { DatePipe } from '@angular/common';
import { AdministrationService } from '@appRoot/administration/administration.service';
import { FoldersService } from '@appRoot/folder/folders.service';
import { ActionPagesService } from '@service/actionPages.service';
import { AppService } from '@service/app.service';
import { HeaderService } from '@service/header.service';
import { PrivilegeService } from '@service/privileges.service';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { NotificationService } from '@service/notification/notification.service';
import { MatLegacySnackBarModule } from '@angular/material/legacy-snack-bar';
import { MatLegacyDialogModule } from '@angular/material/legacy-dialog';
import { LatinisePipe } from 'ngx-pipes';
import { SharedModule } from '@appRoot/app-common.module';
import { ActivatedRoute } from '@angular/router';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { By } from '@angular/platform-browser';
import { OverlayContainer } from '@angular/cdk/overlay';

class FakeLoader implements TranslateLoader {
    getTranslation(): Observable<any> {
        return of({ lang: langFrJson });
    }
}

describe('FolderAdministrationComponent', () => {
    let component: FolderAdministrationComponent;
    let fixture: ComponentFixture<FolderAdministrationComponent>;
    let translateService: TranslateService;
    let httpTestingController: HttpTestingController;
    let overlayContainerElement: HTMLElement;

    beforeEach(() => {
        TestBed.configureTestingModule({
            declarations: [FolderAdministrationComponent],
            imports: [
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: FakeLoader },
                }),
                SharedModule,
                HttpClientTestingModule,
                BrowserAnimationsModule,
                MatLegacySnackBarModule,
                MatLegacyDialogModule
            ],
            providers: [
                HeaderService,
                ActionPagesService,
                FoldersService,
                PrivilegeService,
                DatePipe,
                AdministrationService,
                ActionPagesService,
                NotificationService,
                AppService,
                LatinisePipe,
                { provide: ActivatedRoute, useValue: { params: of({}) } }
            ],
        });

        httpTestingController = TestBed.inject(HttpTestingController);
        translateService = TestBed.inject(TranslateService);
        translateService.use('fr');
        fixture = TestBed.createComponent(FolderAdministrationComponent);
        component = fixture.componentInstance;
        fixture.detectChanges();
        /**
         * A class provided by Angular Material that manages interface elements that are rendered outside the main DOM tree
         */
        const overlayContainer = TestBed.inject(OverlayContainer);
        overlayContainerElement = overlayContainer.getContainerElement();
    });

    describe('Create component',() => {
        it('should create', () => {
            expect(component).toBeTruthy();
        });
    });

    describe('Display component label, init custom fields and get template and check buttons status', () => {
        it ('should display component label onInit, have disabled buttons, defined toolbar elements and sample default mode', fakeAsync(() => {
            const nativeElement = fixture.nativeElement;
            const label = nativeElement.querySelector('.bg-head-title-label');
            expect(label.innerText.trim()).toEqual('Administration des dossiers')

            const customFieldsMock: any = {
                customFields: [
                    {
                        id: 1,
                        label: 'Custom field 1'
                    },
                    {
                        id: 2,
                        label: 'Custom field 2'
                    }
                ]
            }

            const customFieldsReq = httpTestingController.expectOne('../rest/customFields');
            expect(customFieldsReq.request.method).toBe('GET');
            customFieldsReq.flush(customFieldsMock);
            spyOn(component, 'initCustomFields').and.returnValue(Promise.resolve(customFieldsMock));

            fixture.detectChanges();
            tick(300);
            flush();

            const folderConfigMock: any = {
                configuration: {
                    listDisplay: {
                        subInfos: [
                            {
                                "icon": "fa-traffic-light",
                                "label": "Priorité",
                                "value": "getPriority",
                                "sample": "Urgent",
                                "cssClasses": [
                                    "align_leftData",
                                    "boldFontData"
                                ]
                            },
                            {
                                "icon": "fa-exchange-alt",
                                "label": "Catégorie",
                                "value": "getCategory",
                                "sample": "Courrier arrivée",
                                "cssClasses": [
                                    "align_leftData",
                                    "boldFontData"
                                ]
                            },
                            {
                                "icon": "fa-suitcase",
                                "label": "Type de courrier",
                                "value": "getDoctype",
                                "sample": "Réclamation",
                                "cssClasses": [
                                    "align_leftData",
                                    "boldFontData"
                                ]
                            },
                            {
                                "icon": "fa-user",
                                "label": "Destinataire",
                                "value": "getRecipients",
                                "sample": "Patricia PETIT",
                                "cssClasses": [
                                    "align_leftData",
                                    "boldFontData"
                                ]
                            },
                            {
                                "icon": "fa-book",
                                "label": "Expéditeur",
                                "value": "getSenders",
                                "sample": "Alain DUBOIS (MAARCH)",
                                "cssClasses": [
                                    "align_leftData",
                                    "boldFontData"
                                ]
                            }
                        ],
                        templateColumns: 5
                    }
                }
            }

            const foldersConfigReq = httpTestingController.expectOne('../rest/folders/configuration');
            expect(foldersConfigReq.request.method).toBe('GET');
            foldersConfigReq.flush(folderConfigMock);
            spyOn(component, 'getTemplate').and.returnValue(Promise.resolve(folderConfigMock));

            fixture.detectChanges();
            tick(300);
            flush();

            fixture.detectChanges();

            // Without modifications, buttons should be disabled
            const validationBtn = nativeElement.querySelector('#validate');
            const cancelBtn = nativeElement.querySelector('#cancel');
            expect(validationBtn.disabled).toBeTrue();
            expect(cancelBtn.disabled).toBeTrue();

            // The ddefault mode should be 'label'
            const toggleDataBtn = nativeElement.querySelector('button[name=toggleData]');
            expect(toggleDataBtn.querySelector('.mat-icon').classList).toContain('fa-eye');

            // Template used should be equal to 5
            const templateUsed = nativeElement.querySelector('button[name=templateUsed]');
            expect(templateUsed.innerText.trim()).toBe('5')

            // Toolbar list should be defined and contains 5 elements
            const toolbarList = nativeElement.querySelector('.secondaryInformations');
            const toolbarListElements = toolbarList.querySelectorAll('div[name=element-list]');
            expect(toolbarListElements.length).toEqual(5);

            // The last element value of toolbarList should be equal to 'getSenders'
            expect(toolbarListElements[toolbarListElements.length - 1].id).toEqual('getSenders');
        }));
    });

    describe('Toggle display mode and update display list', () => {
        it ('should display configured list when toggling display mode, have data after modification and expect success notification message', fakeAsync(() => {
            const customFieldsMock: any = {
                customFields: [
                    {
                        id: 1,
                        label: 'Custom field 1'
                    },
                    {
                        id: 2,
                        label: 'Custom field 2'
                    }
                ]
            }

            httpTestingController = TestBed.inject(HttpTestingController);

            const customFieldsReq = httpTestingController.expectOne('../rest/customFields');
            expect(customFieldsReq.request.method).toBe('GET');
            customFieldsReq.flush(customFieldsMock);
            spyOn(component, 'initCustomFields').and.returnValue(Promise.resolve(customFieldsMock));

            fixture.detectChanges();
            tick(300);
            flush();

            const folderConfigMock: any = {
                configuration: {
                    listDisplay: {
                        subInfos: [
                            {
                                "icon": "fa-traffic-light",
                                "label": "Priorité",
                                "value": "getPriority",
                                "sample": "Urgent",
                                "cssClasses": [
                                    "align_leftData",
                                    "boldFontData"
                                ]
                            },
                            {
                                "icon": "fa-exchange-alt",
                                "label": "Catégorie",
                                "value": "getCategory",
                                "sample": "Courrier arrivée",
                                "cssClasses": [
                                    "align_leftData",
                                    "boldFontData"
                                ]
                            },
                            {
                                "icon": "fa-suitcase",
                                "label": "Type de courrier",
                                "value": "getDoctype",
                                "sample": "Réclamation",
                                "cssClasses": [
                                    "align_leftData",
                                    "boldFontData"
                                ]
                            },
                            {
                                "icon": "fa-user",
                                "label": "Destinataire",
                                "value": "getRecipients",
                                "sample": "Patricia PETIT",
                                "cssClasses": [
                                    "align_leftData",
                                    "boldFontData"
                                ]
                            },
                            {
                                "icon": "fa-book",
                                "label": "Expéditeur",
                                "value": "getSenders",
                                "sample": "Alain DUBOIS (MAARCH)",
                                "cssClasses": [
                                    "align_leftData",
                                    "boldFontData"
                                ]
                            }
                        ],
                        templateColumns: 5
                    }
                }
            }

            const foldersConfigReq = httpTestingController.expectOne('../rest/folders/configuration');
            expect(foldersConfigReq.request.method).toBe('GET');
            foldersConfigReq.flush(folderConfigMock);
            spyOn(component, 'getTemplate').and.returnValue(Promise.resolve(folderConfigMock));

            fixture.detectChanges();
            tick(300);
            flush();

            fixture.detectChanges();

            const nativeElement = fixture.nativeElement;

            // should not display sample data before toggle mode
            const objectDiv = nativeElement.querySelector('.longData');
            expect(objectDiv.innerText.trim()).toEqual('Objet');

            // Toggle display mode
            let toggleDataBtn = nativeElement.querySelector('button[name=toggleData]');
            toggleDataBtn.click();

            fixture.detectChanges();

            // should display sample data for configured elements and basic fields
            const toolbarList = nativeElement.querySelector('.secondaryInformations');
            const toolbarListElements = toolbarList.querySelectorAll('div[name=element-list]');
            expect(toolbarListElements[0].innerText.trim()).toEqual('Urgent');
            expect(toolbarListElements[4].innerText.trim()).toEqual('Alain DUBOIS (MAARCH)');
            expect(objectDiv.innerText.trim()).toContain('Plainte concernant des nuisances sonores nocturnes');

            // switch to edit mode
            toggleDataBtn = nativeElement.querySelector('button[name=toggleData]');
            toggleDataBtn.click();
            fixture.detectChanges();

            // add new element to the list
            const inputElement = fixture.debugElement.query(By.css('input')).nativeElement;
            inputElement.focus();
            inputElement.value = 'Circuit de visa';
            inputElement.dispatchEvent(new Event('input'));

            fixture.detectChanges();

            tick(500);
            fixture.detectChanges();

            const autocompleteOptions = overlayContainerElement.querySelectorAll('.dataList');
            (autocompleteOptions[0] as HTMLElement).click();

            fixture.detectChanges();
            tick();

            // validation button should not be disabled
            const validationBtn = nativeElement.querySelector('#validate');
            expect(validationBtn.disabled).toBeFalse();

            validationBtn.click();

            fixture.detectChanges();
            tick(300);

            // save template
            const saveTemplateReq = httpTestingController.expectOne('../rest/configurations/admin_folders');
            expect(saveTemplateReq.request.method).toBe('PUT');
            saveTemplateReq.flush({});

            fixture.detectChanges();
            tick(300);
            flush();

            // expect success notification message
            const hasSuccessGritter = document.querySelectorAll('.mat-snack-bar-container.success-snackbar').length;
            expect(hasSuccessGritter).toEqual(1);
            const notifContent = document.querySelector('.notif-container-content-msg #message-content').innerHTML;
            expect(notifContent).toEqual(component.translate.instant('lang.modificationsProcessed'));
            flush();
        }));

        it('Remove all data and save template', fakeAsync(() => {
            const customFieldsMock: any = {
                customFields: [
                    {
                        id: 1,
                        label: 'Custom field 1'
                    },
                    {
                        id: 2,
                        label: 'Custom field 2'
                    }
                ]
            }

            httpTestingController = TestBed.inject(HttpTestingController);

            const customFieldsReq = httpTestingController.expectOne('../rest/customFields');
            expect(customFieldsReq.request.method).toBe('GET');
            customFieldsReq.flush(customFieldsMock);
            spyOn(component, 'initCustomFields').and.returnValue(Promise.resolve(customFieldsMock));

            fixture.detectChanges();
            tick(300);
            flush();

            const folderConfigMock: any = {
                configuration: {
                    listDisplay: {
                        subInfos: [
                            {
                                "icon": "fa-traffic-light",
                                "label": "Priorité",
                                "value": "getPriority",
                                "sample": "Urgent",
                                "cssClasses": [
                                    "align_leftData",
                                    "boldFontData"
                                ]
                            },
                            {
                                "icon": "fa-exchange-alt",
                                "label": "Catégorie",
                                "value": "getCategory",
                                "sample": "Courrier arrivée",
                                "cssClasses": [
                                    "align_leftData",
                                    "boldFontData"
                                ]
                            },
                            {
                                "icon": "fa-suitcase",
                                "label": "Type de courrier",
                                "value": "getDoctype",
                                "sample": "Réclamation",
                                "cssClasses": [
                                    "align_leftData",
                                    "boldFontData"
                                ]
                            },
                            {
                                "icon": "fa-user",
                                "label": "Destinataire",
                                "value": "getRecipients",
                                "sample": "Patricia PETIT",
                                "cssClasses": [
                                    "align_leftData",
                                    "boldFontData"
                                ]
                            },
                            {
                                "icon": "fa-book",
                                "label": "Expéditeur",
                                "value": "getSenders",
                                "sample": "Alain DUBOIS (MAARCH)",
                                "cssClasses": [
                                    "align_leftData",
                                    "boldFontData"
                                ]
                            }
                        ],
                        templateColumns: 5
                    }
                }
            }

            const foldersConfigReq = httpTestingController.expectOne('../rest/folders/configuration');
            expect(foldersConfigReq.request.method).toBe('GET');
            foldersConfigReq.flush(folderConfigMock);
            spyOn(component, 'getTemplate').and.returnValue(Promise.resolve(folderConfigMock));

            fixture.detectChanges();
            tick(300);
            flush();

            fixture.detectChanges();

            const nativeElement = fixture.nativeElement;

            // should display sample data for configured elements and basic fields
            let toolbarList = nativeElement.querySelector('.secondaryInformations');
            const toolbarListElements = toolbarList.querySelectorAll('div[name=element-list]');
            expect(toolbarListElements.length).toBeGreaterThan(0);

            // remove all data
            const removeAllDataBtn = nativeElement.querySelector('.mat-warn');
            expect(removeAllDataBtn.disabled).toBeFalse();

            removeAllDataBtn.click();

            fixture.detectChanges();
            tick();

            toolbarList = nativeElement.querySelector('.secondaryInformations');
            fixture.detectChanges();
            expect(toolbarList).toBe(null);

            fixture.detectChanges();
            tick();

            // validation button should not be disabled
            const validationBtn = nativeElement.querySelector('#validate');
            expect(validationBtn.disabled).toBeFalse();

            validationBtn.click();

            fixture.detectChanges();
            tick(300);

            // save template
            const saveTemplateReq = httpTestingController.expectOne('../rest/configurations/admin_folders');
            expect(saveTemplateReq.request.method).toBe('PUT');
            saveTemplateReq.flush({});

            fixture.detectChanges();
            tick(300);
            flush();

            // expect success notification message
            const hasSuccessGritter = document.querySelectorAll('.mat-snack-bar-container.success-snackbar').length;
            expect(hasSuccessGritter).toEqual(1);
            const notifContent = document.querySelector('.notif-container-content-msg #message-content').innerHTML;
            expect(notifContent).toEqual(component.translate.instant('lang.modificationsProcessed'));
            flush();
        }));
    });
});
