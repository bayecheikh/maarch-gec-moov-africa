import { Component, OnInit, Inject, ViewChild } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';
import { NotificationService } from '@service/notification/notification.service';
import { MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA, MatLegacyDialogRef as MatDialogRef } from '@angular/material/legacy-dialog';
import { HttpClient } from '@angular/common/http';
import { NoteEditorComponent } from '../../notes/note-editor.component';
import { tap, finalize, catchError } from 'rxjs/operators';
import { of } from 'rxjs';
import { FunctionsService } from '@service/functions.service';
import { SessionStorageService } from '@service/session-storage.service';
import { PrivilegeService } from '@service/privileges.service';
import { Router } from '@angular/router';
import { DatasActionSendInterface } from "@models/actions.model";

@Component({
    templateUrl: 'add-user-entity-as-copy.component.html',
    styleUrls: ['add-user-entity-as-copy.component.scss'],
})

export class AddUserEntityAsCopyActionComponent implements OnInit {

    @ViewChild('noteEditor', { static: false }) noteEditor: NoteEditorComponent;

    usersEntitiesAsCopy: UserEntityAsCopyInterface[] = [];

    loading: boolean = true;

    resourcesWarnings: any[] = [];
    resourcesErrors: any[] = [];

    canGoToNextRes: boolean = false;
    showToggle: boolean = false;
    inLocalStorage: boolean = false;

    constructor (
        @Inject(MAT_DIALOG_DATA) public data: DatasActionSendInterface,
        public translate: TranslateService,
        public http: HttpClient,
        public dialogRef: MatDialogRef<AddUserEntityAsCopyActionComponent>,
        public functions: FunctionsService,
        public privilegesService: PrivilegeService,
        public router: Router,
        private notify: NotificationService,
        private sessionStorage: SessionStorageService
    ) {

    }

    ngOnInit(): void {
        this.showToggle = this.data.additionalInfo.showToggle;
        this.canGoToNextRes = this.data.additionalInfo.canGoToNextRes;
        this.inLocalStorage = this.data.additionalInfo.inLocalStorage;
        this.loading = false;
    }

    isValidAction() {
        return !this.functions.empty(this.usersEntitiesAsCopy);
    }

    onSubmit() {
        const realResSelected: number[] = this.data.resIds.filter((resId: any) => this.resourcesErrors.map(resErr => resErr.res_id).indexOf(resId) === -1);
        this.sessionStorage.checkSessionStorage(this.inLocalStorage, this.canGoToNextRes, this.data);
        this.executeAction(realResSelected);
    }

    executeAction(realResSelected: number[]): void {
        this.http.put(this.data.processActionRoute,
            {
                resources : realResSelected,
                note: this.noteEditor.getNote(),
                data: {
                    usersEntitiesAsCopy: this.usersEntitiesAsCopy.map((element: UserEntityAsCopyInterface) => ({
                        id: element.id,
                        type: element.type
                    }))
                }
            }
        ).pipe(
            tap((data: any) => {
                if (!data) {
                    this.dialogRef.close(realResSelected);
                }
                if (data && data.errors != null) {
                    this.notify.error(data.errors);
                }
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                this.loading = false;
                return of(false);
            })
        ).subscribe();
    }

    addElem(element: { serialId: number, type: string, idToDisplay: string, descriptionToDisplay: string, status: string } ): void {
        const userEntityItem: UserEntityAsCopyInterface = this.usersEntitiesAsCopy.find((item: { id: number, type: string } ) => item.id === element.serialId && item.type === element.type);
        if (this.functions.empty(userEntityItem)) {
            this.usersEntitiesAsCopy.push(
                {
                    id: element.serialId,
                    type: element.type,
                    idToDisplay: element.idToDisplay,
                    descriptionToDisplay: element.descriptionToDisplay,
                    status: element.status
                }
            );
        }
    }

    deleteItem(element: { id: number, type: string }): void {
        const userEntityItem: UserEntityAsCopyInterface = this.usersEntitiesAsCopy.find((item: { id: number, type: string } ) => item.id === element.id && item.type === element.type);
        const index: number = this.usersEntitiesAsCopy.indexOf(userEntityItem);
        if (index !== -1) {
            this.usersEntitiesAsCopy.splice(index, 1);
        }
    }

    /**
     * Determines whether the current user has sufficient privileges based on the current route.
     * The logic is divided into three categories based on specific route patterns.
     *
     * @returns {boolean} - True if the user has the necessary privileges; otherwise, false.
    */
    hasPrivilege(): boolean {
        const { url } = this.router;

        // Privilege keys categorized for better maintainability
        const adminPrivileges: string[] = ['ALL_PRIVILEGES', 'admin_users'];
        const processPrivileges: string[] = [
            'update_diffusion_process',
            'update_diffusion_except_recipient_process'
        ];
        const resourcePrivileges: string[] = [
            'update_diffusion_details',
            'update_diffusion_except_recipient_details'
        ];

        // Check privileges based on route patterns
        if (url.includes('process/users/')) {
            return this.hasAnyPrivilege([...adminPrivileges, ...processPrivileges]);
        }

        if (url.includes('/resources')) {
            return this.hasAnyPrivilege([...adminPrivileges, ...resourcePrivileges]);
        }

        // Default case: Check for a comprehensive set of privileges
        return this.hasAnyPrivilege([
            ...adminPrivileges,
            ...processPrivileges,
            ...resourcePrivileges
        ]);
    }

    /**
     * Helper function to check if the current user has at least one of the specified privileges.
     *
     * @param privileges - An array of privilege keys to check.
     * @returns {boolean} - True if the user has any of the privileges; otherwise, false.
    */
    private hasAnyPrivilege(privileges: string[]): boolean {
        return privileges.some(privilege => this.privilegesService.hasCurrentUserPrivilege(privilege));
    }
}

export interface UserEntityAsCopyInterface {
    id: number,
    type: string,
    idToDisplay: string,
    descriptionToDisplay: string,
    status: string
}