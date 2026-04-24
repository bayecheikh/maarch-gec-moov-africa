import { Injectable } from '@angular/core';
import { ProConnectInterface } from "@models/connection.model";
import { SessionStorageService } from "@service/session-storage.service";
import { FunctionsService } from "@service/functions.service";
import { catchError, map, tap } from "rxjs/operators";
import { of } from "rxjs";
import { HttpClient } from "@angular/common/http";
import { NotificationService } from "@service/notification/notification.service";


@Injectable({
    providedIn: 'root',
})
export class ProConnectService {
    proConnectConfig: ProConnectInterface = {
        url: '',
        redirect_uri: '',
        scope: '',
        clientId: '',
        enabled: false
    };

    isProConnectInitialized: boolean = false;

    constructor(
        private sessionStorageService: SessionStorageService,
        private functions: FunctionsService,
        private http: HttpClient,
        private notify: NotificationService,
    ) {
    }

    getProConnectConfig(): void {
        this.http.get('../rest/authenticationInformations').pipe(
            map((data: { proconnect: ProConnectInterface }) => data.proconnect),
            tap((proConnect: ProConnectInterface) => {
                if (!this.functions.empty(proConnect)) {
                    this.proConnectConfig = proConnect;
                }
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    removeProConnectSessions(): void {
        this.sessionStorageService.remove('maarch_proconnect_state');
        this.sessionStorageService.remove('maarch_proconnect_nonce');
        this.sessionStorageService.remove('maarch_proconnect_code');
    }

}
