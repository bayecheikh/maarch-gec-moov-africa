import { Component, Input, OnInit, ElementRef, ViewChild, Output, EventEmitter } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { NotificationService } from '@service/notification/notification.service';
import { CdkDragDrop, moveItemInArray } from '@angular/cdk/drag-drop';
import { FunctionsService } from '@service/functions.service';
import { tap, exhaustMap, map, startWith, catchError, finalize, filter } from 'rxjs/operators';
import { UntypedFormControl } from '@angular/forms';
import { LatinisePipe, ScanPipe } from 'ngx-pipes';
import { Observable, of } from 'rxjs';
import { MatLegacyDialog as MatDialog } from '@angular/material/legacy-dialog';
import { AddVisaModelModalComponent } from './addVisaModel/add-visa-model-modal.component';
import { ConfirmComponent } from '@plugins/modal/confirm.component';
import { ActivatedRoute } from '@angular/router';
import { PrivilegeService } from '@service/privileges.service';
import { HeaderService } from '@service/header.service';
import { UserWorkflowInterface } from '@models/user-workflow.model';
import { WorkflowItemsInterface } from '@models/maarch-plugin-fortify-model';
import { SignaturePositionInterface } from '@models/signature-position.model';
import { SignatureBookService } from '@appRoot/signatureBook/signature-book.service';
@Component({
    selector: 'app-visa-workflow',
    templateUrl: 'visa-workflow.component.html',
    styleUrls: ['visa-workflow.component.scss'],
    providers: [ScanPipe]
})
export class VisaWorkflowComponent implements OnInit {

    @Input() injectDatas: any;
    @Input() target: string = '';
    @Input() adminMode: boolean;
    @Input() resId: number = null;
    @Input() lockVisaCircuit: boolean = false;

    @Input() showListModels: boolean = true;
    @Input() showComment: boolean = true;

    @Input() visaWorkflowFromAction: UserWorkflowInterface[] = [];

    @Input() signaturePositions: SignaturePositionInterface[] = [];

    @Output() workflowUpdated = new EventEmitter<any>();
    @Output() refreshActionsList = new EventEmitter<boolean>();
    @Output() workflowLoaded = new EventEmitter<{ currentUserIndex: number, items: WorkflowItemsInterface[]}>();

    @ViewChild('searchVisaSignUserInput', { static: false }) searchVisaSignUserInput: ElementRef;

    visaWorkflow: any = {
        roles: ['sign', 'visa'],
        items: []
    };
    visaWorkflowClone: any = [];
    visaTemplates: any = {
        private: [],
        public: []
    };

    signVisaUsers: any = [];
    filteredSignVisaUsers: Observable<string[]>;
    filteredPublicModels: Observable<string[]>;
    filteredPrivateModels: Observable<string[]>;

    loading: boolean = false;
    hasHistory: boolean = false;
    visaModelListNotLoaded: boolean = true;
    data: any;

    searchVisaSignUser = new UntypedFormControl();

    loadedInConstructor: boolean = false;

    workflowSignatoryRole: string = '';

    minimumVisaRole: number = 0;
    maximumSignRole: number = 0;

