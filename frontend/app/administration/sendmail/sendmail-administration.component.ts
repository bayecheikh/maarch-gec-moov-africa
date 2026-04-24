import { Component, OnInit, ViewChild, TemplateRef, ViewContainerRef } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { NotificationService } from '@service/notification/notification.service';
import { HeaderService } from '@service/header.service';
import { AppService } from '@service/app.service';
import { NgForm } from '@angular/forms';
import { CheckMailServerModalComponent } from './checkMailServer/check-mail-server-modal.component';
import { catchError, finalize, tap } from 'rxjs/operators';
import { of } from 'rxjs';
import { MatLegacyDialog as MatDialog } from '@angular/material/legacy-dialog';

@Component({
    templateUrl: 'sendmail-administration.component.html',
    styleUrls: ['sendmail-administration.component.scss']
})
export class SendmailAdministrationComponent implements OnInit {

    @ViewChild('adminMenuTemplate', { static: true }) adminMenuTemplate: TemplateRef<any>;

    @ViewChild('sendmailForm', { static: false }) public sendmailFormCpt: NgForm;

    loading: boolean = false;

    savingConfig: boolean = false;

    sendmail: SendMailConfigInterface = {
        type: 'smtp',
        host: '',
        smarthost: '',
        auth: true,
        user: '',
        password: '',
        secure: 'ssl', // tls, ssl, starttls
        port: '465',
        charset: 'utf-8',
        from: '',
        tenantId: '',
        clientId: '',
        clientSecret: '',
        useSMTPAuth: false
    };

    smtpTypeList = [
        {
            id: 'smtp',
            label: this.translate.instant('lang.smtpclient')
        },
        {
            id: 'sendmail',
            label: this.translate.instant('lang.smtprelay')
        },
        {
            id: 'qmail',
            label: this.translate.instant('lang.qmail')
        },
        {
            id: 'mail',
            label: this.translate.instant('lang.phpmail')
        },
        {
            id: 'microsoftOAuth',
            label: this.translate.instant('lang.microsoftOAuth')
        }
    ];
    smtpSecList = [
        {
            id: '',
            label: this.translate.instant('lang.none')
        },
        {
            id: 'ssl',
            label: 'ssl'
        },
        {
            id: 'tls',
            label: 'tls'
        }
    ];

    sendmailClone: SendMailConfigInterface = {
        type: 'smtp',
        host: '',
        smarthost: '',
        auth: false,
        user: '',
        password: '',
        secure: 'ssl',
        port: '465',
        charset: 'utf-8',
        from: '',
        tenantId: '',
        clientId: '',
        clientSecret: '',
        useSMTPAuth: false
    };

    hidePassword: boolean = true;
    serverConnectionLoading: boolean = false;
    emailSendLoading: boolean = false;
    emailSendResult = {
        icon: '',
        msg: '',
        debug: ''
    };
    currentUser: any = {};
    recipientTest: string = '';
    passwordLabel: string = '';

    useSMTPAuth: boolean = false;

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        public appService: AppService,
        public dialog: MatDialog,
        private notify: NotificationService,
        private headerService: HeaderService,
        private viewContainerRef: ViewContainerRef
    ) { }

    ngOnInit(): void {
        this.headerService.setHeader(this.translate.instant('lang.sendmailShort'));

        this.headerService.injectInSideBarLeft(this.adminMenuTemplate, this.viewContainerRef, 'adminMenu');

        this.loading = true;

        this.http.get('../rest/configurations/admin_email_server').pipe(
            tap((data: any) => {
                this.recipientTest = this.headerService.user.mail;
                this.sendmail = data.configuration.value;
                this.sendmailClone = JSON.parse(JSON.stringify(this.sendmail));
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                this.loading = false;
                return of(false);
            })
        ).subscribe();
    }

    cancelModification(): void {
        this.sendmail = JSON.parse(JSON.stringify(this.sendmailClone));
    }

    onSubmit(): Promise<boolean> {
        this.savingConfig = true;
        return new Promise((resolve) => {
            this.http.put('../rest/configurations/admin_email_server', this.formatSendMailConfig()).pipe(
                tap(() => {
                    this.sendmailClone = JSON.parse(JSON.stringify(this.sendmail));
                    this.notify.success(this.translate.instant('lang.configurationUpdated'));
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    this.savingConfig = false;
                    resolve(false);
                    return of(false);
                })
            ).subscribe();
        });
    }

    checkModif(): boolean {
        return (JSON.stringify(this.sendmailClone) === JSON.stringify(this.sendmail));
    }

    cleanAuthInfo() {
        this.sendmail.passwordAlreadyExists = false;

        this.sendmail.user = '';
        this.sendmail.password = '';
    }

    async openMailServerTest(): Promise<void> {
        await this.onSubmit().then((data: boolean) => {
            if (data) {
                this.dialog.open(CheckMailServerModalComponent, {
                    panelClass: 'maarch-modal',
                    disableClose: true,
                    width: '500px',
                    // height: '99%',
                    data: {
                        serverConf: this.sendmail,
                        recipient: this.recipientTest,
                        sender: this.emailSendResult
                    }
                });
            }
        });
        this.savingConfig = false;
    }

    formatSendMailConfig(): SendMailConfigInterface {
        if (this.sendmail.type === 'microsoftOAuth') {
            delete this.sendmail.user;
            delete this.sendmail.charset;
            delete this.sendmail.host;
            delete this.sendmail.smarthost;
            delete this.sendmail.port;
            delete this.sendmail.secure;
            delete this.sendmail.password;
            delete this.sendmail.passwordAlreadyExists;
        }
        return this.sendmail;
    }
}

export interface SendMailConfigInterface {
    type: string;
    host: string;
    smarthost: string;
    auth: boolean;
    user: string;
    password?: string;
    secure: string;
    port: string;
    charset: string;
    from: string;
    tenantId?: string;
    clientId?: string;
    clientSecret?: string;
    passwordAlreadyExists?: boolean;
    useSMTPAuth: boolean;
}
