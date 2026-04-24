import { AfterViewInit, Component, Inject, OnDestroy, OnInit, ViewChild, ViewContainerRef } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';
import { NotificationService } from '@service/notification/notification.service';
import {
    MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA,
    MatLegacyDialogRef as MatDialogRef,
} from '@angular/material/legacy-dialog';
import { HttpClient } from '@angular/common/http';
import { NoteEditorComponent } from '../../../notes/note-editor.component';
import { catchError, finalize, tap } from 'rxjs/operators';
import { of, Subscription } from 'rxjs';
import { FunctionsService } from '@service/functions.service';
import { VisaWorkflowComponent } from '../../../visa/visa-workflow.component';
import { PluginManagerService } from '@service/plugin-manager.service';
import { AuthService } from '@service/auth.service';
import { HeaderService } from '@service/header.service';
import { SignatureBookService } from '@appRoot/signatureBook/signature-book.service';
import {
    DatasActionSendInterface,
    VisaCircuitActionDataToSendInterface,
    VisaCircuitActionObjectInterface
} from "@models/actions.model";
import { MatSidenav } from "@angular/material/sidenav";
import { Attachment } from "@models/attachment.model";
import { MaarchPluginFortifyInterface, WorkflowItemsInterface } from '@models/maarch-plugin-fortify-model';
import { StripTagsPipe } from 'ngx-pipes';
import { PrivilegeService } from "@service/privileges.service";
import { UserStampInterface } from '@models/user-stamp.model';
import { UserWorkflowInterface } from '@models/user-workflow.model';
import { SignaturePositionInterface } from '@models/signature-position.model';
import { ScrollableDirective } from '@directives/scrollable.directive';

@Component({
    templateUrl: 'continue-visa-circuit-action-new-sb.component.html',
    styleUrls: ['continue-visa-circuit-action-new-sb.component.scss'],
    providers: [StripTagsPipe]
})
export class ContinueVisaCircuitActionNewSbComponent implements OnInit, OnDestroy, AfterViewInit {
    @ViewChild('myPlugin', { read: ViewContainerRef, static: false }) myPlugin: ViewContainerRef;
    @ViewChild('noteEditor', { static: false }) noteEditor: NoteEditorComponent;
    @ViewChild('appVisaWorkflow', { static: false }) appVisaWorkflow: VisaWorkflowComponent;
    @ViewChild('snav2', { static: false }) public snav2: MatSidenav;
    @ViewChild('scrollableDirective', { static: false }) scrollableDirective: ScrollableDirective;

    subscription: Subscription;

    loading: boolean = false;

    resourcesMailing: any[] = [];
    resourcesWarnings: any[] = [];
    resourcesErrors: { reason: string, res_id: number, alt_identifier }[] = [];

    noResourceToProcess: boolean = null;
    componentInstance: any = null;

    parameters: { digitalCertificate: boolean, stampSelection: boolean, applyTimestamp: boolean } = {
        digitalCertificate: true,
        stampSelection: false,
        applyTimestamp: false
    }

    noteExpanded: boolean = false;

    visaWorkflow: { currentUserIndex: number, items: WorkflowItemsInterface[] } = { currentUserIndex: 0, items: [] };

    isEmptyWorkflow: boolean = false;

    isModalCanceled: boolean = false;

    selectedStamp: UserStampInterface = null;

    docsToSignWithEmptyStamps: number[] = [];
    visaWorkflowByResource: UserWorkflowInterface[] = [];

    imageDimensions: { height: number, width: number } = null;

