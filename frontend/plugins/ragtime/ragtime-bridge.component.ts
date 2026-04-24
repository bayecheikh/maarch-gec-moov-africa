import { Component, Input, OnDestroy, OnInit, ViewChild, ViewContainerRef } from '@angular/core';
import { PluginManagerService } from "@service/plugin-manager.service";
import { RagtimeService } from './ragtime.service';
import { MatSidenav } from "@angular/material/sidenav";
import { HttpClient } from '@angular/common/http';
import { HeaderService } from '@service/header.service';
import { AuthService } from "@service/auth.service";
import { TranslateService } from '@ngx-translate/core';

@Component({
    selector: 'app-ragtime-bridge',
    templateUrl: 'ragtime-bridge.component.html',
    styleUrls: ['ragtime-bridge.component.scss'],
})
export class RagtimeBridgeComponent implements OnInit, OnDestroy {

    @ViewChild('myPlugin', { read: ViewContainerRef, static: true }) myPlugin: ViewContainerRef;

    @Input() snavRight: MatSidenav | null;

    pluginInstance: any = false;
    pluginUrl: string = '';

    constructor(
        private pluginManagerService: PluginManagerService,
        private headerService: HeaderService,
        private ragtimeService: RagtimeService,
        public http: HttpClient,
        private translateService: TranslateService,
        private authService: AuthService
    ) { }

    async ngOnInit() {
        this.headerService.sideNavLeft?.close();
        window.addEventListener('message', this.handlePluginMessage.bind(this), false);
        await this.initPlugin();
    }

    ngOnDestroy() {
        this.ragtimeService.resetPanel();
        // this.ragtimeService.clearResId();
    }

    handlePluginMessage(event: MessageEvent) {
        if (event.data && event.data.type === 'closeSidenav') {
            this.snavRight.close();
        }
    }

    async initPlugin() {
        const resId: string = this.ragtimeService.getResId();
        const firstname: string = this.ragtimeService.getFirstname();

        await this.pluginManagerService.fetchPlugins().then(() => {
            this.pluginUrl = this.pluginManagerService.getPluginUrl('maarch-plugins-ragtime');
        });

        const extraData: any = {
            additionalInfo: {
                resId : resId,
                firstName : firstname,
                translate: { service: this.translateService, currentLang: this.authService.lang }
            },
        };

        this.pluginInstance = await this.pluginManagerService.initPlugin('maarch-plugins-ragtime', this.myPlugin, extraData);
    }
}