    visaNumberCorrect: boolean = true;
    signNumberCorrect: boolean = true;
    atLeastOneSign: boolean = true;
    lastOneIsSign: boolean = true;
    lastOneMustBeSignatory: boolean = false;

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        private notify: NotificationService,
        public functions: FunctionsService,
        private latinisePipe: LatinisePipe,
        public dialog: MatDialog,
        private scanPipe: ScanPipe,
        private route: ActivatedRoute,
        private privilegeService: PrivilegeService,
        public headerService: HeaderService,
        public signatureBookService: SignatureBookService
    ) {
        // ngOnInit is not called if navigating in the same component : must be in constructor for this case
        this.route.params.subscribe(params => {
            this.loading = true;

            this.resId = params['resId'];

            if (!this.functions.empty(this.resId)) {
                this.loadedInConstructor = true;
                this.loadWorkflow(this.resId);
            } else {
                this.loadedInConstructor = false;
            }

        }, (err: any) => {
            this.notify.handleErrors(err);
        });
    }

    async ngOnInit(): Promise<void> {
        if (!this.functions.empty(this.visaWorkflowFromAction)) {
            this.visaWorkflow.items = this.visaWorkflowFromAction;
        }
        if (this.adminMode) {
            this.loadVisaSignParameters();
        }
        if (!this.functions.empty(this.resId) && !this.loadedInConstructor) {
            // this.initFilterVisaModelList();
            this.loadWorkflow(this.resId);
        } else {
            this.loading = false;
        }
    }

    drop(event: CdkDragDrop<string[]>) {
        if (event.previousContainer === event.container) {
            if (this.canManageUser(this.visaWorkflow.items[event.currentIndex], event.currentIndex)) {
                moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
                this.setSequenceForWorkflowUsers();
                this.checkWorkflowParameters(event.container.data);
                this.workflowUpdated.emit(event.container.data);
            } else {
                this.notify.error(this.translate.instant('lang.moveVisaUserErr', { value1: this.visaWorkflow.items[event.previousIndex].labelToDisplay }));
            }
        }
    }

    loadListModel(entityId: number) {
        this.loading = true;

        const route = `../rest/listTemplates/entities/${entityId}?type=visaCircuit`;

        return new Promise((resolve) => {
            this.http.get(route).pipe(
                tap((data: any) => {
                    this.visaWorkflow.items = [];
                    if (data.listTemplates[0]) {
                        this.visaWorkflow.items = data.listTemplates[0].items.map((item: any) => ({
                            ...item,
                            item_entity: item.descriptionToDisplay,
                            requested_signature: item.item_mode !== 'visa',
                            currentRole: item.item_mode
                        }));
                    }
                    this.visaWorkflowClone = JSON.parse(JSON.stringify(this.visaWorkflow.items));
                    this.checkWorkflowParameters(this.visaWorkflow.items);
                    this.setSequenceForWorkflowUsers();
                }),
                finalize(async () => {
                    await this.loadVisaSignParameters().then(() => {
                        this.checkWorkflowParameters(this.getWorkflow());
                    });
                    this.loading = false;
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    resolve(false);
                    return of(false);
                })
            ).subscribe();
        });
    }

    loadVisaSignUsersList() {
        return new Promise((resolve) => {
            this.http.get('../rest/autocomplete/users/circuit').pipe(
                map((data: any) => {
                    data = data.map((user: any) => ({
                        id: user.id,
                        title: `${user.idToDisplay} (${user.otherInfo})`,
                        label: user.idToDisplay,
                        entity: user.otherInfo,
                        type: 'user',
                        hasPrivilege: true,
                        isValid: true,
                        currentRole: 'visa',
                        status: user.status ?? ''
                    }));
                    return data;
                }),
                tap((data) => {
                    this.signVisaUsers = data;
                    this.filteredSignVisaUsers = this.searchVisaSignUser.valueChanges
                        .pipe(
                            startWith(''),
                            map(value => this._filter(value))
                        );
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    async loadVisaModelList() {
        if (!this.functions.empty(this.resId)) {
            await this.loadDefaultModel();
        }

        return new Promise((resolve) => {
            this.http.get('../rest/availableCircuits?circuit=visa').pipe(
                tap((data: any) => {
                    this.visaTemplates.public = this.visaTemplates.public.concat(data.circuits.filter((item: any) => !item.private).map((item: any) => ({
                        id: item.id,
                        title: item.title,
                        label: item.title,
                        type: 'entity'
                    })));

                    this.visaTemplates.private = data.circuits.filter((item: any) => item.private).map((item: any) => ({
                        id: item.id,
                        title: item.title,
                        label: item.title,
                        type: 'entity'
                    }));
                    this.filteredPublicModels = this.searchVisaSignUser.valueChanges
                        .pipe(
                            startWith(''),
                            map(value => this._filterPublicModel(value))
                        );
                    this.filteredPrivateModels = this.searchVisaSignUser.valueChanges
                        .pipe(
                            startWith(''),
                            map(value => this._filterPrivateModel(value))
                        );
                    resolve(true);
                })
            ).subscribe();
        });
    }

    loadDefaultModel() {
        this.visaTemplates.public = [];
        return new Promise((resolve) => {
            this.http.get(`../rest/resources/${this.resId}/defaultCircuit?circuit=visa`).pipe(
                filter((data: any) => !this.functions.empty(data.circuit)),
                tap((data: any) => {
                    if (!this.functions.empty(data.circuit)) {
                        this.visaTemplates.public.push({
                            id: data.circuit.id,
                            title: data.circuit.title,
                            label: data.circuit.title,
                            type: 'entity'
                        });
                    }
                }),
                finalize(() => resolve(true)),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    async initFilterVisaModelList() {
        if (this.visaModelListNotLoaded) {
            await this.loadVisaSignUsersList();

            if (this.showListModels) {
                await this.loadVisaModelList();
            }

            this.searchVisaSignUser.reset();

            this.visaModelListNotLoaded = false;
        }
    }

    loadWorkflow(resId: number) {
        this.resId = resId;
        this.loading = true;
        this.visaWorkflow.items = [];
        return new Promise((resolve) => {
            this.http.get('../rest/resources/' + resId + '/visaCircuit').pipe(
                tap((data: any) => {
                    if (!this.functions.empty(data.circuit)) {
                        data.circuit.forEach((element: any, index: number) => {
                            this.visaWorkflow.items.push(
                                {
                                    ...element,
                                    difflist_type: 'VISA_CIRCUIT',
                                    currentRole: this.getCurrentRole(element),
                                    signaturePositions: [],
                                    sequence: index
                                });
                        });
                        this.setSequenceForWorkflowUsers();
                        this.visaWorkflowClone = JSON.parse(JSON.stringify(this.visaWorkflow.items));
                    }
                    this.hasHistory = data.hasHistory;
                    this.workflowLoaded.emit(
                        {
                            currentUserIndex: this.getCurrentVisaUserIndex(),
                            items: this.visaWorkflow.items.map((user: any) => ({
                                userId: user.externalId,
                                mode: user.currentRole,
                                processDate: user.process_date,
                                signaturePositions: []
                            }))
                        }
                    );
                }),
                finalize(async () => {
                    await this.loadVisaSignParameters();
                    this.checkWorkflowParameters(this.getWorkflow());
                    this.loading = false;
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    loadDefaultWorkflow(resId: number) {
        this.loading = true;
        return new Promise((resolve) => {
            this.http.get('../rest/resources/' + resId + '/defaultCircuit?circuit=visaCircuit').pipe(
                filter((data: any) => !this.functions.empty(data.circuit)),
                tap((data: any) => {
                    this.visaWorkflow.items = [];
                    data.circuit.items.forEach((element: any, index: number) => {
                        this.visaWorkflow.items.push(
                            {
                                ...element,
                                requested_signature: element.item_mode !== 'visa',
                                difflist_type: 'VISA_CIRCUIT',
                                signaturePositions: [],
                                sequence: index
                            });
                    });
                    this.visaWorkflowClone = JSON.parse(JSON.stringify(this.visaWorkflow.items));
                    this.setSequenceForWorkflowUsers();
                    this.checkWorkflowParameters(this.visaWorkflow.items);
                    this.workflowUpdated.emit(this.visaWorkflow.items);
                }),
                finalize(async () => {
                    await this.loadVisaSignParameters();
                    this.checkWorkflowParameters(this.getWorkflow());
                    this.loading = false;
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    resolve(false);
                    this.loading = false;
                    return of(false);
                })
            ).subscribe();
        });
    }

    deleteItem(index: number) {
        this.visaWorkflow.items.splice(index, 1);
        this.setSequenceForWorkflowUsers();
        this.checkWorkflowParameters(this.visaWorkflow.items);
        this.workflowUpdated.emit(this.visaWorkflow.items);
    }

    getVisaCount() {
        return this.visaWorkflow.items.length;
    }

    changeRole(i: number) {
        this.visaWorkflow.items[i].requested_signature = !this.visaWorkflow.items[i].requested_signature;
        this.visaWorkflow.items[i].currentRole = this.visaWorkflow.items[i].requested_signature ? 'sign' : 'visa';
        this.checkWorkflowParameters(this.visaWorkflow.items);
        this.setSequenceForWorkflowUsers();
        this.workflowUpdated.emit(this.visaWorkflow.items);
    }

    getWorkflow() {
        return this.visaWorkflow.items;
    }

    getCurrentVisaUserIndex() {
        if (this.functions.empty(this.getLastVisaUser()?.listinstance_id)) {
            const index = 0;
            return this.getRealIndex(index);
        } else {
            let index = this.visaWorkflow.items.map((item: any) => item.listinstance_id).indexOf(this.getLastVisaUser()?.listinstance_id);
            index++;
            return this.getRealIndex(index);
        }
    }

    getFirstVisaUser() {
        return !this.functions.empty(this.visaWorkflow.items[0]) && this.visaWorkflow.items[0].isValid ? this.visaWorkflow.items[0] : '';
    }

    /* getCurrentVisaUser() {

        const index = this.visaWorkflow.items.map((item: any) => item.listinstance_id).indexOf(this.getLastVisaUser().listinstance_id);

        return !this.functions.empty(this.visaWorkflow.items[index + 1]) ? this.visaWorkflow.items[index + 1] : '';
    }*/

    getNextVisaUser() {
        let index = this.getCurrentVisaUserIndex();
        index = index + 1;
        const realIndex = this.getRealIndex(index);

        return !this.functions.empty(this.visaWorkflow.items[realIndex]) ? this.visaWorkflow.items[realIndex] : '';
    }

    getLastVisaUser() {
        const arrOnlyProcess = this.visaWorkflow.items.filter((item: any) => !this.functions.empty(item.process_date) && item.isValid);

        return !this.functions.empty(arrOnlyProcess[arrOnlyProcess.length - 1]) ? arrOnlyProcess[arrOnlyProcess.length - 1] : null;
    }

    getRealIndex(index: number) {
        while (index < this.visaWorkflow.items.length && !this.visaWorkflow.items[index].isValid) {
            index++;
        }
        return index;
    }

    checkExternalSignatoryBook() {
        return this.visaWorkflow.items.filter((item: any) => this.functions.empty(item.externalId)).map((item: any) => item.labelToDisplay);
    }

    saveVisaWorkflow(resIds: number[] = [this.resId]): Promise<boolean> {
        return new Promise((resolve) => {
            if (this.visaWorkflow.items.length === 0) {
                this.http.delete(`../rest/resources/${resIds[0]}/circuits/visaCircuit`).pipe(
                    tap(() => {
                        this.visaWorkflowClone = JSON.parse(JSON.stringify(this.visaWorkflow.items));
                        this.notify.success(this.translate.instant('lang.visaWorkflowDeleted'));
                        this.refreshActionsList.emit(true);
                        resolve(true);
                    }),
                    catchError((err: any) => {
                        this.notify.handleSoftErrors(err);
                        resolve(false);
                        return of(false);
                    })
                ).subscribe();
            } else if (this.isValidWorkflow()) {
                this.reOrderSequences();
                const arrVisa = resIds.map(resId => ({
                    resId: resId,
                    listInstances: this.visaWorkflow.items
                }));
                this.http.put('../rest/circuits/visaCircuit', { resources: arrVisa }).pipe(
                    tap(() => {
                        this.visaWorkflowClone = JSON.parse(JSON.stringify(this.visaWorkflow.items));
                        this.notify.success(this.translate.instant('lang.visaWorkflowUpdated'));
                        this.refreshActionsList.emit(true);
                        resolve(true);
                    }),
                    catchError((err: any) => {
                        this.notify.handleSoftErrors(err);
                        resolve(false);
                        return of(false);
                    })
                ).subscribe();
            } else {
                this.notify.error(this.getError());
                resolve(false);
            }
        });
    }

    addItemToWorkflow(item: any) {
        return new Promise((resolve) => {
            if (item.type === 'user') {
                const requestedSignature = !this.functions.empty(item.requested_signature) ? item.requested_signature : false;
                this.visaWorkflow.items.push({
                    item_id: item.id,
                    item_type: 'user',
                    item_entity: item.entity,
                    labelToDisplay: item.label,
                    externalId: !this.functions.empty(item.externalId) ? item.externalId : null,
                    difflist_type: 'VISA_CIRCUIT',
                    signatory: !this.functions.empty(item.signatory) ? item.signatory : false,
                    requested_signature: requestedSignature,
                    hasPrivilege: item.hasPrivilege,
                    isValid: item.isValid,
                    currentRole: requestedSignature ? 'sign' : 'visa',
                    signaturePositions: [],
                    sequence: this.visaWorkflow.items.length,
                    status: item.status ?? ''
                });
                this.setSequenceForWorkflowUsers();
                this.searchVisaSignUser.reset();
                this.searchVisaSignUserInput.nativeElement.blur();
                this.checkWorkflowParameters(this.visaWorkflow.items);
                this.workflowUpdated.emit(this.visaWorkflow.items);
                resolve(true);
            } else if (item.type === 'entity') {
                this.http.get(`../rest/listTemplates/${item.id}`).pipe(
                    tap((data: any) => {
                        this.visaWorkflow.items = this.visaWorkflow.items.concat(

                            data.listTemplate.items.map((itemTemplate: any, index: number) => ({
                                item_id: itemTemplate.item_id,
                                item_type: 'user',
                                labelToDisplay: itemTemplate.idToDisplay,
                                item_entity: itemTemplate.descriptionToDisplay,
                                difflist_type: 'VISA_CIRCUIT',
                                signatory: false,
                                requested_signature: itemTemplate.item_mode === 'sign',
                                hasPrivilege: itemTemplate.hasPrivilege,
                                isValid: itemTemplate.isValid,
                                currentRole: itemTemplate.item_mode,
                                sequence: index,
                                signaturePositions: [],
                            }))
                        );
                        this.setSequenceForWorkflowUsers();
                        this.searchVisaSignUserInput.nativeElement.blur();
                        this.checkWorkflowParameters(this.visaWorkflow.items);
                        this.workflowUpdated.emit(this.visaWorkflow.items);
                        this.searchVisaSignUser.reset();
                        resolve(true);
                    })
                ).subscribe();
            }
        });
    }

    resetWorkflow() {
        this.visaWorkflow.items = [];
    }

    isValidWorkflow() {
        if ((this.visaWorkflow.items.filter((item: any) => (!item.hasPrivilege || !item.isValid) && (item.process_date === null || this.functions.empty(item.process_date))).length === 0) && this.visaWorkflow.items.length > 0) {
            if (this.workflowSignatoryRole === 'optional') {
                return true;
            } else {
                return this.visaWorkflow.items.filter((item: any) => item.requested_signature).length > 0;
            }
        } else {
            return false;
        }
    }

    getError() {
        if (this.visaWorkflow.items.filter((item: any) => item.requested_signature).length === 0) {
            return this.translate.instant('lang.signUserRequired');
        } else if (this.visaWorkflow.items.filter((item: any) => !item.hasPrivilege).length > 0) {
            return this.translate.instant('lang.mustDeleteUsersWithNoPrivileges');
        } else if (this.visaWorkflow.items.filter((item: any) => !item.isValid && (item.process_date === null || this.functions.empty(item.process_date))).length > 0) {
            return this.translate.instant('lang.mustDeleteInvalidUsers');
        }
    }

    emptyWorkflow() {
        return this.visaWorkflow.items.length === 0;
    }

    workflowEnd() {
        if (this.visaWorkflow.items.filter((item: any) => !this.functions.empty(item.process_date)).length === this.visaWorkflow.items.length) {
            return true;
        } else {
            return false;
        }
    }

    openPromptSaveModel() {
        const dialogRef = this.dialog.open(AddVisaModelModalComponent, { panelClass: 'maarch-modal', data: { visaWorkflow: this.visaWorkflow.items } });

        dialogRef.afterClosed().pipe(
            filter((data: string) => !this.functions.empty(data)),

            tap((data: any) => {
                this.visaTemplates.private.push({
                    id: data.id,
                    title: data.title,
                    label: data.title,
                    type: 'entity'
                });
                this.searchVisaSignUser.reset();
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    deletePrivateModel(model: any) {
        const dialogRef = this.dialog.open(ConfirmComponent, { panelClass: 'maarch-modal', autoFocus: false, disableClose: true, data: { title: this.translate.instant('lang.delete'), msg: this.translate.instant('lang.confirmAction') } });

        dialogRef.afterClosed().pipe(
            filter((data: string) => data === 'ok'),
            exhaustMap(() => this.http.delete(`../rest/listTemplates/${model.id}`)),
            tap(() => {
                this.visaTemplates.private = this.visaTemplates.private.filter((template: any) => template.id !== model.id);
                this.searchVisaSignUser.reset();
                this.notify.success(this.translate.instant('lang.modelDeleted'));
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    isModified() {
        return !(this.loading || JSON.stringify(this.visaWorkflow.items) === JSON.stringify(this.visaWorkflowClone));
    }

    canManageUser(item: any, i: number) {
        if (this.adminMode) {
            if (!this.functions.empty(item.process_date)) {
                return false;
            } else if (this.target === 'signatureBook' && this.getCurrentVisaUserIndex() === i) {
                return this.privilegeService.hasCurrentUserPrivilege('modify_visa_in_signatureBook');
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    getCurrentRole(item: any) {
        if (this.functions.empty(item.process_date)) {
            return item.requested_signature ? 'sign' : 'visa';
        } else {
            if (this.stringIncludes(item.process_comment, this.translate.instant('lang.visaWorkflowInterrupted'))) {
                return item.requested_signature ? 'sign' : 'visa';
            } else {
                return item.signatory ? 'sign' : 'visa';
            }
        }
    }

    stringIncludes(source: any, search: any) {
        if (source === undefined || source === null) {
            return false;
        }

        return source.includes(search);
    }

    cancelModifications() {
        this.visaWorkflow.items = this.visaWorkflowClone;
        this.visaWorkflowClone = JSON.parse(JSON.stringify(this.visaWorkflow.items));
    }

    async loadVisaSignParameters(): Promise<boolean> {
        return new Promise((resolve) => {
            this.http.get('../rest/parameters/minimumVisaRole').pipe(
                tap((data: any) => {
                    this.minimumVisaRole = data.parameter.param_value_int;
                }),
                exhaustMap(() => this.http.get('../rest/parameters/maximumSignRole')),
                tap((data: any) => {
                    this.maximumSignRole = data.parameter.param_value_int;
                }),
                exhaustMap(() => this.http.get('../rest/parameters/workflowSignatoryRole')),
                tap((data: any) => {
                    if (!this.functions.empty(data.parameter)) {
                        this.workflowSignatoryRole = data.parameter.param_value_string;
                    }
                }),
                finalize(() => {
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    resolve(false);
                    return of(false);
                })
            ).subscribe();
        });
    }

    checkWorkflowParameters(items: any[]): void {
        let nbVisaRole: number = 0;
        let nbSignRole: number = 0;
        items.forEach(item => {
            if (this.functions.empty(item?.process_date)) {
                if (item.requested_signature) {
                    nbSignRole++;
                } else {
                    nbVisaRole++;
                }
            } else {
                if (item.signatory) {
                    nbSignRole++;
                } else {
                    nbVisaRole++;
                }
            }
        });

        if (['optional', 'mandatory_final'].indexOf(this.workflowSignatoryRole) > -1) {
            this.lastOneMustBeSignatory = this.workflowSignatoryRole === 'mandatory_final';
            this.atLeastOneSign = true;
        } else {
            this.atLeastOneSign = nbSignRole >= 1;
        }

        if (this.maximumSignRole !== 0 || this.minimumVisaRole !== 0) {
            this.visaNumberCorrect = this.minimumVisaRole === 0 || nbVisaRole >= this.minimumVisaRole;
            this.signNumberCorrect = this.maximumSignRole === 0 || nbSignRole <= this.maximumSignRole;
        }

        if (this.lastOneMustBeSignatory) {
            const lastItem: UserWorkflowInterface = items[items.length - 1];
            this.lastOneIsSign = this.functions.empty(lastItem?.process_date) ? lastItem?.requested_signature : lastItem.signatory;
        }
    }

    workflowParametersNotValid(): boolean {
        if (this.visaWorkflow.items.length === 0) {
            return false;
        }
        return !this.loading && (!this.visaNumberCorrect || !this.signNumberCorrect || !this.atLeastOneSign || (!this.lastOneIsSign && this.lastOneMustBeSignatory));
    }

    setPositionsWorkflow(resource: ResourceInterface, positions: { signaturePositions: SignaturePositionInterface[] }): void {
        this.clearOldPositionsFromResource(resource);

        if (!this.functions.empty(positions.signaturePositions)) {
            Object.keys(positions.signaturePositions).forEach(key => {
                const objPos = {
                    ...positions.signaturePositions[key],
                    mainDocument : resource.mainDocument,
                    resId: resource.resId
                };
                this.visaWorkflow.items[positions.signaturePositions[key].sequence].signaturePositions.push(objPos);
            });
        }
    }

    clearOldPositionsFromResource(resource: ResourceInterface): void {
        this.visaWorkflow.items.forEach((user: UserWorkflowInterface, index: number) => {

            if (this.functions.empty(user.signaturePositions)) {
                user.signaturePositions = [];
            } else {
                const signaturePositionsToKeep = [];
                user.signaturePositions.forEach((pos: any) => {
                    pos.sequence = index;
                    if (pos.resId !== resource.resId && pos.mainDocument === resource.mainDocument) {
                        signaturePositionsToKeep.push(pos);
                    } else if (pos.mainDocument !== resource.mainDocument) {
                        signaturePositionsToKeep.push(pos);
                    }
                });
                user.signaturePositions = signaturePositionsToKeep;
            }
        });
    }

    setSequenceForWorkflowUsers(): void {
        if (this.signaturePositions.length > 0) {
            // If all signature positions come from the template
            if (this.signaturePositions.every((data: SignaturePositionInterface) => data.isFromTemplate)) {
                // Apply the positions retrieved from the template
                this.setSignaturePositionsRetrievedFromTemplate();
            }
            // If some signature positions come from the template and others are manually adjusted
            else if (this.signaturePositions.some((data: SignaturePositionInterface) => data.isFromTemplate)) {
                this.signaturePositions.forEach((signature: SignaturePositionInterface) => {
                    // For manually adjusted signature positions (not from the template)
                    if (!signature.isFromTemplate) {
                        // Update sequence based on the current workflow users
                        this.visaWorkflow.items.forEach((user: UserWorkflowInterface, index: number) => {
                            if (!this.functions.empty(user.signaturePositions)) {
                                user.signaturePositions.forEach(position => {
                                    // Ensure correct mapping of sequence and resId
                                    if (position.resId === signature.resId && user.sequence === position.sequence) {
                                        position.sequence = index; // Reassign sequence to match the new order
                                    }
                                });
                            }
                            user.sequence = index; // Update the user's sequence in the workflow
                        });
                    }
                    // For template-based signature positions
                    else {
                        let signIndex = 0; // Start the index for signatories
                        this.visaWorkflow.items.forEach((user: UserWorkflowInterface) => {
                            // If the user is a "visa" (not a signatory), remove their signature positions
                            if (user.currentRole === 'visa' || user.role === 'visa') {
                                user.signaturePositions = user.signaturePositions.filter((data: SignaturePositionInterface) => signature.resId !== data.resId);
                            }
                            // If the user is a signatory, assign their positions from the template
                            else {
                                const userSignaturePositions = this.signaturePositions.filter((pos: SignaturePositionInterface) =>
                                    pos.sequence === signIndex && pos.isFromTemplate && pos.resId === signature.resId
                                );
                                if (userSignaturePositions.length > 0) {
                                    // Append the template positions for this user
                                    user.signaturePositions = user.signaturePositions.concat(userSignaturePositions);
                                }

                                // Filter out any duplicate signature positions
                                user.signaturePositions = user.signaturePositions.filter((signature: SignaturePositionInterface, index: number, self: SignaturePositionInterface[]) =>
                                    index === self.findIndex((t) => t.resId === signature.resId && signature.sequence === t.sequence)
                                );

                                signIndex++; // Increment the index for the next signatory
                            }
                        });
                    }
                });
            }
        }
        // If there are no signature positions or all were manually adjusted (not from the template)
        else {
            if (this.signaturePositions.length === 0 || this.signaturePositions.every((data: SignaturePositionInterface) => !data.isFromTemplate)) {
                // Update the sequence of each workflow user
                this.reOrderSequences();
            }
        }
    }

    getDocumentsFromPositions(): { resId: number, mainDocument: boolean }[] {
        const documents: any[] = [];
        this.visaWorkflow.items.forEach((user: UserWorkflowInterface) => {
            user.signaturePositions?.forEach((element: SignaturePositionInterface) => {
                documents.push({
                    resId: element.resId,
                    mainDocument: element.mainDocument
                });
            });
        });
        return documents;
    }

    setSignaturePositionsRetrievedFromTemplate(): void {
        // Filter users who are signatories (either currentRole or role is 'sign')
        this.visaWorkflow.items
            .filter((user: UserWorkflowInterface) => this.functions.empty(user.process_date))
            .filter((user: UserWorkflowInterface) => user.currentRole === 'sign' || user.role === 'sign')
            .forEach((user: UserWorkflowInterface, index: number) => {
                // Assign the signature positions from the template based on the sequence index
                let signaturePositions: SignaturePositionInterface[] = this.signaturePositions.filter((signature: SignaturePositionInterface) => signature.sequence === index);
                signaturePositions = JSON.parse(JSON.stringify(signaturePositions));
                user.signaturePositions = signaturePositions;
            });

        // Filter users who are "visa" (approvers) and clear their signature positions
        this.visaWorkflow.items
            .filter((user: UserWorkflowInterface) => this.functions.empty(user.process_date))
            .filter((user: UserWorkflowInterface) => user.currentRole === 'visa' || user.role === 'visa')
            .forEach((user: UserWorkflowInterface) => {
                user.signaturePositions = [];
            });

        this.visaWorkflow.items.forEach((item: UserWorkflowInterface, index: number) => {
            item.sequence = index; // Set the user's sequence
            if (!this.functions.empty(item.signaturePositions)) {
                item.signaturePositions.forEach(position => {
                    position.sequence = index; // Reassign the sequence for each position
                    position.isFromTemplate = true;
                });
            } else {
                item.signaturePositions = [];
            }
        });
    }

    reOrderSequences(): void {
        this.visaWorkflow.items.forEach((item: UserWorkflowInterface, index: number) => {
            item.sequence = index; // Set the user's sequence
            if (!this.functions.empty(item.signaturePositions)) {
                item.signaturePositions.forEach(position => {
                    position.sequence = index; // Reassign the sequence for each position
                    position.isFromTemplate = false;
                });
            } else {
                item.signaturePositions = [];
            }
        });
    }

    private _filter(value: string): string[] {
        if (typeof value === 'string') {
            const filterValue = this.latinisePipe.transform(value.toLowerCase());
            return this.signVisaUsers.filter((option: any) => this.latinisePipe.transform(option['title'].toLowerCase()).includes(filterValue));
        } else {
            return this.signVisaUsers;
        }
    }

    private _filterPrivateModel(value: string): string[] {
        if (typeof value === 'string') {
            const filterValue = this.latinisePipe.transform(value.toLowerCase());
            return this.visaTemplates.private.filter((option: any) => this.latinisePipe.transform(option['title'].toLowerCase()).includes(filterValue));
        } else {
            return this.visaTemplates.private;
        }
    }

    private _filterPublicModel(value: string): string[] {
        if (typeof value === 'string') {
            const filterValue = this.latinisePipe.transform(value.toLowerCase());
            return this.visaTemplates.public.filter((option: any) => this.latinisePipe.transform(option['title'].toLowerCase()).includes(filterValue));
        } else {
            return this.visaTemplates.public;
        }
    }
}

export interface ResourceInterface {
    resId: number;
    chrono: string;
    title: string;
    mainDocument: boolean;
}
