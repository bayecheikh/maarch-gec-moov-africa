import { AfterViewInit, Component, EventEmitter, Inject, OnInit, Output, ViewChild } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';
import { NotificationService } from '@service/notification/notification.service';
import {
    MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA,
    MatLegacyDialog as MatDialog,
    MatLegacyDialogRef as MatDialogRef
} from '@angular/material/legacy-dialog';
import { HttpClient } from '@angular/common/http';
import { NoteEditorComponent } from '@appRoot/notes/note-editor.component';
import { catchError, filter, finalize, tap } from 'rxjs/operators';
import { of } from 'rxjs';
import { FunctionsService } from '@service/functions.service';
import { ResourceInterface, VisaWorkflowComponent } from '@appRoot/visa/visa-workflow.component';
import { ActionsService } from '@appRoot/actions/actions.service';
import { Router } from '@angular/router';
import { SessionStorageService } from '@service/session-storage.service';
import { UserWorkflowInterface } from '@models/user-workflow.model';
import { MatSidenav } from '@angular/material/sidenav';
import { AttachmentsListComponent } from '@appRoot/attachments/attachments-list.component';
import { AppService } from '@service/app.service';
import { SignaturePositionComponent } from '@appRoot/visa/signature-position/signature-position.component';
import { SignaturePositionInterface } from '@models/signature-position.model';
import { SignatureBookService } from '@appRoot/signatureBook/signature-book.service';
import { DatasActionSendInterface } from "@models/actions.model";

@Component({
    templateUrl: 'send-signature-book-action.component.html',
    styleUrls: ['send-signature-book-action.component.scss'],
})
export class SendSignatureBookActionComponent implements AfterViewInit, OnInit {

    @ViewChild('noteEditor', { static: false }) noteEditor: NoteEditorComponent;
    @ViewChild('appVisaWorkflow', { static: false }) appVisaWorkflow: VisaWorkflowComponent;
    @ViewChild('attachmentsList', { static: false }) attachmentsList: AttachmentsListComponent;
    @ViewChild('snav2', { static: false }) public snav2: MatSidenav;

    @Output() sidenavStateChanged = new EventEmitter<boolean>();

    actionService: ActionsService; // To resolve circular dependencies

    loading: boolean = true;

    resourcesMailing: any[] = [];
    resourcesError: any[] = [];
    resourcesToSign: any[] = [];

    noResourceToProcess: boolean = null;

    integrationsInfo: any = {
        inSignatureBook: {
            icon: 'fas fa-file-signature'
        }
    };

    visaNumberCorrect: boolean = true;
    signNumberCorrect: boolean = true;
    atLeastOneSign: boolean = true;
    lastOneIsSign: boolean = true;
    lastOneMustBeSignatory: boolean = false;
    lockVisaCircuit: boolean;
    visaWorkflowClone: UserWorkflowInterface[];

    canGoToNextRes: boolean = false;
    showToggle: boolean = false;
    inLocalStorage: boolean = false;
    mainDocumentSigned: boolean = false;

    signaturePositions: SignaturePositionInterface[] = [];

    isFromTemplate: boolean = true;

    constructor(
        @Inject(MAT_DIALOG_DATA) public data: DatasActionSendInterface,
        public translate: TranslateService,
        public http: HttpClient,
        public dialogRef: MatDialogRef<SendSignatureBookActionComponent>,
        public functions: FunctionsService,
        public route: Router,
        public appService: AppService,
        public dialog: MatDialog,
        public signatureBookService: SignatureBookService,
        private sessionStorage: SessionStorageService,
        private notify: NotificationService,
    ) {
    }

    async ngOnInit() {
        this.loading = true;
        if (!this.data.resource?.integrations?.inSignatureBook && this.data?.additionalInfo?.inSignatureBook && this.data?.resIds?.length === 1) {
            try {
                setTimeout(() => {
                    this.toggleIntegration('inSignatureBook');
                }, 0);
            } catch (err) {
                this.notify.handleSoftErrors(err);
            }
        }
        this.loading = false;
    }

