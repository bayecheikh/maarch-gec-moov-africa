import { Injectable, ViewContainerRef } from '@angular/core';
import { loadRemoteModule } from '@angular-architects/module-federation';
import { HttpClient } from '@angular/common/http';
import { NotificationService } from './notification/notification.service';
import { HeaderService } from "@service/header.service";
import { catchError, tap } from "rxjs/operators";
import { of } from "rxjs";
import { TranslateService } from "@ngx-translate/core";

export interface PluginConfigInterface {
    id: string;
    url: string;
    config: object;
}

@Injectable({
    providedIn: 'root',
})
export class PluginManagerService {
    plugins: any = {};

    constructor(
        private httpClient: HttpClient,
        private headerService: HeaderService,
        private notificationService: NotificationService,
        private translate: TranslateService
    ) {
    }

    get http(): HttpClient {
        return this.httpClient;
    }

    get notification(): NotificationService {
        return this.notificationService;
    }

    async fetchPlugins(): Promise<PluginConfigInterface[]> {
        return new Promise((resolve) => {
            this.http.get<PluginConfigInterface[]>('../rest/plugins').pipe(
                tap((data: any) => {
                    this.headerService.plugins = data;
                    this.storePlugins(data);
                    resolve(data);
                }),
                catchError((err: any) => {
                    this.notification.handleSoftErrors(err);
                    resolve([]);
                    return of(false);
                })
            ).subscribe();
        });
    }

    async storePlugins(plugins: PluginConfigInterface[]) {
        for (let index = 0; index < plugins.length; index++) {
            const plugin = plugins[index];
            try {
                const pluginContent = await this.loadRemotePlugin(plugin);
                this.plugins[plugin.id] = pluginContent;
                console.info(`PLUGIN ${plugin.id} LOADED`);
            } catch (err) {
                console.error(`PLUGIN ${plugin.id} FAILED: ${err}`);
            }
        }
    }

    async initPlugin(pluginName: string, containerRef: ViewContainerRef, extraData: any = {}) {
        if (!this.plugins[pluginName]) {
            return false;
        }
        try {
            containerRef.detach();
            const remoteComponent: any = containerRef.createComponent(
                this.plugins[pluginName][Object.keys(this.plugins[pluginName])[0]]
            );
            extraData = {
                ...extraData,
                pluginUrl: this.headerService.plugins.find((plugin) => plugin.id === pluginName).url.replace(/\/$/, ""),
                pluginConfig: this.headerService.plugins.find((plugin) => plugin.id === pluginName)?.config,
            };
            remoteComponent.instance.init({ ...this, ...extraData });
            return remoteComponent.instance;
        } catch (error) {
            this.notificationService.error(`Init plugin ${pluginName} failed !`);
            console.error(error);
            return false;
        }
    }

    async showPluginVersion(pluginName: string, containerRef: ViewContainerRef): Promise<{
        description: string;
        version: string
    } | false> {
        if (!this.plugins[pluginName]) {
            return false;
        }
        try {
            containerRef.detach();
            const remoteComponent: any = containerRef.createComponent(
                this.plugins[pluginName][Object.keys(this.plugins[pluginName])[0]]
            );
            return remoteComponent.instance.getVersion();
        } catch (error) {
            console.error(error);
            return false;
        }
    }

    isPluginLoaded(idPlugin: string): boolean {
        return this.plugins[idPlugin] !== undefined;
    }

    loadRemotePlugin(plugin: PluginConfigInterface): Promise<any> {
        return loadRemoteModule({
            type: 'module',
            remoteEntry: `${plugin.url}/remoteEntry.js`,
            exposedModule: `./${plugin.id}`,
        });
    }

    async destroyPlugin(remoteComponent: ViewContainerRef): Promise<boolean> {
        try {
            remoteComponent.clear();
            return true;
        } catch (error) {
            console.error(`Destroy plugin failed : ${error}`);
            return false;
        }
    }

    getPluginUrl(idPlugin: string): string {
        return this.headerService.plugins.find((plugin) => plugin.id === idPlugin)?.url;
    }

    /**
     * Retrieves information about available plugins, including their name, version, and build details.
     *
     * @param {ViewContainerRef} myPlugin - The reference to the container view for dynamically rendering the plugins.
     * @return {Promise<{name: string, version: string, build: string}[]>} A promise that resolves to an array of objects containing the name, version, and build information of each plugin.
     */
    async getPluginsInfo(myPlugin: ViewContainerRef): Promise<{ name: string, version: string, build: string }[]> {
        const plugins: { name: string, version: string, build: string }[] = [];
        for (let i: number = 0; i < Object.keys(this.plugins).length; i++) {
            const pluginName: string = Object.keys(this.plugins)[i];
            const res: {
                description: string;
                version: string
            } | false = await this.showPluginVersion(
                pluginName,
                myPlugin
            );
            plugins.push({
                name: Object.keys(this.plugins)[i],
                version: res ? res.version : this.translate.instant('lang.undefined'),
                build: res ? res.description : ''
            })
        }
        myPlugin.detach();
        return plugins;
    }
}
