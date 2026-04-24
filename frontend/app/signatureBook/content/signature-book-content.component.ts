import { HttpClient } from '@angular/common/http';
import { Component, EventEmitter, Input, OnDestroy, OnInit, Output, ViewChild, ViewContainerRef } from '@angular/core';
import { ActionsService } from '@appRoot/actions/actions.service';
import { MessageActionInterface } from '@models/actions.model';
import { Attachment, AttachmentInterface, DocumentVersionsInterface } from '@models/attachment.model';
import { TranslateService } from '@ngx-translate/core';
import { FunctionsService } from '@service/functions.service';
import { HeaderService } from '@service/header.service';
import { NotificationService } from '@service/notification/notification.service';
import { PluginManagerService } from '@service/plugin-manager.service';
import { Subscription, catchError, finalize, of, tap } from 'rxjs';
import { SignatureBookService } from "@appRoot/signatureBook/signature-book.service";
import { EditorManagerModalComponent } from "@appRoot/editor/modal/editor-manager-modal.component";
import { MatLegacyDialog as MatDialog, MatLegacyDialogRef as MatDialogRef } from "@angular/material/legacy-dialog";
import { filter } from "rxjs/operators";
import { SelectedAttachment, StampInterface } from '@models/signature-book.model';
import { SignaturePositionInterface } from '@models/signature-position.model';
import { DocumentViewerModalComponent } from '@appRoot/viewer/modal/document-viewer-modal.component';
import { AuthService } from "@service/auth.service";
import { ConfirmComponent } from '@plugins/modal/confirm.component';

@Component({
    selector: 'app-maarch-sb-content',
    templateUrl: 'signature-book-content.component.html',
    styleUrls: ['signature-book-content.component.scss'],
})
export class MaarchSbContentComponent implements OnInit, OnDestroy {
    @ViewChild('myPlugin', { read: ViewContainerRef, static: true }) myPlugin: ViewContainerRef;

    @Input() position: 'left' | 'right' = 'right';
    @Input() userId: number = null;

    @Output() documentChangeEnd = new EventEmitter<any>();
    @Output() documentLoaded = new EventEmitter<boolean>();
    @Output() versionSelected = new EventEmitter<DocumentVersionsInterface>();
    @Output() fetchVersions = new EventEmitter<string>();

    editDocumentAction = new EventEmitter<boolean>();

    subscription: Subscription;

    subscriptionDocument: Subscription;

    documentData: AttachmentInterface;
    currentIndexDocument: number = 0;

    documentType: 'attachments' | 'resources';

    documentContent: Blob = null;

    loading: boolean = false;

    pluginInstance: any = false;

    dialogRef: MatDialogRef<DocumentViewerModalComponent>;

    constructor(
        public functionsService: FunctionsService,
        public dialog: MatDialog,
        public signatureBookService: SignatureBookService,
        public translate: TranslateService,
        private http: HttpClient,
        private actionsService: ActionsService,
        private notificationService: NotificationService,
        private pluginManagerService: PluginManagerService,
        private translateService: TranslateService,
        private headerService: HeaderService,
        private authService: AuthService,
    ) {
        this.subscription = this.actionsService
            .catchActionWithData()
            .pipe(
                tap(async (res: MessageActionInterface) => {
                    if (res.id === 'selectedStamp') {
                        if (this.pluginInstance) {
                            const signContent = await this.signatureBookService.getSignatureContent(res.data.contentUrl);
                            const stamps = this.signatureBookService.docsToSignClone.find((doc: AttachmentInterface) => doc.resId === this.signatureBookService.selectedDocToSign.attachment.resId);
                            const currentUserStamp: SignaturePositionInterface = stamps?.signaturePositions.find((stamp: SignaturePositionInterface) => stamp.sequence === this.signatureBookService.currentUserIndex);
                            if (!this.functionsService.empty(currentUserStamp)) {
                                this.pluginInstance.addStamp(signContent, currentUserStamp.positionX, currentUserStamp.positionY, currentUserStamp.page);
                            } else {
                                this.pluginInstance.addStamp(signContent);
                            }
                        }
                    } else if (res.id === 'attachmentSelected' && this.position === res.data.position) {
                        this.currentIndexDocument = res.data.resIndex ?? 0;
                        this.initDocument();
                    }
                }),
                catchError((err: any) => {
                    this.notificationService.handleSoftErrors(err);
                    return of(false);
                })
            )
            .subscribe();
    }