    async ngAfterViewInit(): Promise<void> {
        if (this.data.resIds.length === 0) {
            // Indexing page
            this.checkSignatureBookInIndexingPage();
        }
        this.initVisaWorkflow();
        if (this.data?.resource?.integrations?.inSignatureBook && this.data.resIds.length === 1) {
            this.http.get(`../rest/resources/${this.data.resource.resId}/versionsInformations`).pipe(
                tap((data: any) => {
                    this.mainDocumentSigned = data.SIGN.length !== 0;
                    if (!this.mainDocumentSigned) {
                        this.toggleDocToSign(true, this.data.resource, true);
                    }
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        }
        this.showToggle = this.data.additionalInfo.showToggle;
        this.canGoToNextRes = this.data.additionalInfo.canGoToNextRes;
        this.inLocalStorage = this.data.additionalInfo.inLocalStorage;
        this.loading = false;
    }

    async onSubmit(): Promise<any> {
        this.loading = true;

        if (this.data.resIds.length === 0) {
            let res: boolean = await this.indexDocument();
            if (res) {
                res = await this.appVisaWorkflow.saveVisaWorkflow(this.data.resIds) as boolean;
            }
            if (res) {
                this.executeIndexingAction(this.data.resIds[0]);
            }
        } else {
            const realResSelected: number[] = this.data.resIds.filter((resId: any) => this.resourcesError.map(resErr => resErr.res_id).indexOf(resId) === -1);

            const res = await this.appVisaWorkflow.saveVisaWorkflow(realResSelected);

            if (res) {
                this.sessionStorage.checkSessionStorage(this.inLocalStorage, this.canGoToNextRes, this.data);
                this.executeAction(realResSelected);
            }
        }
        this.loading = false;
    }

    indexDocument(): Promise<boolean> {
        this.data.resource['integrations'] = {
            inSignatureBook: true
        };

        return new Promise((resolve) => {
            this.http.post('../rest/resources', this.data.resource).pipe(
                tap((data: any) => {
                    this.data.resIds = [data.resId];
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    resolve(false);
                    return of(false);
                })
            ).subscribe();
        });
    }

    executeAction(realResSelected: number[]): void {
        this.http.put(this.data.processActionRoute, { resources: realResSelected, note: this.noteEditor.getNote() }).pipe(
            tap((data: any) => {
                if (!data) {
                    this.dialogRef.close(realResSelected);
                }
                if (data && data.errors != null) {
                    this.notify.error(data.errors);
                }
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.actionService.stopRefreshResourceLock();
                const path: string = `resourcesList/users/${this.data.userId}/groups/${this.data.groupId}/baskets/${this.data.basketId}?limit=10&offset=0`;
                this.http.get(`../rest/${path}`).pipe(
                    tap((data: any) => {
                        if (!this.route.url.includes('signatureBook')) {
                            this.dialogRef.close(data.allResources[0]);
                        } else {
                            if (data.defaultAction?.component === 'signatureBookAction' && data.defaultAction?.data.goToNextDocument) {
                                if (data.count > 0) {
                                    this.dialogRef.close();
                                    this.route.navigate(['/signatureBook/users/' + this.data.userId + '/groups/' + this.data.groupId + '/baskets/' + this.data.basketId + '/resources/' + data.allResources[0]]);
                                } else {
                                    this.dialogRef.close();
                                    this.route.navigate([`/basketList/users/${this.data.userId}/groups/${this.data.groupId}/baskets/${this.data.basketId}`]);
                                    this.notify.handleSoftErrors(err);
                                }
                            } else {
                                this.dialogRef.close();
                                this.route.navigate([`/basketList/users/${this.data.userId}/groups/${this.data.groupId}/baskets/${this.data.basketId}`]);
                                this.notify.handleSoftErrors(err);
                            }
                        }
                    })
                ).subscribe();
                return of(false);
            })
        ).subscribe();
    }

    executeIndexingAction(resId: number): void {
        this.http.put(this.data.indexActionRoute, { resource: resId, note: this.noteEditor.getNote() }).pipe(
            tap((data: any) => {
                if (!data) {
                    this.dialogRef.close(this.data.resIds);
                }
                if (data && data.errors != null) {
                    this.notify.error(data.errors);
                }
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    async initVisaWorkflow(): Promise<any> {
        if (this.data.resIds.length === 0) {
            // Indexing page
            if (!this.functions.empty(this.data.resource.destination) && !this.noResourceToProcess) {
                this.noResourceToProcess = false;
                await this.appVisaWorkflow.loadListModel(this.data.resource.destination);
            }
        } else if (this.data.resIds.length > 1) {
            // List page
            await this.checkSignatureBook();
        } else {
            // Process page
            await this.checkSignatureBook();
            if (!this.noResourceToProcess) {
                await this.appVisaWorkflow.loadWorkflow(this.data.resIds[0]);
                await this.loadWorkflowEntity();
            }
        }
    }

    async loadWorkflowEntity(): Promise<any> {
        if (this.appVisaWorkflow !== undefined) {
            if (this.appVisaWorkflow.emptyWorkflow()) {
                await this.appVisaWorkflow.loadDefaultWorkflow(this.data.resIds[0]);
            }
        } else {
            // issue component undefined ??
            setTimeout(async () => {
                if (this.appVisaWorkflow?.emptyWorkflow()) {
                    await this.appVisaWorkflow.loadDefaultWorkflow(this.data.resIds[0]);
                }
            }, 100);
        }
    }

    checkSignatureBookInIndexingPage(): void {
        if (this.data.resource.encodedFile === null) {
            this.noResourceToProcess = true;
            this.resourcesError = [
                {
                    alt_identifier: this.translate.instant('lang.currentIndexingMail'),
                    reason: 'noDocumentToSend'
                }
            ];
        }
    }

    checkSignatureBook(): Promise<boolean> {
        this.resourcesError = [];
        return new Promise((resolve) => {
            this.http.post('../rest/resourcesList/users/' + this.data.userId +
                '/groups/' + this.data.groupId +
                '/baskets/' + this.data.basketId +
                '/actions/' + this.data.action.id +
                '/checkSignatureBook', { resources: this.data.resIds })
                .pipe(
                    tap((data: any) => {
                        if (!this.functions.empty(data.resourcesInformations.error)) {
                            this.resourcesError = data.resourcesInformations.error;
                        }
                        this.noResourceToProcess = this.data.resIds.length === this.resourcesError.length;
                        if (data.resourcesInformations.success) {
                            this.resourcesMailing = data.resourcesInformations.success.filter((element: any) => element.mailing);
                        }

                        this.lockVisaCircuit = data.lockVisaCircuit;

                        this.resourcesMailing.forEach((element: any) => {
                            if (this.resourcesToSign.find((resource: any) => resource.resId === element.resId) === undefined) {
                                this.toggleDocToSign(true, element, false);
                            }
                        });
                        resolve(true);
                    }),
                    catchError((err: any) => {
                        this.notify.handleSoftErrors(err);
                        this.dialogRef.close();
                        resolve(false);
                        return of(false);
                    })
                ).subscribe();
        });
    }

    toggleIntegration(integrationId: string): void {
        this.loading = true;
        this.http.put('../rest/resourcesList/integrations', {
            resources: this.data.resIds,
            integrations: { [integrationId]: !this.data.resource.integrations[integrationId] }
        }).pipe(
            tap(async () => {
                this.data.resource.integrations[integrationId] = !this.data.resource.integrations[integrationId];
                await this.checkSignatureBook();
                if (!this.mainDocumentSigned) {
                    this.toggleDocToSign(this.data.resource.integrations[integrationId], this.data.resource, true);
                }
                setTimeout(async () => {
                    if (this.appVisaWorkflow?.emptyWorkflow()) {
                        await this.appVisaWorkflow.loadWorkflow(this.data.resIds[0]).then(async () => {
                            await this.appVisaWorkflow.loadVisaSignParameters();
                        });
                    }
                    this.loadWorkflowEntity();
                }, 100);
                this.loading = false
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    async afterAttachmentToggle(data: { id: string, attachments: any[] }): Promise<void> {
        await this.checkSignatureBook();
        this.loadWorkflowEntity();
        this.attachmentsList.setTaget(this.attachmentsList.currentIntegrationTarget);
        if (data.id === 'setInSignatureBook' && this.data.resIds.length === 1) {
            this.resourcesToSign = [];
            this.loading = true;
            if (this.data.resource.integrations.inSignatureBook) {
                this.toggleDocToSign(true, this.data.resource, true);
            }
            const signableAttachments: any[] = data.attachments.filter((attachment: {
                signable: boolean,
                inSignatureBook: boolean,
                status: string
            }) => attachment.signable && attachment.inSignatureBook && attachment.status === 'A_TRA');
            signableAttachments.forEach((attachment: any) => {
                this.toggleDocToSign(true, attachment, false);
            });
            this.loading = false;
        }
    }

    isValidAction(): boolean {
        return !this.noResourceToProcess &&
            this.appVisaWorkflow !== undefined &&
            !this.appVisaWorkflow.emptyWorkflow() &&
            !this.appVisaWorkflow.workflowEnd() &&
            !this.appVisaWorkflow.workflowParametersNotValid();
    }

    onSidenavStateChanged(): void {
        /*
         * Toggle mat-sidenav &
         * Emit an event indicating the current state of the sidenav (true for open, false for closed)
         * Used in the actions.service sendSignatureBookAction() function
        */
        this.snav2?.toggle();
        this.sidenavStateChanged.emit(this.snav2?.opened);
    }

    getIntegratedAttachmentsLength(): number {
        return this.attachmentsList?.attachmentsClone.filter((attachment: any) => attachment.inSignatureBook).length;
    }

    toggleDocToSign(state: boolean, document: any, mainDocument: boolean = true): void {
        if (state) {
            if (this.resourcesToSign.find((resource: any) => resource.resId === document.resId) === undefined) {
                this.resourcesToSign.push(
                    {
                        resId: document.resId,
                        chrono: document.chrono,
                        title: document.subject ?? document.title,
                        mainDocument: mainDocument,
                        template: document.template
                    });
            }
        } else {
            const index = this.resourcesToSign.map((item: any) => `${item.resId}_${item.mainDocument}`).indexOf(`${document.resId}_${mainDocument}`);
            this.resourcesToSign.splice(index, 1);
        }

        setTimeout(() => {
            this.setSignaturePositions();
        }, 500);
    }

    openSignaturePosition(resource: ResourceInterface): void {
        const dialogRef = this.dialog.open(SignaturePositionComponent, {
            height: '99vh',
            panelClass: ['maarch-modal', 'maarch-modal-template'],
            disableClose: true,
            data: {
                resource: resource,
                workflow: this.appVisaWorkflow.getWorkflow().filter((user: UserWorkflowInterface) => this.functions.empty(user.process_date)),
                isInternalParaph: true
            }
        });
        dialogRef.afterClosed().pipe(
            filter((res: any) => !this.functions.empty(res)),
            tap((res: { signaturePositions: SignaturePositionInterface[] }) => {
                this.appVisaWorkflow.setPositionsWorkflow(resource, res);
                this.signaturePositions.filter((data: SignaturePositionInterface) => data.resId === resource.resId).forEach((item: SignaturePositionInterface) => {
                    item.isFromTemplate = false;
                });
                this.notify.success(this.translate.instant('lang.modificationsProcessed'));
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    hasPositions(resource: ResourceInterface): boolean {
        return this.appVisaWorkflow?.getDocumentsFromPositions().filter((document: {
            resId: number,
            mainDocument: boolean
        }) => document.resId === resource.resId && document.mainDocument === resource.mainDocument).length > 0;
    }

    setSignaturePositions(): void {
        this.signaturePositions = [];
        this.resourcesToSign.forEach((resource) => {
            // Check if the resource has a non-empty template with signature positions
            if (!this.functions.empty(resource.template)) {
                // Create a temporary array to store formatted signature positions
                const formatData: SignaturePositionInterface[] = [];
                if (!this.functions.empty(resource.template?.signaturePositions)) {
                    (resource.template.signaturePositions as SignaturePositionInterface[]).forEach((signature: SignaturePositionInterface) => {
                        formatData.push({
                            positionX: signature.positionX,
                            positionY: signature.positionY,
                            sequence: signature.sequence,
                            page: signature.page,
                            mainDocument: resource.mainDocument,
                            resId: resource.resId,
                            isFromTemplate: true
                        });
                    });
                }

                // Concatenate the formatted data to the signaturePositions array
                this.signaturePositions = this.signaturePositions.concat(formatData);
            }
        });
        if (this.signaturePositions.length > 0) {
            // Call a function to apply the retrieved signature positions to the workflow
            setTimeout(() => {
                this.appVisaWorkflow?.setSignaturePositionsRetrievedFromTemplate();
            }, 100);
        }
    }
}
