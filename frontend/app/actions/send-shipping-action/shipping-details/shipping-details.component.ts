import { Component, EventEmitter, Input, OnInit, Output } from "@angular/core";
import { AttachListInterface } from "@models/send-shipping-action.model";
import { TranslateService } from "@ngx-translate/core";
import { catchError, tap } from "rxjs/operators";
import { of } from "rxjs";
import { HttpClient } from "@angular/common/http";
import { NotificationService } from "@service/notification/notification.service";
import { FunctionsService } from "@service/functions.service";

@Component({
    selector: 'app-shipping-details',
    templateUrl: './shipping-details.component.html',
    styleUrls: ['./shipping-details.component.scss']
})

export class ShippingDetailsComponent implements OnInit {
    @Input() shippingDetails: AttachListInterface = null;
    @Input() digitalPackageLength: number = null;

    @Output() closeSidenav: EventEmitter<void> = new EventEmitter<void>();
    @Output() showThumbnail: EventEmitter<{ type: string, resId: number}> = new EventEmitter<{ type: string, resId: number}>();
    @Output() hideThumbnail: EventEmitter<void> = new EventEmitter<void>();

    loading: boolean = true;

    formattedShippingDetails: {
        recipientId: number,
        contactLabel: string,
        documents: {
            title: string,
            type: string,
            chrono: string,
            res_id: number
        }[]
    }[] = [];

    constructor(
        public functions: FunctionsService,
        private translate: TranslateService,
        private http: HttpClient,
        private notifications: NotificationService
    ) {
    }
    
    ngOnInit(): void {
        this.formattedShippingDetails =  this.formatShippingDetails();
    }

    /**
     * Formats the shipping details into a standardized structure.
     *
     * This method processes the `shippingDetails` property, extracting relevant information about recipients,
     * their associated documents, and other contact details. The resulting structure organizes this data
     * to be easily consumable elsewhere.
     *
     * @return {Array<{recipientId: number, contactLabel: string, documents: {title: string, type: string, chrono: string, res_id: number}[]}>}
     *         An array of objects where each object contains the recipient's ID, a contact label, and an array of associated documents
     *         (with attributes such as title, type, chrono, and resource ID).
     */
    formatShippingDetails(): {
        recipientId: number,
        contactLabel: string,
        documents: {
            title: string,
            type: string,
            chrono: string,
            res_id: number
        }[]
    }[] {
        const arrayToSend: any[] = [];
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        if (!this.functions.empty(this.shippingDetails)) {
            // eslint-disable-next-line @typescript-eslint/no-unused-vars
            Object.entries(this.shippingDetails).forEach(([resId, contacts]) => {
                Object.entries(contacts).forEach(([contactId, documents]) => {
                    const contactLabel = documents[0].contactLabel;
                    const recipientId: number = parseInt(contactId, 10);
                    arrayToSend.push({
                        recipientId,
                        contactLabel,
                        documents
                    });
                });
            });
        }

        this.loading = false;

        return arrayToSend;
    }

    getType(type: string): string {
        return type === 'mail' ? this.translate.instant('lang.mainDocument') : this.translate.instant('lang.attachment');
    }

    viewThumbnail(document: { type: string, res_id: number}): void {
        const type: string = document.type === 'mail' ? 'resources' : 'attachments';
        this.showThumbnail.emit({ resId: document.res_id, type: type });
    }

    closeThumbnail(): void {
        this.hideThumbnail.emit();
    }

    viewDocument(document: { type: string, res_id: number, chrono: string}): void {
        const type: string = document.type === 'mail' ? 'resources' : 'attachments';
        this.http.get(`../rest/${type}/${document.res_id}/content?mode=view`, { responseType: 'blob' }).pipe(
            tap((data: any) => {
                const file = new Blob([data], { type: 'application/pdf' });
                const fileURL = URL.createObjectURL(file);
                const newWindow = window.open();
                newWindow.document.write(`<iframe style="width: 100%;height: 100%;margin: 0;padding: 0;" src="${fileURL}" frameborder="0" allowfullscreen></iframe>`);
                newWindow.document.title = document.chrono;
            }),
            catchError((err: any) => {
                this.notifications.handleBlobErrors(err);
                return of(false);
            })
        ).subscribe();
    }
}