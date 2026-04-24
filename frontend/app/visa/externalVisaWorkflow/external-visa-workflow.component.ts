import { Component, EventEmitter, Input, OnInit, Output } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { NotificationService } from '@service/notification/notification.service';
import { CdkDragDrop, moveItemInArray } from '@angular/cdk/drag-drop';
import { FunctionsService } from '@service/functions.service';
import { tap } from 'rxjs/operators';
import { UntypedFormControl } from '@angular/forms';
import { ScanPipe } from 'ngx-pipes';
import { MatLegacyDialog as MatDialog } from '@angular/material/legacy-dialog';
import { CreateExternalUserComponent } from './createExternalUser/create-external-user.component';
import { ActionsService } from '@appRoot/actions/actions.service';
import {
    ExternalSignatoryBookManagerService
} from '@service/externalSignatoryBook/external-signatory-book-manager.service';
import { UserWorkflow, UserWorkflowInterface } from '@models/user-workflow.model';
import { AuthService } from '@service/auth.service';
import { SignaturePositionInterface } from '@models/signature-position.model';
import { DatePositionInterface } from '@models/date-position.model';

@Component({
    selector: 'app-external-visa-workflow',
    templateUrl: 'external-visa-workflow.component.html',
    styleUrls: ['external-visa-workflow.component.scss'],
    providers: [ScanPipe, ExternalSignatoryBookManagerService]
})
export class ExternalVisaWorkflowComponent implements OnInit {

    @Input() injectDatas: any;
    @Input() adminMode: boolean;
    @Input() resId: number = null;

    @Output() workflowUpdated = new EventEmitter<any>();

    visaWorkflowClone: any = [];

    loading: boolean = false;
    data: any;

    searchVisaSignUser = new UntypedFormControl();

    otpConfig: number = 0;

    visaWorkflow = new VisaWorkflow();

