import { Component, Inject, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { NotificationService } from '@service/notification/notification.service';
import {
    MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA,
    MatLegacyDialog as MatDialog,
    MatLegacyDialogRef as MatDialogRef
} from '@angular/material/legacy-dialog';
import { catchError, finalize, map, tap } from 'rxjs/operators';
import { FunctionsService } from '@service/functions.service';
import { ContactService } from '@service/contact.service';
import { AppService } from '@service/app.service';
import { PrivilegeService } from '@service/privileges.service';
import { HeaderService } from '@service/header.service';
import { of } from 'rxjs';
import { FullDatePipe } from '@plugins/fullDate.pipe';
import { SortPipe } from '@plugins/sorting.pipe';
import { ShippingAttachmentInterface, ShippingHistoryInterface, ShippingStatusesInterface } from "@models/shipping.model";
import { DatasActionSendInterface } from "@models/actions.model";

@Component({
    templateUrl: 'shipping-modal.component.html',
    styleUrls: ['shipping-modal.component.scss'],
    providers: [ContactService, AppService, FullDatePipe, SortPipe],
})

export class ShippingModalComponent implements OnInit {

    loading: boolean = true;
    downloadingProofs: boolean = false;

    shippingAttachments: ShippingAttachmentInterface [] = [];
    shippingHistory: ShippingHistoryInterface[] = [];
    statuses: ShippingStatusesInterface[] = [];

    depositProof: {
        attachmentType: string,
        resId: number,
        title: string
    } = null;

    creationDate: string = '';
    sendDate: string = '';

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        public dialog: MatDialog,
        public dialogRef: MatDialogRef<ShippingModalComponent>,
        public functions: FunctionsService,
        public privilegeService: PrivilegeService,
        public headerService: HeaderService,
        public translate: TranslateService,
        @Inject(MAT_DIALOG_DATA) public data: DatasActionSendInterface,
        private fullDate: FullDatePipe,
        private sortPipe: SortPipe
    ) {
    }

    async ngOnInit(): Promise<void> {
        if (this.data.shippingData?.sendMode !== 'ere') {
            await Promise.all([
                this.getStatus(),
                this.getAttachments(),
                this.getShippingHistory(),
            ]).then(() => {
                this.setValues();
                this.loading = false;
            }).catch((err) => {
                this.notify.handleSoftErrors(err);
                this.loading = false;
            })
        } else {
            this.setValues();
            this.loading = false;
        }
    }

    setValues(): void {
        this.creationDate = this.formatDate(this.data.shippingData.creationDate);
        this.sendDate = this.formatDate(this.data.shippingData.sendDate);
        this.data.shippingData.recipients.forEach((element: any, index: number) => {
            this.data.shippingData.recipients[index] = element.filter((item: any) => item !== '');
        });
    }

    getAttachments(): Promise<boolean> {
        return new Promise((resolve) => {
            this.http.get(`../rest/shippings/${this.data.shippingData.sendingId}/attachments`).pipe(
                tap((data: any) => {
                    if (data.attachments.length > 0) {
                        this.depositProof = data.attachments.find((item: any) => item.attachmentType === 'shipping_deposit_proof');
                        this.shippingAttachments = data.attachments.filter((item: any) => item.attachmentType === 'shipping_acknowledgement_of_receipt');
                    }
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    getShippingHistory(): Promise<boolean> {
        return new Promise((resolve) => {
            this.http.get(`../rest/shippings/${this.data.shippingData.sendingId}/history`).pipe(
                tap((data: any) => {
                    if (data.history.length > 0) {
                        this.shippingHistory = data.history.filter((history: any) => ['ON_DEPOSIT_PROOF_RECEIVED', 'ON_ACKNOWLEDGEMENT_OF_RECEIPT_RECEIVED'].indexOf(history.eventType) === -1);
                        this.shippingHistory = this.sortPipe.transform(this.shippingHistory, 'eventDate').reverse();
                    }
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    downloadFile(resId: number): void {
        const downloadLink = document.createElement('a');
        this.http.get(`../rest/attachments/${resId}/originalContent?mode=base64`).pipe(
            tap((data: any) => {
                downloadLink.href = `data:${data.mimeType};base64,${data.encodedDocument}`;
                downloadLink.setAttribute('download', data.filename);
                document.body.appendChild(downloadLink);
                downloadLink.click();
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();

    }

    getStatus(): Promise<boolean> {
        return new Promise((resolve) => {
            this.http.get('../rest/statuses').pipe(
                map((data: any) => data.statuses),
                tap((data: ShippingStatusesInterface[]) => {
                    this.statuses = data;
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    setStatus(status: string): string {
        return this.statuses.find((element: ShippingStatusesInterface) => element.id === status).label_status;
    }

    formatDate(date: string): string {
        return this.fullDate.transform(new Date(date).toString());
    }

    downloadProof(proof: string): void {
        this.downloadingProofs = true;
        const target: string = proof === 'depositProof' ? 'downloadDepositProof' : 'downloadProofOfReceipt';
        this.http.get(`../rest/shippings/${this.data.shippingData.sendingId}/recipient/${this.data.shippingData.recipientId}/${target}`).pipe(
            tap((data: { encodedDocument: string, filename: string }) => {
                if (!this.functions.empty(data.encodedDocument)) {
                    const downloadLink = document.createElement('a');
                    downloadLink.href = `data:application/pdf;base64,${data.encodedDocument}`;
                    downloadLink.setAttribute('download', `${data.filename}.pdf`);
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                }
            }),
            finalize(() => this.downloadingProofs = false),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }
}
