import { Component, EventEmitter, HostListener, Input, OnDestroy, OnInit, Output, ViewChild } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { catchError, filter, tap } from 'rxjs/operators';
import { TranslateService } from '@ngx-translate/core';
import { ConfirmComponent } from '../modal/confirm.component';
import { MatLegacyDialog as MatDialog, MatLegacyDialogRef as MatDialogRef } from '@angular/material/legacy-dialog';
import { HeaderService } from '@service/header.service';
import { DomSanitizer } from '@angular/platform-browser';
import { NotificationService } from '@service/notification/notification.service';
import { Observable, of, Subject } from 'rxjs';
import { Router } from '@angular/router';
import { FunctionsService } from '@service/functions.service';
import { CollaboraOnlineOptionsInterface } from "@models/editor-manager.model";
import { AuthService } from '@service/auth.service';

declare let $: any;

@Component({
    selector: 'app-collabora-online-viewer',
    templateUrl: 'collabora-online-viewer.component.html',
    styleUrls: ['collabora-online-viewer.component.scss'],
})
export class CollaboraOnlineViewerComponent implements OnInit, OnDestroy {

    @Input() editMode: boolean = false;
    @Input() file: any = {};
    @Input() params: CollaboraOnlineOptionsInterface;
    @Input() loading: boolean = false;
    @Input() hideCloseEditor: boolean = false;
    @Input() hideFullscreenButton: boolean = false;
    @Input() hideCollapseButton: boolean = false;
    @Input() unannotatedVersion: boolean = true;
    @Input() isAnnotated: boolean = false;

    @Output() triggerAfterUpdatedDoc = new EventEmitter<string>();
    @Output() triggerCloseEditor = new EventEmitter<string>();
    @Output() triggerModifiedDocument = new EventEmitter<string>();
    @Output() triggerModeModified = new EventEmitter<boolean>();
    @Output() triggerFullScreen = new EventEmitter<void>();

    @ViewChild('collaboraFrame', { static: false }) collaboraFrame: any;

    editorConfig: any;
    key: number = 0;
    isSaving: boolean = false;
    isModified: boolean = false;
    fullscreenMode: boolean = false;
    hideButtons: boolean = false;

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

    editorUrl: any = '';
    token: string = '';

