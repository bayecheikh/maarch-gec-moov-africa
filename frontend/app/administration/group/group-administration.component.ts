import { Component, OnInit, ViewChild } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { ActivatedRoute, Router } from '@angular/router';
import { TranslateService } from '@ngx-translate/core';
import { NotificationService } from '@service/notification/notification.service';
import { HeaderService } from '@service/header.service';
import { MatLegacyPaginator as MatPaginator } from '@angular/material/legacy-paginator';
import { MatSidenav } from '@angular/material/sidenav';
import { MatSort } from '@angular/material/sort';
import { MatLegacyTableDataSource as MatTableDataSource } from '@angular/material/legacy-table';
import { AppService } from '@service/app.service';
import { PrivilegeService } from '@service/privileges.service';
import { catchError, exhaustMap, filter, finalize, map, tap } from 'rxjs/operators';
import { MatLegacyDialog as MatDialog } from '@angular/material/legacy-dialog';
import { ConfirmComponent } from '@plugins/modal/confirm.component';
import { of } from 'rxjs';
import { AuthService } from '@service/auth.service';
import { FunctionsService } from '@service/functions.service';
import { PluginManagerService } from '@service/plugin-manager.service';

@Component({
    templateUrl: 'group-administration.component.html',
    styleUrls: ['group-administration.component.scss']
})
export class GroupAdministrationComponent implements OnInit {

    @ViewChild('snav2', { static: true }) public sidenavRight: MatSidenav;
    @ViewChild('paginatorBaskets', { static: false }) paginatorBaskets: MatPaginator;
    @ViewChild('sortBaskets', { static: false }) sortBaskets: MatSort;
    @ViewChild(MatPaginator, { static: false }) paginator: MatPaginator;
    @ViewChild('sortUsers', { static: false }) sortUsers: MatSort;

    loading: boolean = false;
    paramsLoading: boolean = false;

    group: any = {
        security: {},
    };
    creationMode: boolean;
    menus: any = {};

    usersDisplayedColumns: string[] = ['firstname', 'lastname'];
    basketsDisplayedColumns: string[] = ['basket_name', 'basket_desc'];
    usersDataSource: any;
    basketsDataSource: any;

    unitPrivileges: any[] = [];

    administrationPrivileges: any[] = [];

