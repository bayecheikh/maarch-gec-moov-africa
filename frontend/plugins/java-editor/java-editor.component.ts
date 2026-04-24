import { Component, EventEmitter, Input, OnDestroy, OnInit, Output } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { catchError, filter, finalize, tap } from 'rxjs/operators';
import { TranslateService } from '@ngx-translate/core';
import { HeaderService } from '@service/header.service';
import { NotificationService } from '@service/notification/notification.service';
import { Observable, of } from 'rxjs';
import { JavaEditorOptionsInterface } from "@models/editor-manager.model";

@Component({
    selector: 'app-java-editor',
    templateUrl: 'java-editor.component.html',
    styleUrls: [
        'java-editor.component.scss'
    ],
})
export class JavaEditorComponent implements OnInit, OnDestroy {

    @Input() file: any = {};
    @Input() params: JavaEditorOptionsInterface;
    @Input() hideCancelButton: boolean = false;
    @Input() primaryColor: boolean = false;
    @Input() unannotatedVersion: boolean = true;

    @Output() triggerCloseEditor = new EventEmitter<boolean>();
    @Output() triggerModifiedDocument = new EventEmitter<string>();

    loading: boolean = true;
    isSaving: boolean = false;
    editInProgress: boolean = false;

    intervalLockFile: any;

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        private notify: NotificationService,
        public headerService: HeaderService) { }

    ngOnInit(): void {
        this.initJNLP();
    }

    ngOnDestroy(): void {
        clearInterval(this.intervalLockFile);
    }

    initJNLP(): void {
        this.loading = true;

        const body = {
            ...this.params,
            unannotatedVersion: this.unannotatedVersion
        };

        this.http.post('../rest/jnlp', body).pipe(
            tap((data: any) => {
                this.editInProgress = true;
                window.location.href = '../rest/jnlp/' + data.generatedJnlp;
                this.checkLockFile(data.jnlpUniqueId, this.file.format);
                this.loading = false;
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                this.triggerCloseEditor.emit();
                return of(false);
            })
        ).subscribe();
    }

    checkLockFile(id: string, extension: string): void {
        this.intervalLockFile = setInterval(() => {
            this.http.get('../rest/jnlp/lock/' + id).pipe(
                tap(async (data: any) => {
                    if (!data.lockFileFound) {
                        clearInterval(this.intervalLockFile);
                        await this.loadTmpFile(`${data.fileTrunk}.${extension}`);
                        this.triggerModifiedDocument.emit();
                        this.editInProgress = false;
                    }
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    clearInterval(this.intervalLockFile);
                    this.triggerCloseEditor.emit();
                    return of(false);
                })
            ).subscribe();
        }, 1000);
    }

    loadTmpFile(filenameOnTmp: string): Promise<boolean> {
        this.isSaving = true;
        return new Promise((resolve) => {
            return this.http.get(`../rest/convertedFile/${filenameOnTmp}?convert=true`)
                .pipe(
                    filter((data: any) => data.encodedResource),
                    tap((data: any) => {
                        this.file = {
                            name: filenameOnTmp,
                            format: data.extension,
                            type: data.type,
                            contentMode: 'base64',
                            content: data.encodedResource,
                        };
                        resolve(true);
                    }),
                    finalize(() => this.isSaving = false),
                    catchError((err: any) => {
                        this.notify.handleSoftErrors(err);
                        resolve(false);
                        return of(false);
                    })
                ).subscribe();
        });
    }

    getFile(): Observable<any> {
        return of(this.file);
    }

    closeEditor(): void {
        this.triggerCloseEditor.emit(true);
    }
}
