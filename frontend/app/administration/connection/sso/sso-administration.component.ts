import { HttpClient } from '@angular/common/http';
import { Component, OnInit, TemplateRef, ViewChild, ViewContainerRef } from '@angular/core';
import { NgForm } from '@angular/forms';
import { MatLegacyDialog as MatDialog } from '@angular/material/legacy-dialog';
import { MatLegacyPaginator as MatPaginator } from '@angular/material/legacy-paginator';
import { MatSort } from '@angular/material/sort';
import { TranslateService } from '@ngx-translate/core';
import { ConfirmComponent } from '@plugins/modal/confirm.component';
import { AppService } from '@service/app.service';
import { AuthService } from '@service/auth.service';
import { HeaderService } from '@service/header.service';
import { NotificationService } from '@service/notification/notification.service';
import { Observable, of } from 'rxjs';
import { catchError, exhaustMap, filter, finalize, tap } from 'rxjs/operators';
import { AdministrationService } from '../../administration.service';

@Component({
    selector: 'app-sso-administration',
    templateUrl: './sso-administration.component.html',
    styleUrls: ['./sso-administration.component.scss']
})
export class SsoAdministrationComponent implements OnInit {

    @ViewChild('adminMenuTemplate', { static: true }) adminMenuTemplate: TemplateRef<any>;
    @ViewChild(MatPaginator, { static: false }) paginator: MatPaginator;
    @ViewChild(MatSort, { static: false }) sort: MatSort;

    loading: boolean = true;
    savingConfig: boolean = false;

    sso: SsoConfigInterface = {
        url: '',
        ssoLogoutUrl: '',
        mapping: [
            {
                maarchId: 'login',
                ssoId: 'id',
                desc: 'lang.fieldUserIdDescSso'
            }
        ]
    };

    ssoClone: SsoConfigInterface;

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        public appService: AppService,
        public adminService: AdministrationService,
        public dialog: MatDialog,
        private notify: NotificationService,
        private headerService: HeaderService,
        private viewContainerRef: ViewContainerRef,
        private authService: AuthService,
    ) { }

    ngOnInit(): void {
        this.headerService.injectInSideBarLeft(this.adminMenuTemplate, this.viewContainerRef, 'adminMenu');
        this.headerService.setHeader(this.translate.instant('lang.administration') + ' ' + this.translate.instant('lang.ssoConnections'));
        this.getConnection();
    }

    getConnection() {
        this.http.get('../rest/configurations/admin_sso').pipe(
            tap((data: any) => {
                this.sso = data.configuration.value;
                this.ssoClone = JSON.parse(JSON.stringify(this.sso));
                this.loading = false;
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    isValid(ssoForm: NgForm): boolean {
        return ssoForm.form.valid && JSON.stringify(this.sso) !== JSON.stringify(this.ssoClone);
    }

    cancel(): void {
        this.sso = JSON.parse(JSON.stringify(this.ssoClone));
        this.sso = JSON.parse(JSON.stringify(this.ssoClone));
    }

    formatData(): SsoConfigInterface {
        const objTosend = JSON.parse(JSON.stringify(this.sso));

        objTosend.mapping = objTosend.mapping.map(((item: any) => {
            delete item.desc;
            return item;
        }));

        return objTosend;
    }

    onSubmit(): void {
        const formattedData: SsoConfigInterface = this.formatData();

        // Check if the authentication mode is different from SSO
        if (this.authService.authMode !== 'sso') {
            this.confirmAndUpdateConfiguration(formattedData);
        } else {
            this.updateConfiguration(formattedData);
        }
    }

    /**
     * Displays a confirmation dialog then updates the configuration
     * @param formattedData The formatted data to send
     */
    private confirmAndUpdateConfiguration(formattedData: SsoConfigInterface): void {
        const dialogConfig = {
            panelClass: 'maarch-modal',
            autoFocus: false,
            disableClose: true,
            data: {
                title: this.translate.instant('lang.warning') + ' !',
                msg: this.translate.instant('lang.warningConnectionMsg')
            }
        };

        this.dialog.open(ConfirmComponent, dialogConfig)
            .afterClosed()
            .pipe(
                filter((response: string) => response === 'ok'),
                exhaustMap(() => this.updateConfigurationRequest(formattedData))
            ).subscribe();
    }

    /**
     * Updates the SSO configuration without confirmation
     * @param formattedData The formatted data to send
     */
    private updateConfiguration(formattedData: SsoConfigInterface): void {
        this.updateConfigurationRequest(formattedData).subscribe();
    }

    /**
     * Sends the configuration update request and handles responses
     * @param formattedData The formatted data to send
     * @returns Observable of the request result
     */
    private updateConfigurationRequest(formattedData: SsoConfigInterface): Observable<any> {
        this.savingConfig = true;
        return this.http.put('../rest/configurations/admin_sso', formattedData)
            .pipe(
                tap(() => {
                    this.notify.success(this.translate.instant('lang.dataUpdated'));
                    this.ssoClone = JSON.parse(JSON.stringify(this.sso));
                }),
                finalize(() => this.savingConfig = false),
                catchError((error: any) => {
                    this.notify.handleSoftErrors(error);
                    this.savingConfig = false;
                    return of(false);
                })
            );
    }
}

export interface SsoConfigInterface {
    url: string;
    ssoLogoutUrl: string;
    mapping: MappingInterface[];
}

export interface MappingInterface {
    maarchId: string;
    ssoId: string;
    desc: string;
}