    noSignaturePositionsAvailableErrors: { reason: string, res_id: number, alt_identifier }[] = [];

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        public dialogRef: MatDialogRef<ContinueVisaCircuitActionNewSbComponent>,
        @Inject(MAT_DIALOG_DATA) public data: DatasActionSendInterface,
        public functions: FunctionsService,
        public signatureBookService: SignatureBookService,
        private notify: NotificationService,
        private pluginManagerService: PluginManagerService,
        private authService: AuthService,
        private headerService: HeaderService,
        private privilegeService: PrivilegeService,
    ) {
    }

    async ngOnInit(): Promise<void> {
        this.loading = true;
        await this.signatureBookService.getTimestampConfig();
        if (this.signatureBookService.isValidRoute()) {
            this.signatureBookService.selectedResources = [];
            for (let i = 0; i < this.data.resIds.length; i++) {
                try {
                    await this.signatureBookService.toggleSelection(true, this.data.userId, this.data.groupId, this.data.basketId, this.data.resIds[i]);
                } catch (error) {
                    this.dialogRef.close();
                }
            }
            delete this.data?.resource?.docsToSign;
            this.data = {
                ...this.data,
                resource: {
                    ...this.data.resource,
                    docsToSign: this.signatureBookService.selectedResources ?? []
                }
            }
        }
        await this.checkSignatureBook();

        this.docsToSignWithEmptyStamps = this.data.resource.docsToSign.filter((resource: Attachment) => this.functions.empty(resource.stamps)).map((item: Attachment) => item.resId);

        if (this.canApplyStamp() && this.docsToSignWithEmptyStamps.length > 0 && this.signatureBookService.userStamps.length === 0) {
            const userId: number = this.headerService.user.id === this.data.userId ? this.data.userId : this.headerService.user.id;
            await this.signatureBookService.getUserSignatures(userId);
        }
        this.loading = false;
    }

    ngAfterViewInit(): void {
        if (!this.functions.empty(this.scrollableDirective)) {
            this.scrollableDirective.checkScrollButtons();
        }
    }

    checkSignatureBook(): Promise<boolean> {
        this.resourcesErrors = [];
        this.resourcesWarnings = [];
        this.loading = true;

        return new Promise((resolve) => {
            this.http
                .post(
                    '../rest/resourcesList/users/' +
                    this.data.userId +
                    '/groups/' +
                    this.data.groupId +
                    '/baskets/' +
                    this.data.basketId +
                    '/actions/' +
                    this.data.action.id +
                    `/checkContinueVisaCircuit?applyStampEnabled=${this.parameters.stampSelection}`,
                    { resources: this.data.resIds }
                )
                .pipe(
                    tap((data: {
                        parameters: { digitalCertificateByDefault: boolean },
                        resourcesInformations: {
                            warning: { res_id: number, alt_identifier: string, reason: string }[],
                            error: { res_id: number, alt_identifier: string, reason: string }[],
                            success: { res_id: number, alt_identifier: string, reason: string }[]
                        },
                        visaWorkflow: UserWorkflowInterface[]
                    }) => {
                        this.parameters.digitalCertificate = data?.parameters?.digitalCertificateByDefault ?? true;

                        if (!this.functions.empty(data.resourcesInformations.warning)) {
                            this.resourcesWarnings = (data.resourcesInformations.warning as any[]).filter((warning: any) => warning.reason !== 'userHasntSigned');
                        }

                        if (!this.functions.empty(data.resourcesInformations.error)) {
                            this.resourcesErrors = data.resourcesInformations.error;
                            if (this.parameters.stampSelection && this.resourcesErrors.filter((error: {
                                reason: string
                            }) => error.reason === 'noSignaturePositionsAvailable').length > 0) {
                                for (let i = this.resourcesErrors.length - 1; i >= 0; i--) {
                                    const error: { reason: string, res_id: number } = this.resourcesErrors[i];
                                    if (error.reason === 'noSignaturePositionsAvailable' && this.data.resource.docsToSign.find((res: Attachment) => res.resId === error.res_id)?.stamps?.length > 0) {
                                        this.resourcesErrors.splice(i, 1);
                                    }
                                }
                            }

                            this.noSignaturePositionsAvailableErrors = this.resourcesErrors.filter((error: {
                                reason: string
                            }) => error.reason === 'noSignaturePositionsAvailable');

                            this.noResourceToProcess = this.resourcesErrors.filter((error: {
                                reason: string
                            }) => error.reason !== 'noSignaturePositionsAvailable').length === this.data.resIds.length;

                            const ignoredResources: number[] = this.resourcesErrors.filter((error: {
                                reason: string
                            }) => error.reason !== 'noSignaturePositionsAvailable').map((item: any) => item.res_id);
                            this.data.resource.docsToSign = this.data.resource.docsToSign.filter((attachment: Attachment) => ignoredResources.indexOf(attachment.resId) === -1);
                        } else {
                            this.noSignaturePositionsAvailableErrors = [];
                            this.noResourceToProcess = null;
                        }
                        if (data.resourcesInformations.success) {
                            data.resourcesInformations.success.forEach((value: any) => {
                                if (value.mailing) {
                                    this.resourcesMailing.push(value);
                                }
                            });
                        }

                        if (!this.functions.empty(data.visaWorkflow)) {
                            this.visaWorkflowByResource = data.visaWorkflow;
                            this.setVisaWorkflow(this.visaWorkflowByResource);
                        }

                        if (this.data.resource.docsToSign.length > 0 && (this.data.resource.docsToSign).every((attachment: Attachment) => this.functions.empty(attachment.visaWorkflow))) {
                            this.isEmptyWorkflow = true;
                        }

                        resolve(true);
                    }),
                    finalize(() => {
                        this.loading = false;
                    }),
                    catchError((err: any) => {
                        this.notify.handleSoftErrors(err);
                        this.loading = false;
                        resolve(false);
                        this.dialogRef?.close();
                        return of(false);
                    })
                ).subscribe();
        });
    }

    async onSubmit(): Promise<void> {
        this.loading = true;
        const realResSelected: number[] = this.data.resIds
            .filter(
                (resId: any) => this.resourcesErrors
                    .filter((error) => error.reason !== "noSignaturePositionsAvailable")
                    .map((resErr) => resErr.res_id).indexOf(resId) === -1
            );
        this.noteExpanded = true;
        this.signatureBookService.config.url = this.signatureBookService.config.url?.replace(/\/$/, '')
        this.componentInstance = await this.pluginManagerService.initPlugin(
            'maarch-plugins-fortify',
            this.myPlugin,
            this.setPluginData()
        );
        if (this.componentInstance) {
            this.componentInstance
                .open()
                .pipe(
                    tap(async (data: any) => {
                        if (!this.functions.empty(data) && typeof data === 'object') {
                            const dataToSend = await this.signatureBookService.formatDataToSend(data);
                            if (this.hasCkrCancel(dataToSend)) {
                                this.loading = false;
                                this.noteExpanded = false;
                                this.notify.handleSoftErrors(this.translate.instant('lang.cancelAction'));
                            } else {
                                this.executeAction(realResSelected, dataToSend);
                            }
                        } else {
                            this.loading = false;
                            this.noteExpanded = false;
                        }
                    }),
                    catchError((err: any) => {
                        this.notify.handleSoftErrors(err);
                        return of(false);
                    })
                ).subscribe();
        } else {
            this.loading = false;
            this.noteExpanded = false;
        }
    }

    hasCkrCancel(obj: any): boolean {
        if (typeof obj === 'object' && obj !== null) {
            for (const key in obj) {
                if (key === 'hashSignature' && obj[key] === 'CKR_CANCEL') {
                    return true;
                }
                if (this.hasCkrCancel(obj[key])) {
                    return true;
                }
            }
        }
        return false;
    }

    executeAction(realResSelected: number[], objToSend: VisaCircuitActionObjectInterface = null): void {
        const dataToSend: VisaCircuitActionDataToSendInterface = {
            resources: realResSelected,
            note: this.noteEditor.getNote(),
            data: { ...objToSend, digitalCertificate: this.parameters.digitalCertificate },
        };
        this.http
            .put(this.data.processActionRoute, dataToSend)
            .pipe(
                tap((data: any) => {
                    if (!data) {
                        this.dialogRef.close(realResSelected);
                    }
                    if (data && data.errors != null) {
                        this.notify.error(data.errors);
                    }
                }),
                finalize(() => (this.loading = false)),
                catchError((err: any) => {
                    this.loading = false;
                    this.noteExpanded = false;
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            )
            .subscribe();
    }

    isValidAction(): boolean {
        if (this.noSignaturePositionsAvailableErrors.length > 0) {
            return false;
        }
        return !this.noResourceToProcess;
    }

    atLeastOneDocumentHasNoStamp(): boolean {
        if (!this.functions.empty(this.data.resource)) {
            if (this.data.resource?.docsToSign?.length > 0) {
                return (this.data.resource.docsToSign).some((resource: Attachment) => resource.stamps.length === 0);
            }
        }
        return false;
    }

    atLeastOneDocumentHasDigitalSignature(): boolean {
        if (this.data.resource.docsToSign.length > 0) {
            return (this.data.resource.docsToSign).some((resource: Attachment) => resource.hasDigitalSignature === true);
        }
        return false;
    }

    setPluginData(): MaarchPluginFortifyInterface {
        if (!this.canShowDigitalCertificate()) {
            this.parameters.digitalCertificate = false;
            this.parameters.stampSelection = false;
            this.selectedStamp = null;
        }
        const data: MaarchPluginFortifyInterface = {
            functions: this.functions,
            notification: this.notify,
            translate: { service: this.translate, currentLang: this.authService.lang },
            pluginUrl: this.authService.maarchUrl.replace(/\/$/, '') + '/plugins/maarch-plugins',
            additionalInfo: {
                resources: this.data.resource.docsToSign,
                sender: `${this.headerService.user.firstname} ${this.headerService.user.lastname}`,
                externalUserId: this.headerService.user.externalId,
                signatureBookConfig: this.signatureBookService.config,
                digitalCertificate: this.parameters.digitalCertificate,
                applyTimestamp: this.canApplyTimestamp()
            },
        };
        return data;
    }

    canApplyTimestamp(): boolean {
        if (this.signatureBookService.timestampConfig.autoApply) {
            return true;
        }

        return this.parameters.applyTimestamp;
    }

    canShowDigitalCertificate(): boolean {
        if (this.data.resIds.length === 1) {
            return this.visaWorkflow.items[this.visaWorkflow.currentUserIndex]?.mode === 'sign' && this.privilegeService.hasCurrentUserPrivilege('sign_document')
        } else {
            if (this.hasOnlyVisaUsers()) {
                this.parameters.digitalCertificate = false;
                this.selectedStamp = null;
                return false;
            }
            return this.privilegeService.hasCurrentUserPrivilege('sign_document');
        }
    }

    canHadStampSignature(): boolean {
        if (this.visaWorkflow.items.length > 0) {
            return (
                this.visaWorkflow.items[this.visaWorkflow.currentUserIndex]?.mode === 'sign' ||
                (this.visaWorkflow.items[this.visaWorkflow.currentUserIndex]?.mode === 'visa' && !this.atLeastOneDocumentHasDigitalSignature())
            );
        }

        return true;
    }

    formatVisaWorkflow(visaWorkflow: any[]): WorkflowItemsInterface[] {
        return visaWorkflow.map((user: any) => ({
            userId: user.externalId,
            mode: user.item_mode,
            signatureMode: this.getUserMode(user),
            signaturePositions: []
        }));
    }

    getUserMode(user: { item_mode: string }): string {
        if (this.parameters.digitalCertificate) {
            return user.item_mode === 'sign' ? this.getSignUserMode() : 'stamp';
        }
        return 'stamp';
    }

    getSignUserMode(): string {
        return this.parameters.applyTimestamp ? 'rgs_2stars_timestamped' : 'rgs_2stars';
    }

    hasOnlyVisaUsers(): boolean {
        const allItemsVisa: boolean = (this.data.resource.docsToSign).every((attachment: Attachment) => {
            return attachment.visaWorkflow[0]?.mode === 'visa';
        });

        return allItemsVisa;
    }

    setVisaWorkflow(visaWorkflow: UserWorkflowInterface[]): void {
        Object.keys(visaWorkflow).forEach((resId: string) => {
            (this.data.resource.docsToSign)
                .filter((attachment: Attachment) => attachment.resIdMaster === +resId)
                .forEach((item: Attachment) => {
                    item.visaWorkflow = this.formatVisaWorkflow(visaWorkflow[resId]);
                });
        });
    }

    isNotValidAction(): boolean {
        return this.loading || this.isEmptyWorkflow || !this.isValidAction() ||
            this.data.resource.docsToSign?.length === 0 ||
            (this.data.resIds.length === 1 && this.visaWorkflow.items.length === 0) ||
            (this.data.resIds.length === 1 && this.atLeastOneDocumentHasNoStamp() && !this.parameters.digitalCertificate && this.visaWorkflow.items[this.visaWorkflow.currentUserIndex]?.mode === 'sign') ||
            (this.parameters.stampSelection &&
                this.noSignaturePositionsAvailableErrors.length > 0 &&
                this.data.resource.docsToSign?.filter((resource: Attachment) => this.functions.empty(resource.signaturePositions)).length === this.noSignaturePositionsAvailableErrors.length
            );
    }

    ngOnDestroy(): void {
        if (!this.isModalCanceled) {
            this.signatureBookService.resetSelection();
        }

        this.signatureBookService.docsToSign.forEach((resource: Attachment) => {
            if (this.docsToSignWithEmptyStamps.indexOf(resource.resId) > -1) {
                resource.stamps = [];
            }
        });
    }

    async selectStamp(stamp: UserStampInterface): Promise<void> {
        if (this.selectedStamp !== null && this.selectedStamp?.id === stamp.id) {
            this.selectedStamp = null;
        } else {
            this.selectedStamp = stamp;
        }

        this.updateResourcesStamps();
    }

    async toggleSelection(event: { checked: boolean }): Promise<void> {
        // Set the selected stamp based on the toggle's checked state
        this.selectedStamp = event.checked ? this.signatureBookService.userStamps[0] : null;
        await this.checkSignatureBook();

        this.updateResourcesStamps();

        setTimeout(() => {
            if (!this.functions.empty(this.scrollableDirective)) {
                this.scrollableDirective.checkScrollButtons();
            }
        }, 0);
    }

    async updateResourcesStamps(): Promise<void> {
        // Check if the 'selectedStamp' object is not empty
        if (!this.functions.empty(this.selectedStamp)) {
            let currentUserIndex: number = null;
            let isSignUser: boolean = true
            // Filter the resources that have no stamps assigned
            this.data.resource.docsToSign
                .forEach(async (item: Attachment) => {
                    if (item.signaturePositions.length > 0 && this.docsToSignWithEmptyStamps.indexOf(item.resId) > -1) {
                        // Iterate over the 'visaWorkflowByResource' keys to find the workflow corresponding to the resource ID
                        Object.keys(this.visaWorkflowByResource).forEach((key: string) => {
                            if (item.resIdMaster === parseInt(key)) {
                                const currentUser = this.visaWorkflowByResource[key].find((user: UserWorkflowInterface) => this.functions.empty(user.process_date));
                                // Find the current user's sequence in the workflow where 'process_date' is empty
                                isSignUser = currentUser.item_mode === 'sign';
                                currentUserIndex = currentUser.sequence;
                                return;
                            }
                        });

                        if (!item.hasDigitalSignature || (item.hasDigitalSignature && isSignUser)) {
                            // Retrieve the stamps corresponding to the current user's sequence from 'docsToSignClone'
                            const stamp: SignaturePositionInterface = this.signatureBookService.docsToSignClone
                                .find((doc: Attachment) => doc.resId === item.resId)
                                .signaturePositions.find((signature: SignaturePositionInterface) => signature.sequence === currentUserIndex);

                            // If no stamps are found for the current sequence, set an empty array; otherwise, set the retrieved stamps
                            if (!this.functions.empty(stamp)) {
                                const base64: string = await this.signatureBookService.getSignatureContent(this.selectedStamp.contentUrl);

                                const imageDimensions: {
                                    height: number,
                                    width: number
                                } = await this.functions.getImageSizeFromBlob(base64);

                                item.stamps = [{
                                    base64Url: base64,
                                    positionX: stamp.positionX,
                                    positionY: stamp.positionY,
                                    page: stamp.page,
                                    height: (imageDimensions.height / 842) * 100,
                                    width: (imageDimensions.width / 600) * 100
                                }];
                            }
                        }

                    }
                });
        } else {
            // If 'selectedStamp' is empty, set empty stamps for resources in 'docsToSignWithEmptyStamps'
            this.data.resource.docsToSign.forEach((resource: Attachment) => {
                if (this.docsToSignWithEmptyStamps.indexOf(resource.resId) > -1) {
                    resource.stamps = [];
                }
            });
        }
    }

    canApplyStamp(): boolean {
        if (this.data.resIds.length === 1 && (this.data.resource.docsToSign).every((resoure: Attachment) => resoure.hasDigitalSignature)) {
            return this.visaWorkflow.items[this.visaWorkflow.currentUserIndex]?.mode === 'sign';
        }
        return true;
    }

    canShowTimestamp(): boolean {
        return !this.loading && this.canShowDigitalCertificate() &&
            !this.functions.empty(this.signatureBookService.timestampConfig.using) &&
            this.signatureBookService.timestampConfig.enabled &&
            !this.signatureBookService.timestampConfig.autoApply;
    }
}
