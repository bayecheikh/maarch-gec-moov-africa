import { Component, HostListener, OnDestroy, ViewChild } from '@angular/core';
import { ActionsService } from '@appRoot/actions/actions.service';
import { HttpClient } from '@angular/common/http';
import { ActivatedRoute, Router } from '@angular/router';
import { NotificationService } from '@service/notification/notification.service';
import { catchError, filter, of, Subscription, tap } from 'rxjs';
import { MatDrawer } from '@angular/material/sidenav';
import { Attachment } from '@models/attachment.model';
import { MessageActionInterface } from '@models/actions.model';
import { SignatureBookService } from './signature-book.service';
import { ResourcesListComponent } from './resourcesList/resources-list.component';
import { TranslateService } from '@ngx-translate/core';
import { FunctionsService } from '@service/functions.service';
import { UserStampInterface } from '@models/user-stamp.model';
import { SelectedAttachment, SignatureBookDataReturnInterface } from "@models/signature-book.model";
import { SignatureBookActionsComponent } from "@appRoot/signatureBook/actions/signature-book-actions.component";
import { MaarchSbContentComponent } from '@appRoot/signatureBook/content/signature-book-content.component';
import { mapAttachment } from './signature-book.utils';
import { MaarchSbTabsComponent } from './tabs/signature-book-tabs.component';
import { AlertComponent } from "@plugins/modal/alert.component";
import { MatLegacyDialog as MatDialog, MatLegacyDialogRef as MatDialogRef } from "@angular/material/legacy-dialog";

@Component({
    templateUrl: 'signature-book.component.html',
    styleUrls: ['signature-book.component.scss']
})
export class SignatureBookComponent implements OnDestroy {

    @ViewChild('drawerStamps', { static: true }) stampsPanel: MatDrawer;
    @ViewChild('drawerResList', { static: false }) drawerResList: MatDrawer;
    @ViewChild('resourcesList', { static: false }) resourcesList: ResourcesListComponent;
    @ViewChild('actionsList', { static: false }) actionsList: SignatureBookActionsComponent;
    @ViewChild('signatureBookContent', { static: false }) signatureBookContent: MaarchSbContentComponent;
    @ViewChild('maarchSbTabsLeft', { static: false }) maarchSbTabsLeft: MaarchSbTabsComponent;
    @ViewChild('maarchSbTabsRight', { static: false }) maarchSbTabsRight: MaarchSbTabsComponent;

    loadingAttachments: boolean = true;
    loadingDocsToSign: boolean = true;
    loading: boolean = true;

    resId: number = 0;
    basketId: number;
    groupId: number;
    userId: number;

    attachments: Attachment[] = [];
    docsToSign: Attachment[] = [];

    subscription: Subscription;
    defaultUserStamp: UserStampInterface;

    processActionSubscription: Subscription;

    canGoToNext: boolean = false;
    canGoToPrevious: boolean = false;
    hidePanel: boolean = true;

    dialogRef: MatDialogRef<AlertComponent>;

    constructor(
        public http: HttpClient,
        public signatureBookService: SignatureBookService,
        public translate: TranslateService,
        public functions: FunctionsService,
        public dialog: MatDialog,
        private route: ActivatedRoute,
        private router: Router,
        private notify: NotificationService,
        private actionsService: ActionsService,
    ) {

        this.initParams();

        this.subscription = this.actionsService.catchActionWithData().pipe(
            filter((data: MessageActionInterface) => data.id === 'selectedStamp'),
            tap(() => {
                this.stampsPanel?.close();
            })
        ).subscribe();

        // Event after process action
        this.processActionSubscription = this.actionsService.catchAction().subscribe(() => {
            this.processAfterAction();
        });
    }

    @HostListener('window:unload', ['$event'])
    async unloadHandler(): Promise<void> {
        this.unlockResource();
        this.signatureBookService.resetSelection();
    }

