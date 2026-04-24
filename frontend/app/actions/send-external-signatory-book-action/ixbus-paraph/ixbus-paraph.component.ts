import { Component, OnInit, Input } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';
import { HttpClient } from '@angular/common/http';
import { UntypedFormControl } from '@angular/forms';
import { LocalStorageService } from '@service/local-storage.service';
import { HeaderService } from '@service/header.service';
import { catchError, tap } from 'rxjs/operators';
import { NotificationService } from '@service/notification/notification.service';
import { of } from 'rxjs';
import { FunctionsService } from '@service/functions.service';
import { ExternalSignatoryBookManagerService } from '@service/externalSignatoryBook/external-signatory-book-manager.service';

@Component({
    selector: 'app-ixbus-paraph',
    templateUrl: 'ixbus-paraph.component.html',
    styleUrls: ['ixbus-paraph.component.scss'],
})
export class IxbusParaphComponent implements OnInit {

    @Input() additionalsInfos: any;
    @Input() externalSignatoryBookDatas: any;

    loading: boolean = true;

    natures: { id: string, label: string }[] = [];

    messagesModel: {
        dateRealisation: string,
        identifiant: string,
        nom: string
    }[] = [];

    users: {
        email: string,
        identifiant: string,
        nom: string,
        prenom: string,
        nomUtilisateur: string
    }[] = [];

    ixbusDatas: { instanceId: number, nature: string, messageModel: string, userId: string, signatureMode: string} = {
        instanceId: null,
        nature: '',
        messageModel: '',
        userId: '',
        signatureMode: 'electronic'
    };

    injectDatasParam = {
        resId: 0,
        editable: true
    };

    selectInstance = new UntypedFormControl();
    selectNature = new UntypedFormControl();
    selectWorkflow = new UntypedFormControl();
    selectUser = new UntypedFormControl();

    instances: { id: string, label: string }[] = [];

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        public headerService: HeaderService,
        public functions: FunctionsService,
        private localStorage: LocalStorageService,
        private notifications: NotificationService,
        private externalSignatureBookService: ExternalSignatoryBookManagerService
    ) { }

    async ngOnInit(): Promise<void> {
        const savedState = this.externalSignatureBookService.getComponentState('IxbusParaphComponent');
        if (savedState) {
            this.loading = true;
            Object.assign(this, savedState);
            this.loading = false;
        } else {
            if (!this.functions.empty(this.additionalsInfos.ixbus?.instances)) {
                this.instances = this.additionalsInfos.ixbus.instances
                this.selectInstance.setValue(this.instances[0].id);
                this.ixbusDatas.instanceId = this.selectInstance.value
                await this.getNaturesByInstance(this.selectInstance.value);
            } else if (!this.functions.empty(this.additionalsInfos.ixbus?.natures)) {
                this.natures = this.additionalsInfos.ixbus.natures.map((element: { identifiant: string, nom: string }) => ({
                    id: element.identifiant,
                    label: element.nom
                }));
                this.selectNature.setValue(this.natures[0].id);
                this.ixbusDatas.nature = this.selectNature.value;
                await this.changeModel(this.selectNature.value);
            }

            if (this.localStorage.get(`ixBusSignatureMode_${this.headerService.user.id}`) !== null) {
                this.ixbusDatas.signatureMode = this.localStorage.get(`ixBusSignatureMode_${this.headerService.user.id}`);
            }
        }
    }


    changeModel(natureId: string): Promise<boolean> {
        this.loading = true;
        return new Promise((resolve) => {
            this.http.get(`../rest/ixbus/natureDetails/${natureId}`, { params: { instanceId: this.selectInstance.value } }).pipe(
                tap((data: any) => {
                    this.messagesModel = [];
                    this.ixbusDatas.messageModel = '';
                    this.selectWorkflow.setValue('');
                    if (!this.functions.empty(data.messageModels)) {
                        this.messagesModel = data.messageModels.map((message: any) => ({
                            id: message.identifiant,
                            label: message.nom
                        }));
                    }

                    this.users = [];
                    this.ixbusDatas.userId = '';
                    this.selectUser.setValue('');
                    if (!this.functions.empty(data.users)) {
                        this.users = data.users.map((user: any) => ({
                            id: user.identifiant,
                            label: `${user.prenom} ${user.nom}`
                        }));
                    }
                    this.persistDatas();
                    this.loading = false;
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notifications.handleSoftErrors(err);
                    this.loading = false;
                    resolve(false);
                    return of(false);
                })
            ).subscribe();
        })
    }

    isValidParaph() {
        if (this.additionalsInfos.attachments.length === 0 || this.natures.length === 0 || this.messagesModel.length === 0 || this.users.length === 0 || !this.ixbusDatas.nature
            || !this.ixbusDatas.messageModel || !this.ixbusDatas.userId) {
            return false;
        } else {
            return true;
        }
    }

    getRessources() {
        return this.additionalsInfos.attachments.map((e: any) => e.res_id);
    }

    getDatas() {
        this.localStorage.save(`ixBusSignatureMode_${this.headerService.user.id}`, this.ixbusDatas.signatureMode);
        this.externalSignatoryBookDatas = {
            'ixbus': this.ixbusDatas,
            'steps': []
        };
        return this.externalSignatoryBookDatas;
    }

    getNaturesByInstance(instanceId: number): Promise<{id: string, label: string}[]> {
        this.loading = true;
        return new Promise((resolve) => {
            this.http.get(`../rest/externalSignatureBook/ixbus/instance/${instanceId}/natures`).pipe(
                tap(async (data: { identifiant: string, nom: string }[]) => {
                    this.natures = [];
                    this.natures = data.map((nature: { identifiant: string, nom: string }) => ({
                        id: nature.identifiant,
                        label: nature.nom
                    }));

                    this.selectNature.setValue(this.natures[0].id);

                    this.ixbusDatas.nature = this.selectNature.value;
                    await this.changeModel(this.selectNature.value);

                    resolve(this.natures);
                }),
                catchError((err: any) => {
                    this.notifications.handleSoftErrors(err);
                    this.loading = false;
                    resolve([]);
                    return of(false);
                })
            ).subscribe();
        });
    }

    persistDatas(): void {
        this.externalSignatureBookService.saveComponentState('IxbusParaphComponent', this);
    }
}
