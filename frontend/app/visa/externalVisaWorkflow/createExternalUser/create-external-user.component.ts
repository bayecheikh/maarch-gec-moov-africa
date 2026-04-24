import { Component, Inject, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { NotificationService } from '@service/notification/notification.service';
import { FunctionsService } from '@service/functions.service';
import { catchError, tap } from 'rxjs/operators';
import {
    MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA,
    MatLegacyDialogRef as MatDialogRef
} from '@angular/material/legacy-dialog';
import { of } from 'rxjs';
import { ContactService } from '@service/contact.service';
import {
    ExternalSignatoryBookManagerService
} from '@service/externalSignatoryBook/external-signatory-book-manager.service';

@Component({
    templateUrl: 'create-external-user.component.html',
    styleUrls: ['create-external-user.component.scss'],
    providers: [ContactService, ExternalSignatoryBookManagerService]
})

export class CreateExternalUserComponent implements OnInit {

    sources: any[] = [];

    currentSource: any[] = [];

    availableRoles: { id: string, label: string }[] = [
        {
            id: 'visa',
            label: this.translate.instant('lang.visaUser')
        },
        {
            id: 'sign',
            label: this.translate.instant('lang.signUser')
        }
    ];

    securityModes: { id: string, label: string }[] = [
        {
            id: 'sms',
            label: this.translate.instant('lang.sms')
        },
        {
            id: 'email',
            label: this.translate.instant('lang.email')
        }
    ];

    userOTP: any = {
        firstname: '',
        lastname: '',
        email: '',
        phone: '',
        security: '',
        sourceId: '',
        type: '',
        role: 'sign',
        availableRoles: this.availableRoles.map((role: any) => role.id),
        step: null,
        isSignatureRequired: false,
    };

    correspondentShorcuts: any[] = [];

    loading: boolean = true;

    searchMode: boolean = false;

    sourceTypes: string[] = [];

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        public functions: FunctionsService,
        public notify: NotificationService,
        public externalSignatoryBookManagerService: ExternalSignatoryBookManagerService,
        @Inject(MAT_DIALOG_DATA) public data: any,
        private contactService: ContactService,
        private dialogRef: MatDialogRef<CreateExternalUserComponent>
    ) {
    }

    async ngOnInit(): Promise<void> {
        await this.getConfig();
        if (this.data.resId !== null) {
            this.getCorrespondents();
        } else {
            this.searchMode = true;
        }
    }

    async getConfig(): Promise<void> {
        const data: any = await this.externalSignatoryBookManagerService.getOtpConfig();
        if (!this.functions.empty(data)) {
            this.sources = data;
            this.sourceTypes = [...new Set(this.sources.map((item: any) => item.type))];
            this.setCurrentSource(this.data.otpInfo !== null ? this.data.otpInfo.sourceId : this.sources[0].id);
            if (this.data.otpInfo === null) {
                this.userOTP.sourceId = this.sources[0].id;
                this.userOTP.type = this.sources[0].type;
            } else {
                this.userOTP = this.data.otpInfo;
            }
        } else {
            this.notify.handleSoftErrors(this.translate.instant('lang.noOtpConfigurationFound'));
            this.dialogRef.close();
        }
        this.loading = false;
    }

    getSources(type: string): any[] {
        return this.sources.filter((item: any) => item.type === type);
    }

    addOtpUser(): void {
        if (['fast', 'goodflag'].indexOf(this.userOTP.type) > -1) {
            this.userOTP.availableRoles = this.userOTP.availableRoles.filter((item: any) => item !== 'visa');
        }
        this.userOTP.step = this.data.step ?? this.userOTP.step;
        this.userOTP.isSignatureRequired = this.data.isSignatureRequired ?? this.userOTP.isSignatureRequired;
        this.dialogRef.close({ otp: this.userOTP });
    }

    isValidForm(): boolean {
        return Object.values(this.userOTP).every(item => (item !== '')) && this.validFormat();
    }

    validFormat(): boolean {
        const phoneRegex = /^((\+)33)[1-9](\d{2}){4}$/;
        const emailRegex = /^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/;
        return (!this.functions.empty(this.userOTP.phone) && this.userOTP.phone.trim().match(phoneRegex) !== null) && (!this.functions.empty(this.userOTP.email) && this.userOTP.email.trim().match(emailRegex) !== null);
    }

    setCurrentSource(id: any): void {
        const selectedSource: any = this.sources.filter((item: any) => item.id === id)[0];
        this.userOTP.type = selectedSource.type;
        this.currentSource = [...new Set(selectedSource.securityModes)];
        this.userOTP.security = this.currentSource[0];
    }

    getContact(item: any): void {
        const url: string = item.type === 'contact' ? '../rest/contacts/' + item.id : '../rest/users/' + item.id;
        this.http.get(`${url}?otpTarget=true`).pipe(
            tap((data: any) => {
                const phone: string = data.phone;
                this.userOTP.firstname = data.firstname;
                this.userOTP.lastname = data.lastname;
                this.userOTP.email = data[item.type === 'user' ? 'mail' : 'email'];
                this.userOTP.phone = !this.functions.empty(phone) ? phone.replace(/( |\.|-)/g, '').replace('0', '+33') : '';
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    formatPhone(): void {
        if (this.userOTP.phone.length > 1 && this.userOTP.phone[0] === '0') {
            this.userOTP.phone = this.userOTP.phone.replace('0', '+33');
        }
    }

    getCorrespondents(): void {
        this.http.get(`../rest/resources/${this.data.resId}?light=true`).pipe(
            tap((data: any) => {
                if (data.categoryId === 'outgoing') {
                    data.recipients.forEach((element: any) => {
                        this.setCorrespondentsShorcuts(element, 'recipient');
                    });
                } else if (data.senders !== undefined) {
                    data.senders.forEach((element: any) => {
                        this.setCorrespondentsShorcuts(element, 'sender');
                    });
                }
            }),
            catchError((err) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    setCorrespondentsShorcuts(item: any, itemCategory: string): void {
        let objCorr = {};
        const url: string = item.type === 'user' ? '../rest/users/' + item.id : '../rest/contacts/' + item.id;
        this.http.get(`${url}?otpTarget=true`).pipe(
            tap((data: any) => {
                objCorr = {
                    title: this.translate.instant('lang.' + itemCategory),
                    label: this.contactService.formatContact(data),
                    firstname: data.firstname,
                    lastname: data.lastname,
                    email: data[item.type === 'user' ? 'mail' : 'email'],
                    phone: !this.functions.empty(data.phone) ? data.phone.replace(/( |\.|-)/g, '').replace('0', '+33') : ''
                };
                this.correspondentShorcuts.push(objCorr);
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    setOtpInfoFromShortcut(item: any): void {
        this.userOTP.firstname = item.firstname;
        this.userOTP.lastname = item.lastname;
        this.userOTP.email = item.email;
        this.userOTP.phone = item.phone;
        this.formatPhone();
    }

    getRegexPhone(): RegExp {
        // map country calling code with national number length
        const phonesMap = {
            '32': [8, 10],      // Belgium
            '41': [4, 12],      // Swiss
            '44': [7, 10],      // United Kingdom
            '352': [4, 11],     // Luxembourg
            '351': [9, 11],     // Portugal
            '33': 9,            // France
            '1': 10,           // USA
            '39': 11,           // Italy
            '34': 9             // Spain
        };
        const regex = Object.keys(phonesMap).reduce((phoneFormats: any [], countryCode: any) => {
            const numberLength = phonesMap[countryCode];
            if (Array.isArray(numberLength)) {
                phoneFormats.push('(\\+' + countryCode + `[0-9]{${numberLength[0]},${numberLength[1]}})`);
            } else {
                phoneFormats.push('(\\+' + countryCode + `[0-9]{${numberLength}})`);
            }
            return phoneFormats;
        }, []).join('|');
        return new RegExp(`^(${regex})$`);
    }
}