    ngOnInit(): void {
        this.initDocument();
    }

    async initDocument(): Promise<void> {
        this.loading = true;
        this.subscriptionDocument?.unsubscribe();

        if (this.position === 'right' && this.signatureBookService.selectedDocToSign.index !== null) {
            await this.initDocToSign();
        } else if (this.position === 'left' && this.signatureBookService.selectedAttachment.index !== null) {
            await this.initAnnexe();
        }

        this.documentType = this.documentData?.isAttachment ? 'attachments' : 'resources';
        this.loading = false;
    }

    initAnnexe(): Promise<true> {
        return new Promise((resolve) => {
            this.documentData = this.signatureBookService.selectedAttachment.attachment;
            this.pluginInstance = null;
            this.pluginManagerService.destroyPlugin(this.myPlugin);
            setTimeout(() => {
                resolve(true);
            }, 0)
        });

    }

    async initDocToSign(isDocEdited: boolean = false, resId: number = null): Promise<void> {
        this.documentData = this.signatureBookService.selectedDocToSign.attachment;
        const stamps: StampInterface[] = this.signatureBookService.docsToSign.find((attachment: Attachment) => attachment.resId === this.documentData.resId)?.stamps ?? [];
        const documentContentWithAnnotations: Blob = this.signatureBookService.docsToSign.find((attachment: Attachment) => attachment.resId === this.documentData.resId).fileContentWithAnnotations;
        if (!this.functionsService.empty(documentContentWithAnnotations) && !isDocEdited) {
            this.documentContent = documentContentWithAnnotations;
        } else {
            await this.loadContent(resId);
        }

        if (this.pluginInstance) {
            this.pluginInstance.loadDocument({
                fileName: this.documentData.title,
                content: this.documentContent,
            }, stamps.filter((stamp: StampInterface) => this.functionsService.empty(stamp.sequence))
            ,this.documentData.versions, this.documentData.resId, this.documentData.isAnnotated);
        } else {
            this.initPlugin();
        }
    }

    ngOnDestroy(): void {
        // unsubscribe to ensure no memory leaks
        this.subscription.unsubscribe();
        this.subscriptionDocument?.unsubscribe();
        this.versionSelected.unsubscribe();
        this.documentChangeEnd.unsubscribe();
        this.documentLoaded.unsubscribe();
        this.editDocumentAction.unsubscribe();
    }

