import { Component, EventEmitter, Input, OnInit, Output, ViewChild } from '@angular/core';
import { HttpClient, } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { HeaderService } from "@service/header.service";
import { catchError, exhaustMap, finalize, map, take, tap } from "rxjs/operators";
import { of } from "rxjs";
import { NotificationService } from "@service/notification/notification.service";
import { AuthService } from "@service/auth.service";
import {
    CollaboraOnlineConfig,
    JavaEditorConfig,
    OnlyOfficeConfig,
    ResourceFileInterface, SharepointConfig
} from "@models/editor-manager.model";
import { ConfirmComponent } from '@plugins/modal/confirm.component';
import { MatLegacyDialog as MatDialog, MatLegacyDialogRef as MatDialogRef } from '@angular/material/legacy-dialog';
import { SignatureBookService } from '@appRoot/signatureBook/signature-book.service';
import { FunctionsService } from '@service/functions.service';


@Component({
    selector: 'app-editor-manager',
    templateUrl: 'editor-manager.component.html',
    styleUrls: ['editor-manager.component.scss'],
})

export class EditorManagerComponent implements OnInit {

    @ViewChild('editorComp', { static: false }) editorComp: any;

    @Input() resId: number;
    @Input() isAttachment: boolean = false;
    @Input() createVersion: { enabled: boolean, default: boolean } = null;
    @Input() unannotatedVersion: boolean = true;

    @Output() metaDataLoaded: EventEmitter<string> = new EventEmitter<string>();
    @Output() fileSaved: EventEmitter<boolean> = new EventEmitter<boolean>();
    @Output() processError: EventEmitter<boolean> = new EventEmitter<boolean>();
    @Output() closeModalEvent: EventEmitter<void> = new EventEmitter<void>();

    loading: boolean = true;
    onError: boolean = false;
    processing: boolean = false;

    editor: any;

    file: ResourceFileInterface = {
        name: '',
        content: null,
        format: null
    };

    modified: boolean = false;

    encodedFile: string = '';

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        public headerService: HeaderService,
        public dialog: MatDialog,
        public functions: FunctionsService,
        private notificationService: NotificationService,
        private authService: AuthService,
        private signatureBookService: SignatureBookService
    ) { }

    async ngOnInit(): Promise<void> {
        this.setEditor();
        await this.loadResource();
        this.loading = false;
    }

    setEditor(): void {
        if (this.headerService.user.preferences.documentEdition === 'java') {
            this.editor = new JavaEditorConfig({
                options: {
                    objectId : this.resId,
                    objectType: this.isAttachment ? 'attachmentModification' : 'resourceModification',
                    cookie: document.cookie,
                    authToken: this.authService.getToken()
                }
            });

        } else if (this.headerService.user.preferences.documentEdition === 'onlyoffice') {
            this.editor = new OnlyOfficeConfig({
                options: {
                    objectId : this.resId,
                    objectType: this.isAttachment ? 'attachmentModification' : 'resourceModification',
                    docUrl: 'rest/onlyOffice/mergedFile'
                }
            });
        } else if (this.headerService.user.preferences.documentEdition === 'collaboraonline') {
            this.editor = new CollaboraOnlineConfig({
                options: {
                    objectId : this.resId,
                    objectType: this.isAttachment ? 'attachmentModification' : 'resourceModification',
                }
            });
        } else if (this.headerService.user.preferences.documentEdition === 'office365sharepoint') {
            this.editor = new SharepointConfig({
                options: {
                    objectId : this.resId,
                    objectType: this.isAttachment ? 'attachmentModification' : 'resourceModification',
                }
            });
        }
    }

    async loadResource(): Promise<boolean> {
        return new Promise((resolve) => {
            const resourceUrl = `../rest/${this.isAttachment ? 'attachments' : 'resources'}/${this.resId}/originalContent?mode=base64&unannotatedVersion=${this.unannotatedVersion}`;
            return this.http.get(resourceUrl).pipe(
                tap((data: any) => {
                    if (data.extension === 'pdf') {
                        this.notificationService.error(this.translate.instant('lang.cannotEditFile'));
                        this.setOnError();
                        resolve(false);
                    }
                    this.file.content = this.base64ToArrayBuffer(data.encodedDocument);
                    this.file.name = `${data.filename}`;
                    this.file.format = data.extension;
                    this.metaDataLoaded.emit(this.file.name);
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notificationService.handleSoftErrors(err);
                    this.setOnError();
                    resolve(false);
                    return of(false);
                })
            ).subscribe();
        })
    }

    base64ToArrayBuffer(base64: string): ArrayBuffer {
        const binary_string = window.atob(base64);
        const len = binary_string.length;
        const bytes = new Uint8Array(len);
        for (let i = 0; i < len; i++) {
            bytes[i] = binary_string.charCodeAt(i);
        }
        return bytes.buffer;
    }

    setOnError(): void {
        this.onError = true;
        this.processError.emit(true);
    }

    saveFile(): void {
        this.encodedFile = '';
        this.processing = true;
        const isNewVersion: boolean = this.createVersion?.enabled ? this.createVersion?.default : false;
        this.editorComp?.getFile().pipe(
            take(1),
            map((data: any) => {
                this.encodedFile = data.content;
                return {
                    encodedFile: this.encodedFile,
                    format: data.format,
                    resId: this.resId
                };
            }),
            exhaustMap((data) => {
                if (this.resId !== null && ((this.isAttachment && !isNewVersion) || (!this.isAttachment))) {
                    const type: string = this.isAttachment ? 'attachments' : 'resources';
                    return this.http.put(`../rest/${type}/${this.resId}?onlyDocument=true`, data);
                }
                return of(null);
            }),
            tap(() => {
                this.fileSaved.emit(true);
            }),
            finalize(() => this.processing = false),
            catchError((err: any) => {
                this.notificationService.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    closeModal(): void {
        if (this.modified) {
            const dialogRef = this.openConfirmModification();
            dialogRef.afterClosed().pipe(
                tap((data: string) => {
                    if (data === 'ok') {
                        this.saveFile();
                    } else {
                        this.closeModalEvent.emit();
                    }
                }),
                catchError((err: any) => {
                    this.notificationService.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        } else {
            this.closeModalEvent.emit();
        }
    }

    openConfirmModification(): MatDialogRef<ConfirmComponent> {
        return this.dialog.open(ConfirmComponent, {
            panelClass: 'maarch-modal',
            autoFocus: false,
            disableClose: true,
            data: {
                title: this.translate.instant('lang.confirm'),
                msg: this.translate.instant('lang.saveModifiedData'),
                buttonValidate: this.translate.instant('lang.yes'),
                buttonCancel: this.translate.instant('lang.no')
            }
        });
    }
}