    workflowTypes: any[] = [];
    workflowType: string = 'BUREAUTIQUE_PDF';

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        public functions: FunctionsService,
        public dialog: MatDialog,
        public actionService: ActionsService,
        public authService: AuthService,
        public externalSignatoryBookManagerService: ExternalSignatoryBookManagerService,
        private notify: NotificationService
    ) {
    }

    async ngOnInit(): Promise<any> {
        if (this.adminMode) {
            await this.workflowDetails();
        }

        if (this.externalSignatoryBookManagerService.canAddExternalUser()) {
            const data: any = await this.externalSignatoryBookManagerService?.getOtpConfig();
            if (!this.functions.empty(data)) {
                this.otpConfig = data.length;
            }
        }
    }

    canAddExternalUser(): boolean {
        return this.externalSignatoryBookManagerService.canAddExternalUser();
    }

    async getWorkflowDetails(): Promise<any> {
        return await this.externalSignatoryBookManagerService?.getWorkflowDetails();
    }

    drop(event: CdkDragDrop<string[]>) {
        if (event.previousContainer === event.container) {
            if (this.canMoveUserExtParaph(event)) {
                moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
                this.setSequenceForWorkflowUsers();
                this.workflowUpdated.emit(this.visaWorkflow.items);
            } else {
                this.notify.error(this.translate.instant('lang.errorUserSignType'));
            }
        }
    }

    canMoveUserExtParaph(ev: any): boolean {
        const newWorkflow = this.arrayMove(this.visaWorkflow.items.slice(), ev.currentIndex, ev.previousIndex);
        return this.isValidExtWorkflow(newWorkflow);
    }

    arrayMove(arr: any, oldIndex: number, newIndex: number) {
        if (newIndex >= arr.length) {
            let k = newIndex - arr.length + 1;
            while (k--) {
                arr.push(undefined);
            }
        }
        arr.splice(newIndex, 0, arr.splice(oldIndex, 1)[0]);
        return arr;
    }

    isValidExtWorkflow(workflow: any = this.visaWorkflow): boolean {
        return this.externalSignatoryBookManagerService.isValidExtWorkflow(workflow);
    }

    async workflowDetails() {
        const workflow = await this.getWorkflowDetails();
        if (this.externalSignatoryBookManagerService.signatoryBookEnabled === 'fastParapheur' && !this.functions.empty(workflow?.types)) {
            this.workflowTypes = workflow.types;
            this.workflowType = workflow.types[0].id;
        } else if (this.externalSignatoryBookManagerService.signatoryBookEnabled === 'goodflag') {
            this.workflowTypes = workflow;
            this.workflowType = workflow[0].id;
        }
    }

    async loadListModel(entityId: number): Promise<void> {
        this.loading = true;
        await this.workflowDetails();
        const listModel: any = await this.externalSignatoryBookManagerService?.loadListModel(entityId);
        if (!this.functions.empty(listModel)) {
            if (listModel.listTemplates[0]) {
                const users: UserWorkflow[] = listModel.listTemplates[0].items.map((item: any) => ({
                    ...this.externalSignatoryBookManagerService.setExternalInformation(item),
                    item_entity: item.descriptionToDisplay,
                    requested_signature: item.item_mode !== 'visa',
                    currentRole: item.item_mode,
                    role: item?.role ?? item.item_mode
                }));
                users.forEach((item: UserWorkflow) => {
                    if (this.visaWorkflow.items.find((user: UserWorkflow) => user?.item_id === item?.item_id && user.id === item.id) === undefined) {
                        this.visaWorkflow.items.push(item);
                    }
                })
            }
            this.setSequenceForWorkflowUsers();
            this.visaWorkflowClone = JSON.parse(JSON.stringify(this.visaWorkflow.items));
        }
        this.visaWorkflow.items.forEach((element: any, key: number) => {
            if (!this.functions.empty(element['externalId'])) {
                this.getUserAvatar(element?.externalInformations?.type ?? element.externalId[this.authService.externalSignatoryBook.id], key);
            }
        });
        this.workflowUpdated.emit(this.visaWorkflow.items);
        this.loading = false;
    }

    async loadExternalWorkflow(attachmentId: number, type: string): Promise<void> {
        this.loading = true;
        const data: any = await this.externalSignatoryBookManagerService?.loadWorkflow(attachmentId, type);
        if (!this.functions.empty(data.workflow)) {
            data.workflow.forEach((element: any, key: any) => {
                const steps: number[] = [...new Set(this.visaWorkflow.items.map((user: UserWorkflowInterface) => user.step))];
                const step: number = this.visaWorkflow.items.length === 0 ? 1 : Math.max(...steps) + 1;
                const user: UserWorkflowInterface = {
                    listinstance_id: key,
                    id: element.userId,
                    labelToDisplay: element.userDisplay,
                    item_type: 'user',
                    requested_signature: element.mode !== 'visa',
                    process_date: this.functions.formatFrenchDateToTechnicalDate(element.processDate),
                    picture: '',
                    hasPrivilege: true,
                    isValid: true,
                    delegatedBy: null,
                    step: step,
                    isSignatureRequired: this.visaWorkflow.items.filter((user: UserWorkflowInterface) => user.step === step).length <= 1,
                    role: !this.functions.empty(element.mode) ? (element.mode !== 'visa' ? (element.signatureMode ?? element.mode) : 'visa') : 'undefined',
                    status: this.getStatus(element),
                    externalSignatureBookStatus: element.status,
                    isSystem: element.isSystem ?? false,
                };
                let externalId: string | number = element.userId;
                if (this.functions.empty(element.userId) && !this.functions.empty(element.externalInformations)) {
                    user['role'] = element.mode;
                    externalId = element.externalInformations.type;
                }
                this.visaWorkflow.items.push(user);
                this.getUserAvatar(externalId, key);
            });
            this.setSequenceForWorkflowUsers();
            this.visaWorkflowClone = JSON.parse(JSON.stringify(this.visaWorkflow.items));
            this.workflowUpdated.emit(this.visaWorkflow.items);
        }
        this.loading = false;
    }

    deleteItem(index: number): void {
        this.visaWorkflow.items.splice(index, 1);
        this.setSequenceForWorkflowUsers();

        if (this.externalSignatoryBookManagerService.canAddSteps()) {
            // Map to store the correspondence between old step values and new sequential step values
            const stepMapping = new Map<number, number>();
            let nextStepValue = 1; // The next available step value to assign

            // Reassign step numbers so they remain sequential (1, 2, 3, ...)
            // while keeping grouped items together
            this.visaWorkflow.items.forEach(item => {
                // If this step value hasn't been mapped yet, assign it a new sequential value
                if (!stepMapping.has(item.step)) {
                    stepMapping.set(item.step, nextStepValue++);
                }
                // Replace the item's step with the new sequential step value
                item.step = stepMapping.get(item.step)!;
            });

            // Update the "isSignatureRequired" flag
            // A step requires a signature if it is unique (only one user has this step)
            this.visaWorkflow.items.forEach(item => {
                item.isSignatureRequired = this.visaWorkflow.items
                    .filter(i => i.step === item.step).length === 1;
            });
        }

        this.workflowUpdated.emit(this.visaWorkflow.items);
    }

    getWorkflow(): UserWorkflow[] {
        return this.visaWorkflow.items;
    }

    getCurrentVisaUserIndex(): number {
        if (this.functions.empty(this.getLastVisaUser()?.listinstance_id)) {
            const index = 0;
            return this.getRealIndex(index);
        } else {
            let index = this.visaWorkflow.items.map((item: any) => item.listinstance_id).indexOf(this.getLastVisaUser()?.listinstance_id);
            index++;
            return this.getRealIndex(index);
        }
    }

    getLastVisaUser(): UserWorkflow | null {
        const arrOnlyProcess = this.visaWorkflow.items.filter((item: any) => !this.functions.empty(item.process_date) && item.isValid);
        return !this.functions.empty(arrOnlyProcess[arrOnlyProcess.length - 1]) ? arrOnlyProcess[arrOnlyProcess.length - 1] : null;
    }

    getRealIndex(index: number): number {
        while (index < this.visaWorkflow.items.length && !this.visaWorkflow.items[index].isValid) {
            index++;
        }
        return index;
    }

    getUsersMissingInSignatureBook(): string[] {
        return this.visaWorkflow.items.filter((item: {
            externalId: number,
            item_type: string,
            labelToDisplay: string
        }) => this.functions.empty(item.externalId) && !item.item_type.toLowerCase().includes('otp')).map((item: any) => item.labelToDisplay);
    }

    async addItemToWorkflow(item: any): Promise<unknown> {
        item = this.externalSignatoryBookManagerService.setExternalInformation(item);
        const picture: string = await this.externalSignatoryBookManagerService?.getUserAvatar(item.externalId[this.externalSignatoryBookManagerService.signatoryBookEnabled]);
        return new Promise((resolve) => {
            const isGoodflagEnabled: boolean = this.externalSignatoryBookManagerService.signatoryBookEnabled === 'goodflag';
            const steps: number[] = [...new Set(this.visaWorkflow.items.map((user: UserWorkflowInterface) => user.step))];
            const step: number = this.visaWorkflow.items.length === 0 ? 1 : Math.max(...steps) + 1;
            const user: UserWorkflowInterface = {
                id: item.id,
                item_id: item.id,
                item_type: item.type,
                item_entity: isGoodflagEnabled ? null : item.email,
                labelToDisplay: item.idToDisplay,
                externalId: item.externalId,
                difflist_type: 'VISA_CIRCUIT',
                signatory: !this.functions.empty(item.signatory) ? item.signatory : false,
                hasPrivilege: true,
                isValid: true,
                availableRoles: isGoodflagEnabled ? ['sign'] : [...new Set(['visa'].concat(item.signatureModes))],
                role: item.signatureModes[item.signatureModes.length - 1],
                step: step,
                isSignatureRequired: this.visaWorkflow.items.filter((user: UserWorkflowInterface) => user.step === step).length <= 1,
                picture: picture,
            };
            this.visaWorkflow.items.push(user);
            if (!this.isValidRole(this.visaWorkflow.items.length - 1, item.signatureModes[item.signatureModes.length - 1], item.signatureModes[item.signatureModes.length - 1])) {
                this.visaWorkflow.items[this.visaWorkflow.items.length - 1].role = 'visa';
            }

            this.setSequenceForWorkflowUsers();
            this.getUserAvatar(item.externalId[this.externalSignatoryBookManagerService.signatoryBookEnabled], this.visaWorkflow.items.length - 1);
            this.searchVisaSignUser.reset();
            this.workflowUpdated.emit(this.visaWorkflow.items);
            resolve(true);
        });
    }

    resetWorkflow(): void {
        this.visaWorkflow.items = [];
    }

    isValidWorkflow(): boolean {
        return (this.visaWorkflow.items.filter((item: any) => item.requested_signature).length > 0 && this.visaWorkflow.items.filter((item: any) => (!item.hasPrivilege || !item.isValid) && (item.process_date === null || this.functions.empty(item.process_date))).length === 0) && this.visaWorkflow.items.length > 0;
    }

    getError(): string {
        if (this.visaWorkflow.items.filter((item: any) => item.requested_signature).length === 0) {
            return this.translate.instant('lang.signUserRequired');
        } else if (this.visaWorkflow.items.filter((item: any) => !item.hasPrivilege).length > 0) {
            return this.translate.instant('lang.mustDeleteUsersWithNoPrivileges');
        } else if (this.visaWorkflow.items.filter((item: any) => !item.isValid && (item.process_date === null || this.functions.empty(item.process_date))).length > 0) {
            return this.translate.instant('lang.mustDeleteInvalidUsers');
        }
    }

    async getUserAvatar(externalId: any = null, key: number): Promise<void> {
        this.visaWorkflow.items[key].picture = await this.externalSignatoryBookManagerService?.getUserAvatar(externalId);
    }

    isModified(): boolean {
        return !(this.loading || JSON.stringify(this.visaWorkflow.items) === JSON.stringify(this.visaWorkflowClone));
    }

    canManageUser(): boolean {
        return this.adminMode;
    }

    isValidRole(indexWorkflow: any, role: string, currentRole: string): boolean {
        if (this.visaWorkflow.items.filter((item: any, index: any) => index > indexWorkflow && ['stamp'].indexOf(item.role) > -1).length > 0 && ['visa', 'stamp'].indexOf(currentRole) > -1 && ['visa', 'stamp'].indexOf(role) === -1) {
            return false;
        } else return !(this.visaWorkflow.items.filter((item: any, index: any) => index < indexWorkflow && ['visa', 'stamp'].indexOf(item.role) === -1).length > 0 && role === 'stamp');
    }

    openCreateUserOtp(item: any = null): void {
        if (this.adminMode && (item === null || (item && item.item_id === null))) {
            const objToSend: any = item === null || (item && item.item_id) !== null ? null : {
                firstname: item.externalInformations.firstname,
                lastname: item.externalInformations.lastname,
                email: item.externalInformations.email,
                phone: item.externalInformations.phone,
                security: item.externalInformations.security,
                sourceId: item.externalInformations.sourceId,
                type: item.externalInformations.type,
                role: item.role,
                availableRoles: item.externalInformations.availableRoles
            };
            const dialogRef = this.dialog.open(CreateExternalUserComponent, {
                panelClass: 'maarch-modal',
                disableClose: true,
                width: '500px',
                data: {
                    otpInfo: objToSend,
                    resId: this.resId,
                    step: item.step,
                    isSignatureRequired: item.isSignatureRequired
                }
            });
            dialogRef.afterClosed().pipe(
                tap(async (data: any) => {
                    if (data) {
                        const steps: number[] = [...new Set(this.visaWorkflow.items.map((user: UserWorkflowInterface) => user.step))];
                        const step: number = this.visaWorkflow.items.length === 0 ? 1 : Math.max(...steps) + 1;
                        const user: UserWorkflowInterface = {
                            item_id: null,
                            item_type: 'userOtp',
                            labelToDisplay: `${data.otp.firstname} ${data.otp.lastname}`,
                            picture: await this.externalSignatoryBookManagerService?.getUserAvatar(data.otp.type),
                            hasPrivilege: true,
                            isValid: true,
                            externalId: {
                                [this.externalSignatoryBookManagerService.signatoryBookEnabled]: null
                            },
                            externalInformations: data.otp,
                            role: data.otp.role,
                            availableRoles: data.otp.availableRoles,
                            step: steps.indexOf(data.otp.step) > -1 ? data.otp.step : step,
                            isSignatureRequired: data.otp.isSignatureRequired ?? (this.visaWorkflow.items.filter((user: UserWorkflowInterface) => user.step === step).length <= 1)
                        };
                        if (objToSend !== null) {
                            this.visaWorkflow.items[this.visaWorkflow.items.indexOf(item)] = user;
                            this.notify.success(this.translate.instant('lang.modificationSaved'));
                        } else {
                            this.visaWorkflow.items.push(user);
                        }

                        this.setSequenceForWorkflowUsers();
                        this.workflowUpdated.emit(this.visaWorkflow.items);
                    }
                })
            ).subscribe();
        }
    }

    updateVisaWorkflow(user: any): void {
        const steps: number[] = [...new Set(this.visaWorkflow.items.map((user: UserWorkflowInterface) => user.step))];
        const step: number = (this.visaWorkflow.items.length === 0 ? 1 : Math.max(...steps) + 1);
        user['step'] = steps.indexOf(user.step) > -1 ? user.step : step;
        user['isSignatureRequired'] = user.isSignatureRequired ?? (this.visaWorkflow.items.filter((user: UserWorkflowInterface) => user.step === step).length <= 1);
        this.visaWorkflow.items.push(user);
        this.setSequenceForWorkflowUsers();
        this.workflowUpdated.emit(this.visaWorkflow.items);
    }

    setPositionsWorkflow(resource: any, positions: any): void {
        this.clearOldPositionsFromResource(resource);

        if (positions.signaturePositions !== undefined) {
            Object.keys(positions.signaturePositions).forEach(key => {
                const objPos = {
                    ...positions.signaturePositions[key],
                    mainDocument: resource.mainDocument,
                    resId: resource.resId
                };
                this.visaWorkflow.items[positions.signaturePositions[key].sequence].signaturePositions.push(objPos);
            });
        }
        if (positions.datePositions !== undefined) {
            Object.keys(positions.datePositions).forEach(key => {
                const objPos = {
                    ...positions.datePositions[key],
                    mainDocument: resource.mainDocument,
                    resId: resource.resId
                };
                this.visaWorkflow.items[positions.datePositions[key].sequence].datePositions.push(objPos);
            });
        }
    }

    clearOldPositionsFromResource(resource: any): void {
        this.visaWorkflow.items.forEach((user: any) => {

            if (user.signaturePositions === undefined) {
                user.signaturePositions = [];
            } else {
                const signaturePositionsToKeep = [];
                user.signaturePositions.forEach((pos: any) => {
                    if (pos.resId !== resource.resId && pos.mainDocument === resource.mainDocument) {
                        signaturePositionsToKeep.push(pos);
                    } else if (pos.mainDocument !== resource.mainDocument) {
                        signaturePositionsToKeep.push(pos);
                    }
                });
                user.signaturePositions = signaturePositionsToKeep;
            }

            if (user.datePositions === undefined) {
                user.datePositions = [];
            } else {
                const datePositionsToKeep = [];
                user.datePositions.forEach((pos: any) => {
                    if (pos.resId !== resource.resId && pos.mainDocument === resource.mainDocument) {
                        datePositionsToKeep.push(pos);
                    } else if (pos.mainDocument !== resource.mainDocument) {
                        datePositionsToKeep.push(pos);
                    }
                });
                user.datePositions = datePositionsToKeep;
            }
        });
    }

    getDocumentsFromPositions(): { resId: number, mainDocument: boolean }[] {
        const documents: { resId: number, mainDocument: boolean }[] = [];
        this.visaWorkflow.items.forEach((user: any) => {
            user.signaturePositions?.forEach((element: { resId: any; mainDocument: any; }) => {
                documents.push({
                    resId: element.resId,
                    mainDocument: element.mainDocument
                });
            });
            user.datePositions?.forEach((element: { resId: any; mainDocument: any; }) => {
                documents.push({
                    resId: element.resId,
                    mainDocument: element.mainDocument
                });
            });
        });
        return documents;
    }

    hasOtpNoSignaturePositionFromResource(resource: any): boolean {
        let state: boolean = true;
        this.visaWorkflow.items.filter((user: any) => user.item_id === null && user.role === 'sign').forEach((user: any) => {
            if (user.signaturePositions?.filter((pos: any) => pos.resId === resource.resId && pos.mainDocument === resource.mainDocument).length > 0) {
                state = false;
            }
        });
        return state;
    }

    getRouteDatas(): string[] {
        return [this.externalSignatoryBookManagerService.getAutocompleteUsersRoute()];
    }

    getWorkflowTypeLabel(workflowType: string): string {
        return this.workflowTypes.find((item: any) => item.id === workflowType).label;
    }

    setSequenceForWorkflowUsers(): void {
        for (let i = 0; i < this.visaWorkflow.items.length; i++) {
            this.visaWorkflow.items[i].sequence = i;

            if (!this.functions.empty(this.visaWorkflow.items[i].signaturePositions)) {
                this.visaWorkflow.items[i].signaturePositions.forEach((position: SignaturePositionInterface) => {
                    position.sequence = i;
                });
            }

            if (!this.functions.empty(this.visaWorkflow.items[i].datePositions)) {
                this.visaWorkflow.items[i].signaturePositions.forEach((position: DatePositionInterface) => {
                    position.sequence = i;
                });
            }
        }
    }

    sanitizeStep(event: Event, user: UserWorkflowInterface): void {
        const input = event.target as HTMLInputElement;
        const value: number = parseInt(input.value, 10);
        const min: number = 1;
        const steps: number[] = [...new Set(this.visaWorkflow.items.map((user: UserWorkflowInterface) => user.step))];
        const max: number = Math.max(...steps);

        if (isNaN(value) || value < min) {
            input.value = min.toString();
            user.step = min;
        } else if (value > max) {
            input.value = max.toString();
            user.step = max;
        } else {
            user.step = value;
        }
    }

    signatureRequiredForStep(user: UserWorkflowInterface): boolean {
        return user.isSignatureRequired && this.visaWorkflow.items.filter((item: UserWorkflowInterface) => item.step === user.step).length <= 1
    }

    onStepChange(value: number, user: UserWorkflowInterface): void {
        const corrected: number = this.correctStepValue(value, user, this.visaWorkflow.items);

        if (corrected !== value) {
            user.step = corrected;
            setTimeout(() => user.step = corrected);
        } else {
            user.step = value;
        }

        this.correctAllSteps(this.visaWorkflow.items);
        setTimeout(() => this.setSignatureRequiredForStep(user));
    }

    setSignatureRequiredForStep(user: UserWorkflowInterface): void {
        const steps: number[] = this.visaWorkflow.items.map((item: UserWorkflowInterface) => item.step);
        steps.forEach((step: number) => {
            const usersByStep: UserWorkflowInterface[] = this.visaWorkflow.items.filter((item: UserWorkflowInterface) => item.step === step)
            if (usersByStep.length === 1) {
                this.visaWorkflow.items.find((item: UserWorkflowInterface) => item.step === step).isSignatureRequired = true;
            } else {
                usersByStep.forEach((element: UserWorkflowInterface) => {
                    if (user !== element && user.isSignatureRequired !== element.isSignatureRequired) {
                        setTimeout(() => user.isSignatureRequired = element.isSignatureRequired);
                    }
                })
            }
        });
    }

    onSignatureRequiredChange(user: UserWorkflowInterface): void {
        this.visaWorkflow.items.filter((item: UserWorkflowInterface) => item.step === user.step).forEach((item: UserWorkflowInterface) => {
            if (user !== item) {
                item.isSignatureRequired = !user.isSignatureRequired;
            }

        });
        user.isSignatureRequired = !user.isSignatureRequired;
    }

    /**
     * Adjusts the provided step value to ensure it forms a unique, continuous sequence
     * when combined with other workflow steps, without falling below 1.
     *
     * @param {number} currentValue - The current step value to be corrected.
     * @param {UserWorkflowInterface} currentUser - The workflow item corresponding to the current user.
     * @param {UserWorkflowInterface[]} workflowItems - An array of all workflow items.
     * @return {number} The corrected step value that maintains a unique, continuous sequence.
     */
    correctStepValue(
        currentValue: number,
        currentUser: UserWorkflowInterface,
        workflowItems: UserWorkflowInterface[]
    ): number {
        const otherSteps = workflowItems
            .filter(i => i !== currentUser)
            .map(i => i.step);

        let correctedStep = currentValue;

        /**
         * Determines if the given sequence of steps is unique, sorted, and continuous.
         *
         * @param {number[]} steps - An array of numbers representing the sequence of steps.
         * @return {boolean} True if the sequence is unique, sorted, and continuous; otherwise, false.
         */
        function isUniqueSequenceContinuous(steps: number[]): boolean {
            const uniqueSorted = Array.from(new Set(steps)).sort((a, b) => a - b);
            for (let i = 1; i < uniqueSorted.length; i++) {
                if (uniqueSorted[i] !== uniqueSorted[i - 1] + 1) {
                    return false; // Missing a required step -> invalid sequence
                }
            }
            return true;
        }

        while (correctedStep > 0) {
            const testSequence = [...otherSteps, correctedStep];
            if (isUniqueSequenceContinuous(testSequence)) {
                break;
            }
            correctedStep--;
        }

        return correctedStep < 1 ? 1 : correctedStep;
    }

    /**
     * Reorganizes the workflow by sorting the workflow items based on their step numbers in ascending order.
     * It removes duplicate steps, restructures the workflow, updates sequence for workflow users,
     * and emits the updated workflow items.
     *
     * @return {void} Does not return a value.
     */
    reorganizeWorkflow(): void {
        this.loading = true;
        setTimeout(() => {
            const steps: number[] = [...new Set(this.visaWorkflow.items.map((item: UserWorkflowInterface) => item.step))].sort((a, b) => a - b);
            const sortedWorkflow: UserWorkflowInterface[] = [];
            steps.forEach((step: number) => {
                sortedWorkflow.push(...this.visaWorkflow.items.filter((item: UserWorkflowInterface) => item.step === step));
            });
            this.visaWorkflow.items = sortedWorkflow;
            this.setSequenceForWorkflowUsers();
            this.workflowUpdated.emit(this.visaWorkflow.items);
        }, 0)
        this.loading = false;
    }

    getStepMax(): number {
        const steps = this.visaWorkflow.items.map(item => item.step);
        return steps.length > 0 ? Math.max(...steps) + 1 : 1;
    }

    /**
     * Adjusts and corrects the steps of all items in the given list by normalizing
     * them to a sequential order (1, 2, 3, ...). Each unique step is assigned a
     * new step number based on its sorted order.
     *
     * @param {UserWorkflowInterface[]} items - An array of items with a step property
     * which will be corrected and normalized.
     * @return {void} This method does not return a value. It modifies the input array in place.
     */
    correctAllSteps(items: UserWorkflowInterface[]): void {
        // Liste des steps uniques triés
        const uniqueSteps = Array.from(new Set(items.map(i => i.step))).sort((a, b) => a - b);

        // Mapping ancien step → nouveau step (1, 2, 3...)
        const stepMap = new Map<number, number>();
        uniqueSteps.forEach((oldStep, index) => {
            stepMap.set(oldStep, index + 1);
        });

        // Réaffecte les steps corrigés
        items.forEach(item => {
            item.step = stepMap.get(item.step)!;
        });
    }

    getStatus(user: UserWorkflowInterface): string {
        if (!this.externalSignatoryBookManagerService.isMappingWithTechnicalStatus()) {
            const signed: string = this.translate.instant('lang.signed').toLowerCase();
            const refused: string = this.translate.instant('lang.refused').toLowerCase();
            const visaRefused: string = this.translate.instant('lang.visaRefused').toLowerCase();
            const visaApproved: string = this.translate.instant('lang.visaApproved').toLowerCase();
            const status: string = user.status?.toLowerCase();
            if (
                status.includes(signed) || status.includes(refused) ||
                status.includes(visaRefused) || status.includes(visaApproved)
            ) {
                return (user.status.toLowerCase().includes(signed) || user.status.toLowerCase().includes(visaApproved)) ?
                    'VAL' :
                    'REF';
            }
        }

        return user.status;
    }

    formatStatusWithProcessDate(user: UserWorkflowInterface): string {
        let status: string = ['REF', 'VAL'].indexOf(user.status) > -1 ? user.externalSignatureBookStatus : user.status;
        const processDate: string = user.process_date;

        if (!this.functions.empty(processDate)) {
            const [dateOnly, timeOnly] = user.process_date.split(' ');
            status = `${status} | ${this.translate.instant('lang.dateTo')} ${dateOnly} ${this.translate.instant('lang.atRange')} ${timeOnly}`;
        }
        return status;
    }

    getStatusTitle(user: UserWorkflowInterface): string {
        if (!this.externalSignatoryBookManagerService.isMappingWithTechnicalStatus() && ['VAL', 'REF'].indexOf(user.status) === -1) {
            return this.translate.instant('lang.nullStatus');
        }

        const status: string | null = this.functions.empty(user.status) ? null : user.status.toLowerCase();

        return this.translate.instant(`lang.${status}Status`);
    }
}

export interface VisaWorkflowInterface {
    type: string;
    roles: string[];
    items: UserWorkflow[];
}

export class VisaWorkflow implements VisaWorkflowInterface {
    type = null;
    roles = ['visa', 'sign'];
    items = [];

    constructor(json: any = null) {
        if (json) {
            Object.assign(this, json);
        }
    }
}
