import {
    ApplicationRef,
    ComponentFactoryResolver,
    Injectable,
    Injector,
    TemplateRef,
    ViewContainerRef
} from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { map } from 'rxjs/operators';
import { MatSidenav } from '@angular/material/sidenav';
import { FoldersService } from '../app/folder/folders.service';
import { DomPortalOutlet, TemplatePortal } from '@angular/cdk/portal';
import { PluginConfigInterface } from "@service/plugin-manager.service";
import { SafeHtml } from "@angular/platform-browser";

@Injectable({
    providedIn: 'root'
})
export class HeaderService {
    sideBarForm: boolean = false;
    sideBarAdmin: boolean = false;
    hideSideBar: boolean = false;
    showhHeaderPanel: boolean = true;
    showMenuShortcut: boolean = true;
    showMenuNav: boolean = true;

    sideNavLeft: MatSidenav = null;
    sideNavRight: MatSidenav = null;
    sideBarButton: any = null;

    currentBasketInfo: any = {
        ownerId: 0,
        groupId: 0,
        basketId: ''
    };
    folderId: number = 0;
    tiles: any[];
    welcomeMessage: SafeHtml;
    headerMessageIcon: string = '';
    headerMessage: string = '';
    subHeaderMessage: string = '';
    user: any = {
        firstname: '',
        lastname: '',
        groups: [],
        privileges: [],
        preferences: [],
        featureTour: [],
        externalId: null
    };
    nbResourcesFollowed: number = 0;
    plugins: PluginConfigInterface[] = [];
    base64: string = null;

    resIdMaster: number = null;

    private portalHost: DomPortalOutlet;

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        public foldersService: FoldersService,
        private componentFactoryResolver: ComponentFactoryResolver,
        private injector: Injector,
        private appRef: ApplicationRef,
    ) {
    }

    resfreshCurrentUser() {
        return new Promise((resolve) => {
            this.http.get('../rest/currentUser/profile')
                .pipe(
                    map((data: any) => {
                        this.user = {
                            mode: data.mode,
                            id: data.id,
                            userId: data.user_id,
                            mail: data.mail,
                            firstname: data.firstname,
                            lastname: data.lastname,
                            entities: data.entities,
                            groups: data.groups,
                            preferences: data.preferences,
                            privileges: data.privileges[0] === 'ALL_PRIVILEGES' ? this.user.privileges : data.privileges,
                            featureTour: data.featureTour,
                            externalId: data.external_id
                        };
                        this.nbResourcesFollowed = data.nbFollowedResources;
                        resolve(data);
                    })
                ).subscribe();
        });

    }

    setUser(user: any = { firstname: '', lastname: '', groups: [], privileges: [] }) {
        this.user = user;
    }

    getLastLoadedFile() {
        return this.base64;
    }

    setLoadedFile(base64: string) {
        this.base64 = base64;
    }

    setResIdMaster(resId: number) {
        this.resIdMaster = resId;
    }

    setHeader(maintTitle: string, subTitle: any = '', icon = '') {
        this.headerMessage = maintTitle;
        this.subHeaderMessage = subTitle;
        this.headerMessageIcon = icon;
    }

    resetSideNavSelection() {
        this.currentBasketInfo = {
            ownerId: 0,
            groupId: 0,
            basketId: ''
        };
        this.foldersService.setFolder({ id: 0 });
        this.sideBarForm = false;
        this.showhHeaderPanel = true;
        this.showMenuShortcut = true;
        this.showMenuNav = true;
        this.sideBarAdmin = false;
        this.sideBarButton = null;
        this.hideSideBar = true;
    }

    resetBase64AndResId() {
        this.base64 = null;
        this.resIdMaster = null;
    }

    injectInSideBarLeft(template: TemplateRef<any>, viewContainerRef: ViewContainerRef, id: string = 'adminMenu', mode: string = '') {

        if (mode === 'form') {
            this.sideBarForm = true;
            this.showhHeaderPanel = true;
            this.showMenuShortcut = false;
            this.showMenuNav = false;
            this.sideBarAdmin = true;
        } else {
            this.showhHeaderPanel = true;
            this.showMenuShortcut = true;
            this.showMenuNav = true;
        }

        const division = document.querySelector(`#${id}`)
        if (division !== null) {
            // Create a portalHost from a DOM element
            this.portalHost = new DomPortalOutlet(
                division,
                this.componentFactoryResolver,
                this.appRef,
                this.injector
            );

            // Create a template portal
            const templatePortal = new TemplatePortal(
                template,
                viewContainerRef
            );

            // Attach portal to host
            this.portalHost.attach(templatePortal);
        }

    }

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    initTemplate(template: TemplateRef<any>, viewContainerRef: ViewContainerRef, id: string = 'adminMenu', mode: string = '') {
        // Create a portalHost from a DOM element
        this.portalHost = new DomPortalOutlet(
            document.querySelector(`#${id}`),
            this.componentFactoryResolver,
            this.appRef,
            this.injector
        );

        // Create a template portal
        const templatePortal = new TemplatePortal(
            template,
            viewContainerRef
        );

        // Attach portal to host
        this.portalHost.attach(templatePortal);
    }
}
