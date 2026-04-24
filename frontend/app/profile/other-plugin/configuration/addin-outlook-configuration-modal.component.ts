import { Component } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';
import { MatLegacyDialogRef as MatDialogRef } from '@angular/material/legacy-dialog';
import { HttpClient } from '@angular/common/http';
import { NotificationService } from '@service/notification/notification.service';
import { of } from 'rxjs';
import { catchError, tap } from 'rxjs/operators';

@Component({
    templateUrl: 'addin-outlook-configuration-modal.component.html',
    styleUrls: ['addin-outlook-configuration-modal.component.scss'],
})
export class AddinOutlookConfigurationModalComponent {

    hidePassword: boolean = true;

    configurartionStemps: { content: string, imgSrc: string }[] = [
        {
            content: this.translate.instant('lang.addingOutlookWebMsg1'),
            imgSrc: 'assets/addin_outlook_1.png'
        },
        {
            content: this.translate.instant('lang.addingOutlookWebMsg2'),
            imgSrc: 'assets/addin_outlook_2.png'
        },
        {
            content: this.translate.instant('lang.addingOutlookWebMsg3'),
            imgSrc: 'assets/addin_outlook_3.png'
        },
        {
            content: this.translate.instant('lang.addingOutlookWebMsg4'),
            imgSrc: 'assets/addin_outlook_4.png'
        },
        {
            content: this.translate.instant('lang.addingOutlookWebMsg5'),
            imgSrc: 'assets/addin_outlook_5.png'
        },
        {
            content: this.translate.instant('lang.addingOutlookWebMsg6'),
            imgSrc: 'assets/addin_outlook_6.png'
        }
    ];

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        public translate: TranslateService,
        public dialogRef: MatDialogRef<AddinOutlookConfigurationModalComponent>
    ) { }

    getManifest() {
        this.http.get('../rest/plugins/outlook/manifest', { responseType: 'blob' }).pipe(
            tap((data: any) => {
                const downloadLink = document.createElement('a');
                downloadLink.href = window.URL.createObjectURL(data);
                downloadLink.setAttribute('download', 'manifest.xml');
                document.body.appendChild(downloadLink);
                downloadLink.click();
            }),
            catchError((err: any) => {
                this.notify.handleBlobErrors(err);
                this.dialogRef.close();
                return of(false);
            })
        ).subscribe();
    }
}
