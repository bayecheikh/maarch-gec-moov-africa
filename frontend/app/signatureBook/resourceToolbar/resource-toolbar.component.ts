import { Component, Input, Output, OnInit, ViewChild, EventEmitter } from '@angular/core';
import { TranslateService } from "@ngx-translate/core";
import { PrivilegeService } from "@service/privileges.service";
import { catchError, tap } from "rxjs/operators";
import { of } from "rxjs";
import { HttpClient } from "@angular/common/http";
import { NotificationService } from "@service/notification/notification.service";
import { DiffusionsListComponent } from "@appRoot/diffusions/diffusions-list.component";
import { VisaWorkflowComponent } from "@appRoot/visa/visa-workflow.component";
import { AvisWorkflowComponent } from "@appRoot/avis/avis-workflow.component";
import { NotesListComponent } from "@appRoot/notes/notes-list.component";
import { ConfirmComponent } from "@plugins/modal/confirm.component";
import { MatLegacyDialog as MatDialog } from '@angular/material/legacy-dialog';
import { ActionsService } from '../../actions/actions.service';
import { HeaderService } from '@service/header.service';
import { FunctionsService } from '@service/functions.service';
import { Router } from "@angular/router";
import { UserWorkflowInterface } from '@models/user-workflow.model';
import { SignatureBookService } from '../signature-book.service';

@Component({
    selector: 'app-resource-toolbar',
    templateUrl: 'resource-toolbar.component.html',
    styleUrls: ['resource-toolbar.component.scss'],
})
export class ResourceToolbarComponent implements OnInit {
    @ViewChild('appDiffusionsList', { static: false }) appDiffusionsList: DiffusionsListComponent;
    @ViewChild('appVisaWorkflow', { static: false }) appVisaWorkflow: VisaWorkflowComponent;
    @ViewChild('appAvisWorkflow', { static: false }) appAvisWorkflow: AvisWorkflowComponent;
    @ViewChild('appNotesList', { static: false }) appNotesList: NotesListComponent;

    @Output() refreshVisaWorkflow = new EventEmitter<void>();
    @Output() attachmentsCreationSb = new EventEmitter<{ resId: number, type: string, signable: boolean }[]>();
    @Output() attachmentsDeletionSb = new EventEmitter<number>();
    @Output() attachmentsModificationSb = new EventEmitter<number>();

    @Input() resId: number;
    @Input() userId: number;
    @Input() groupId: number;
    @Input() basketId: number;

    currentTool: string = 'visaCircuit';
    modelId: number;

    processTool: any[] = [
        {
            id: 'dashboard',
            icon: 'fas fa-columns',
            label: this.translate.instant('lang.newsFeed'),
            disabled: false,
            count: 0
        },
        {
            id: 'history',
            icon: 'fas fa-history',
            label: this.translate.instant('lang.history'),
            disabled: false,
            count: 0
        },
        {
            id: 'notes',
            icon: 'fas fa-pen-square',
            label: this.translate.instant('lang.notesAlt'),
            disabled: false,
            count: 0
        },
        {
            id: 'attachments',
            icon: 'fas fa-paperclip',
            label: this.translate.instant('lang.attachments'),
            disabled: false,
            count: 0
        },
        {
            id: 'linkedResources',
            icon: 'fas fa-link',
            label: this.translate.instant('lang.links'),
            disabled: false,
            count: 0
        },
        {
            id: 'emails',
            icon: 'fas fa-envelope',
            label: this.translate.instant('lang.mailsSentAlt'),
            disabled: false,
            count: 0
        },
        {
            id: 'diffusionList',
            icon: 'fas fa-share-alt',
            label: this.translate.instant('lang.diffusionList'),
            disabled: false,
            editMode: false,
            count: 0
        },
        {
            id: 'visaCircuit',
            icon: 'fas fa-list-ol',
            label: this.translate.instant('lang.visaWorkflow'),
            disabled: false,
            count: 0
        },
        {
            id: 'opinionCircuit',
            icon: 'fas fa-comment-alt',
            label: this.translate.instant('lang.avis'),
            disabled: false,
            count: 0
        },
        {
            id: 'info',
            icon: 'fas fa-info-circle',
            label: this.translate.instant('lang.informations'),
            disabled: false,
            count: 0
        }
    ];

