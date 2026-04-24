import { Component, OnInit, AfterViewInit, ViewChild, ViewContainerRef } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { MatLegacyDialog as MatDialog } from '@angular/material/legacy-dialog';
import { HeaderService } from '@service/header.service';
import { AppService } from '@service/app.service';
import { FeatureTourService } from '@service/featureTour.service';
import { FunctionsService } from '@service/functions.service';

@Component({
    templateUrl: 'home.component.html',
    styleUrls: ['home.component.scss']
})
export class HomeComponent implements OnInit, AfterViewInit {

    @ViewChild('remotePlugin2', { read: ViewContainerRef, static: true }) remotePlugin2: ViewContainerRef;

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        public dialog: MatDialog,
        public appService: AppService,
        public functions: FunctionsService,
        private headerService: HeaderService,
        private featureTourService: FeatureTourService,
    ) { }

    async ngOnInit(): Promise<void> {
        this.headerService.setHeader(this.translate.instant('lang.home'));
    }

    ngAfterViewInit(): void {
        if (!this.featureTourService.isComplete()) {
            this.featureTourService.init();
        }
    }
}
