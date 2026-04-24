import { ComponentFixture, discardPeriodicTasks, fakeAsync, flush, TestBed, tick } from '@angular/core/testing';
import { TranslateService, TranslateModule, TranslateLoader } from '@ngx-translate/core';
import { NotificationService } from '@service/notification/notification.service';
import {
    MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA,
    MatLegacyDialogModule,
    MatLegacyDialogRef as MatDialogRef
} from '@angular/material/legacy-dialog';
import { HttpClientTestingModule } from '@angular/common/http/testing';
import { FunctionsService } from '@service/functions.service';
import { SessionStorageService } from '@service/session-storage.service';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { NoopAnimationsModule } from '@angular/platform-browser/animations';
import { MatLegacyCheckboxModule as MatCheckboxModule } from '@angular/material/legacy-checkbox';
import { MatLegacySelectModule as MatSelectModule } from '@angular/material/legacy-select';
import { MatLegacyFormFieldModule as MatFormFieldModule } from '@angular/material/legacy-form-field';
import { MatLegacyInputModule as MatInputModule } from '@angular/material/legacy-input';
import { MatLegacyProgressSpinnerModule as MatProgressSpinnerModule } from '@angular/material/legacy-progress-spinner';
import { MatSlideToggleModule } from '@angular/material/slide-toggle';
import { MatSidenavModule } from '@angular/material/sidenav';
import { Observable, of, throwError } from 'rxjs';
import { Component, Input } from '@angular/core';
import { By } from '@angular/platform-browser';
import { SendShippingActionComponent } from '@appRoot/actions/send-shipping-action/send-shipping-action.component';
import { NoteEditorComponent } from '@appRoot/notes/note-editor.component';
import * as langFrJson from '@langs/lang-fr.json';
import { SecureUrlPipe } from "@plugins/secureUrl.pipe";
import { AdministrationService } from "@appRoot/administration/administration.service";
import { FoldersService } from "@appRoot/folder/folders.service";
import { PrivilegeService } from "@service/privileges.service";
class FakeLoader implements TranslateLoader {
    getTranslation(): Observable<{ lang: any }> {
        return of({ lang: langFrJson });
    }
}

// Mock components
@Component({
    selector: 'app-note-editor',
    template: '<div>Mock Note Editor</div>'
})

@Component({
    selector: 'app-maarch-message',
    template: '<div>{{ content }}</div>'
})

class MockMaarchMessageComponent {
    @Input() mode: string;
    @Input() content: string;
    @Input() role: string;
}

