import { ComponentFixture, TestBed, fakeAsync, flush, tick } from '@angular/core/testing';
import { TranslateLoader, TranslateModule, TranslateService, TranslateStore } from '@ngx-translate/core';
import { Observable, of } from 'rxjs';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { BrowserModule } from '@angular/platform-browser';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { RouterTestingModule } from '@angular/router/testing';
import { SharedModule } from '@appRoot/app-common.module';
import { HttpClient } from '@angular/common/http';
import { DatePipe } from '@angular/common';
import { SignaturePositionComponent } from '@appRoot/visa/signature-position/signature-position.component';
import * as langFrJson from '@langs/lang-fr.json';
import { MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA , MatLegacyDialogRef as MatDialogRef } from "@angular/material/legacy-dialog";
import { FoldersService } from '@appRoot/folder/folders.service';
import { PrivilegeService } from '@service/privileges.service';
import { AdministrationService } from '@appRoot/administration/administration.service';
import { SignaturePositionInterface } from '@models/signature-position.model';

class FakeLoader implements TranslateLoader {
    getTranslation(): Observable<any> {
        return of({ lang: langFrJson });
    }
}

describe('SignaturePositionComponent', () => {
    let component: SignaturePositionComponent;
    let fixture: ComponentFixture<SignaturePositionComponent>;
    let httpTestingController: HttpTestingController;
    let translateService: TranslateService;

    beforeEach(async () => {

        await TestBed.configureTestingModule({
            imports: [
                SharedModule,
                RouterTestingModule,
                BrowserAnimationsModule,
                TranslateModule,
                HttpClientTestingModule,
                BrowserModule,
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: FakeLoader },
                }),
            ],
            providers: [
                {
                    provide: MatDialogRef,
                    useValue: {}
                },
                {
                    provide: MAT_DIALOG_DATA,
                    useValue: {
                        resource: {
                            chrono: 'MAARCH/2024A/228',
                            mainDocument: true,
                            resId: 100,
                            title: 'Courrier de test'
                        },
                        workflow: getWorkflow(),
                        isInternalParaph: true
                    }
                },
                TranslateService,
                DatePipe,
                TranslateStore,
                HttpClient,
                FoldersService,
                PrivilegeService,
                AdministrationService
            ],
            declarations: [SignaturePositionComponent],
        }).compileComponents();

        // Set lang
        translateService = TestBed.inject(TranslateService);
        translateService.use('fr');
    });

    beforeEach(fakeAsync(() => {
        httpTestingController = TestBed.inject(HttpTestingController);
        fixture = TestBed.createComponent(SignaturePositionComponent);
        component = fixture.componentInstance;
        fixture.detectChanges();
    }));

    describe('Create component', () => {
        it('should create', () => {
            expect(component).toBeTruthy();
        });
    });


    describe('onInit load document pages and display users workflow', () => {
        it('should get document pages and load users workflow with empty signatures positions', fakeAsync(() => {
            const res = httpTestingController.expectOne(`../rest/resources/${component.data.resource.resId}/thumbnail/${component.currentPage}`);
            expect(res.request.method).toBe('GET');
            res.flush(getDocumentInfo());
            tick(300);
            fixture.detectChanges();
            fixture.detectChanges();
            flush();
            spyOn(component, 'handleApiRequest').and.returnValue(Promise.resolve(true));

            fixture.detectChanges();
            tick(300);


            const nativeElement = fixture.nativeElement;
            const activeTab = nativeElement.querySelector('.workflowList').querySelector('.mat-tab-label-active');

            expect(activeTab.innerText).toContain('Barbara BAIN');
            expect(activeTab.innerText).toContain('- Signataire');

            const validateBtn = nativeElement.querySelector('button[name=validate]');
            const cancelBtn = nativeElement.querySelector('button[name=cancel]');

            expect(validateBtn.disabled).toBeFalse();
            expect(cancelBtn.disabled).toBeFalse();

            const signatureContainer = nativeElement.querySelector('.signatureContainer').querySelector('.signature');
            expect(signatureContainer).toBe(null);

            const addSignaturePositionBtn = nativeElement.querySelector('button[name=add-signature-position]');
            expect(addSignaturePositionBtn.disabled).toBeFalse();
        }));
    });

    describe('Get document pages, load users workflow, signatures positions and delete signature position for user', () => {
        it('should get document pages and load users workflow with signatures positions', fakeAsync(() => {
            const res = httpTestingController.expectOne(`../rest/resources/${component.data.resource.resId}/thumbnail/${component.currentPage}`);
            expect(res.request.method).toBe('GET');
            res.flush(getDocumentInfo());
            tick(300);
            fixture.detectChanges();
            fixture.detectChanges();
            flush();

            component.data.workflow = getWorkflow(false);
            component.getAllUnits();

            tick(300);
            fixture.detectChanges();

            const nativeElement = fixture.nativeElement;

            const signatureContainer = nativeElement.querySelector('.signatureContainer');
            const signature = signatureContainer.querySelector('.signature');
            const signatureUserName = signature.querySelector('.signUserName');

            expect(signatureContainer).toBeDefined();
            expect(signatureUserName.innerHTML).toEqual('Barbara BAIN');

            const addSignaturePositionBtn = nativeElement.querySelector('button[name=add-signature-position]');
            expect(addSignaturePositionBtn.disabled).toBeTrue();
        }));

        it('delete signature position for current user', fakeAsync(() => {
            const res = httpTestingController.expectOne(`../rest/resources/${component.data.resource.resId}/thumbnail/${component.currentPage}`);
            expect(res.request.method).toBe('GET');
            res.flush(getDocumentInfo());
            tick(300);
            fixture.detectChanges();
            fixture.detectChanges();
            flush();

            component.data.workflow = getWorkflow(false);
            component.getAllUnits();

            tick(300);
            fixture.detectChanges();

            const nativeElement = fixture.nativeElement;

            const deleteBtn = nativeElement.querySelector('button[name=delete-stamp]');

            deleteBtn.click();

            fixture.detectChanges();
            tick();

            // the button to add new signature position sould not be disabled
            const addSignaturePositionBtn = nativeElement.querySelector('button[name=add-signature-position]');
            expect(addSignaturePositionBtn.disabled).toBeFalse();

            // the signature div is not defined
            const signature = nativeElement.querySelector('.signatureContainer').querySelector('.signature');
            expect(signature).toEqual(null);
        }));
    });

});

