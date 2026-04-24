import { AppService } from '@service/app.service';
import { Component, OnInit, TemplateRef, ViewChild, ViewContainerRef } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { NotificationService } from '@service/notification/notification.service';
import { catchError, finalize, map, of, tap } from 'rxjs';
import { FunctionsService } from '@service/functions.service';
import { HeaderService } from '@service/header.service';
import { TranslateService } from '@ngx-translate/core';
import { NgForm } from '@angular/forms';
import { AdministrationService } from '@appRoot/administration/administration.service';
import { ProConnectService } from "@service/proconnect.service";

@Component({
    selector: 'app-proconnect-administration',
    templateUrl: './proconnect-administration.component.html',
    styleUrls: ['./proconnect-administration.component.scss']
})

export class ProConnectAdministrationComponent implements OnInit {

    @ViewChild('adminMenuTemplate', { static: true }) adminMenuTemplate: TemplateRef<any>;

    loading: boolean = true;

    proConnectConfig: ProConnectConfigInterface = {
        clientId: '',
        clientSecret: '',
        url: '',
        enabled: false
    };

    proConnectConfigClone: ProConnectConfigInterface = {
        clientId: '',
        clientSecret: '',
        url: '',
        enabled: false
    };

    constructor(
        public functions: FunctionsService,
        public translate: TranslateService,
        public appService: AppService,
        public adminService: AdministrationService,
        public proConnectService: ProConnectService,
        private httpClient: HttpClient,
        private notifications: NotificationService,
        private headerService: HeaderService,
        private viewContainerRef: ViewContainerRef
    ) {

    }

    async ngOnInit() {
        this.headerService.injectInSideBarLeft(this.adminMenuTemplate, this.viewContainerRef, 'adminMenu');
        this.headerService.setHeader(this.translate.instant('lang.administration') + ' ' + this.translate.instant('lang.proConnectConnections'));
        await this.getProConnectConfig();
    }

    /**
     * Fetches the ProConnect configuration from the server and updates the local configuration state.
     * The method uses an HTTP GET request to retrieve the configuration and processes it to validate and store locally.
     * Handles errors gracefully and provides a resolved promise with the success status.
     *
     * @return {Promise<boolean>} Promise that resolves to true if the configuration is successfully fetched and processed, otherwise resolves to false in case of an error.
     */
    getProConnectConfig(): Promise<boolean> {
        return new Promise((resolve) => {
            this.httpClient.get('../rest/configurations/admin_proconnect').pipe(
                map((data: { configuration: { value: ProConnectConfigInterface } }) => data.configuration.value),
                tap((data: ProConnectConfigInterface) => {
                    if (!this.functions.empty(data)) {
                        this.proConnectConfig = data;
                        this.proConnectConfigClone = JSON.parse((JSON.stringify(this.proConnectConfig)));
                    }
                    resolve(true);
                }),
                finalize(() => this.loading = false),
                catchError((err: any) => {
                    this.notifications.handleSoftErrors(err);
                    resolve(false);
                    return of(false);
                })
            ).subscribe();
        })
    }

    /**
     * Handles the submission of the ProConnect configuration form.
     * Sends an HTTP PUT request with the updated configuration data to save the changes.
     * Displays a success notification upon a successful update and updates the local configuration state.
     * Handles errors by displaying an error notification.
     *
     * @return {void} This method does not return a value.
     */
    onSubmit(): void {
        this.loading = true;
        const body: ProConnectConfigInterface = this.proConnectConfig;
        this.httpClient.put('../rest/configurations/admin_proconnect', body).pipe(
            tap(() => {
                this.notifications.success(this.translate.instant('lang.modificationSaved'));
                this.proConnectConfigClone = JSON.parse((JSON.stringify(this.proConnectConfig)));
                this.proConnectService.proConnectConfig.enabled = this.proConnectConfig.enabled;
                this.proConnectService.proConnectConfig.clientId = this.proConnectConfig.clientId;
                this.proConnectService.proConnectConfig.url = this.functions.formatUrlWithHttpsProtocol(this.proConnectConfig.url);
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notifications.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    /**
     * Restores the `proConnectConfig` property to its previously saved state by resetting it to the value stored in `proConnectConfigClone`.
     *
     * @return {void} Does not return a value.
     */
    cancel() {
        this.proConnectConfig = JSON.parse((JSON.stringify(this.proConnectConfigClone)));
    }

    /**
     * Validates the provided form and checks if the configuration has been modified.
     *
     * @param {NgForm} proConnectForm - The form to be validated.
     * @return {boolean} Returns true if the form is valid and the configuration has been modified, otherwise false.
     */
    isValid(proConnectForm: NgForm) {
        return proConnectForm.form.valid && JSON.stringify(this.proConnectConfig) !== JSON.stringify(this.proConnectConfigClone);
    }
}

/**
 * Interface representing the configuration settings for ProConnect.
 *
 * This interface defines the structure and required properties for
 * configuring the ProConnect integration. It includes the connection
 * URL, client credentials, and a toggle to enable or disable the
 * integration.
 *
 * Properties:
 * - url: The endpoint URL for ProConnect.
 * - clientId: Unique identifier for the client accessing ProConnect.
 * - clientSecret: Secret key associated with the client ID for authentication.
 * - enabled: Indicates whether the ProConnect integration is active.
 */
export interface ProConnectConfigInterface {
    url: string;
    clientId: string;
    clientSecret: string;
    enabled: boolean;
}
