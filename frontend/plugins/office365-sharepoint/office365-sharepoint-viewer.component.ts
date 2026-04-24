import { Component, EventEmitter, Input, OnDestroy, OnInit, Output } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { catchError, tap } from 'rxjs/operators';
import { TranslateService } from '@ngx-translate/core';
import { MatLegacyDialog as MatDialog, MatLegacyDialogRef as MatDialogRef } from '@angular/material/legacy-dialog';
import { HeaderService } from '@service/header.service';
import { NotificationService } from '@service/notification/notification.service';
import { Observable, of, Subject } from 'rxjs';
import { SharepointOptionsInterface } from "@models/editor-manager.model";

@Component({
    selector: 'app-office-sharepoint-viewer',
    templateUrl: 'office365-sharepoint-viewer.component.html',
    styleUrls: [
        'office365-sharepoint-viewer.component.scss'
    ],
})
export class Office365SharepointViewerComponent implements OnInit, OnDestroy {

    @Input() editMode: boolean = false;
    @Input() file: any = {};
    @Input() params: SharepointOptionsInterface;
    @Input() hideDownloadButton: boolean = false;
    @Input() hideCancelButton: boolean = false;
    @Input() primaryColor: boolean = false;
    @Input() unannotatedVersion: boolean = true;
    @Input() isAnnotated: boolean = false;

    @Output() triggerAfterUpdatedDoc = new EventEmitter<string>();
    @Output() triggerCloseEditor = new EventEmitter<string>();
    @Output() triggerModifiedDocument = new EventEmitter<string>();
    @Output() triggerDocumentDownload = new EventEmitter<string>();

    loading: boolean = true;

    editorConfig: any;
    key: number = 0;
    isSaving: boolean = false;
    isModified: boolean = false;

    allowedExtension: string[] = [
        'doc',
        'docx',
        'dotx',
        'odt',
        'ott',
        'rtf',
        'txt',
        'html',
        'xlsl',
        'xlsx',
        'xltx',
        'ods',
        'ots',
        'csv',
    ];

    dialogRef: MatDialogRef<any>;

    documentId: any;
    documentWebUrl: string = null;

    private eventAction = new Subject<any>();

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        public dialog: MatDialog,
        private notify: NotificationService,
        public headerService: HeaderService) { }

    async ngOnInit(): Promise<void> {
        this.key = this.generateUniqueId(10);

        if (this.canLaunchOffice365Sharepoint()) {
            this.params.objectPath = undefined;
            if (typeof this.params.objectId === 'string' && (this.params.objectType === 'templateModification' || this.params.objectType === 'templateCreation')) {
                this.params.objectPath = this.params.objectId;
                this.params.objectId = this.key;
            } else if (typeof this.params.objectId === 'string' && this.params.objectType === 'encodedResource') {
                this.params.content = this.params.objectId;
                this.params.objectId = this.key;
                this.params.objectType = 'templateEncoded';
            }

            await this.sendDocument();
            this.loading = false;
        }
    }

    closeEditor(): void {
        if (this.headerService.sideNavLeft !== null && !this.headerService.hideSideBar) {
            this.headerService.sideNavLeft.open();
        }

        this.triggerCloseEditor.emit();

        setTimeout(() => {
            this.deleteDocument();
        }, 10000);
    }

    canLaunchOffice365Sharepoint(): boolean {
        if (this.isAllowedEditExtension(this.file.format) || this.isAnnotated) {
            return true;
        } else {
            this.notify.error(this.translate.instant('lang.onlyofficeEditDenied') + ' <b>' + this.file.format + '</b> ' + this.translate.instant('lang.officeSharepointEditDenied'));
            this.triggerCloseEditor.emit();
            return false;
        }
    }

    getEncodedDocument(): Promise<boolean> {
        return new Promise((resolve) => {
            this.http.get('../rest/office365/' + this.documentId).pipe(
                tap((data: any) => {
                    this.file = {
                        name: this.key,
                        type: null,
                        contentMode: 'base64',
                        content: data.content,
                        src: null,
                        format: this.file.format
                    };
                    this.eventAction.next(this.file);
                    setTimeout(() => {
                        this.deleteDocument();
                    }, 10000);
                    resolve(true);
                }),
                catchError((err) => {
                    this.notify.handleErrors(err);
                    this.triggerCloseEditor.emit();
                    resolve(false);
                    return of(false);
                }),
            ).subscribe();
        });
    }

    generateUniqueId(length: number = 5): number {
        let result = '';
        const characters = '0123456789';
        const charactersLength = characters.length;
        for (let i = 0; i < length; i++) {
            result += characters.charAt(Math.floor(Math.random() * charactersLength));
        }
        return parseInt(result, 10);
    }

    sendDocument(): Promise<boolean> {
        return new Promise((resolve) => {
            this.http.post('../rest/office365', {
                resId: this.params.objectId,
                type: this.params.objectType,
                format: this.file.format,
                path: this.params.objectPath,
                data: this.params.dataToMerge,
                unannotatedVersion: this.unannotatedVersion,
                encodedContent: this.params.content
            }).pipe(
                tap((data: any) => {
                    this.documentId = data.documentId;
                    this.documentWebUrl = data.webUrl;
                    this.openDocument();
                    resolve(true);
                }),
                catchError((err) => {
                    this.notify.handleErrors(err);
                    this.triggerCloseEditor.emit();
                    resolve(false);
                    return of(false);
                }),
            ).subscribe();
        });
    }

    deleteDocument(): Promise<boolean> {
        return new Promise((resolve) => {
            this.http.request('DELETE', '../rest/office365/' + this.documentId, { body: { resId: this.params.objectId } }).pipe(
                tap(() => {
                    resolve(true);
                }),
                catchError((err) => {
                    this.notify.handleErrors(err);
                    this.triggerCloseEditor.emit();
                    resolve(false);
                    return of(false);
                }),
            ).subscribe();
        });
    }

    getFile(): Observable<any> {
        this.isSaving = true;
        this.getEncodedDocument();
        return this.eventAction.asObservable();
    }

    ngOnDestroy(): void {
        this.eventAction.complete();
    }

    isAllowedEditExtension(extension: string): boolean {
        return this.allowedExtension.filter(ext => ext.toLowerCase() === extension.toLowerCase()).length > 0;
    }

    openDocument(): void {
        this.triggerModifiedDocument.emit();
        this.isModified = true;
        window.open(this.documentWebUrl, '_blank');
    }

    downloadDocument(): void {
        this.isSaving = true;
        this.triggerDocumentDownload.emit();
    }
}