describe('SendShippingActionComponent', () => {
    let component: SendShippingActionComponent;
    let fixture: ComponentFixture<SendShippingActionComponent>;
    let notifyService: NotificationService;
    let sessionStorageService: SessionStorageService;
    let dialogRefMock: { close: jasmine.Spy<jasmine.Func> };
    let translateService: TranslateService;

    /**
     * Mock data for dialog configuration used in send-shipping-action component tests.
     *
     * @property {Object} action - The action details.
     * @property {number} action.id - The ID of the action.
     * @property {string} action.label - The label of the action.
     * @property {number[]} resIds - An array of resource IDs.
     * @property {Object} resource - The resource details.
     * @property {string} resource.chrono - The chronological identifier of the resource.
     * @property {Object} resource.integrations - Integration details of the resource.
     * @property {boolean} resource.integrations.inShipping - Indicates if the resource is in shipping.
     * @property {number} userId - The ID of the user.
     * @property {number} groupId - The ID of the group.
     * @property {number} basketId - The ID of the basket.
     * @property {string} processActionRoute - The route for processing the action.
     * @property {Object} additionalInfo - Additional information for the dialog.
     * @property {boolean} additionalInfo.showToggle - Indicates if the toggle should be shown.
     * @property {boolean} additionalInfo.canGoToNextRes - Indicates if it can go to the next resource.
     * @property {boolean} additionalInfo.inLocalStorage - Indicates if the data is in local storage.
     */
    const mockDialogData = {
        action: { id: 1, label: 'Envoyer un pli postal Maileva' },
        resIds: [529],
        resource: { chrono: 'MAARCH/2023A/1', integrations: { inShipping: false } },
        userId: 19,
        groupId: 5,
        basketId: 53,
        processActionRoute: '../rest/resourcesList/users/19/groups/5/baskets/53/actions/529',
        additionalInfo: {
            showToggle: true,
            canGoToNextRes: true,
            inLocalStorage: false
        }
    };

    /**
     * Mock response for shipping action.
     *
     * @property {Object[]} shippingTemplates - Array of shipping templates.
     * @property {number} shippingTemplates[].id - Unique identifier for the shipping template.
     * @property {string} shippingTemplates[].label - Label for the shipping template.
     * @property {string} shippingTemplates[].description - Description of the shipping template.
     * @property {Object} shippingTemplates[].options - Options for the shipping template.
     * @property {string[]} shippingTemplates[].options.shapingOptions - Array of shaping options.
     * @property {string} shippingTemplates[].options.sendMode - Mode of sending.
     * @property {number} shippingTemplates[].fee - Fee for the shipping template.
     * @property {Object} shippingTemplates[].account - Account information for the shipping template.
     * @property {string} shippingTemplates[].account.id - Account ID.
     * @property {string} shippingTemplates[].account.password - Account password.
     * @property {resId: number, chrono: string, reason: string[]} canNotSend - Array of entities that cannot be sent.
     * @property {string[]} entities - Array of entities.
     * @property {[contactId: number]: AttachListProperties[]} resources - Array of resources.
     * @property {boolean} invalidEntityAddress - Flag indicating if the entity address is invalid.
     */
    const mockShippingResponse = {
        shippingTemplates: [
            {
                id: 1,
                label: "Modèle d'exemple d'envoi postal",
                description: "Modèle d'exemple d'envoi postal",
                options: {
                    shapingOptions: ['envelopeWindowsType', 'addressPage'],
                    sendMode: 'fast'
                },
                fee: {
                    totalShippingFee: 10
                },
                account: {
                    id: 'account1',
                    password: 'password1'
                }
            }
        ],
        canNotSend: [],
        entities: ['PJS'],
        resources: {
            '90': {
                '1234': [
                    {
                        res_id: 1234,
                        res_id_master: null,
                        chrono: 'MAARCH/2023A/1',
                        title: 'Test Document',
                        type: 'mail',
                        docserver_id: 'docserver_1',
                        integrations: '{ "inShipping": true }'
                    }
                ]
            }
        },
        invalidEntityAddress: false
    };

    beforeEach(async () => {
        dialogRefMock = {
            close: jasmine.createSpy('close')
        };

        const notifyServiceMock = {
            error: jasmine.createSpy('error'),
            success: jasmine.createSpy('success'),
            handleSoftErrors: jasmine.createSpy('handleSoftErrors')
        };

        const functionsServiceMock = {
            empty: (obj: any) => {
                if (obj === null || obj === undefined) {
                    return true;
                }
                if (typeof obj === 'object') {
                    return Object.keys(obj).length === 0;
                }
                return false;
            },

            safeParseJson: (json: string | undefined) => {
                try {
                    return json ? JSON.parse(json) : null;
                } catch {
                    return null;
                }
            }
        };

        const sessionStorageServiceMock = {
            checkSessionStorage: jasmine.createSpy('checkSessionStorage')
        };

        await TestBed.configureTestingModule({
            imports: [
                HttpClientTestingModule,
                FormsModule,
                ReactiveFormsModule,
                NoopAnimationsModule,
                MatCheckboxModule,
                MatSelectModule,
                MatFormFieldModule,
                MatInputModule,
                MatProgressSpinnerModule,
                MatSlideToggleModule,
                MatSidenavModule,
                MatLegacyDialogModule,
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: FakeLoader },
                }),
            ],
            declarations: [
                SendShippingActionComponent,
                MockMaarchMessageComponent,
                SecureUrlPipe
            ],
            providers: [
                { provide: NotificationService, useValue: notifyServiceMock },
                { provide: FunctionsService, useValue: functionsServiceMock },
                { provide: SessionStorageService, useValue: sessionStorageServiceMock },
                { provide: MatDialogRef, useValue: dialogRefMock },
                { provide: MAT_DIALOG_DATA, useValue: mockDialogData },
                TranslateService,
                AdministrationService,
                FoldersService,
                PrivilegeService
            ]
        }).compileComponents();

        // Set lang
        translateService = TestBed.inject(TranslateService);
        translateService.use('fr');

        fixture = TestBed.createComponent(SendShippingActionComponent);
        component = fixture.componentInstance;

        spyOn(component, 'checkShipping').and.callThrough();
        notifyService = TestBed.inject(NotificationService);
        sessionStorageService = TestBed.inject(SessionStorageService);
    });

    // Unit Tests (TU)
    describe('Unit Tests', () => {
        it('should create the component', () => {
            expect(component).toBeTruthy();
        });

        it('should initialize component properties on ngOnInit', async () => {
            spyOn(component.http, 'post').and.returnValue(of(mockShippingResponse));

            await component.ngOnInit();

            expect(component.loading).toBeFalse();
            expect(component.showToggle).toBeTrue();
            expect(component.canGoToNextRes).toBeTrue();
            expect(component.inLocalStorage).toBeFalse();
            expect(component.checkShipping).toHaveBeenCalled();
        });

        it('should check shipping and handle successful response', async () => {
            spyOn(component.http, 'post').and.returnValue(of(mockShippingResponse));

            const result = await component.checkShipping();

            expect(result).toBeTrue();
            expect(component.shippings).toEqual(mockShippingResponse.shippingTemplates);
            expect(component.mailsNotSend).toEqual(mockShippingResponse.canNotSend);
            expect(component.entitiesList).toEqual(mockShippingResponse.entities);
            expect(component.attachList).toEqual(mockShippingResponse.resources);
            expect(component.invalidEntityAddress).toBeFalse();
            expect(component.loading).toBeFalse();
        });

        it('should handle error in shipping check', async () => {
            spyOn(component.http, 'post').and.returnValue(throwError(() => new Error('Test error')));

            const result = await component.checkShipping();

            expect(result).toBeFalse();
            expect(notifyService.handleSoftErrors).toHaveBeenCalled();
            expect(dialogRefMock.close).toHaveBeenCalled();
        });

        it('should execute action and handle successful response', () => {
            component.noteEditor = {
                title: '',
                content: '',
                resIds: [],
                addMode: false,
                getNote: () => ({ content: 'Test note', entities: ['ALL'] })
            } as NoteEditorComponent;
            component.currentShipping = { ...component.currentShipping, id: 1 } as any;
            component.attachList = {
                529: {
                    1: [{
                        res_id: 529,
                        res_id_master: null,
                        type: 'mail',
                        chrono: 'MAARCH/2025A/25',
                        title: 'Courrier de test',
                        docserver_id: 'FASTHD_MAN',
                        integrations: '{ "inShipping": true }'
                    }]
                }
            }

            spyOn(component.http, 'put').and.returnValue(of({ success: true }));

            component.executeAction();

            expect(component.http.put).toHaveBeenCalledWith(
                mockDialogData.processActionRoute,
                {
                    resources: [529],
                    data: { shippingTemplateId: 1 },
                    note: { content: 'Test note', entities: ['ALL'] }
                }
            );

            expect(dialogRefMock.close).toHaveBeenCalledWith([529]);
            expect(component.loading).toBeFalse();
        });

        it('should toggle integration and refresh shipping', fakeAsync(() => {
            component.data = {
                ...mockDialogData,
                ...component.data,
                resource: { ...component.data.resource, integrations: { inShipping: false } }
            };

            spyOn(component.http, 'put').and.returnValue(of({ success: true }));
            spyOn(component.http, 'post').and.returnValue(of(mockShippingResponse));

            component.toggleIntegration('inShipping');

            // Verify first HTTP call (PUT)
            expect(component.http.put).toHaveBeenCalledWith(
                '../rest/resourcesList/integrations',
                {
                    resources: mockDialogData.resIds,
                    integrations: { 'inShipping': true }
                }
            );

            // Handle checkShipping HTTP request
            tick();

            expect(component.data.resource.integrations.inShipping).toBeTrue();
            expect(component.currentShipping).not.toBeNull();
            expect(component.checkShipping).toHaveBeenCalled();

        }));

        it('should validate form with economic shipping', () => {
            component.currentShipping = {
                options: { sendMode: 'economic' }
            } as any;
            component.attachList = {
                529: {
                    1: [{
                        res_id: 529,
                        res_id_master: null,
                        type: 'mail',
                        chrono: 'MAARCH/2025A/25',
                        title: 'Courrier de test',
                        docserver_id: 'FASTHD_MAN',
                        integrations: '{ "inShipping": true }'
                    }]
                }
            }
            component.mailsNotSend = [];

            expect(component.isValid()).toBeTrue();
        });

        it('should validate form with digital registered mail', () => {
            component.currentShipping = {
                options: { sendMode: 'digital_registered_mail' }
            } as any;
            component.attachList = {
                529: {
                    1: [{
                        res_id: 529,
                        res_id_master: null,
                        type: 'mail',
                        chrono: 'MAARCH/2025A/25',
                        title: 'Courrier de test',
                        docserver_id: 'FASTHD_MAN',
                        integrations: '{ "inShipping": true }'
                    }]
                }
            }
            component.mailsNotSend = [];
            component.invalidEntityAddress = false;

            expect(component.isValid()).toBeTrue();
        });

        it('should invalidate form with digital registered mail and invalid entity address', () => {
            component.currentShipping = {
                options: { sendMode: 'digital_registered_mail' }
            } as any;
            component.attachList = {
                529: {
                    1: [{
                        res_id: 529,
                        res_id_master: null,
                        type: 'mail',
                        chrono: 'MAARCH/2025A/25',
                        title: 'Courrier de test',
                        docserver_id: 'FASTHD_MAN',
                        integrations: '{ "inShipping": true }'
                    }]
                }
            }
            component.mailsNotSend = [];
            component.invalidEntityAddress = true;

            expect(component.isValid()).toBeFalse();
        });

        it('should invalidate form when all mails cannot be sent', () => {
            component.currentShipping = {
                options: { sendMode: 'economic' }
            } as any;
            component.attachList = {
                529: {
                    529: [{
                        res_id: 529,
                        res_id_master: null,
                        type: 'mail',
                        chrono: 'MAARCH/2025A/25',
                        title: 'Courrier de test',
                        docserver_id: 'FASTHD_MAN',
                        integrations: '{ "inShipping": true }'
                    }]
                }
            }
            component.mailsNotSend = [{ resId: 123, chrono: 'MAARCH/2023A/1', reason: 'documentNotFoundOnDocserver' }];

            expect(component.isValid()).toBeFalse();
        });

        it('should invalidate form when no shipping is selected', () => {
            component.currentShipping = null;
            component.attachList = {
                529: {
                    1: [{
                        res_id: 529,
                        res_id_master: null,
                        type: 'mail',
                        chrono: 'MAARCH/2025A/25',
                        title: 'Courrier de test',
                        docserver_id: 'FASTHD_MAN',
                        integrations: '{ "inShipping": true }'
                    }]
                }
            }
            component.mailsNotSend = [];

            expect(component.isValid()).toBeFalse();
        });

        it('should invalidate form when no attachments are available', () => {
            component.currentShipping = {
                options: { sendMode: 'economic' }
            } as any;
            component.attachList = [];
            component.mailsNotSend = [];

            expect(component.isValid()).toBeFalse();
        });

        it('should call checkSessionStorage and executeAction on onSubmit', () => {
            spyOn(component, 'executeAction');
            component.data.resIds = [1234];

            component.onSubmit();

            expect(component.loading).toBeTrue();
            expect(sessionStorageService.checkSessionStorage).toHaveBeenCalledWith(
                component.inLocalStorage,
                component.canGoToNextRes,
                component.data
            );
            expect(component.executeAction).toHaveBeenCalled();
        });
    });

    // Template Function Tests (TF)
    describe('Template Function Tests', () => {
        beforeEach(() => {
            spyOn(component.http, 'post').and.returnValue(of(mockShippingResponse));
            fixture.detectChanges();
        });

        it('should display loading spinner when loading is true', () => {
            component.loading = true;
            fixture.detectChanges();

            const spinner = fixture.debugElement.query(By.css('mat-spinner'));
            expect(spinner).toBeTruthy();
        });

        it('should display fatal error message when present', fakeAsync(() => {
            component.fatalError = { reason: 'disabledMailevaConfig' };

            component.currentShipping = null;

            fixture.detectChanges();
            tick();

            const errorMessage = fixture.debugElement.query(By.css('.alert-message-danger'));
            expect(errorMessage).toBeTruthy();

            const submitButton = fixture.nativeElement.querySelector('#validate');
            expect(submitButton.disabled).toBeTruthy();

            const cancelButton = fixture.nativeElement.querySelector('#cancel');
            expect(cancelButton.disabled).toBeFalsy();
            flush();

            discardPeriodicTasks();
        }));

        it('should display no shipping template message when shippings array is empty', fakeAsync(() => {
            component.shippings = [];
            component.fatalError = null;

            fixture.detectChanges();
            tick();

            const noTemplateMessage = fixture.debugElement.query(By.css('.alert-message-danger'));

            expect(noTemplateMessage).toBeTruthy();
            expect(noTemplateMessage.nativeElement.innerText.trim()).toContain("Aucun modèle d'envoi Maileva commun disponible pour ces entités");
        }));

        it('should display selected resource information', fakeAsync(() => {
            component.shippings = mockShippingResponse.shippingTemplates;

            fixture.detectChanges();
            tick();
            flush();

            const resourceInfo = fixture.debugElement.query(By.css('.highlight'));
            expect(resourceInfo.nativeElement.textContent).toContain('MAARCH/2023A/1');

            discardPeriodicTasks();
        }));

        it('should display multiple resources count when multiple resIds', fakeAsync(() => {
            component.data.resIds = [100, 101, 102];
            component.shippings = mockShippingResponse.shippingTemplates;

            fixture.detectChanges();
            tick();

            const resourceInfo = fixture.debugElement.query(By.css('.highlight'));
            expect(resourceInfo.nativeElement.textContent.trim()).toEqual('3 élément(s)');

            discardPeriodicTasks()
        }));

        it('should display shipping dropdown when shippings are available and display price content when shipping template is selected', fakeAsync(() => {
            component.shippings = mockShippingResponse.shippingTemplates;

            fixture.detectChanges();
            tick();

            const select = fixture.debugElement.query(By.css('mat-select'));
            expect(select).toBeTruthy();

            fixture.nativeElement.querySelector('mat-select').click();

            fixture.detectChanges();
            tick();

            const matOption = document.querySelector('mat-option');
            expect(matOption).toBeDefined();

            const matOptionText = matOption.querySelector('.mat-option-text');
            expect(matOptionText.innerHTML.trim()).toEqual("Modèle d'exemple d'envoi postal");

            (matOption as HTMLElement).click();

            fixture.detectChanges();
            tick();

            const priceContent = fixture.nativeElement.querySelector('.priceContent');
            expect(priceContent).toBeDefined();

            const priceInfo = priceContent.querySelector('.priceInfo');
            expect(priceInfo).toBeDefined();

            const shippingMode = priceInfo.querySelector('.col-md-6').querySelector('li');
            expect(shippingMode.innerText.trim()).toEqual("Lettre grand compte (J+2)");

            discardPeriodicTasks();
        }));

        it('should display not eligible mails when present and validate button should be disabled', fakeAsync(() => {
            component.mailsNotSend = [{ resId: 123, chrono: 'MAARCH/2023A/1', reason: 'noAttachmentContact' }];
            component.shippings = mockShippingResponse.shippingTemplates;

            fixture.detectChanges();
            tick();

            const maarchMessage = fixture.nativeElement.querySelector('.mailsNotSend').querySelector('app-maarch-message');

            expect(maarchMessage.querySelector('p').innerText.trim()).toEqual("1 Document(s) non éligible(s) :")
            expect(maarchMessage.querySelector('ul').querySelector('li').innerText.trim()).toEqual("MAARCH/2023A/1 : Aucun contact (externe) attaché pour cette pièce jointe");

            const submitButton = fixture.nativeElement.querySelector('#validate');
            expect(submitButton.disabled).toBeTruthy();

            discardPeriodicTasks();
        }));

        it('should display note editor', fakeAsync(() => {
            component.shippings = mockShippingResponse.shippingTemplates;

            fixture.detectChanges();
            tick();

            const noteEditor = fixture.debugElement.query(By.css('app-note-editor'));
            expect(noteEditor).toBeTruthy();

            discardPeriodicTasks();
        }));

        it('should display toggle option when showToggle is true', fakeAsync(() => {
            component.showToggle = true;
            component.shippings = mockShippingResponse.shippingTemplates;

            fixture.detectChanges();
            tick();

            const toggle = fixture.debugElement.query(By.css('mat-slide-toggle'));
            expect(toggle).toBeTruthy();

            discardPeriodicTasks();
        }));

        it('should call onSubmit when validate button is clicked', fakeAsync(() => {
            spyOn(component, 'onSubmit');
            spyOn(component, 'isValid').and.returnValue(true);

            component.currentShipping = mockShippingResponse.shippingTemplates[0];
            component.attachList = {
                200: {
                    1: [{
                        res_id: 100,
                        type: 'attachment',
                        res_id_master: 200,
                        chrono: 'MAARCH/2025A/24',
                        title: 'Pièce jointe de test',
                        docserver_id: 'FASTHD_MAN',
                        integrations: '{ "inShipping": true }'
                    }]
                }
            };
            component.mailsNotSend = [];
            component.loading = false;
            component.shippings = mockShippingResponse.shippingTemplates;

            fixture.detectChanges();
            tick();

            const submitButton = fixture.nativeElement.querySelector('#validate');
            expect(submitButton.disabled).toBeFalsy();

            submitButton.click();

            fixture.detectChanges();
            tick();

            expect(component.onSubmit).toHaveBeenCalled();

            fixture.detectChanges();
            tick();

            discardPeriodicTasks();
        }));
    });
});
