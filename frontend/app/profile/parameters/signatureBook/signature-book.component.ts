import { Component, Input, NgZone } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { NotificationService } from '@service/notification/notification.service';
import { TranslateService } from '@ngx-translate/core';
import { FunctionsService } from '@service/functions.service';
import { HeaderService } from '@service/header.service';
import { ConfirmComponent } from '@plugins/modal/confirm.component';
import { MatLegacyDialog as MatDialog } from '@angular/material/legacy-dialog';
import { catchError, exhaustMap, filter, tap } from 'rxjs/operators';
import { of } from 'rxjs';
import { ExternalSignatoryBookManagerService } from '@service/externalSignatoryBook/external-signatory-book-manager.service';

@Component({
    selector: 'app-signature-book',
    templateUrl: './signature-book.component.html',
    styleUrls: ['./signature-book.component.scss'],
    providers: [ExternalSignatoryBookManagerService]
})

export class MySignatureBookComponent {

    @Input() signatureModel: any;
    @Input() userSignatures: any[];
    @Input() externalIdMaarchParapheur: any;
    @Input() loadingSign: boolean;

    highlightMe: boolean = false;

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        public functionsService: FunctionsService,
        public headerService: HeaderService,
        public dialog: MatDialog,
        public externalSignatoryBook: ExternalSignatoryBookManagerService,
        private zone: NgZone,
        private notify: NotificationService
    ){
        window['angularProfileComponent'] = {
            componentAfterUpload: (base64Content: any) => this.processAfterUpload(base64Content),
        };
    }

    clickOnUploader(id: string): void {
        $('#' + id).click();
    }

    processAfterUpload(b64Content: any) {
        this.zone.run(() => this.resfreshUpload(b64Content));
    }

    resfreshUpload(b64Content: any): void {
        if (this.signatureModel.size <= 2000000) {
            this.signatureModel.base64 = b64Content.replace(/^data:.*?;base64,/, '');
            this.signatureModel.base64ForJs = b64Content;
        } else {
            this.signatureModel.name = '';
            this.signatureModel.size = 0;
            this.signatureModel.type = '';
            this.signatureModel.base64 = '';
            this.signatureModel.base64ForJs = '';

            this.notify.error('Taille maximum de fichier dépassée (2 MB)');
        }
    }

    uploadSignatureTrigger(fileInput: any): void {
        if (fileInput.target.files && fileInput.target.files[0]) {
            const reader = new FileReader();

            this.signatureModel.name = fileInput.target.files[0].name;
            this.signatureModel.size = fileInput.target.files[0].size;
            this.signatureModel.type = fileInput.target.files[0].type;
            if (this.signatureModel.label == '') {
                this.signatureModel.label = this.signatureModel.name;
            }

            reader.readAsDataURL(fileInput.target.files[0]);

            reader.onload = (value: any) => {
                window['angularProfileComponent'].componentAfterUpload(value.target.result);
                this.submitSignature();
            };

        }
    }

    /**
     * Handles the dragover event triggered when a draggable element is dragged over the target element.
     * Prevents the default behavior and stops propagation of the event to higher-level elements.
     * Additionally, modifies the state to visually indicate that the element is a valid drop target.
     *
     * @param {DragEvent} event - The dragover event object containing details about the dragged content and target element.
     * @return {void} Does not return a value.
     */
    onDragOver(event: DragEvent): void {
        event.preventDefault();
        event.stopPropagation();
        this.highlightMe = true;
    }

    /**
     * Handles the drag-and-drop upload of a digital signature file.
     *
     * @param {DragEvent} event The drag event containing the dropped files.
     * @return {void} No return value.
     */
    dndUploadSignature(event: DragEvent): void {
        event.preventDefault();
        event.stopPropagation();

        if (event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files[0]) {
            const reader = new FileReader();
            this.signatureModel.name = event.dataTransfer.files[0].name;
            this.signatureModel.size = event.dataTransfer.files[0].size;
            this.signatureModel.type = event.dataTransfer.files[0].type;

            if (this.signatureModel.label == '') {
                this.signatureModel.label = this.signatureModel.name;
            }

            reader.readAsDataURL(event.dataTransfer.files[0]);
            reader.onload = (value: any) => {
                window['angularProfileComponent'].componentAfterUpload(value.target.result);
                this.submitSignature();
            };
        }

        this.highlightMe = false;
    }

    submitSignature() {
        this.http.post('../rest/users/' + this.headerService.user.id + '/signatures', this.signatureModel).pipe(
            tap((data: any) => {
                this.userSignatures = data.signatures;
                this.signatureModel = {
                    base64: '',
                    base64ForJs: '',
                    name: '',
                    type: '',
                    size: 0,
                    label: '',
                };
                this.notify.success(this.translate.instant('lang.signatureAdded'));
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    updateSignature(signature: any) {
        this.http.put('../rest/users/' + this.headerService.user.id + '/signatures/' + signature.id, { 'label': signature.signature_label })
            .pipe(
                tap(() => {
                    this.notify.success(this.translate.instant('lang.signatureUpdated'));
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                }))
            .subscribe();
    }

    deleteSignature(id: number) {
        const dialogRef = this.dialog.open(ConfirmComponent, { panelClass: 'maarch-modal', autoFocus: false, disableClose: true, data: { title: `${this.translate.instant('lang.confirmDeleteSignature')}`, msg: this.translate.instant('lang.confirmAction') } });
        dialogRef.afterClosed().pipe(
            filter((data: string) => data === 'ok'),
            exhaustMap(() => this.http.delete('../rest/users/' + this.headerService.user.id + '/signatures/' + id)),
            tap((data: any) => {
                this.headerService.user.signatures = data.signatures;
                this.userSignatures = data.signatures;
                this.notify.success(this.translate.instant('lang.signatureDeleted'));
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    async synchronizeSignatures() {
        this.loadingSign = true;
        await this.externalSignatoryBook.synchronizeSignatures(this.headerService.user).finally(() => this.loadingSign = false);
    }

}
