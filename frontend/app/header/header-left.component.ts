import { Component, Input } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';
import { HeaderService } from '@service/header.service';
import { MatLegacyDialogRef as MatDialogRef } from '@angular/material/legacy-dialog';
import { MatSidenav } from '@angular/material/sidenav';

@Component({
    selector: 'app-header-left',
    styleUrls: ['header-left.component.scss'],
    templateUrl: 'header-left.component.html',
})
export class HeaderLeftComponent {

    @Input() snavLeft: MatSidenav;

    dialogRef: MatDialogRef<any>;
    config: any = {};

    constructor(
        public translate: TranslateService,
        public headerService: HeaderService
    ) { }
}
