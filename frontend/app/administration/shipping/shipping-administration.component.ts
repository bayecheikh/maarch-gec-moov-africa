import { Component, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { MatSidenav } from '@angular/material/sidenav';
import { NotificationService } from '@service/notification/notification.service';
import { HeaderService } from '@service/header.service';
import { ActivatedRoute, Router } from '@angular/router';
import { AppService } from '@service/app.service';
import { catchError, debounceTime, finalize, tap } from 'rxjs/operators';
import { of, Subject } from 'rxjs';
import { MaarchFlatTreeComponent } from '@plugins/tree/maarch-flat-tree.component';
import { ShippingInterface, ShippingSendersInterface } from "@models/shipping.model";
import { FunctionsService } from "@service/functions.service";

@Component({
    templateUrl: 'shipping-administration.component.html',
    styleUrls: ['shipping-administration.component.scss']
})
export class ShippingAdministrationComponent implements OnInit, OnDestroy {

    @ViewChild('snav2', { static: true }) public sidenavRight: MatSidenav;
    @ViewChild('maarchTree', { static: true }) maarchTree: MaarchFlatTreeComponent;

    loading: boolean = false;
    creationMode: boolean = true;
    loadingSenders: boolean = false;

    shipping: ShippingInterface = {
        label: '',
        description: '',
        options: {
            shapingOptions: ['addressPage'],
            sendMode: 'fast',
            senderId: ''
        },
        fee: {
            firstPagePrice: 0,
            nextPagePrice: 0,
            postagePrice: 0,
            ereSendingPrice: 0,
        },
        account: {
            id: '',
            password: ''
        },
        entities: [],
        senders: []
    };

    entities: any[] = [];
    entitiesClone: any = null;
    shippingClone: ShippingInterface = null;

    shapingOptions: string[] = [
        'color',
        'duplexPrinting',
        'addressPage',
        'envelopeWindowsType',
    ];

    shapingOptionsClone: string[] = [];

    sendModes: string[] = [
        'digital_registered_mail',
        'digital_registered_mail_with_AR',
        'fast',
        'economic',
        'ere'
    ];

    hidePassword: boolean = true;
    shippingAvailable: boolean = false;

    templateId: number = null;

    subscription: any;

    private accountChange$ = new Subject<void>();

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        public appService: AppService,
        public functions: FunctionsService,
        public route: ActivatedRoute,
        private router: Router,
        private notify: NotificationService,
        private headerService: HeaderService,
    ) {
    }

    async ngOnInit(): Promise<void> {
        this.loading = true;

        this.http.get('../rest/externalConnectionsEnabled').pipe(
            tap((data: any) => {
                this.shippingAvailable = data.connection.maileva === true;
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                this.loading = false;
                return of(false);
            })
        ).subscribe();

        this.route.params.subscribe(async params => {
            if (typeof params['id'] === 'undefined') {
                this.headerService.setHeader(this.translate.instant('lang.shippingCreation'));

                this.creationMode = true;

                this.http.get('../rest/administration/shippings/new').pipe(
                    tap((data: any) => {
                        this.entities = data['entities'].map(
                            (item: any) => ({
                                ...item,
                                id: parseInt(item.id)
                            })
                        );
                        this.cloneValuesAndAddMissingPriceFields();
                    }),
                    finalize(() => this.loading = false),
                    catchError((err: any) => {
                        this.notify.handleSoftErrors(err);
                        this.loading = false;
                        return of(false);
                    })
                ).subscribe()
            } else {
                this.templateId = parseInt(params['id']);
                this.headerService.setHeader(this.translate.instant('lang.shippingModification'));
                this.creationMode = false;
                this.http.get(`../rest/administration/shippings/${this.templateId}`).pipe(
                    tap((data: any) => {
                        this.shipping = data['shipping'];
                        this.shipping['senders'] = this.formatSenders(data['senders'] ?? []);
                        this.shipping.options.senderId = data['shipping']['options']?.senderId ?? '';

                        if (this.shipping.senders.length === 1) {
                            this.shipping.options.senderId = this.shipping.senders[0].id;
                        }

                        this.entities = data['entities'];

                        this.cloneValuesAndAddMissingPriceFields();
                    }),
                    finalize(() => this.loading = false),
                    catchError((err: any) => {
                        this.notify.handleSoftErrors(err);
                        return of(false);
                    })
                ).subscribe();

            }

            /**
             * A property that holds the subscription details.
             * Typically used to manage the subscription details of a user
             * or a component's state related to subscriptions.
             */
            this.subscription = this.accountChange$
                .pipe(debounceTime(400))
                .subscribe(() => {
                    this.getShippingSenders(true);
                });
        });
    }

    /**
     * Clones the current entities and shipping data, ensuring that all missing price-related fields are added.
     * The method performs the following tasks:
     * - Creates a deep clone of the `entities` array and assigns it to `entitiesClone`.
     * - Initializes the entity tree structure based on the cloned entities.
     * - Adds any missing price-related fields to the entities.
     * - Clones the `shipping` object and updates the `shapingOptionsClone` with the cloned shaping options.
     *
     * @return {void} No return value.
     */
    cloneValuesAndAddMissingPriceFields(): void {
        this.entitiesClone = JSON.parse(JSON.stringify(this.entities));
        this.initEntitiesTree(this.entities);

        this.addMissingPriceFields();

        this.shippingClone = JSON.parse(JSON.stringify(this.shipping));
        this.shapingOptionsClone = [...this.shippingClone.options.shapingOptions];
    }

    ngOnDestroy(): void {
        this.subscription?.unsubscribe();
    }

    onAccountFieldChanged(): void {
        this.accountChange$.next();
    }

    initEntitiesTree(entities: any): void {
        this.maarchTree.initData(entities);
    }

    addMissingPriceFields(): void {
        if (this.shipping.options.sendMode === 'ere') {
            this.shipping.fee.firstPagePrice = 0;
            this.shipping.fee.nextPagePrice = 0;
            this.shipping.fee.postagePrice = 0;
        } else {
            this.shipping.fee.ereSendingPrice = 0;
        }
    }

    updateSelectedEntities(): void {
        this.shipping.entities = this.maarchTree.getSelectedNodes().map((ent: { id: any; }) => ent.id);
    }

    onSubmit(): void {
        this.loading = true;

        if (this.shipping.options.sendMode === 'ere') {
            this.shipping.subscribed = false;

            delete this.shipping.fee.firstPagePrice;
            delete this.shipping.fee.nextPagePrice;
            delete this.shipping.fee.postagePrice;
        } else {
            delete this.shipping.fee.ereSendingPrice;
            delete this.shipping.options.senderId;
            delete this.shipping.senders;
        }

        const url: string = this.creationMode ? '../rest/administration/shippings' : '../rest/administration/shippings/' + this.shipping.id;
        const method: string = this.creationMode ? 'post' : 'put';
        const objToSend: ShippingInterface = this.getShipping();
        delete objToSend.senders;
        this.http[method](url, objToSend).pipe(
            tap(() => {
                const message: string = this.creationMode ? 'lang.shippingAdded' : 'lang.shippingUpdated';
                this.shippingClone = JSON.parse(JSON.stringify(this.shipping));
                this.notify.success(this.translate.instant(message));
                this.router.navigate(['/administration/shippings']);
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    checkModif(): boolean {
        return (JSON.stringify(this.shippingClone) === JSON.stringify(this.shipping));
    }

    toggleShapingOption(option: string): void {
        const index = this.shipping.options.shapingOptions.indexOf(option);
        if (index > -1) {
            this.shipping.options.shapingOptions.splice(index, 1);
        } else {
            this.shipping.options.shapingOptions.push(option);
        }
        this.shapingOptionsClone = [...this.shipping.options.shapingOptions];
    }

    getShapingWarning(): string {
        if (this.shipping.options.sendMode === 'ere') {
            return 'lang.warnShapingEre';
        }
        return 'lang.warnShapingOption';
    }

    shouldDisableToggle(option: string): boolean {
        if (this.shipping.options.sendMode === 'ere') {
            this.shipping.options.shapingOptions = [];
            return true;
        }

        return (option === 'envelopeWindowsType') &&
            ['digital_registered_mail', 'digital_registered_mail_with_AR'].indexOf(this.shipping.options.sendMode) > -1;
    }

    cancelModification(): void {
        this.shipping = JSON.parse(JSON.stringify(this.shippingClone));
        this.entities = JSON.parse(JSON.stringify(this.entitiesClone));
        this.initEntitiesTree(this.entities);
    }

    changeSendMode(mode: string, option: string): void {
        if (['digital_registered_mail', 'digital_registered_mail_with_AR'].indexOf(mode) > -1) {
            const index = this.shipping.options.shapingOptions.indexOf(option);
            if (index !== -1) {
                this.shipping.options.shapingOptions.splice(index, 1);
            }
        }
    }

    getShipping(): ShippingInterface {
        /**
         * 'envelopeWindowsType' option used only for simple send mode
         */
        this.changeSendMode(this.shipping.options.sendMode, 'envelopeWindowsType');
        return this.shipping;
    }

    onSendModeChange(newMode: string): void {
        if (newMode !== 'ere') {
            this.shipping.options.shapingOptions = [...this.shapingOptionsClone];
            this.shipping.fee.firstPagePrice = this.shipping.fee.nextPagePrice = this.shipping.fee.postagePrice = 0;
        } else {
            this.shipping.fee.firstPagePrice = this.shipping.fee.nextPagePrice = this.shipping.fee.postagePrice = 0;
            /**
             * On mode change, verify if the account has changed to get the sender list
             * by passing the account credentials to the API or by using the template id
             */
            let accountChanged: boolean = false;
            if (!this.creationMode && (this.shipping.account.id !== this.shippingClone.account.id || this.shipping.account.password !== this.shippingClone.account.password)) {
                accountChanged = true;
            }

            this.getShippingSenders(accountChanged);
        }
    }

    /**
     * Retrieves the list of shipping senders based on the current shipping configuration and mode.
     * Performs an HTTP request to fetch senders, formats the sender data, and updates the sender list.
     * Handles conditions for both account and template-based retrieval.
     *
     * @param {boolean} accountChanged - Indicates whether the account has changed, which determines the parameters for the request.
     * @return {void} No value is returned from this method.
     */
    getShippingSenders(accountChanged: boolean): void {
        if (this.shipping.options.sendMode === 'ere') {
            let params: HttpParams = new HttpParams();

            if (!this.functions.empty(this.shipping.account.id) && !this.functions.empty(this.shipping.account.password) && (this.creationMode || accountChanged)) {
                params = params
                    .set('accountId', this.shipping.account.id)
                    .set('accountPassword', this.shipping.account.password);
            } else {
                params = params.set('templateId', this.templateId);
            }

            this.loadingSenders = true;

            this.http.get('../rest/shippings/senders', { params }).pipe(
                tap((data: ShippingSendersInterface[]) => {
                    this.shipping.senders = this.formatSenders(data);
                }),
                finalize(() => this.loadingSenders = false),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    this.shipping.options.senderId = '';
                    this.shipping.senders = [];
                    return of(false);
                })
            ).subscribe();
        } else {
            this.shipping.options.senderId = '';
            this.shipping.senders = [];
        }
    }

    /**
     * Formats a list of shipping senders into an array of objects containing id and label properties.
     * The label is a concatenation of the sender's first name, last name, and email within parentheses.
     *
     * @param {ShippingSendersInterface[]} senders - An array of sender objects containing details such as id, firstname, lastname, and email.
     * @return {{ id: string, label: string }[]} An array of formatted sender objects where each object includes an id and a concatenated label.
     */
    formatSenders(senders: ShippingSendersInterface[]): { id: string, label: string }[] {
        return senders.map((sender) => ({
            id: sender.id,
            label: `${sender.firstname} ${sender.lastname} (${sender.email})`
        }));
    }

    isEreMode(): boolean {
        return this.shipping.options.sendMode === 'ere';
    }
}