    authorizedGroupsUserParams: any[] = [];
    panelMode: string = 'keywordInfos';

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        public appService: AppService,
        public privilegeService: PrivilegeService,
        public authService: AuthService,
        public functions: FunctionsService,
        public pluginManagerService: PluginManagerService,
        private route: ActivatedRoute,
        private router: Router,
        private notify: NotificationService,
        private headerService: HeaderService,
        private dialog: MatDialog
    ) {
    }

    ngOnInit(): void {
        this.loading = true;

        this.route.params.subscribe(params => {
            if (typeof params['id'] === 'undefined') {

                this.headerService.setHeader(this.translate.instant('lang.groupCreation'));

                this.creationMode = true;
                this.loading = false;
            } else {
                this.creationMode = false;
                this.http.get('../rest/groups/' + params['id'] + '/details').pipe(
                    tap((data: any) => {
                        this.group = data['group'];

                        this.administrationPrivileges = this.privilegeService.getAdministrations();

                        this.administrationPrivileges = this.administrationPrivileges.map(admin => ({
                            ...admin,
                            checked: this.group.privileges.indexOf(admin.id) > -1
                        }));

                        this.privilegeService.getUnitsPrivileges().forEach(element => {
                            let services: any[] = this.privilegeService.getPrivilegesByUnit(element);

                            if (element === 'diffusionList') {
                                services = [
                                    {
                                        id: 'indexing_diffList',
                                        label: this.translate.instant('lang.diffListPrivilegeMsgIndexing'),
                                        current: this.group.privileges.filter((priv: any) => ['update_diffusion_indexing', 'update_diffusion_except_recipient_indexing'].indexOf(priv) > -1)[0] !== undefined ? this.group.privileges.filter((priv: any) => ['update_diffusion_indexing', 'update_diffusion_except_recipient_indexing'].indexOf(priv) > -1)[0] : '',
                                        services: this.privilegeService.getPrivileges(['update_diffusion_indexing', 'update_diffusion_except_recipient_indexing'])
                                    },
                                    {
                                        id: 'process_diffList',
                                        label: this.translate.instant('lang.diffListPrivilegeMsgProcess'),
                                        current: this.group.privileges.filter((priv: any) => ['update_diffusion_process', 'update_diffusion_except_recipient_process'].indexOf(priv) > -1)[0] !== undefined ? this.group.privileges.filter((priv: any) => ['update_diffusion_process', 'update_diffusion_except_recipient_process'].indexOf(priv) > -1)[0] : '',
                                        services: this.privilegeService.getPrivileges(['update_diffusion_process', 'update_diffusion_except_recipient_process'])
                                    },
                                    {
                                        id: 'details_diffList',
                                        label: this.translate.instant('lang.diffListPrivilegeMsgDetails'),
                                        current: this.group.privileges.filter((priv: any) => ['update_diffusion_details', 'update_diffusion_except_recipient_details'].indexOf(priv) > -1)[0] !== undefined ? this.group.privileges.filter((priv: any) => ['update_diffusion_details', 'update_diffusion_except_recipient_details'].indexOf(priv) > -1)[0] : '',
                                        services: this.privilegeService.getPrivileges(['update_diffusion_details', 'update_diffusion_except_recipient_details'])
                                    }
                                ];
                            } else if (element === 'confidentialityAndSecurity') {
                                let priv = '';
                                if (this.group.privileges.filter((privGroup: any) => privGroup === 'manage_personal_data')[0]) {
                                    priv = 'manage_personal_data';
                                } else if (this.group.privileges.filter((privGroup: any) => privGroup === 'view_personal_data')[0]) {
                                    priv = 'view_personal_data';
                                }
                                services = [
                                    {
                                        id: 'confidentialityAndSecurity_personal_data',
                                        label: this.translate.instant('lang.personalDataMsg'),
                                        current: priv,
                                        services: this.privilegeService.getPrivileges(['view_personal_data', 'manage_personal_data'])
                                    }
                                ];
                            } else if (element === 'attachments') {
                                const privileges: string[] = ['view_attachments', 'update_attachments', 'update_delete_attachments', 'update_attachments_except_in_visa_workflow', 'update_delete_attachments_except_in_visa_workflow'];
                                const current: string = this.group.privileges.filter((privilege: any) => privileges.indexOf(privilege) > -1)[0];
                                services = [
                                    {
                                        id: 'manageAttachments',
                                        label: this.translate.instant('lang.manageAttachments'),
                                        current: !this.functions.empty(current) ? current : 'update_attachments',
                                        services: this.privilegeService.getPrivileges(privileges)
                                    }
                                ];
                            } else if (element === 'resources') {
                                const privileges: string[] = ['view_resources', 'update_resources', 'update_resources_except_in_visa_workflow'];
                                const current: string = this.group.privileges.filter((privilege: any) => privileges.indexOf(privilege) > -1)[0];
                                services = [
                                    {
                                        id: 'resources',
                                        label: this.translate.instant('lang.manageResourcesDesc'),
                                        current: !this.functions.empty(current) ? current : 'update_resources',
                                        services: this.privilegeService.getPrivileges(privileges)
                                    }
                                ];
                            }

                            this.unitPrivileges.push({
                                id: element,
                                label: this.translate.instant('lang.' + element),
                                services: services
                            });
                        });

                        this.verifyAndAddPluginPrivilege('ragtime_plugin', 'maarch-plugins-ragtime');

                        this.checkContactPrivileges();

                        this.headerService.setHeader(this.translate.instant('lang.groupModification'), this.group['group_desc']);

                        this.loading = false;
                        setTimeout(() => {
                            this.usersDataSource = new MatTableDataSource(this.group.users);
                            this.usersDataSource.paginator = this.paginator;
                            this.usersDataSource.sort = this.sortUsers;
                            this.basketsDataSource = new MatTableDataSource(this.group.baskets);
                            this.basketsDataSource.paginator = this.paginatorBaskets;
                            this.basketsDataSource.sort = this.sortBaskets;
                        }, 0);
                    }),
                    catchError((err: any) => {
                        this.notify.handleErrors(err);
                        return of(false);
                    })
                ).subscribe();
            }
        });
    }

    changeDifflistPrivilege(ev: any, mode: string): void {
        if (mode === 'indexing_diffList') {
            if (ev.value === 'update_diffusion_indexing') {
                this.manageServices(['update_diffusion_indexing', 'update_diffusion_except_recipient_indexing']);
            } else if (ev.value === 'update_diffusion_except_recipient_indexing') {
                this.manageServices(['update_diffusion_except_recipient_indexing', 'update_diffusion_indexing']);
            } else {
                this.manageServices(['update_diffusion_indexing', 'update_diffusion_except_recipient_indexing'], 'deleteAll');
            }
        } else if (mode === 'process_diffList') {
            if (ev.value === 'update_diffusion_process') {
                this.manageServices(['update_diffusion_process', 'update_diffusion_except_recipient_process']);
            } else if (ev.value === 'update_diffusion_except_recipient_process') {
                this.manageServices(['update_diffusion_except_recipient_process', 'update_diffusion_process']);
            } else {
                this.manageServices(['update_diffusion_process', 'update_diffusion_except_recipient_process'], 'deleteAll');
            }
        } else {
            if (ev.value === 'update_diffusion_details') {
                this.manageServices(['update_diffusion_details', 'update_diffusion_except_recipient_details']);
            } else if (ev.value === 'update_diffusion_except_recipient_details') {
                this.manageServices(['update_diffusion_except_recipient_details', 'update_diffusion_details']);
            } else {
                this.manageServices(['update_diffusion_details', 'update_diffusion_except_recipient_details'], 'deleteAll');

            }
        }

    }

    manageServices(servicesId: any[], mode: string = null): void {
        if (mode !== 'deleteAll') {
            this.http.post(`../rest/groups/${this.group.id}/privileges/${servicesId[0]}`, {}).pipe(
                tap(() => {
                    this.group.privileges.push(servicesId[0]);
                }),
                exhaustMap(() => this.http.delete(`../rest/groups/${this.group.id}/privileges/${servicesId[1]}`)),
                tap(() => {
                    this.group.privileges.splice(this.group.privileges.indexOf(servicesId[1]), 1);
                    this.headerService.resfreshCurrentUser();
                    this.notify.success(this.translate.instant('lang.groupServicesUpdated'));
                }),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
        } else {
            this.http.delete(`../rest/groups/${this.group.id}/privileges/${servicesId[0]}`).pipe(
                tap(() => {
                    this.group.privileges.splice(this.group.privileges.indexOf(servicesId[0]), 1);
                }),
                exhaustMap(() => this.http.delete(`../rest/groups/${this.group.id}/privileges/${servicesId[1]}`)),
                tap(() => {
                    this.group.privileges.splice(this.group.privileges.indexOf(servicesId[1]), 1);
                    this.headerService.resfreshCurrentUser();
                    this.notify.success(this.translate.instant('lang.groupServicesUpdated'));
                }),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
        }
    }

    changePersonalDataPrivilege(ev: any): void {
        if (ev.value === 'view_personal_data') {
            this.manageServices(['view_personal_data', 'manage_personal_data']);
        } else if (ev.value === 'manage_personal_data') {
            this.http.post(`../rest/groups/${this.group.id}/privileges/view_personal_data`, {}).pipe(
                tap(() => {
                    this.group.privileges.push('view_personal_data');
                }),
                exhaustMap(() => this.http.post(`../rest/groups/${this.group.id}/privileges/manage_personal_data`, {})),
                tap(() => {
                    this.group.privileges.splice(this.group.privileges.indexOf('manage_personal_data'), 1);
                    this.headerService.resfreshCurrentUser();
                    this.notify.success(this.translate.instant('lang.groupServicesUpdated'));
                }),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
        } else {
            this.manageServices(['view_personal_data', 'manage_personal_data'], 'deleteAll');
        }
    }

    changeAttachmentPrivilege(event: any): void {
        const privileges: string[] = ['view_attachments', 'update_attachments', 'update_delete_attachments', 'update_attachments_except_in_visa_workflow', 'update_delete_attachments_except_in_visa_workflow'];
        const current: string = this.group.privileges.filter((privilege: any) => privileges.indexOf(privilege) > -1)[0];
        this.manageServices([event.value, current]);
    }

    changeDocumentPrivilege(event: any): void {
        const privileges: string[] = ['view_resources', 'update_resources', 'update_resources_except_in_visa_workflow'];
        const current: string = this.group.privileges.filter((privilege: any) => privileges.indexOf(privilege) > -1)[0];
        this.manageServices([event.value, current]);
    }

    async resfreshShortcut(): Promise<void> {
        await this.headerService.resfreshCurrentUser();
        this.privilegeService.resfreshUserShortcuts();
    }

    onSubmit(): void {
        if (this.creationMode) {
            this.http.post('../rest/groups', this.group).pipe(
                tap((data: any) => {
                    this.notify.success(this.translate.instant('lang.groupAdded'));
                    this.router.navigate(['/administration/groups/' + data.group]);
                }),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
        } else {
            this.http.put('../rest/groups/' + this.group['id'], {
                'description': this.group['group_desc'],
                'security': this.group['security']
            }).pipe(
                tap(() => {
                    this.notify.success(this.translate.instant('lang.groupUpdated'));
                }),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
        }
    }

    toggleService(ev: any, service: any): void {
        if (ev.checked) {
            if (service.id === 'admin_groups') {
                const dialogRef = this.dialog.open(ConfirmComponent, {
                    panelClass: 'maarch-modal',
                    autoFocus: false,
                    disableClose: true,
                    data: {
                        title: this.translate.instant('lang.confirmAction'),
                        msg: this.translate.instant('lang.enableGroupMsg')
                    }
                });

                dialogRef.afterClosed().pipe(
                    tap((data: string) => {
                        if (data !== 'ok') {
                            service.checked = false;
                        }
                    }),
                    filter((data: string) => data === 'ok'),
                    tap(() => {
                        this.addService(service);
                    }),
                    catchError((err: any) => {
                        this.notify.handleErrors(err);
                        return of(false);
                    })
                ).subscribe();
            } else {
                this.addService(service);
            }

        } else {
            const isAdminContact = this.group.privileges.includes('admin_contacts');
            const updateConfidentialInfo = this.group.privileges.find((privilege: string) => privilege === 'update_confidential_contact_information');
            this.sidenavRight.close();
            this.removeService(service);

            if (!isAdminContact && ['update_contacts', 'view_confidential_contact_information'].indexOf(service.id) > -1 && !this.functions.empty(updateConfidentialInfo)) {
                this.removeService({ id: 'update_confidential_contact_information' }, true);
            }
        }
    }

    addService(service: any, hideNotification: boolean = false): void {
        this.http.post(`../rest/groups/${this.group.id}/privileges/${service.id}`, {}).pipe(
            tap(() => {
                this.group.privileges.push(service.id);
                this.headerService.resfreshCurrentUser();
                if (hideNotification) {
                    this.notify.success(this.translate.instant('lang.groupServicesUpdated'));
                }
                this.checkContactPrivileges();
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    removeService(service: any, hideNotification: boolean = false): void {
        this.http.delete(`../rest/groups/${this.group.id}/privileges/${service.id}`).pipe(
            tap(() => {
                this.group.privileges.splice(this.group.privileges.indexOf(service.id), 1);
                this.headerService.resfreshCurrentUser();
                this.checkContactPrivileges();
                if (!hideNotification) {
                    this.notify.success(this.translate.instant('lang.groupServicesUpdated'));
                }
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    linkUser(newUser: any): void {
        const groupReq = {
            'groupId': this.group.group_id,
            'role': this.group.role
        };
        this.http.post('../rest/users/' + newUser.id + '/groups', groupReq).pipe(
            tap((data: any) => {
                const displayName = newUser.idToDisplay.split(' ');
                const user = {
                    id: newUser.id,
                    user_id: newUser.otherInfo,
                    firstname: displayName[0],
                    lastname: displayName[1],
                    allowed: true
                };
                this.group.users.push(user);
                this.usersDataSource = new MatTableDataSource(this.group.users);
                this.usersDataSource.paginator = this.paginator;
                this.usersDataSource.sort = this.sortUsers;
                this.notify.success(this.translate.instant('lang.userAdded'));
                // Recover the problem sent by the back (users can be added to a group even if they are not in sync)
                if (data?.problem?.lang) {
                    setTimeout(() => {
                        this.notify.error(this.translate.instant('lang.' + data.problem.lang))
                    }, 1000);
                }
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    openUserParams(id: string): void {
        this.sidenavRight.toggle();
        if (!this.sidenavRight.opened) {
            this.panelMode = '';
        } else {
            this.panelMode = id;
            this.paramsLoading = true;
            this.http.get('../rest/groups').pipe(
                map((data: any) => {
                    data.groups = data.groups.map((group: any) => ({
                        id: group.id,
                        label: group.group_desc
                    }));
                    return data;
                }),
                tap((data: any) => {
                    this.authorizedGroupsUserParams = data.groups;
                }),
                exhaustMap(() => this.http.get(`../rest/groups/${this.group.id}/privileges/${this.panelMode}/parameters?parameter=groups`)),
                tap((data: any) => {
                    const allowedGroups: any[] = data;
                    this.authorizedGroupsUserParams.forEach(group => {
                        group.checked = allowedGroups.indexOf(group.id) > -1;
                    });
                }),
                finalize(() => this.paramsLoading = false),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
        }
    }

    updatePrivilegeParams(paramList: any): void {
        let obj = {};
        if (this.panelMode === 'admin_users') {
            obj = {
                groups: paramList.map((param: any) => param.value)
            };
        }
        this.http.put(`../rest/groups/${this.group.id}/privileges/${this.panelMode}/parameters`, { parameters: obj }).pipe(
            tap(() => {
                this.notify.success(this.translate.instant('lang.parameterUpdated'));
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    goToUserAdmin(user: any): void {
        if (user.allowed) {
            this.router.navigate(['/administration/users/' + user.id]);
        }
    }

    applyFilter(filterValue: string): void {
        filterValue = filterValue.trim();
        filterValue = filterValue.toLowerCase();
        this.usersDataSource.filter = filterValue;
    }

    applyBasketsFilter(filterValue: string): void {
        filterValue = filterValue.trim();
        filterValue = filterValue.toLowerCase();
        this.basketsDataSource.filter = filterValue;
    }

    // Function to verify and potentially add a plugin privilege
    verifyAndAddPluginPrivilege(privilege: string, pluginId: string): void {
        /*
        * Find the index of the given privilege in the group's privilege array
        * Returns -1 if the privilege is not found
        */
        const privilegeIndex: number = this.group.privileges.indexOf(privilege);

        // Check if the privilege does not exist and the plugin is not already loaded
        if (privilegeIndex === -1 && !this.pluginManagerService.isPluginLoaded(pluginId)) {
            if (privilege === 'ragtime_plugin') {
                this.unitPrivileges.find((unit: { id: string }) => unit.id === 'application').services.push({
                    id: 'ragtime_plugin',
                    label: 'lang.activateRagtime',
                    comment: 'lang.activateRagtime',
                    unit: 'application',
                    disabled: true
                });
            }
        }
    }

    checkContactPrivileges(): void {
        const contactUnit = this.unitPrivileges.find((unit: { id: string }) => unit.id === 'contact');

        if (contactUnit) {
            const isAdminContact: boolean = this.group.privileges.includes('admin_contacts');
            const hasUpdateContacts: boolean = this.group.privileges.indexOf('update_contacts') > -1;
            const updateConfidentialContact = contactUnit.services.find((service: { id: string }) =>
                service.id === 'update_confidential_contact_information');

            const viewConfidentialContact = contactUnit.services.find((service: { id: string }) =>
                service.id === 'view_confidential_contact_information');

            const hasViewConfidentialContacts: boolean = this.group.privileges.indexOf('view_confidential_contact_information') > -1;

            const hasUpdateConfidentialContacts: boolean = this.group.privileges.indexOf('update_confidential_contact_information') > -1;

            if (!isAdminContact) {
                if (updateConfidentialContact) {
                    updateConfidentialContact.disabled = !hasUpdateContacts;
                }

                const updateConfidentialContactService = contactUnit.services.find((service: { id: string }) =>
                    service.id === 'update_confidential_contact_information');

                if (updateConfidentialContactService) {
                    updateConfidentialContactService.disabled = !(hasUpdateContacts && hasViewConfidentialContacts);
                    viewConfidentialContact.disabled = false;
                }
            } else {
                if (!hasViewConfidentialContacts) {
                    this.addService({ id: 'view_confidential_contact_information' }, true);
                }

                updateConfidentialContact.disabled = true;

                if (!hasUpdateConfidentialContacts) {
                    this.addService({ id: 'update_confidential_contact_information' }, true);
                }

                viewConfidentialContact.disabled = true;
            }

        }
    }
}