    initParams(): void {
        this.route.params.subscribe(async params => {
            this.resetValues();

            this.resId = parseInt(params['resId']);
            this.basketId = parseInt(params['basketId']);
            this.groupId = parseInt(params['groupId']);
            this.userId = parseInt(params['userId']);

            if (!this.signatureBookService.config.isNewInternalParaph) {
                this.router.navigate([`/signatureBook/users/${this.userId}/groups/${this.groupId}/baskets/${this.basketId}/resources/${this.resId}`]);
                return;
            }

            if (this.resId !== undefined) {
                this.actionsService.lockResource(this.userId, this.groupId, this.basketId, [this.resId]);
                this.setNextPrev();
                await this.initDocuments();
            } else {
                this.router.navigate(['/home']);
            }
        });
    }

    setNextPrev() {
        const index: number = this.signatureBookService.resourcesListIds.indexOf(this.resId);
        this.canGoToNext = this.signatureBookService.resourcesListIds[index + 1] !== undefined;
        this.canGoToPrevious = this.signatureBookService.resourcesListIds[index - 1] !== undefined;
    }

    resetValues(): void {
        this.loading = true;
        this.loadingDocsToSign = true;
        this.loadingAttachments = true;

        this.attachments = [];
        this.signatureBookService.docsToSign = [];

        this.subscription?.unsubscribe();
    }

    async initDocuments(event = null): Promise<void> {
        await this.signatureBookService.initDocuments(this.userId, this.groupId, this.basketId, this.resId).then((data: SignatureBookDataReturnInterface) => {
            this.signatureBookService.canUpdateResources = data.canUpdateResources;
            this.signatureBookService.canAddAttachments = data.canAddAttachments;

            this.signatureBookService.selectedAttachment = new SelectedAttachment();
            this.signatureBookService.selectedDocToSign = new SelectedAttachment();

            this.signatureBookService.getCurrentUserIndex(this.resId);

            if (!this.signatureBookService.toolBarActive) {
                this.signatureBookService.toolBarActive = data.resourcesAttached.length === 0;
            }
            this.signatureBookService.docsToSign = data.resourcesToSign;

            this.signatureBookService.resourcesAttached = data.resourcesAttached;
            this.attachments = data.resourcesAttached;

            if (event === 'fetchVersions' && this.signatureBookContent?.position === 'right') {
                this.signatureBookContent?.fetchDocumentVersions();
            }

            this.loadingAttachments = false;
            this.loadingDocsToSign = false;
            this.loading = false;
        });
    }

    async processAfterAction() {
        if (this.canGoToNext) {
            this.goToNextUnlockedResource();
        } else {
            this.backToBasket();
        }
    }

    backToBasket(): void {
        const path = '/basketList/users/' + this.userId + '/groups/' + this.groupId + '/baskets/' + this.basketId;
        this.router.navigate([path]);
    }

    ngOnDestroy(): void {
        // unsubscribe to ensure no memory leaks
        this.subscription.unsubscribe();
        this.processActionSubscription.unsubscribe();
        this.unlockResource();
        this.signatureBookService.resetSelection();
        this.signatureBookService.docsToSignWithStamps = [];
    }

    async unlockResource(): Promise<void> {
        const path = '/basketList/users/' + this.userId + '/groups/' + this.groupId + '/baskets/' + this.basketId;
        this.actionsService.stopRefreshResourceLock();
        await this.actionsService.unlockResource(this.userId, this.groupId, this.basketId, [this.resId], path);
    }

    openResListPanel() {
        setTimeout(() => {
            this.drawerResList.open();
        }, 300);
    }

    showPanelContent() {
        this.resourcesList.initViewPort();
    }

    docsToSignUpdated(updatedDocsToSign: Attachment[]): void {
        this.docsToSign = updatedDocsToSign;
    }

    onRefreshVisaWorkflow(): void {
        this.actionsList.getBasketGroupActions();
    }