function getWorkflow(emptySignPositions: boolean = true) {
    const signaturesPositions: SignaturePositionInterface[] = emptySignPositions ? [] : [
        {
            sequence: 0,
            page: 1,
            positionX: 8.1,
            positionY: 4.169611307420495,
            mainDocument: true,
            resId: 100
        }
    ];

    const workflow = [
        {
            id: 520,
            list_template_id: 397,
            item_id: 19,
            sequence: 0,
            labelToDisplay: "Barbara BAIN",
            currentRole: "sign",
            signaturePositions: signaturesPositions
        },
        {
            id: 519,
            list_template_id: 397,
            item_id: 10,
            sequence: 1,
            labelToDisplay: "Patricia PETIT",
            currentRole: "visa",
            signaturePositions: []
        }

    ];
    return workflow;
}

function getDocumentInfo(): {fileContent: string, pageCount: number} {
    const data: { fileContent: string, pageCount: number } = {
        pageCount: 1,
        fileContent: "iVBORw0KGgoAAAANSUhEUgAAADIwAAAyCAYAAACRxR5aAAAACXBIWXMAAAsTAAALEwEAmpwYAAABUUlEQVR4nO3SsU0DQRSG4Z+QmNgIItAlRYWDiIqNBoqiYVCoVGBpCguMABIKv0CooFIorFLADiJFZ+gYZgfgntbPxtknNlnbsfAAAAAAAAAAAAAAAAAADw3V6qvb/g1vLZ/C+OZ29/wu1mZ2/fgxr5t8jOb2f4Nt6t/Vr9tv4LV5bvVgfCebtbD4vPLtvk3fq31a/ZX+J9/qf1a/ZX+J9/qf1a/ZX+LvFrsrweNj03zpavwexj/NdttY6x1DfG+NfzTe3cN+P5fNv5mOdmp/D5/jfOtvxlOd2v9Kfw+V9mt6f3r/F5X1a/rfOvzA8mtdL9gHAAAAAAAAAAAAAAAAAADgE/sBHrgLfEuEKlYAAAAASUVORK5CYII=", // encoded empty image
    }

    return data;
}