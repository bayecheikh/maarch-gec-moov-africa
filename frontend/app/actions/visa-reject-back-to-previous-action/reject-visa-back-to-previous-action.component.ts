import { Component, OnInit, Inject, ViewChild } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';
import { NotificationService } from '@service/notification/notification.service';
import { MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA, MatLegacyDialogRef as MatDialogRef } from '@angular/material/legacy-dialog';
import { HttpClient } from '@angular/common/http';
import { NoteEditorComponent } from '../../notes/note-editor.component';
import { tap, finalize, catchError } from 'rxjs/operators';
import { of } from 'rxjs';
import { VisaWorkflowComponent } from '../../visa/visa-workflow.component';
import { FunctionsService } from '@service/functions.service';
import { SignatureBookService } from '@appRoot/signatureBook/signature-book.service';
import { mapVisaCircuitActionDataToSend } from '@appRoot/signatureBook/signature-book.utils';
import { DatasActionSendInterface } from "@models/actions.model";

@Component({
    templateUrl: 'reject-visa-back-to-previous-action.component.html',
    styleUrls: ['reject-visa-back-to-previous-action.component.scss'],
})
export class RejectVisaBackToPrevousActionComponent implements OnInit {

    @ViewChild('noteEditor', { static: true }) noteEditor: NoteEditorComponent;
    @ViewChild('appVisaWorkflow', { static: false }) appVisaWorkflow: VisaWorkflowComponent;

    loading: boolean = false;

    resourcesWarnings: any[] = [];
    resourcesErrors: any[] = [];

    noResourceToProcess: boolean = null;

    constructor(
        @Inject(MAT_DIALOG_DATA) public data: DatasActionSendInterface,
        public translate: TranslateService,
        public http: HttpClient,
        public dialogRef: MatDialogRef<RejectVisaBackToPrevousActionComponent>,
        public functions: FunctionsService,
        public signatureBookService: SignatureBookService,
        private notify: NotificationService,
    ) { }

    async ngOnInit() {
        this.loading = true;
        await this.checkRejectVisaBackToPrevious();
        this.loading = false;
    }

    checkRejectVisaBackToPrevious() {
        this.resourcesErrors = [];
        this.resourcesWarnings = [];

        return new Promise((resolve) => {
            this.http.post('../rest/resourcesList/users/' + this.data.userId + '/groups/' + this.data.groupId + '/baskets/' + this.data.basketId + '/actions/' + this.data.action.id + '/checkRejectVisa', { resources: this.data.resIds }).pipe(
                tap((data: any) => {
                    if (!this.functions.empty(data.resourcesInformations.warning)) {
                        this.resourcesWarnings = data.resourcesInformations.warning;
                    }

                    if (!this.functions.empty(data.resourcesInformations.error)) {
                        this.resourcesErrors = data.resourcesInformations.error;
                        this.noResourceToProcess = this.resourcesErrors.length === this.data.resIds.length;
                    }
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

    onSubmit() {
        this.loading = true;
        this.executeAction();
    }

    async executeAction() {
        const realResSelected: number[] = this.data.resIds.filter(
            (resId: number) => this.resourcesErrors.map((resErr) => resErr.res_id).indexOf(resId) === -1
        );
        this.http.put(this.data.processActionRoute,
            {
                resources : realResSelected,
                note : this.noteEditor.getNote(),
                data: await mapVisaCircuitActionDataToSend(await this.signatureBookService.formatDataToSend(this.signatureBookService.getDocsToSign()))
            }).pipe(
            tap(() => {
                this.dialogRef.close(this.data.resIds);
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    isValidAction() {
        return !this.noResourceToProcess;
    }
}