    constructor(
        public http: HttpClient,
        public translate: TranslateService,
        public privilegeService: PrivilegeService,
        public dialog: MatDialog,
        public actionService: ActionsService,
        public headerService: HeaderService,
        public functions: FunctionsService,
        public signatureBookService: SignatureBookService,
        public router: Router,
        private notify: NotificationService
    ) { }

    ngOnInit(): void {
        this.loadBadges();
    }

    async changeTab(tabId: string): Promise<void> {
        if (!this.modelId && tabId === 'info') {
            const res: false | number = await this.getResourceInformation();
            if (res) {
                this.modelId = res;
            }
        }
        this.currentTool = tabId;
    }

    getResourceInformation() : Promise<false | number> {
        return new Promise((resolve) => {
            this.http.get(`../rest/resources/${this.resId}?light=true`).pipe(
                tap((data: any) => {
                    resolve(data.modelId);
                }),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    resolve(false);
                    return of(false);
                })
            ).subscribe();
        })
    }

    loadBadges(): void {
        this.http.get(`../rest/resources/${this.resId}/items`).pipe(
            tap((data: any) => {
                this.processTool.forEach(element => {
                    element.count = data[element.id] !== undefined ? data[element.id] : 0;
                });
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    refreshBadge(countItems: any, id: string) {
        this.processTool.filter(tool => tool.id === id)[0].count = countItems;
    }

    isToolModified(): boolean {
        if (this.currentTool === 'diffusionList' && this.appDiffusionsList?.isModified()) {
            return true;
        } else if (this.currentTool === 'visaCircuit' && this.appVisaWorkflow?.isModified() && !this.appVisaWorkflow?.workflowParametersNotValid()) {
            return true;
        } else if (this.currentTool === 'opinionCircuit' && this.appAvisWorkflow?.isModified()) {
            return true;
        } else if (this.currentTool === 'notes' && this.appNotesList?.isModified()) {
            return true;
        } else {
            return false;
        }
    }

    async saveTool(): Promise<void> {
        if (this.currentTool === 'diffusionList') {
            await this.appDiffusionsList?.saveListinstance();
            this.loadBadges();
        } else if (this.currentTool === 'visaCircuit') {
            const dialogRef = this.dialog.open(
                ConfirmComponent,
                {
                    panelClass: 'maarch-modal',
                    autoFocus: false,
                    disableClose: true,
                    data: {
                        title: this.translate.instant('lang.confirmAction'),
                        msg: this.translate.instant('lang.updateWorkflowWarning'),
                        buttonValidate: this.translate.instant('lang.validate')
                    }
                });

            dialogRef.afterClosed().pipe(
                tap(async (response: any) => {
                    if (response === 'ok') {
                        if (this.appVisaWorkflow.getWorkflow().filter((user: any) => this.functions.empty(user.process_date))[0]?.item_id !== this.headerService.user.id) {
                            this.actionService.stopRefreshResourceLock();
                            this.actionService.unlockResource(this.userId, this.groupId, this.basketId, [this.resId]);
                        }

                        const resWorkflow = await this.appVisaWorkflow?.saveVisaWorkflow();
                        if (resWorkflow) {
                            if (this.appVisaWorkflow.getWorkflow().filter((user: any) => this.functions.empty(user.process_date))[0]?.item_id !== this.headerService.user.id) {
                                const path = '/basketList/users/' + this.userId + '/groups/' + this.groupId + '/baskets/' + this.basketId;
                                this.router.navigate([path]);
                            } else {
                                this.actionService.lockResource(this.userId, this.groupId, this.basketId, [this.resId]);
                                this.refreshVisaWorkflow.emit();
                            }
                        } else {
                            this.actionService.lockResource(this.userId, this.groupId, this.basketId, [this.resId]);
                        }
                        this.loadBadges();
                    } else {
                        this.appVisaWorkflow.cancelModifications();
                    }
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        } else if (this.currentTool === 'opinionCircuit') {
            await this.appAvisWorkflow?.saveAvisWorkflow();
            this.loadBadges();
        } else if (this.currentTool === 'notes') {
            await this.appNotesList?.addNote();
            this.loadBadges();
        }
    }

    updateCurrentWorkflowUserRole(): void {
        this.signatureBookService.currentWorkflowRole = this.appVisaWorkflow.visaWorkflow.items.filter((user: UserWorkflowInterface) => this.functions.empty(user.process_date))[0].currentRole;
    }
}
