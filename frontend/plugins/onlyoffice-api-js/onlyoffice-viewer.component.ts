import {
    Component,
    OnInit,
    Input,
    EventEmitter,
    Output,
    HostListener,
    OnDestroy,
    Renderer2
} from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { catchError, tap, filter } from 'rxjs/operators';
import { TranslateService } from '@ngx-translate/core';
import { ConfirmComponent } from '../modal/confirm.component';
import { MatLegacyDialogRef as MatDialogRef, MatLegacyDialog as MatDialog } from '@angular/material/legacy-dialog';
import { HeaderService } from '@service/header.service';
import { Observable, of, Subject } from 'rxjs';
import { NotificationService } from '@service/notification/notification.service';
import { ScriptInjectorService } from '@service/script-injector.service';
import { Router } from '@angular/router';
import { FunctionsService } from '@service/functions.service';
import { OnlyOfficeOptionsInterface } from "@models/editor-manager.model";
import { AuthService } from '@service/auth.service';

declare let $: any;
declare let DocsAPI: any;

@Component({
    selector: 'app-onlyoffice-viewer',
    templateUrl: 'onlyoffice-viewer.component.html',
    styleUrls: ['onlyoffice-viewer.component.scss'],
})
export class EcplOnlyofficeViewerComponent implements OnInit, OnDestroy {

    @Input() editMode: boolean = false;
    @Input() file: any = {};
    @Input() params: OnlyOfficeOptionsInterface;
    @Input() hideCloseEditor: boolean = false;
    @Input() hideFullscreenButton: boolean = false;
    @Input() hideCollapseButton: boolean = false;
    @Input() loading: boolean = false;
    @Input() unannotatedVersion: boolean = true;
    @Input() isAnnotated: boolean = false;

    @Output() triggerAfterUpdatedDoc = new EventEmitter<string>();
    @Output() triggerCloseEditor = new EventEmitter<string>();
    @Output() triggerModifiedDocument = new EventEmitter<string>();
    @Output() triggerModeModified = new EventEmitter<boolean>();
    @Output() triggerFullScreen = new EventEmitter<void>();

    editorConfig: any;
    docEditor: any;
    key: string = '';
    documentLoaded: boolean = false;
    canUpdateDocument: boolean = false;
    isSaving: boolean = false;
    fullscreenMode: boolean = false;

    tmpFilename: string = '';

    appUrl: string = '';
    onlyOfficeUrl: string = '';
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

