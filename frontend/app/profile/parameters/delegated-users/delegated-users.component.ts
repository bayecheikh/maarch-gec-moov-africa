import { HttpClient } from '@angular/common/http';
import { Component, Input } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';
import { ConfirmComponent } from '@plugins/modal/confirm.component';
import { FunctionsService } from '@service/functions.service';
import { HeaderService } from '@service/header.service';
import { NotificationService } from '@service/notification/notification.service';
import { catchError, exhaustMap, filter, of, tap } from 'rxjs';
import { MatLegacyDialog as MatDialog, MatLegacyDialogRef as MatDialogRef } from '@angular/material/legacy-dialog';

@Component({
    selector: 'app-delegated-users',
    templateUrl: './delegated-users.component.html',
    styleUrls: ['./delegated-users.component.scss']
})
export class DelegatedUsersComponent {

    @Input() delegatedUsers: { id: number, fullName: string, checked: boolean }[] = [];

    dialogRef: MatDialogRef<ConfirmComponent>;

    constructor(
        public functions: FunctionsService,
        public headerService: HeaderService,
        public translate: TranslateService,
        private notifications: NotificationService,
        private http: HttpClient,
        private dialog: MatDialog,
    ) {}

    allSelected(): boolean {
        return this.delegatedUsers.every((user: { checked: boolean}) => user.checked);
    }

    toggleAll(): void {
        if (this.allSelected()) {
            this.delegatedUsers.forEach((user: { checked: boolean }) => {
                user.checked = false;
            });
        } else {
            this.delegatedUsers.forEach((user: { checked: boolean }) => {
                user.checked = true;
            });
        }
    }

    oneOrMoreSelected(): boolean {
        return this.delegatedUsers.some((user: { checked: boolean }) => user.checked) && this.delegatedUsers.filter((user: { checked: boolean }) => user.checked).length < this.delegatedUsers.length;
    }

    getDisabledItems(): number[] {
        return [this.headerService.user.id].concat(this.delegatedUsers.map((user) => user.id));
    }

    addUser(user: { serialId: number, idToDisplay: string }): void {
        this.http.put(`../rest/users/${this.headerService.user.id}/signatorySubstitute`, { destUser: user.serialId } ).pipe(
            tap(() => {
                this.delegatedUsers.push({
                    id: user.serialId,
                    fullName: user.idToDisplay,
                    checked: false
                });
                this.notifications.success(this.translate.instant('lang.userAdded'));
            }),
            catchError((err: any) => {
                this.notifications.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    removeUser(selectedUser: { id: number, fullName: string, checked: boolean } = null): void {
        let usersIds: number[] = [];
        if (selectedUser !== null) {
            usersIds = [selectedUser.id];
        } else {
            usersIds = this.delegatedUsers.filter((user) => user.checked).map((user) => user.id);
        }

        this.dialogRef = this.dialog.open(ConfirmComponent, {
            panelClass: 'maarch-modal',
            autoFocus: false,
            disableClose: true,
            data: {
                title: `${this.translate.instant('lang.delete')}`,
                msg: this.translate.instant('lang.confirmAction')
            } });
        this.dialogRef.afterClosed().pipe(
            filter((data: string) => data === 'ok'),
            exhaustMap(() => this.http.delete(`../rest/users/${this.headerService.user.id}/signatorySubstitute`, { body: { destUsers: usersIds } })),
            tap(() => {
                if (!this.functions.empty(selectedUser)) {
                    const index: number = this.delegatedUsers.indexOf(selectedUser);
                    if (index !== -1) {
                        this.delegatedUsers.splice(index, 1);
                    }
                } else {
                    this.delegatedUsers.forEach((user: { checked: boolean }, index: number) => {
                        if (user.checked) {
                            this.delegatedUsers.splice(index, 1);
                        }
                    });
                }
                this.notifications.success(this.translate.instant('lang.userDeleted'));
            }),
            catchError((err: any) => {
                this.notifications.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    atLeastOneDelegated(): boolean {
        return this.delegatedUsers.filter((user) => user.checked).length >= 1;
    }
}
