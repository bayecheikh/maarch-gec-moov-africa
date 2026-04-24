import { HttpClient } from '@angular/common/http';
import { Component, EventEmitter, Input, OnInit, Output } from '@angular/core';
import { Router } from '@angular/router';
import { ActionsService } from '@appRoot/actions/actions.service';
import { FunctionsService } from '@service/functions.service';
import { NotificationService } from '@service/notification/notification.service';
import { Subscription, catchError, of, tap, firstValueFrom } from 'rxjs';
import { SignatureBookService } from '../signature-book.service';
import { UserStampInterface } from '@models/user-stamp.model';
import { Attachment } from "@models/attachment.model";
import { ActionInterface } from '@models/actions.model';

@Component({
    selector: 'app-maarch-sb-actions',
    templateUrl: 'signature-book-actions.component.html',
    styleUrls: ['signature-book-actions.component.scss'],
})
export class SignatureBookActionsComponent implements OnInit {
    @Input() resId: number;
    @Input() basketId: number;
    @Input() groupId: number;
    @Input() userId: number;
    @Input() userStamp: UserStampInterface;

    @Output() openPanelSignatures = new EventEmitter<true>();
    @Output() docsToSignUpdated = new EventEmitter<Attachment[]>();

    subscription: Subscription;

    loading: boolean = true;

    basketGroupActions: ActionInterface[] = [];

    refusalActions: ActionInterface[] = [];
    validationActions: ActionInterface[] = [];

    selectedValidationAction: ActionInterface;
    selectedRefusalAction: ActionInterface;

    constructor(
        public http: HttpClient,
        public functions: FunctionsService,
        public signatureBookService: SignatureBookService,
        private notify: NotificationService,
        private actionsService: ActionsService,
        private router: Router
    ) { }

    async ngOnInit(): Promise<void> {
        this.loading = true;
        try {
            await this.getBasketGroupActions();
        } finally {
            this.loading = false;
        }
    }

    async getBasketGroupActions(): Promise<void> {
        try {
            await this.loadActions();

            const basketGroupActionsMap = new Map(
                this.basketGroupActions.map(action => [action.id, action])
            );

            const { validations, refusals } = this.signatureBookService.basketGroupActions
                .reduce((acc, action) => {
                    const fullAction = basketGroupActionsMap.get(action.id);
                    if (fullAction) {
                        acc[action.type === 'valid' ? 'validations' : 'refusals'].push(fullAction);
                    }
                    return acc;
                }, { validations: [] as ActionInterface[], refusals: [] as ActionInterface[] });

            this.validationActions = validations;
            this.refusalActions = refusals;
            this.selectedValidationAction = validations[0] ?? null;
            this.selectedRefusalAction = refusals[0] ?? null;

        } catch (err: any) {
            this.notify.handleSoftErrors(err);
        }
    }

    async loadActions(): Promise<ActionInterface[]> {
        try {
            const actions = await firstValueFrom(
                this.actionsService.getActions(this.userId, this.groupId, this.basketId, this.resId)
            );
            this.basketGroupActions = actions;
            return actions;
        } catch (err: any) {
            this.notify.handleSoftErrors(err);
            this.basketGroupActions = [];
            return [];
        }
    }

    openSignaturesList(): void {
        this.openPanelSignatures.emit(true);
    }

    async processAction(action: ActionInterface): Promise<void> {
        let resIds: number[] = [this.resId];
        resIds = resIds.concat(this.signatureBookService.selectedResources.map((resource: Attachment) => resource.resIdMaster));
        // Get docs to sign attached to the current resource by default if the selection is empty
        const docsToSign: Attachment[] = this.signatureBookService.getDocsToSign();
        this.http
            .get(`../rest/resources/${this.resId}?light=true`)
            .pipe(
                tap((data: any) => {
                    this.actionsService.launchAction(
                        action,
                        this.userId,
                        this.groupId,
                        this.basketId,
                        [... new Set(resIds)],
                        { ...data, docsToSign: [... new Set(docsToSign)] },
                        false
                    );
                }),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            )
            .subscribe();
    }

    processAfterAction(): void {
        this.backToBasket();
    }

    backToBasket(): void {
        const path = '/basketList/users/' + this.userId + '/groups/' + this.groupId + '/baskets/' + this.basketId;
        this.router.navigate([path]);
    }

    signWithStamp(userStamp: UserStampInterface): void {
        this.actionsService.emitActionWithData({
            id: 'selectedStamp',
            data: userStamp,
        });
    }

    canShowStamps(): boolean {
        const selectedDocAttachment = this.signatureBookService.selectedDocToSign.attachment;

        if (!selectedDocAttachment) {
            return true;
        }

        const hasDigitalSignature = selectedDocAttachment.hasDigitalSignature ?? false;
        if (!hasDigitalSignature) {
            return true;
        } else {
            return this.signatureBookService.currentWorkflowRole !== 'visa';
        }
    }
}