    private eventAction = new Subject<any>();

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        public dialog: MatDialog,
        public router: Router,
        public headerService: HeaderService,
        public functions: FunctionsService,
        private renderer: Renderer2,
        private notify: NotificationService,
        private scriptInjectorService: ScriptInjectorService,
        private authService: AuthService,
    ) { }

    @HostListener('window:message', ['$event'])
    /**
     * Handles messages received from the OnlyOffice viewer component.
     *
     * @param e - The event object containing the message data.
     *
     * The method processes the incoming message by parsing its data and performing
     * actions based on the event type. Supported events include:
     * - `onCollaborativeChanges`, `onDocumentStateChange`, `onMetaChange`, `onRequestEditRights`:
     *   Resets the authentication timer using the `authService`.
     * - `onDownloadAs`: Encodes the document data for download.
     * - `onDocumentReady`: Emits an event to indicate that the document has been modified.
     */
    onMessage(e: any) {
        const response = JSON.parse(e.data);
        if (['onCollaborativeChanges', 'onDocumentStateChange', 'onMetaChange', 'onRequestEditRights'].indexOf(response.event) > -1) {
            this.authService.resetTimer();
        }
        // EVENT TO CONSTANTLY UPDATE CURRENT DOCUMENT
        if (response.event === 'onDownloadAs') {
            this.getEncodedDocument(response.data);
        } else if (response.event === 'onDocumentReady') {
            this.triggerModifiedDocument.emit();
        }
    }

    quit(): void {
        this.dialogRef = this.dialog.open(ConfirmComponent, { panelClass: 'maarch-modal', autoFocus: false, disableClose: true, data: { title: this.translate.instant('lang.close'), msg: this.translate.instant('lang.confirmCloseEditor') } });

        this.dialogRef.afterClosed().pipe(
            filter((data: string) => data === 'ok'),
            tap(() => {
                this.docEditor?.destroyEditor();
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
        this.triggerAfterUpdatedDoc.emit();
        this.triggerCloseEditor.emit();
    }

    getDocument(): void {
        this.isSaving = true;
        this.docEditor.downloadAs(this.file.format);
    }

    getEncodedDocument(data: any): void {
        const urlParam: any = !this?.functions.empty(data?.url) ? data.url : data;
        this.http.get('../rest/onlyOffice/encodedFile', { params: { url: urlParam } }).pipe(
            tap((result: any) => {
                this.file.content = result.encodedFile;
                this.isSaving = false;
                this.triggerAfterUpdatedDoc.emit();
                this.eventAction.next(this.file);
            })
        ).subscribe();
    }

    getEditorMode(extension: string): string {
        if (['csv', 'fods', 'ods', 'ots', 'xls', 'xlsm', 'xlsx', 'xlt', 'xltm', 'xltx'].indexOf(extension) > -1) {
            return 'spreadsheet';
        } else if (['fodp', 'odp', 'otp', 'pot', 'potm', 'potx', 'pps', 'ppsm', 'ppsx', 'ppt', 'pptm', 'pptx'].indexOf(extension) > -1) {
            return 'presentation';
        } else {
            return 'text';
        }
    }


    async ngOnInit(): Promise<void> {
        this.key = this.generateUniqueId();

        if (this.canLaunchOnlyOffice()) {
            await this.getServerConfiguration();
            this.loadApi();
        }
    }

    loadApi(): void {
        const scriptElement = this.scriptInjectorService.loadJsScript(
            this.renderer,
            this.onlyOfficeUrl + '/web-apps/apps/api/documents/api.js'
        );
        scriptElement.onload = async () => {
            await this.checkServerStatus();

            await this.getMergedFileTemplate();

            this.setEditorConfig();

            await this.getTokenOOServer();

            this.initOfficeEditor();

            this.loading = false;
        };
        scriptElement.onerror = () => {
            // eslint-disable-next-line no-console
            console.log('Could not load the onlyoffice API Script!');
            this.triggerCloseEditor.emit();
        };
    }

    canLaunchOnlyOffice(): boolean {
        if (this.isAllowedEditExtension(this.file.format) || this.isAnnotated) {
            return true;
        } else {
            this.notify.error(this.translate.instant('lang.onlyofficeEditDenied') + ' <b>' + this.file.format + '</b> ' + this.translate.instant('lang.onlyofficeEditDenied2'));
            this.triggerCloseEditor.emit();
            return false;
        }
    }

    getServerConfiguration(): Promise<boolean> {
        return new Promise((resolve) => {
            this.http.get('../rest/onlyOffice/configuration').pipe(
                tap((data: any) => {
                    if (data.enabled) {

                        const serverUriArr = data.serverUri.split('/');
                        const protocol = data.serverSsl ? 'https://' : 'http://';
                        const domain = data.serverUri.split('/')[0];
                        const path = serverUriArr.slice(1).join('/');
                        const port = data.serverPort ? `:${data.serverPort}` : ':80';

                        const serverUri = [domain + port, path].join('/');

                        this.onlyOfficeUrl = `${protocol}${serverUri}`;
                        this.appUrl = data.coreUrl.replace(/\/$/, "");
                        resolve(true);
                    } else {
                        this.triggerCloseEditor.emit();
                    }
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


    checkServerStatus(): Promise<boolean> {
        return new Promise((resolve) => {
            const regex = /127\.0\.0\.1/g;
            const regex2 = /localhost/g;
            if (this.appUrl.match(regex) !== null || this.appUrl.match(regex2) !== null) {
                this.notify.error(`${this.translate.instant('lang.errorOnlyoffice1')}`);
                this.triggerCloseEditor.emit();
            } else {
                this.http.get('../rest/onlyOffice/available').pipe(
                    tap((data: any) => {
                        if (data.isAvailable) {
                            resolve(true);
                        } else {
                            this.notify.error(`${this.translate.instant('lang.errorOnlyoffice2')} ${this.onlyOfficeUrl}`);
                            this.triggerCloseEditor.emit();
                        }
                    }),
                    catchError((err) => {
                        this.notify.error(this.translate.instant('lang.' + err.error.lang));
                        this.triggerCloseEditor.emit();
                        resolve(false);
                        return of(false);
                    }),
                ).subscribe();
            }
        });
    }

    getMergedFileTemplate(): Promise<boolean> {
        return new Promise((resolve) => {
            const body = {
                objectId: this.params.objectId,
                objectType: this.params.objectType,
                format: this.file.format,
                onlyOfficeKey: this.key,
                data: this.params.dataToMerge,
                unannotatedVersion: this.unannotatedVersion
            }
            this.http.post(`../${this.params.docUrl}`, body).pipe(
                tap((data: any) => {
                    this.tmpFilename = data.filename;

                    this.file = {
                        name: this.key,
                        format: data.filename.split('.').pop(),
                        type: null,
                        contentMode: 'base64',
                        content: null,
                        src: null
                    };
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

    generateUniqueId(length: number = 5): string {
        let result = '';
        const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        const charactersLength = characters.length;
        for (let i = 0; i < length; i++) {
            result += characters.charAt(Math.floor(Math.random() * charactersLength));
        }
        return result;
    }

    initOfficeEditor(): void {
        this.docEditor = new DocsAPI.DocEditor('placeholder', this.editorConfig, this.onlyOfficeUrl);
    }

    getTokenOOServer(): Promise<boolean> {
        return new Promise((resolve) => {
            this.http.post('../rest/onlyOffice/token', { config: this.editorConfig }).pipe(
                tap((data: any) => {
                    if (data !== null) {
                        this.editorConfig.token = data;
                    }
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

    setEditorConfig(): void {
        this.editorConfig = {
            documentType: this.getEditorMode(this.file.format),
            document: {
                fileType: this.file.format,
                key: this.key,
                title: 'Edition',
                url: `${this.appUrl}/${this.params.docUrl}?filename=${this.tmpFilename}`,
                permissions: {
                    comment: true,
                    download: true,
                    edit: this.editMode,
                    print: true,
                    deleteCommentAuthorOnly: true,
                    editCommentAuthorOnly: true,
                    review: false,
                    commentGroups: {
                        edit: ['owner'],
                        remove: ['owner'],
                        view: ''
                    },
                }
            },
            editorConfig: {
                callbackUrl: `${this.appUrl}/rest/onlyOfficeCallback`,
                lang: this.translate.instant('lang.language'),
                region: this.translate.instant('lang.langISO'),
                mode: 'edit',
                customization: {
                    chat: false,
                    comments: true,
                    compactToolbar: false,
                    feedback: false,
                    forcesave: false,
                    goback: false,
                    hideRightMenu: true,
                    showReviewChanges: false,
                    zoom: -2,
                },
                user: {
                    id: this.headerService.user.id.toString(),
                    name: `${this.headerService.user.firstname} ${this.headerService.user.lastname}`,
                    group: 'owner'
                },
            },
        };
    }

    isLocked(): boolean {
        return this.isSaving;
    }

    getFile(): Observable<any> {
        // return this.file;
        this.getDocument();
        return this.eventAction.asObservable();
    }

    ngOnDestroy(): void {
        this.eventAction.complete();
    }

    openFullscreen(): void {
        $('iframe[name=\'frameEditor\']').css('top', '0px');
        $('iframe[name=\'frameEditor\']').css('left', '0px');

        if (!this.fullscreenMode) {
            this.formatAppToolsCss('fullscreen');
            this.triggerModeModified.emit(true);
            if (this.headerService.sideNavLeft !== null) {
                this.headerService.sideNavLeft.close();
            }
            $('iframe[name=\'frameEditor\']').css('position', 'fixed');
            $('iframe[name=\'frameEditor\']').css('z-index', '2');
        } else {
            this.formatAppToolsCss('default');
            this.triggerModeModified.emit(false);
            if (this.headerService.sideNavLeft !== null && !this.headerService.hideSideBar) {
                this.headerService.sideNavLeft.open();
            }
            $('iframe[name=\'frameEditor\']').css('position', 'initial');
            $('iframe[name=\'frameEditor\']').css('z-index', '1');
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
                    appTools.style.display = 'none';
                    appTools.style.transition =  'all 0.5s';
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
