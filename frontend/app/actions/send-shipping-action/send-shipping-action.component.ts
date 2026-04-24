import { Component, Inject, OnInit, ViewChild } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';
import { NotificationService } from '@service/notification/notification.service';
import {
    MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA,
    MatLegacyDialogRef as MatDialogRef
} from '@angular/material/legacy-dialog';
import { HttpClient } from '@angular/common/http';
import { NoteEditorComponent } from '../../notes/note-editor.component';
import { catchError, finalize, tap } from 'rxjs/operators';
import { of } from 'rxjs';
import { FunctionsService } from '@service/functions.service';
import { SessionStorageService } from '@service/session-storage.service';
import { AttachListInterface, AttachListProperties, ShippingConfigInterface } from '@models/send-shipping-action.model';
import { DatasActionSendInterface } from "@models/actions.model";
import { AppService } from "@service/app.service";
import { MatSidenav } from "@angular/material/sidenav";

@Component({
    templateUrl: 'send-shipping-action.component.html',
    styleUrls: ['send-shipping-action.component.scss'],
})
export class SendShippingActionComponent implements OnInit {

    @ViewChild('noteEditor', { static: false }) noteEditor: NoteEditorComponent;
    @ViewChild('shippingDetailsSidenav', { static: false }) public shippingDetailsSidenav: MatSidenav;

    shippings: ShippingConfigInterface[] = [{
        id: null,
        label: '',
        description: '',
        options: {
            shapingOptions: [],
            sendMode: '',
            senderLabel: ''
        },
        fee: {
            totalShippingFee: 0
        },
        account: {
            id: '',
            password: ''
        },
    }];

    currentShipping: ShippingConfigInterface = null;

    entitiesList: string[] = [];

    /**
     * Ex :
     * {
     100{
     1: [
     {
     res_id: 100,
     chrono: 'MAARCH/2025A/24',
     title: 'PV',
     type: 'mail',
     docserver_id: 'FASTHD_MAN',
     integrations: { inShipping: true }
     },
     {
     res_id: 200,
     res_id_master: 100,
     chrono: 'MAARCH/2025A/25',
     title: 'Document 1',
     type: 'attachment',
     docserver_id: 'FASTHD_DOC1',
     integrations: { inShipping: false }
     }
     ],
     2: [
     {
     res_id: 201,
     res_id_master: 101,
     chrono: 'MAARCH/2025A/27',
     title: 'Document 2',
     type: 'attachment',
     docserver_id: 'FASTHD_ATTACH3',
     integrations: { inShipping: true }
     },
     {
     res_id: 202,
     res_id_master: 101,
     chrono: 'MAARCH/2025A/29',
     title: 'Document 3',
     type: 'attachment',
     docserver_id: 'FASTHD_ATTACH3',
     integrations: { inShipping: true }
     },
     ],
     3: [
     {
     res_id: 102,
     chrono: 'MAARCH/2025A/30',
     title: 'Courrier de test',
     type: 'mail',
     docserver_id: 'FASTHD_MAN',
     integrations: { inShipping: true }
     }
     ]
     }
     };
     */

    attachList: AttachListInterface = {};

    mailsNotSend: {
        resId: number,
        chrono: string,
        reason: string
    }[] = [];

    integrationsInfo: { inShipping: { icon: string } } = {
        inShipping: {
            icon: 'fas fa-shipping-fast'
        }
    };

    fatalError: { reason: string } = null;

    loading: boolean = false;
    invalidEntityAddress: boolean = false;
    canGoToNextRes: boolean = false;
    showToggle: boolean = false;
    inLocalStorage: boolean = false;