    async initPlugin(): Promise<void> {
        /*
        * In case of delegation
        * set the delegated user id
        */
        const userId: number = this.headerService.user.id === this.userId ? this.userId : this.headerService.user.id;

        const data: any = {
            file: {
                fileName: this.documentData.title,
                content: this.documentContent,
            },
            userName: `${this.headerService.user.firstname} ${this.headerService.user.lastname}`,
            userId: `${userId.toString()}-${this.signatureBookService.currentUserIndex}`,
            translate: { service: this.translateService.getTranslation(this.authService.lang), currentLang: this.authService.lang },
            canEditOriginalDocument: this.signatureBookService.canUpdateResources || this.documentData.canUpdate,
            versions: this.documentData.versions,
            resId: this.documentData.resId,
            isAnnotated: this.documentData.isAnnotated,
            documentChangeEnd: this.documentChangeEnd,
            editDocumentAction: this.editDocumentAction,
            documentLoaded: this.documentLoaded,
            versionSelected: this.versionSelected
        };
        this.pluginInstance = await this.pluginManagerService.initPlugin('maarch-plugins-pdftron', this.myPlugin, data);
        this.documentChangeEnd.pipe(
            tap(async (data: { stamps: StampInterface[], fileContent: Blob, isAnnotated: boolean }) => {
                this.signatureBookService.docsToSign.find(
                    (doc: AttachmentInterface) =>
                        doc.resId === this.documentData.resId
                ).stamps = data.stamps;
                if (!this.functionsService.empty(data.fileContent)) {
                    this.signatureBookService.docsToSign.find(
                        (doc: AttachmentInterface) =>
                            doc.resId === this.documentData.resId
                    ).fileContentWithAnnotations = data.fileContent;
                }

                this.signatureBookService.docsToSign.find(
                    (doc: AttachmentInterface) =>
                        doc.resId === this.documentData.resId
                ).isAnnotated = data.isAnnotated;
                this.documentData.isAnnotated = data.isAnnotated;
                this.documentData.stamps = data.stamps;
                if (this.signatureBookService.docsToSignWithStamps.find((attachment: Attachment) => attachment.resId === this.documentData.resId) !== undefined) {
                    this.signatureBookService.docsToSignWithStamps.find((attachment: Attachment) => attachment.resId === this.documentData.resId).stamps = data.stamps;
                } else {
                    this.signatureBookService.docsToSignWithStamps = this.signatureBookService.docsToSignWithStamps.concat([this.documentData]);
                }

                // Filter the docsToSignWithStamps array to remove duplicate entries based on resId
                this.signatureBookService.docsToSignWithStamps = this.signatureBookService.docsToSignWithStamps.filter((resource: Attachment, index: number, self: Attachment[]) =>
                // Keep the current resource only if it is the first occurrence of this resId in the array
                    index === self.findIndex((t) => t.resId == resource.resId)
                );
            })
        ).subscribe();
        this.editDocumentAction.pipe(
            tap(() => {
                const currentResId: number = this.documentData.resId;
                const isClonedAnnotation: boolean = this.signatureBookService.docsToSignClone.find((attachment: Attachment) => attachment.resId === currentResId)?.isAnnotated ?? false;
                const isAnnotated: boolean = this.signatureBookService.docsToSign.find((attachment: Attachment) => attachment.resId === currentResId)?.isAnnotated;
                if (isAnnotated || isClonedAnnotation) {
                    const dialogRef: MatDialogRef<ConfirmComponent, any> = this.dialog.open(ConfirmComponent, {
                        panelClass: 'maarch-modal',
                        autoFocus: false,
                        disableClose: true,
                        data: {
                            title: this.translate.instant('lang.warning'),
                            msg: this.translate.instant('lang.editWithAnnotations')
                        }
                    });

                    dialogRef.afterClosed().pipe(
                        filter((data: string) => data === 'ok'),
                        tap(() => this.openDocumentEditor()),
                        catchError((err: any) => {
                            this.notificationService.handleSoftErrors(err);
                            return of(false);
                        })
                    ).subscribe();
                } else {
                    this.openDocumentEditor();
                }
            })
        ).subscribe();

        this.documentLoaded.pipe(
            tap((event: boolean) => {
                if (event) {
                    const docWithStamp: Attachment = this.signatureBookService.docsToSignWithStamps.find((attachment: Attachment) => attachment.resId === this.documentData.resId);
                    if (!this.functionsService.empty(docWithStamp)) {
                        this.signatureBookService.docsToSign.find(
                            (doc: AttachmentInterface) =>
                                doc.resId === this.documentData.resId
                        ).stamps = docWithStamp.stamps;
                        this.pluginInstance.loadStamps(docWithStamp.stamps);
                    }

                    setTimeout(() => {
                        this.actionsService.emitActionWithData({
                            id: 'documentLoaded',
                            data: {}
                        });
                    }, 0);
                }
            })
        ).subscribe();

        this.versionSelected.pipe(
            tap((version: DocumentVersionsInterface) => {
                if (!this.functionsService.empty(version)) {
                    this.openDocumentViewerModal(version);
                }
            })
        ).subscribe();
    }

    getLabel(): string {
        return !this.functionsService.empty(this.documentData?.chrono)
            ? `${this.documentData?.chrono}: ${this.documentData?.title}`
            : `${this.documentData?.title}`;
    }

    getTitle(): string {
        if (this.documentType === 'attachments') {
            return `${this.getLabel()} (${this.documentData.typeLabel})`;
        } else if (this.documentType === 'resources') {
            return `${this.getLabel()}`;
        }
    }

    loadContent(resId: number = null): Promise<boolean> {
        let resourceUrn: string = this.documentData.resourceUrn;
        if (resId !== null) {
            resourceUrn = `rest/attachments/${resId}/content`
        }
        this.documentContent = null;
        return new Promise((resolve) => {
            this.subscriptionDocument = this.http
                .get(`../${resourceUrn}?watermark=false`, { responseType: 'blob' })
                .pipe(
                    tap((data: Blob) => {
                        this.documentContent = data;
                    }),
                    finalize(() => {
                        this.loading = false;
                        resolve(true);
                    }),
                    catchError((err: any) => {
                        this.notificationService.handleSoftErrors(err);
                        this.actionsService.emitActionWithData({
                            id: 'documentLoaded',
                            data: {}
                        });
                        return of(false);
                    })
                )
                .subscribe();
        });
    }

