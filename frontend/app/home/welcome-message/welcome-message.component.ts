import { Component, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { NotificationService } from '@service/notification/notification.service';
import { DomSanitizer } from '@angular/platform-browser';
import { catchError, of, tap } from 'rxjs';
import { FunctionsService } from "@service/functions.service";
import { HeaderService } from "@service/header.service";

@Component({
    selector: 'app-welcome-message',
    templateUrl: 'welcome-message.component.html',
    styleUrls: ['welcome-message.component.scss']
})
export class WelcomeMessageComponent implements OnInit {

    constructor(
        public http: HttpClient,
        public headerService: HeaderService,
        private functionsService: FunctionsService,
        private notify: NotificationService,
        private sanitizer: DomSanitizer
    ) { }

    async ngOnInit(): Promise<void> {
        if (!this.headerService.welcomeMessage) {
            const rawMsg: string = await this.getMessage();
            const sanitizedHtml: string = this.functionsService.sanitizeHtml(rawMsg);
            this.headerService.welcomeMessage =  this.sanitizer.bypassSecurityTrustHtml(sanitizedHtml);
        }
    }

    getMessage(): Promise<string> {
        return new Promise(resolve => {
            this.http.get('../rest/home').pipe(
                tap((data: any) => {
                    resolve(data['homeMessage']);
                }),
                catchError((err: any) => {
                    resolve('');
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        })
    }
}