    thumbnailUrl: string = '';

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        public dialogRef: MatDialogRef<SendShippingActionComponent>,
        @Inject(MAT_DIALOG_DATA) public data: DatasActionSendInterface,
        public functions: FunctionsService,
        public appService: AppService,
        private notify: NotificationService,
        private sessionStorage: SessionStorageService
    ) {
    }

    async ngOnInit(): Promise<void> {
        this.loading = true;
        this.showToggle = this.data.additionalInfo.showToggle;
        this.canGoToNextRes = this.data.additionalInfo.canGoToNextRes;
        this.inLocalStorage = this.data.additionalInfo.inLocalStorage;
        await this.checkShipping();
        this.currentShipping = this.functions.empty(this.shippings) ? null : this.shippings[0];
    }

    onSubmit(): void {
        this.loading = true;
        if (this.data.resIds.length > 0) {
            this.sessionStorage.checkSessionStorage(this.inLocalStorage, this.canGoToNextRes, this.data);
            this.executeAction();
        }
    }

    checkShipping(sendMode: string = ''): Promise<boolean> {
        this.loading = true;
        return new Promise((resolve) => {
            this.http.post(`../rest/resourcesList/users/${this.data.userId}/groups/${this.data.groupId}/baskets/${this.data.basketId}/actions/${this.data.action.id}/checkShippings`, {
                resources: this.data.resIds,
                sendMode: sendMode
            }).pipe(
                tap((data: any) => {
                    if (!this.functions.empty(data.fatalError)) {
                        this.fatalError = data;
                        this.shippings = [];
                    } else {
                        this.shippings = data.shippingTemplates;
                        this.mailsNotSend = data.canNotSend;
                        this.entitiesList = data.entities;
                        this.attachList = data.resources;
                        this.invalidEntityAddress = data.invalidEntityAddress;
                    }
                    resolve(true);
                }),
                finalize(() => {
                    this.currentShipping = this.functions.empty(this.shippings) ? null : this.shippings[0];
                    this.loading = false;
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    this.dialogRef.close();
                    resolve(false);
                    return of(false);
                })
            ).subscribe();
        })
    }

    executeAction(): void {
        const realResSelected: number[] = [];

        // Check if the attachment list is not empty
        if (!this.functions.empty(this.attachList)) {
            // Iterate through each master resource ID
            Object.keys(this.attachList).forEach((resIdMaster: string) => {
                // Iterate through each contact ID for this master resource
                Object.keys(this.attachList[resIdMaster]).forEach((contactId: string) => {
                    // Process 'mail' type attachments
                    // Filter mails with 'inShipping' integration and add their res_id to the selection
                    realResSelected.push(
                        ...this.attachList[resIdMaster][contactId]
                            .filter((attachment: AttachListProperties) => {
                                const integrations = this.functions.safeParseJson(attachment.integrations) as {
                                    inShipping: boolean
                                };
                                return attachment.type === 'mail' && integrations?.inShipping;
                            })
                            .map((item: AttachListProperties) => item.res_id)
                    );

                    // Process 'attachment' type attachments
                    // For attachments, simply collect their res_id_master values
                    realResSelected.push(
                        ...this.attachList[resIdMaster][contactId]
                            .filter((attachment: AttachListProperties) =>
                                // Only include proper attachment types with a valid res_id_master
                                attachment.type === 'attachment' && attachment.res_id_master)
                            .map((item: AttachListProperties) => item.res_id_master)
                    );
                });
            });
        }

        this.http.put(this.data.processActionRoute, {
            resources: realResSelected,
            data: { shippingTemplateId: this.currentShipping.id },
            note: this.noteEditor.getNote()
        }).pipe(
            tap((data: any) => {
                if (data && data.errors != null) {
                    this.notify.error(data.errors);
                } else {
                    this.dialogRef.close(realResSelected);
                }
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    toggleIntegration(integrationId: string): void {
        this.http.put('../rest/resourcesList/integrations', {
            resources: this.data.resIds,
            integrations: { [integrationId]: !this.data.resource.integrations[integrationId] }
        }).pipe(
            tap(async () => {
                this.data.resource.integrations[integrationId] = !this.data.resource.integrations[integrationId];
                this.currentShipping = null;
                await this.checkShipping();
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    /**
     * Determines whether the current shipping configuration is valid.
     *
     * @returns {boolean} `true` if the shipping configuration is valid, otherwise `false`.
     *
     * The method performs the following checks:
     * - Ensures that the `currentShipping` object is not empty.
     * - Verifies that the total number of valid attachments is greater than zero and
     *   meets or exceeds the number of mails that have not been sent.
     * - Checks if the `sendMode` of the current shipping is one of the valid modes
     *   (`digital_registered_mail` or `digital_registered_mail_with_AR`).
     * - If the `sendMode` requires a valid address, ensures that the entity address is valid.
     */
    isValid(): boolean {
        if (this.functions.empty(this.currentShipping)) {
            return false;
        }

        // Calculate total documents in attachList object
        let totalAttachments: number = 0;

        if (this.attachList) {
            // For each resIdMaster
            Object.keys(this.attachList).forEach(resIdMaster => {
                // For each contactId (recipient)
                Object.keys(this.attachList[resIdMaster]).forEach(contactId => {
                    // Filter documents with inShipping=true for the recipients
                    const inShippingDocs = this.attachList[resIdMaster][contactId].filter(
                        (attachment: AttachListProperties) => {
                            const integrations = this.functions.safeParseJson(attachment.integrations) as {
                                inShipping: boolean
                            };
                            return integrations?.inShipping === true;
                        }
                    )
                    totalAttachments = totalAttachments + inShippingDocs.length;
                });
            });
        }
        const isValidAttachList: boolean = totalAttachments > 0 && totalAttachments > this.mailsNotSend.length;
        const sendMode: string = this.currentShipping?.options?.sendMode ?? '';
        const requiresValidAddress: boolean = ['digital_registered_mail', 'digital_registered_mail_with_AR'].indexOf(sendMode) > -1;

        return requiresValidAddress ? isValidAttachList && !this.invalidEntityAddress : isValidAttachList;
    }

    /**
     * Calculates the total number of digital packages to be sent.
     * Counts the number of contacts across all resource IDs in the attachList.
     * Each combination of resource ID and contact represents a separate digital package.
     *
     * @returns {number} Total count of digital packages
     */
    getDigitalPackageLength(): number {
        return Object.values(this.attachList)
            .reduce((totalCount, contactMap) => totalCount + Object.keys(contactMap).length, 0);
    }

    async onShippingChange(value: ShippingConfigInterface) {
        const sendMode: string = value?.options?.sendMode ?? '';
        await this.checkShipping(sendMode).then(() => {
            this.currentShipping = this.shippings.find(shipping => shipping.id === value.id);
        });
    }

    viewThumbnail(value: { resId: number, type: string}) {
        this.thumbnailUrl = `../rest/${value.type}/${value.resId}/thumbnail`;
        $('#thumbnailDoc').show();
    }

    closeThumbnail(): void {
        this.thumbnailUrl = null;
        $('#thumbnailDoc').hide();

    }
}
