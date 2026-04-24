import { Component, OnInit, TemplateRef, ViewChild, ViewContainerRef } from '@angular/core';
import { AppService } from "@service/app.service";
import { TranslateService } from "@ngx-translate/core";
import { HeaderService } from "@service/header.service";
import { HttpClient } from "@angular/common/http";
import { NotificationService } from "@service/notification/notification.service"
import { catchError, finalize, of, tap } from "rxjs";
import { AdministrationService } from "@appRoot/administration/administration.service";
import { MatLegacyPaginator as MatPaginator } from "@angular/material/legacy-paginator";
import { MatSort } from "@angular/material/sort";
import { GoodFlagConfigurationInterface, GoodFlagTemplateInterface } from "@models/goodflag.model";
import { ConfirmComponent } from "@plugins/modal/confirm.component";
import { exhaustMap, filter, map } from "rxjs/operators";
import { MatLegacyDialog as MatDialog, MatLegacyDialogRef as MatDialogRef } from '@angular/material/legacy-dialog';
import { FunctionsService } from "@service/functions.service";
import { NgModel, UntypedFormControl } from "@angular/forms";

@Component({
    templateUrl: './goodflag-list-administration.component.html',
    styleUrls: ['./goodflag-list-administration.component.scss']
})
export class GoodflagListAdministrationComponent implements OnInit {

    @ViewChild('adminMenuTemplate', { static: true }) adminMenuTemplate: TemplateRef<any>;
    @ViewChild(MatPaginator, { static: false }) paginator: MatPaginator;
    @ViewChild(MatSort, { static: false }) sort: MatSort;

    // Define time constants in milliseconds
    readonly DAY = 24 * 60 * 60 * 1000;   // One day = 86,400,000 ms
    readonly WEEK = 7 * this.DAY;              // One week = 604,800,000 ms
    readonly TWO_WEEKS = 2 * this.WEEK;        // Two weeks = 1,209,600,000 ms
    readonly MONTH = 30 * this.DAY;            // Approx. one month = 2,592,000,000 ms (30 days)

    loading: boolean = false;

    goodflagConfig: GoodFlagConfigurationInterface = {
        url: '',
        accessToken: '',
        accessTokenAlreadySet: false,
        options: {
            optionOtp: true,
            validityPeriod: null,
            invitePeriod: null,
            workflowFinishedStatus: null,
            workflowStoppedStatus: null
        }
    };

    // Invitation periods with translated labels
    invitePeriods: { label: string; value: number }[] = [];

    workflowFinishedStatus = new UntypedFormControl('');
    workflowStoppedStatus = new UntypedFormControl('');

    defaultValidityPeriod: number = 8553600000;

    displayedColumns: string[] = ['label', 'description', 'actions'];
    filterColumns: string[] = ['label', 'description'];

    templates: GoodFlagTemplateInterface[] = [];
    statuses: { id: number, label: string }[] = [];

    dialogRef: MatDialogRef<ConfirmComponent>;

    constructor(
        public appService: AppService,
        public translate: TranslateService,
        public headerService: HeaderService,
        public adminService: AdministrationService,
        public functions: FunctionsService,
        private viewContainerRef: ViewContainerRef,
        private http: HttpClient,
        private notifications: NotificationService,
        private dialog: MatDialog
    ) {
    }

    async ngOnInit(): Promise<void> {
        this.loading = true;
        this.invitePeriods = [
            { label: this.translate.instant('lang.everyDay'), value: this.DAY },
            { label: this.translate.instant('lang.everyWeek'), value: this.WEEK },
            { label: this.translate.instant('lang.everyTwoWeeks'), value: this.TWO_WEEKS },
            { label: this.translate.instant('lang.everyMonth'), value: this.MONTH }
        ]
        this.headerService.setHeader(`${this.translate.instant('lang.administration')} ${this.translate.instant('lang.adminGoodflag')}`);
        this.headerService.injectInSideBarLeft(this.adminMenuTemplate, this.viewContainerRef, 'adminMenu');
        await this.getStatuses();
        await Promise.all([this.getConfiguration(), this.getTemplates()]).finally(() => this.loading = false);
    }