    goToNextUnlockedResource(): void {
        const index: number = this.signatureBookService.resourcesListIds.indexOf(this.resId);
        const c: number = this.signatureBookService.resourcesListIds.length;

        const tabRemainResources: number[] = [];
        for (let i = index + 1; i < c; i++) {
            tabRemainResources.push(this.signatureBookService.resourcesListIds[i]);
        }
        this.http.put(`../rest/resourcesList/users/${this.userId}/groups/${this.groupId}/baskets/${this.basketId}/locked`, { resources: tabRemainResources }).pipe(
            tap((data: any) => {
                let nextResId: number = -1;
                for (let j = 0; j < tabRemainResources.length; j++) {
                    if (data.resourcesToProcess.includes(tabRemainResources[j])) {
                        nextResId = tabRemainResources[j];
                        break;
                    }
                }

                if (nextResId !== -1) {
                    const path: string = `/signatureBookNew/users/${this.userId}/groups/${this.groupId}/baskets/${this.basketId}/resources/${nextResId}`;
                    this.router.navigate([path]);
                } else {
                    this.backToBasket();
                }
                this.unlockResource();
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    /**
     * Refreshes the attachments by loading the latest data and updating the `resourcesToSign` and `resourcesAttached` lists.
     * It ensures that new attachments are added while maintaining the integrity of existing ones.
     * If no documents were previously available, it selects the first one.
     *
     * @param attachments - The list of attachments to be refreshed, containing their resource IDs and signable status.
     */
    async refreshAttachments(attachments: { resId: number, signable: boolean }[]) {
        this.loading = true;

        // Load the latest attachments from the service
        const loadedAttachments: { resourcesToSign: Attachment[], resourcesAttached: Attachment[] } =
            await this.signatureBookService.loadAttachments(this.userId, this.groupId, this.basketId, this.resId);

        if (!this.functions.empty(loadedAttachments)) {
            // Separate attachments into signable and nonsignable groups
            const signableAttachments = attachments.filter((attachment) => attachment.signable);
            const nonSignableAttachments = attachments.filter((attachment) => !attachment.signable);

            // Extract resource IDs for signable and non-signable attachments
            const signableAttachmentsResId: number[] = signableAttachments.map((attachment) => attachment.resId);
            const nonSignableAttachmentsResId: number[] = nonSignableAttachments.map((attachment) => attachment.resId);

            // Filter and map signable attachments
            const resourcesToSign: Attachment[] = loadedAttachments?.resourcesToSign
                .filter((resource: Attachment) => signableAttachmentsResId.indexOf(resource.resId) > -1)
                .map(mapAttachment) ?? [];

            // Filter- and map-nonsignable attachments
            const resourcesAttached: Attachment[] = loadedAttachments?.resourcesAttached
                .filter((resource: Attachment) => nonSignableAttachmentsResId.indexOf(resource.resId) > -1)
                .map(mapAttachment) ?? [];

            // Check if `docsToSign` was empty before adding new documents
            const isEmpty: boolean = JSON.parse(JSON.stringify(this.signatureBookService.docsToSign.length === 0));

            // Add new signable documents if they are not already present
            resourcesToSign.forEach((doc: Attachment) => {
                if (this.signatureBookService.docsToSign.findIndex((docToSign: Attachment) => docToSign.resId === doc.resId) === -1) {
                    this.signatureBookService.docsToSign.push(doc);
                }
            });

            // Add new nonsignable documents if they are not already present
            resourcesAttached.forEach((doc: Attachment) => {
                if (this.signatureBookService.resourcesAttached.findIndex((attachment: Attachment) => attachment.resId === doc.resId) === -1) {
                    this.signatureBookService.resourcesAttached.push(doc);
                }
            });

            // If `docsToSign` was empty before, select the first document and emit an event
            if (isEmpty && resourcesToSign.length === 1) {
                this.signatureBookService.selectedDocToSign.index = 0;
                this.signatureBookService.selectedDocToSign.attachment = this.signatureBookService.docsToSign[0];
                this.actionsService.emitActionWithData({
                    id: 'attachmentSelected',
                    data: {
                        attachment: this.signatureBookService.docsToSign[0],
                        position: 'right',
                        resIndex: 0
                    },
                });
            }

            // Reset the selected tab on the left panel
            this.maarchSbTabsLeft.selectedId = null;
        }

        this.loading = false;
    }

    /**
     * Deletes a resource and updates the signature book accordingly.
     * If the deleted resource was the last document to sign, the plugin is destroyed.
     * Otherwise, the first remaining document is selected.
     *
     * @param resId - The ID of the resource to be deleted.
     */
    deleteResource(resId: number): void {
        this.loading = true;

        // Attempt to delete the resource and check if it was removed from `docsToSign`
        const resourceToSignDeleted: boolean = this.signatureBookService.deleteResource(resId);

        if (resourceToSignDeleted) {
            // If there are no more documents left to sign, destroy the plugin
            if (this.signatureBookService.docsToSign.length === 0) {
                this.signatureBookContent.destroyPlugin();
            } else {
                // Select the first remaining document
                this.signatureBookService.selectedDocToSign.index = 0;
                this.signatureBookService.selectedDocToSign.attachment = this.signatureBookService.docsToSign[0];

                // Update the selected tab
                this.maarchSbTabsRight.selectedId = this.signatureBookService.selectedDocToSign.index;

                // Emit an event to notify that a new attachment has been selected
                this.actionsService.emitActionWithData({
                    id: 'attachmentSelected',
                    data: {
                        attachment: this.signatureBookService.docsToSign[0],
                        position: 'right',
                        resIndex: 0
                    },
                });
            }
        }

        this.loading = false;
    }

    /**
     * Handles attachment modification based on the updated resource lists.
     * It moves attachments between `docsToSign` and `attachments` if necessary or updates their details.
     *
     * @param data
     */
    async handleAttachmentModification(data: {
        resId: number,
        isDocModified: boolean,
        isNewVersion: boolean,
        newResId: number
    }) {
        this.loading = true;

        let resId: number = data.resId;

        if (data.isNewVersion && data.newResId !== null) {
            resId = data.newResId;
            if (!this.functions.empty(this.signatureBookService.docsToSign.find((attachment: Attachment) => attachment.resId === data.resId))) {
                this.signatureBookService.docsToSign.find((attachment: Attachment) => attachment.resId === data.resId).resId = resId;
                if (this.signatureBookService.selectedDocToSign.attachment.resId === data.resId) {
                    this.signatureBookService.selectedDocToSign.attachment.resId = resId;
                }
            }

            if (!this.functions.empty(this.signatureBookService.docsToSignWithStamps.find((attachment: Attachment) => attachment.resId === data.resId))) {
                this.signatureBookService.docsToSignWithStamps.find((attachment: Attachment) => attachment.resId === data.resId).resId = resId;
            }

            if (!this.functions.empty(this.signatureBookService.resourcesAttached.find((attachment: Attachment) => attachment.resId === data.resId))) {
                this.signatureBookService.resourcesAttached.find((attachment: Attachment) => attachment.resId === data.resId).resId = resId;
            }
        }

        const inSignatureBook: boolean = await this.setInSignatureBook(resId);

        // Fetch updated lists of attachments
        const updatedResources: { resourcesToSign: Attachment[], resourcesAttached: Attachment[] } =
            await this.signatureBookService.loadAttachments(this.userId, this.groupId, this.basketId, this.resId);

        if (!this.functions.empty(updatedResources)) {
            // Extract the updated lists
            const { resourcesToSign, resourcesAttached } = updatedResources;

            // Find if the attachment exists in the updated lists
            const foundInResourcesToSign = resourcesToSign.find(resource => resource.resId === resId);
            const foundInResourcesAttached = resourcesAttached.find(resource => resource.resId === resId);

            // Check if the attachment exists in the current lists
            const indexInDocsToSign = this.signatureBookService.docsToSign.findIndex((attachment: Attachment) => attachment.resId === resId);
            const indexInAttachments = this.signatureBookService.resourcesAttached.findIndex((attachment: Attachment) => attachment.resId === resId);

            const canIntegrate: boolean = indexInAttachments !== -1 || (inSignatureBook && !this.functions.empty(foundInResourcesToSign));

            // Case 1: Moving from `resourcesAttached` to `docsToSign`
            if (canIntegrate) {
                const isEmpty: boolean = JSON.parse(JSON.stringify(this.signatureBookService.docsToSign.length === 0));
                this.signatureBookService.deleteResource(resId);
                const selectedAttachment: Attachment[] = [resourcesToSign.find((attachment: Attachment) => attachment.resId === resId)].map(mapAttachment);
                this.signatureBookService.docsToSign.push(selectedAttachment[0]);
                // If `docsToSign` was empty before, select the first document and emit an event
                if (isEmpty && this.signatureBookService.docsToSign.length === 1) {
                    this.signatureBookService.selectedDocToSign.index = 0;
                    this.signatureBookService.selectedDocToSign.attachment = this.signatureBookService.docsToSign[0];
                    this.actionsService.emitActionWithData({
                        id: 'attachmentSelected',
                        data: {
                            attachment: this.signatureBookService.docsToSign[0],
                            position: 'right',
                            resIndex: 0
                        },
                    });
                }
            }

            // Case 2: Moving from `docsToSign` to `resourcesAttached`
            else if (indexInDocsToSign !== -1 && foundInResourcesAttached) {
                this.deleteResource(resId);
                const selectedAttachment: Attachment[] = [resourcesAttached.find((attachment: Attachment) => attachment.resId === resId)].map(mapAttachment);
                this.signatureBookService.resourcesAttached.push(selectedAttachment[0]);
            }

            // Case 3: Update the existing attachment in `docsToSign`
            else if (indexInDocsToSign !== -1 && foundInResourcesToSign) {
                this.signatureBookContent.loading = true;
                const selectedAttachment: Attachment = mapAttachment(foundInResourcesToSign);
                this.signatureBookService.docsToSign[indexInDocsToSign] = selectedAttachment;

                if (this.signatureBookService.selectedDocToSign.attachment.resId === resId) {
                    this.signatureBookService.selectedDocToSign.attachment = selectedAttachment;
                    this.signatureBookContent.documentData = selectedAttachment;
                }

                if (data.isDocModified) {
                    this.signatureBookService.docsToSign[indexInDocsToSign].fileContentWithAnnotations = null;
                    if (this.signatureBookService.selectedDocToSign.attachment.resId === resId) {
                        this.actionsService.emitActionWithData({
                            id: 'attachmentSelected',
                            data: {
                                attachment: this.signatureBookService.docsToSign[0],
                                position: 'right',
                                resIndex: 0
                            },
                        });
                    }
                }

                this.signatureBookContent.loading = false;

                this.signatureBookService.docsToSign[indexInDocsToSign].stamps = this.signatureBookService.docsToSignWithStamps.find((attachment: Attachment) => attachment.resId === resId)?.stamps;
            }

            // Case 4: Update existing attachment in `resourcesAttached`
            else if (indexInAttachments !== -1 && foundInResourcesAttached) {
                this.attachments[indexInAttachments] = mapAttachment(foundInResourcesAttached);
            }
        }

        this.loading = false;
    }

    setInSignatureBook(resId: number): Promise<boolean> {
        return new Promise((resolve) => {
            this.http.get(`../rest/attachments/${resId}`).pipe(
                tap((data: { inSignatureBook: boolean }) => {
                    const inSignatureBook = data.inSignatureBook ?? false;
                    resolve(inSignatureBook);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    resolve(false);
                    return of(false);
                })
            ).subscribe();
        })
    }
}
