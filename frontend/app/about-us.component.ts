import { Component, OnInit, ViewChild, ViewContainerRef } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';
import { AppService } from '@service/app.service';
import { environment } from '@environments/environment';
import { HttpClient } from '@angular/common/http';
import { MatLegacyDialogRef as MatDialogRef } from '@angular/material/legacy-dialog';
import { MatIconRegistry } from '@angular/material/icon';
import { DomSanitizer } from '@angular/platform-browser';
import { PluginManagerService } from "@service/plugin-manager.service";
import { DatePipe } from "@angular/common";
import { SignatureBookService } from "@appRoot/signatureBook/signature-book.service";
import { NotificationService } from "@service/notification/notification.service";
import { FunctionsService } from "@service/functions.service";

@Component({
    templateUrl: 'about-us.component.html',
    styleUrls: ['about-us.component.css']
})
export class AboutUsComponent implements OnInit {

    @ViewChild('myPlugin', { read: ViewContainerRef, static: true }) myPlugin: ViewContainerRef;

    applicationVersion: string;
    applicationDesc: string;
    currentYear: number;

    plugins: any[] = [];

    loading: boolean = false;

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        public appService: AppService,
        public signatureBookService: SignatureBookService,
        public dialogRef: MatDialogRef<AboutUsComponent>,
        public functions: FunctionsService,
        private pluginManagerService: PluginManagerService,
        private translateService: TranslateService,
        private datePipe: DatePipe,
        private notificationService: NotificationService,
        iconReg: MatIconRegistry,
        sanitizer: DomSanitizer,
    ) {
        iconReg.addSvgIcon('maarchBox', sanitizer.bypassSecurityTrustResourceUrl('assets/maarch_box.svg'));
    }

    async ngOnInit() {
        this.applicationVersion = environment.VERSION;
        this.applicationDesc = environment.APP_DESC;
        this.currentYear = new Date().getFullYear();
        await this.getMaarchParapheurApiInfo();
        await this.getPluginsInfo();
        this.loading = false;
    }

    async getMaarchParapheurApiInfo(): Promise<void> {
        const res: { version: string, time: string } = await this.signatureBookService.getMaarchParapheurApiInfo();
        if (res !== null) {
            this.plugins.push({
                name: 'maarch-parapheur-api',
                version: res.version ? res.version : this.translateService.instant('lang.undefined'),
                build: res.time ? `(BUILD ${this.datePipe.transform(res.time, 'dd-MM-yyyy HH:mm')})` : ''
            })
        }
    }

    async getPluginsInfo(): Promise<void> {
        try {
            this.plugins = this.plugins.concat(await this.pluginManagerService.getPluginsInfo(this.myPlugin));
        } catch (err) {
            this.notificationService.handleSoftErrors(err);
        } finally {
            this.signatureBookService.checkVersionsConsistency(environment.FULL_VERSION, {
                fortify: this.plugins.find((plugin: { name: string }) => plugin.name === 'maarch-plugins-fortify').version,
                pdftron: this.plugins.find((plugin: { name: string }) => plugin.name === 'maarch-plugins-pdftron').version,
                mpApi: this.plugins.find((plugin: { name: string }) => plugin.name === 'maarch-parapheur-api').version,
            });
        }
        this.myPlugin.detach();
    }
}