    private eventAction = new Subject<any>();

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        public dialog: MatDialog,
        public router: Router,
        public headerService: HeaderService,
        public functions: FunctionsService,
        private sanitizer: DomSanitizer,
        private notify: NotificationService,
        private authService: AuthService
    ) { }

    @HostListener('window:message', ['$event'])
    /**
     * Handles incoming messages from the Collabora Online iframe.
     * Processes the message data, performs specific actions based on the message type,
     * and triggers appropriate events or updates the state of the component.
     *
     * @param e - The event object containing the message data.
     *
     * The function performs the following actions based on the `MessageId` in the message:
     * - `Doc_ModifiedStatus`: Resets the authentication timer, updates the `isModified` state,
     *   and triggers events when the document's modified status changes.
     * - `Action_Save_Resp`: Triggers an event and fetches the temporary file after a successful save.
     * - `App_LoadingStatus`: Sends a readiness message to the Collabora iframe when the document is loaded.
     *
     * Error handling is included to log and handle invalid or malformed message data.
     */
    onMessage(e: any) {
        try {
            let data: any;

            try {
                data = typeof e.data === 'string' ? JSON.parse(e.data) : e.data;
            } catch (err) {
                console.error(err, e.data);
                return;
            }
            const response: MessageInterface = data;
            if (['Doc_ModifiedStatus', 'FollowUser_Changed'].indexOf(response.MessageId) > -1) {
                this.authService.resetTimer();
            }
            // EVENT TO CONSTANTLY UPDATE CURRENT DOCUMENT
            if (response.MessageId === 'Doc_ModifiedStatus' && response.Values.Modified === false) {
                this.isModified = false;
            }
            if (response.MessageId === 'Action_Save_Resp' && response.Values.success === true && !this.isModified) {
                setTimeout(() => {
                    this.triggerAfterUpdatedDoc.emit();
                    this.getTmpFile();
                }, 500);
            } else if (response.MessageId === 'Doc_ModifiedStatus' && response.Values.Modified === false && this.isSaving) {
                // Collabora sends 'Action_Save_Resp' when it starts saving the document, then sends Doc_ModifiedStatus with Modified = false when it is done saving
                this.triggerAfterUpdatedDoc.emit();
                this.getTmpFile();
            } else if (response.MessageId === 'Doc_ModifiedStatus' && response.Values.Modified === true) {
                this.isModified = true;
                this.authService.resetTimer();
                this.triggerModifiedDocument.emit();
            } else if (response.MessageId === 'App_LoadingStatus' && response.Values.Status === 'Document_Loaded') {
                const message = { 'MessageId': 'Host_PostmessageReady' };
                this.collaboraFrame.nativeElement.contentWindow.postMessage(JSON.stringify(message), '*');
            }
        } catch(err) {
            console.error(err, e.data);
        }
    }

    quit(): void {
        this.dialogRef = this.dialog.open(ConfirmComponent, { panelClass: 'maarch-modal', autoFocus: false, disableClose: true, data: { title: this.translate.instant('lang.close'), msg: this.translate.instant('lang.confirmCloseEditor') } });

        this.dialogRef.afterClosed().pipe(
            filter((data: string) => data === 'ok'),
            tap(() => {
                this.closeEditor();
                this.formatAppToolsCss('default');
            })
        ).subscribe();
    }

    closeEditor(): void {
        if (this.headerService.sideNavLeft !== null && !this.headerService.hideSideBar) {
            this.headerService.sideNavLeft.open();
        }
        $('iframe[name=\'frameEditor\']').css('position', 'initial');
        this.fullscreenMode = false;

        const message = {
            'MessageId': 'Action_Close',
            'Values': null
        };
        this.collaboraFrame.nativeElement.contentWindow.postMessage(JSON.stringify(message), '*');

        this.deleteTmpFile();

        this.triggerAfterUpdatedDoc.emit();
        this.triggerCloseEditor.emit();
    }

    saveDocument(): void {
        this.isSaving = true;

        const message = {
            'MessageId': 'Action_Save',
            'Values': {
                'Notify': true,
                'ExtendedData': 'FinalSave=True',
                'DontTerminateEdit': true,
                'DontSaveIfUnmodified': false
            }
        };
        this.collaboraFrame.nativeElement.contentWindow.postMessage(JSON.stringify(message), '*');
    }

    async ngOnInit(): Promise<void> {
        this.key = this.generateUniqueId(10);

        if (this.canLaunchCollaboraOnline()) {
            await this.checkServerStatus();

            this.params.objectPath = undefined;
            if (typeof this.params.objectId === 'string' && (this.params.objectType === 'templateModification' || this.params.objectType === 'templateCreation')) {
                this.params.objectPath = this.params.objectId;
                this.params.objectId = this.key;
            } else if (typeof this.params.objectId === 'string' && this.params.objectType === 'encodedResource') {
                this.params.content = this.params.objectId;
                this.params.objectId = this.key;
                this.params.objectType = 'templateEncoded';

                await this.saveEncodedFile();
            }

            await this.getConfiguration();

            this.loading = false;
            this.triggerModifiedDocument.emit();
        }
    }

    canLaunchCollaboraOnline(): boolean {
        if (this.isAllowedEditExtension(this.file.format) || this.isAnnotated) {
            return true;
        } else {
            this.notify.error(this.translate.instant('lang.onlyofficeEditDenied') + ' <b>' + this.file.format + '</b> ' + this.translate.instant('lang.collaboraOnlineEditDenied2'));
            this.triggerCloseEditor.emit();
            return false;
        }
    }

    checkServerStatus(): Promise<boolean> {
        return new Promise((resolve) => {
            if (location.host === '127.0.0.1' || location.host === 'localhost') {
                this.notify.error(`${this.translate.instant('lang.errorCollaboraOnline1')}`);
                this.triggerCloseEditor.emit();
            } else {
                this.http.get('../rest/collaboraOnline/available').pipe(
                    tap((data: any) => {
                        if (data.isAvailable) {
                            resolve(true);
                        } else {
                            this.notify.error(`${this.translate.instant('lang.errorCollaboraOnline2')}`);
                            this.triggerCloseEditor.emit();
                        }
                    }),
                    catchError((err) => {
                        this.notify.error(this.translate.instant('lang.' + err.error.lang));
                        this.triggerCloseEditor.emit();
                        return of(false);
                    }),
                ).subscribe();
            }
        });
    }

    getTmpFile(): Promise<boolean> {
        return new Promise((resolve) => {
            this.http.post('../rest/collaboraOnline/file', { token: this.token }).pipe(
                tap((data: any) => {
                    this.file = {
                        name: this.key,
                        format: data.format,
                        type: null,
                        contentMode: 'base64',
                        content: data.content,
                        src: null
                    };
                    this.eventAction.next(this.file);
                    resolve(true);
                }),
                catchError((err) => {
                    this.notify.handleErrors(err);
                    this.triggerCloseEditor.emit();
                    return of(false);
                }),
            ).subscribe();
        });
    }

    deleteTmpFile(): Promise<boolean> {
        return new Promise((resolve) => {
            this.http.delete('../rest/collaboraOnline/file?token=' + this.token).pipe(
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

    saveEncodedFile(): Promise<boolean> {
        return new Promise((resolve) => {
            const body = {
                content: this.params.content,
                format: this.file.format,
                key: this.key
            };
            this.http.post('../rest/collaboraOnline/encodedFile', body).pipe(
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

    generateUniqueId(length: number = 5): number {
        let result = '';
        const characters = '0123456789';
        const charactersLength = characters.length;
        for (let i = 0; i < length; i++) {
            result += characters.charAt(Math.floor(Math.random() * charactersLength));
        }
        return parseInt(result, 10);
    }

    getConfiguration(): Promise<boolean> {
        return new Promise((resolve) => {
            this.http.post('../rest/collaboraOnline/configuration', {
                resId: this.params.objectId,
                type: this.params.objectType,
                format: this.file.format,
                path: this.params.objectPath,
                data: this.params.dataToMerge,
                unannotatedVersion: this.unannotatedVersion,
                lang: this.translate.instant('lang.langISO')
            }).pipe(
                tap((data: any) => {
                    this.editorUrl = data.url;
                    this.editorUrl = this.sanitizer.bypassSecurityTrustResourceUrl(this.editorUrl);
                    this.token = data.token;
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
        this.saveDocument();
        return this.eventAction.asObservable();
    }

    ngOnDestroy(): void {
        this.eventAction.complete();
    }

    openFullscreen(): void {
        const iframe = $('iframe[name=\'frameEditor\']');
        iframe.css('top', '0px');
        iframe.css('left', '0px');

        if (!this.fullscreenMode) {
            this.formatAppToolsCss('fullscreen');
            this.triggerModeModified.emit(true);
            if (this.headerService.sideNavLeft !== null) {
                this.headerService.sideNavLeft.close();
            }
            iframe.css('position', 'fixed');
            iframe.css('z-index', '2');
        } else {
            this.formatAppToolsCss('default');
            this.triggerModeModified.emit(false);
            if (this.headerService.sideNavLeft !== null && !this.headerService.hideSideBar) {
                this.headerService.sideNavLeft.open();
            }
            iframe.css('position', 'initial');
            iframe.css('z-index', '1');
        }
        this.fullscreenMode = !this.fullscreenMode;
        this.triggerFullScreen.emit();
    }

    isAllowedEditExtension(extension: string): boolean {
        return this.allowedExtension.filter(ext => ext.toLowerCase() === extension.toLowerCase()).length > 0;
    }

    formatAppToolsCss(mode: string, hide: boolean = false): void {
        const appTools: HTMLElement = $('app-tools-informations')[0];
        if (!this.functions.empty(appTools)) {
            if (mode === 'fullscreen') {
                appTools.style.top = '10px';
                appTools.style.right = '160px';
                if (hide) {
                    appTools.style.transition =  'all 0.5s';
                    appTools.style.display = 'none';
                } else {
                    appTools.style.transition =  'all 0.5s';
                    appTools.style.display = 'flex';
                }
            } else {
                appTools.style.top = 'auto';
                appTools.style.right = 'auto';
            }
        }
    }
}

export interface MessageInterface {
    MessageId: string;
    SendTime: number;
    Values: Record<string, string | number | boolean>;
}