    async openDocumentEditor() {
        const createVersion: { enabled: boolean, default: boolean } = { enabled: false, default: false };
        if (this.documentData.isAttachment) {
            await this.signatureBookService.loadAttachmentTypes().then(() => {
                const selectedAttachment: { versionEnabled: boolean, newVersionDefault: boolean } = this.signatureBookService.attachmentsTypes.find((item) => item.typeId === this.documentData.type);
                if (selectedAttachment?.versionEnabled) {
                    createVersion.enabled = true;
                    createVersion.default = selectedAttachment.newVersionDefault;
                }
            });
        }

        const dialogRef: MatDialogRef<EditorManagerModalComponent, any> = this.dialog.open(EditorManagerModalComponent, {
            panelClass: 'maarch-full-height-modal',
            disableClose: true,
            width: '99vw',
            maxWidth: '99vw',
            data: {
                resId: this.documentData.resId,
                isAttachment: this.documentData.isAttachment,
                typeLabel: this.documentData.type,
                createVersion: createVersion
            }
        });
        dialogRef.afterClosed().pipe(
            filter((data: any) => !this.functionsService.empty(data)),
            tap(async (data: any) => {
                if (data === 'fileSaved') {
                    const newVersion: boolean = dialogRef.componentInstance?.editorManager?.createVersion?.default;
                    const encodedFile: string = dialogRef.componentInstance?.editorManager?.encodedFile ?? '';
                    const format: string = dialogRef.componentInstance?.editorManager?.file.format;
                    this.loading = true;
                    await this.initDocToSign(true).then(async () => {
                        this.loading = true;
                        if (newVersion && this.documentData.isAttachment && !this.functionsService.empty(encodedFile)) {
                            await this.signatureBookService.createNewVersion(this.documentData, encodedFile, format).then(async (resId: number) => {
                                this.fetchVersions.emit('fetchVersions');
                                this.signatureBookService.docsToSign.find((attachment: Attachment) => attachment.resId === this.documentData.resId).resId = resId;
                                this.documentData = this.signatureBookService.docsToSign.find((attachment: Attachment) => attachment.resId === resId);
                                this.documentData.fileInformation.version++;
                                await this.initDocToSign(true, resId);
                            }).catch((err: any) => {
                                this.notificationService.handleSoftErrors(err);
                                this.loading = false;
                            })
                        } else {
                            this.fetchVersions.emit('fetchVersions');
                        }
                        this.loading = false;
                    }).finally(() => this.notificationService.success(this.translateService.instant('lang.modificationsProcessed')))
                        .catch((err: any) => {
                            this.notificationService.handleSoftErrors(err);
                            this.loading = false;
                        });
                }
            }),
        ).subscribe();
    }

    openDocumentViewerModal(version: DocumentVersionsInterface): void {
        const file = {
            title: '',
            filename: '',
            base64: '',
        }
        const url: string = this.documentData.isAttachment ? `../rest/attachments/${version.resId}/content?mode=base64` : `../rest/resources/${version.resId}/content/${version.relation}?type=PDF`;

        this.http.get(url).pipe(
            tap((data: { encodedDocument: string, filename: string }) => {
                file['base64'] = data['encodedDocument'];
                file['filename'] = data['filename'];
                file['title'] = `${this.translateService.instant('lang.version')} ${version.relation}`;

                this.dialog.open(DocumentViewerModalComponent, {
                    autoFocus: false,
                    disableClose: true,
                    panelClass: ['maarch-full-height-modal', 'maarch-doc-modal'],
                    data: {
                        title: file.title,
                        filename: file.filename,
                        base64: file.base64
                    }
                });
            }),
            catchError((err: any) => {
                this.notificationService.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    fetchDocumentVersions(): void {
        if (this.pluginInstance) {
            const selectedResource: Attachment = this.signatureBookService.docsToSign.find((attachment: Attachment) => attachment.resId === this.documentData.resId);
            const versions: DocumentVersionsInterface[] = selectedResource.versions;
            this.pluginInstance.fetchDocumentVerions(versions);
            this.signatureBookService.selectedDocToSign = {
                attachment: selectedResource,
                index: this.signatureBookService.docsToSign.indexOf(selectedResource)
            }
        }
    }

    destroyPlugin(): void {
        this.pluginManagerService.destroyPlugin(this.myPlugin);
        this.pluginInstance = null;
        this.documentData = null;
        this.signatureBookService.selectedDocToSign = new SelectedAttachment();
    }
}