    getConfiguration(): Promise<void> {
        return new Promise((resolve) => {
            this.http.get('../rest/goodflag/configuration').pipe(
                tap((res: GoodFlagConfigurationInterface) => {
                    this.goodflagConfig = res;
                    if (typeof this.goodflagConfig.options.optionOtp === 'string') {
                        this.goodflagConfig.options.optionOtp = this.goodflagConfig.options.optionOtp === 'true';
                    }

                    this.goodflagConfig.options.validityPeriod = this.functions.msToDays(
                        this.functions.empty(this.goodflagConfig.options.validityPeriod) ? this.defaultValidityPeriod : this.goodflagConfig.options.validityPeriod
                    );

                    this.goodflagConfig.options.invitePeriod = this.functions.empty(this.goodflagConfig.options.invitePeriod) ? this.invitePeriods[0].value : this.goodflagConfig.options.invitePeriod;

                    this.workflowFinishedStatus.setValue(this.goodflagConfig.options.workflowFinishedStatus ?? '');
                    this.workflowStoppedStatus.setValue(this.goodflagConfig.options.workflowStoppedStatus ?? '');

                    resolve();
                }),
                catchError((err: any) => {
                    this.loading = false;
                    this.notifications.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    getTemplates(): Promise<GoodFlagTemplateInterface[]> {
        return new Promise((resolve) => {
            this.http.get('../rest/goodflag/templates').pipe(
                tap((templates: GoodFlagTemplateInterface[]) => {
                    this.templates = templates ?? [];
                    setTimeout(() => {
                        this.adminService.setDataSource('admin_goodflag', this.templates, this.sort, this.paginator, this.filterColumns);
                    }, 0);
                    resolve(this.templates);
                }),
                finalize(() => this.loading = false),
                catchError((err: any) => {
                    this.notifications.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        })
    }

    saveConfiguration(ngModel: NgModel = null): void {
        if (!this.functions.empty(ngModel) && ngModel?.invalid) {
            return;
        }
        this.http.put('../rest/goodflag/configuration', this.formatGoodflagConfig()).pipe(
            tap(() => {
                this.notifications.success(this.translate.instant('lang.modificationsProcessed'));
            }),
            catchError((err: any) => {
                this.notifications.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    deleteAccount(id: string) {
        this.dialogRef = this.dialog.open(ConfirmComponent, {
            panelClass: 'maarch-modal', autoFocus: false, disableClose: true,
            data: {
                title: this.translate.instant('lang.delete'),
                msg: this.translate.instant('lang.confirmAction')
            }
        });
        this.dialogRef.afterClosed().pipe(
            filter((data: string) => data === 'ok'),
            exhaustMap(() => this.http.delete('../rest/goodflag/templates/' + id)),
            tap(() => {
                this.templates = this.templates.filter((account: any) => account.id !== id);
                setTimeout(() => {
                    this.adminService.setDataSource('admin_goodflag', this.templates, this.sort, this.paginator, this.filterColumns);
                }, 0);
                this.notifications.success(this.translate.instant('lang.modelDeleted'));
            }),
            catchError((err: any) => {
                this.notifications.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    formatGoodflagConfig(): GoodFlagConfigurationInterface {
        return {
            ...this.goodflagConfig,
            options: {
                ...this.goodflagConfig.options,
                validityPeriod: this.functions.daysToms(this.goodflagConfig.options.validityPeriod),
                invitePeriod: this.goodflagConfig.options.invitePeriod,
                workflowFinishedStatus: this.workflowFinishedStatus.value,
                workflowStoppedStatus: this.workflowStoppedStatus.value
            }
        }
    }

    getStatuses(): Promise<boolean> {
        return new Promise((resolve) => {
            this.http.get('../rest/statuses').pipe(
                map((res: { statuses: { id: string, label_status: string }[] }) => res.statuses),
                tap((statuses: any) => {
                    this.statuses = statuses.map((status: { id: string, label_status: string }) => ({
                        id: status.id,
                        label: status.label_status
                    }));
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notifications.handleSoftErrors(err);
                    resolve(false);
                    return of(false);
                })
            ).subscribe();
        });
    }
}
