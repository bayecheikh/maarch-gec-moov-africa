import { HttpClient } from '@angular/common/http';
import { Component, Input, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormControl, UntypedFormGroup, Validators } from '@angular/forms';
import { TranslateService } from '@ngx-translate/core';
import { ConfirmComponent } from '@plugins/modal/confirm.component';
import { FunctionsService } from "@service/functions.service";
import { NotificationService } from "@service/notification/notification.service";
import { catchError, tap, of, finalize, map, filter, exhaustMap } from 'rxjs';
import { MatLegacyDialog as MatDialog } from '@angular/material/legacy-dialog';
import { FullDatePipe } from '@plugins/fullDate.pipe';
@Component({
    selector: 'app-administration-user-access-token',
    templateUrl: 'user-administration-access-token.component.html',
    styleUrls: ['user-administration-access-token.component.scss'],
    providers: [FullDatePipe]
})

export class UserAdministrationAccesTokenComponent implements OnInit {

    @Input() userId: number = null;

    accessTokenFormGroup: UntypedFormGroup;

    accessTokenConfig: { expirationDate: UntypedFormControl } = {
        expirationDate: new UntypedFormControl('', [Validators.required])
    }

    accessToken: UserAcceTokenInterface = { token: '', creationDate: '', expirationDate: '', lastUsedDate: '' };

    minDate: Date = new Date(new Date().setDate(new Date().getDate() + 1));
    maxDate: Date = new Date(this.minDate);

    loading: boolean = true;
    newAccessTokenCreated: boolean = false;
    hideToken: boolean = true;

    constructor(
        public functionsService: FunctionsService,
        public translate: TranslateService,
        public _formBuilder: UntypedFormBuilder,
        private notificationsService: NotificationService,
        private httpClient: HttpClient,
        private dialog: MatDialog

    ) { }
    async ngOnInit(): Promise<void> {
        this.newAccessTokenCreated = false;
        this.maxDate.setFullYear(this.maxDate.getFullYear() + 1); // 1 year as maximum
        this.accessTokenFormGroup = this._formBuilder.group(this.accessTokenConfig);
        await this.getUserAccessToken();
    }

    getUserAccessToken(): Promise<boolean> {
        return new Promise((resolve) => {
            this.httpClient.get(`../rest/users/${this.userId}/tokens`).pipe(
                map((data: { token: { creation_date: string, expiration_date: string, last_used_date: string, token: string }}) => data.token),
                tap((data: { creation_date: string, expiration_date: string, last_used_date: string, token: string }) => {
                    this.accessToken = {
                        token: data.token,
                        creationDate: data.creation_date,
                        expirationDate: data.expiration_date,
                        lastUsedDate: data.last_used_date
                    };
                    resolve(true);
                }),
                finalize(() => this.loading = false),
                catchError((err: any) => {
                    this.notificationsService.handleSoftErrors(err);
                    resolve(false);
                    return of(false);
                })
            ).subscribe();
        });
    }

    generateAccessToken(): void {
        this.loading = true;
        this.httpClient.post(`../rest/users/${this.userId}/tokens`, { expirationDate: this.accessTokenFormGroup.controls['expirationDate'].value }).pipe(
            tap(async () => {
                await this.getUserAccessToken().then(() => {
                    this.newAccessTokenCreated = true;
                    this.accessTokenFormGroup.controls['expirationDate'].setValue('');
                    this.notificationsService.success(this.translate.instant('lang.accessTokenCreated'));
                });
            }),
            catchError((err: any) => {
                this.notificationsService.handleSoftErrors(err);
                this.loading = false;
                return of(false);
            })
        ).subscribe();
    }

    revokeAccessToken(): void {
        const dialogRef = this.dialog.open(ConfirmComponent,
            {
                panelClass: 'maarch-modal',
                autoFocus: false,
                disableClose: true,
                data: {
                    title: `${this.translate.instant('lang.delete')}`,
                    msg: this.translate.instant('lang.confirmAction'),
                }
            });

        dialogRef.afterClosed().pipe(
            filter((data: string) => data === 'ok'),
            exhaustMap(() => of(this.loading = true)),
            exhaustMap(() => this.httpClient.delete(`../rest/users/${this.userId}/tokens`)),
            tap(() => {
                this.accessToken = { token: '', creationDate: '', expirationDate: '', lastUsedDate: '' };
                this.newAccessTokenCreated = false;
                this.accessTokenFormGroup.controls['expirationDate'].setValue('');
                this.notificationsService.success(this.translate.instant('lang.accessTokenDeleted'));
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notificationsService.handleSoftErrors(err);

                return of(false);
            })
        ).subscribe();
    }

    copyToken(token: string): void {
        navigator.clipboard.writeText(token).then(() => {
        }).catch(err => {
            console.error('Erreur lors de la copie :', err);
        });
    }
}

export interface UserAcceTokenInterface {
    token: string;
    creationDate: string;
    expirationDate: string;
    lastUsedDate: string;
}
