import { Component, OnDestroy, OnInit, ViewChild, ViewContainerRef } from '@angular/core';
import { MatLegacyDialog as MatDialog } from '@angular/material/legacy-dialog';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { FormControl, UntypedFormGroup, Validators } from '@angular/forms';
import { catchError, finalize, tap } from 'rxjs/operators';
import { AuthService } from '@service/auth.service';
import { NotificationService } from '@service/notification/notification.service';
import { environment } from '@environments/environment';
import { lastValueFrom, of } from 'rxjs';
import { HeaderService } from '@service/header.service';
import { FunctionsService } from '@service/functions.service';
import { TimeLimitPipe } from '@plugins/timeLimit.pipe';
import { TranslateService } from '@ngx-translate/core';
import { LocalStorageService } from '@service/local-storage.service';
import { SignatureBookService } from "@appRoot/signatureBook/signature-book.service";
import { PluginManagerService } from "@service/plugin-manager.service";
import { SessionStorageService } from "@service/session-storage.service";
import { ProConnectService } from "@service/proconnect.service";

@Component({
    templateUrl: 'login.component.html',
    styleUrls: ['login.component.scss'],
    providers: [TimeLimitPipe]
})
export class LoginComponent implements OnInit, OnDestroy {

    @ViewChild('myPlugin', { read: ViewContainerRef, static: true }) myPlugin: ViewContainerRef;

    loginForm: UntypedFormGroup;

    loading: boolean = false;
    showForm: boolean = true;
    loadingAuthWithProConnect: boolean = false;
    hidePassword: boolean = true;

    environment: any;

    applicationName: string = '';
    loginMessage: string = '';

    constructor(
        public translate: TranslateService,
        public router: Router,
        public authService: AuthService,
        public dialog: MatDialog,
        private http: HttpClient,
        public proConnectService: ProConnectService,
        private headerService: HeaderService,
        private localStorage: LocalStorageService,
        private functionsService: FunctionsService,
        private notify: NotificationService,
        private timeLimit: TimeLimitPipe,
        private signatureBookService: SignatureBookService,
        private pluginManagerService: PluginManagerService,
        private sessionStorage: SessionStorageService,
    ) {
        this.loginForm = new UntypedFormGroup({
            login: new FormControl(null, Validators.required),
            password: new FormControl(null, Validators.required)
        });
    }

    async ngOnInit(): Promise<void> {
        this.headerService.hideSideBar = true;
        this.loading = true;
        this.environment = environment;

        this.proConnectService.getProConnectConfig();

        this.initConnection();

        this.loading = false;

    }

    ngOnDestroy(): void {
        this.proConnectService.removeProConnectSessions();
    }

    onSubmit(ssoToken = null, standardAuth: boolean = true): void {
        this.loading = true;

        let url = '../rest/authenticate';

        if (ssoToken !== null) {
            url += ssoToken;
        }

        const body: {
            login: string,
            password: string,
            mode?: string,
            code?: string,
            nonce?: string,
            state?: string
        } = {
            login: this.loginForm.get('login').value,
            password: this.loginForm.get('password').value,
            mode: '',
            code: '',
            nonce: '',
            state: ''
        }

        if (!this.functionsService.empty(this.sessionStorage.get('maarch_proconnect_code')) && !standardAuth) {
            body.mode = 'proconnect';
            body.code = this.sessionStorage.get('maarch_proconnect_code');
            body.nonce = this.sessionStorage.get('maarch_proconnect_nonce');
            body.state = this.sessionStorage.get('maarch_proconnect_state');

            delete body.login;
            delete body.password;
        } else {
            delete body.mode;
            delete body.code;
            delete body.nonce;
            delete body.state;
        }

        this.http.post(url, body, { observe: 'response' }).pipe(
            tap(async (data: any) => {
                this.localStorage.resetLocal();
                this.authService.saveTokens(data.headers.get('Token'), data.headers.get('Refresh-Token'));
                if (this.sessionStorage.get('maarch_proconnect_code') && this.isProConnectEnabled()) {
                    this.localStorage.save('Proconnect-idToken', data.headers.get('Proconnect-idToken'));
                }
                await lastValueFrom(this.authService.getCurrentUserInfo());
                await this.signatureBookService.getInternalSignatureBookConfig();
                await this.pluginManagerService.fetchPlugins();
                if (this.authService.getCachedUrl()) {
                    this.router.navigateByUrl(this.authService.getCachedUrl());
                    this.authService.cleanCachedUrl();
                } else if (!this.functionsService.empty(this.authService.getToken()?.split('.')[1]) && !this.functionsService.empty(this.authService.getUrl(JSON.parse(atob(data.headers.get('Token').split('.')[1])).user.id))) {
                    this.router.navigate([this.authService.getUrl(JSON.parse(atob(data.headers.get('Token').split('.')[1])).user.id)]);
                } else {
                    this.router.navigate(['/home']);
                }
                this.loading = false;
                this.loadingAuthWithProConnect = false;
            }),
            finalize(() => {
                if (this.isProConnectEnabled()) {
                    window.history.replaceState({}, document.title, window.location.pathname);
                    this.proConnectService.isProConnectInitialized = true;
                }
            }),
            catchError((err: any) => {
                this.loading = false;
                if (err.error.errors === 'Authentication Failed') {
                    this.notify.error(this.translate.instant('lang.wrongLoginPassword'));
                } else if (err.error.errors === 'Account Locked') {
                    this.notify.error(this.translate.instant('lang.accountLocked') + ' ' + this.timeLimit.transform(err.error.date));
                } else if (this.authService.authMode === 'sso' && err.error.errors === 'Authentication Failed : login not present in header' && !this.functionsService.empty(this.authService.authUri)) {
                    window.location.href = this.authService.authUri;
                } else if (this.authService.authMode === 'azure_saml' && err.error.errors === 'Authentication Failed : not logged') {
                    window.location.href = err.error.authUri;
                } else {
                    this.notify.handleSoftErrors(err);
                }
                this.loading = false;
                this.loadingAuthWithProConnect = false;
                return of(false);
            })
        ).subscribe();
    }

    /**
     * Initializes the connection based on the current authentication mode and session status.
     * Determines the appropriate flow for supported authentication modes (e.g., SSO, SAML, CAS, Keycloak, ProConnect).
     * Redirects, processes tokens, or enables silent login as required by the configuration.
     *
     * @return {void} Does not return a value.
     */
    initConnection(): void {
        if (['sso', 'azure_saml'].indexOf(this.authService.authMode) > -1) {
            this.loginForm.disable();
            this.loginForm.setValidators(null);
            this.onSubmit();
        } else if (['cas', 'keycloak'].indexOf(this.authService.authMode) > -1) {
            this.loginForm.disable();
            this.loginForm.setValidators(null);
            const regexCas = /ticket=[.]*/g;
            const regexKeycloak = /code=[.]*/g;
            if (window.location.search.match(regexCas) !== null || window.location.search.match(regexKeycloak) !== null) {
                const ssoToken = window.location.search.substring(1, window.location.search.length);
                const regexKeycloakState = /state=[.]*/g;

                if (ssoToken.match(regexKeycloakState) !== null) {
                    const params = new URLSearchParams(window.location.search.substring(1));
                    const keycloakState = this.localStorage.get('keycloakState');
                    const paramState = params.get('state');

                    if (keycloakState !== paramState && keycloakState !== null) {
                        const redirectState = new URL(this.authService.authUri).searchParams.get('state');
                        this.localStorage.save('keycloakState', redirectState);
                        window.location.href = this.authService.authUri;
                        return;
                    }

                    this.localStorage.save('keycloakState', null);
                }

                window.history.replaceState({}, document.title, window.location.pathname + window.location.hash);
                this.onSubmit(`?${ssoToken}`);
            } else {
                window.location.href = this.authService.authUri;
            }
        } else if (this.isProConnectEnabled()) {
            this.loadingAuthWithProConnect = true;
            const regexErrorProconnect: RegExp = /error=[.]*/g;
            const regexCodeProconnect: RegExp = /code=[.]*/g;
            const regexStateProconnect: RegExp = /state=[.]*/g;

            if (!this.proConnectService.isProConnectInitialized && window.location.search.match(regexErrorProconnect) === null && window.location.search.match(regexCodeProconnect) === null) {
                this.redirectToProConnect(true);
            } else if (window.location.search.match(regexErrorProconnect) !== null) {
                this.proConnectService.isProConnectInitialized = true;
                window.history.replaceState({}, document.title, window.location.pathname);
                this.loadingAuthWithProConnect = false;
                this.notify.error(this.translate.instant('lang.proConnectSessionExpired'));
            }

            if (window.location.search.match(regexCodeProconnect) !== null && window.location.search.match(regexStateProconnect) !== null) {
                const params = new URLSearchParams(window.location.search.substring(1));
                if (this.sessionStorage.get('maarch_proconnect_state') === params.get('state')) {
                    this.sessionStorage.save('maarch_proconnect_code', params.get('code'));
                    this.sessionStorage.remove('maarch_proconnect_error');
                    this.onSubmit(null, false);
                }
            } else {
                this.loadingAuthWithProConnect = false;
            }
        }
    }

    goTo(route: string): void {
        if (this.authService.mailServerOnline) {
            this.router.navigate([route]);
        } else {
            this.notify.error(this.translate.instant('lang.mailServerOffline'));
        }
    }

    isProConnectEnabled(): boolean {
        return this.proConnectService.proConnectConfig.enabled && this.authService.authMode === 'standard';
    }

    isStandardAuthMode(): boolean {
        return ['cas', 'keycloak', 'sso', 'azure_saml'].indexOf(this.authService.authMode) === -1;
    }

    authWithProConnect(): void {
        if (!this.functionsService.empty(this.sessionStorage.get('maarch_proconnect_code'))) {
            this.sessionStorage.remove('maarch_proconnect_code');
        }
        this.loadingAuthWithProConnect = true;
        setTimeout(() => {
            this.redirectToProConnect();
        }, 2000)
    }

    /**
     * Redirects the user to the ProConnect authorization endpoint with the required parameters for authentication.
     * This method generates unique `state` and `nonce` values to prevent replay attacks, saves them in session storage,
     * and then constructs the authorization URL with the specified parameters. If previous state, nonce, or code values
     * exist in session storage, they are cleared before saving new ones. Finally, the user is redirected to the generated URL.
     *
     * @return {void} Nothing is returned as this method performs a redirection.
     */
    redirectToProConnect(isSingleSignOn: boolean = false): void {
        const state: string = this.functionsService.generateRandomBase64String(32);
        const nonce: string = this.functionsService.generateRandomBase64String(32);

        if (!this.functionsService.empty(this.sessionStorage.get('maarch_proconnect_state'))) {
            this.sessionStorage.remove('maarch_proconnect_state');
        }

        if (!this.functionsService.empty(this.sessionStorage.get('maarch_proconnect_nonce'))) {
            this.sessionStorage.remove('maarch_proconnect_nonce');
        }

        this.sessionStorage.save('maarch_proconnect_state', state);
        this.sessionStorage.save('maarch_proconnect_nonce', nonce);

        const baseUrl: string = `${this.proConnectService.proConnectConfig.url}/api/v2/authorize`;
        const params: URLSearchParams = new URLSearchParams();

        params.append('nonce', nonce);
        params.append('state', state);
        params.append('redirect_uri', encodeURI(this.proConnectService.proConnectConfig.redirect_uri));
        params.append('scope', this.proConnectService.proConnectConfig.scope);
        params.append('client_id', this.proConnectService.proConnectConfig.clientId);
        params.append('response_type', 'code');

        if (isSingleSignOn) {
            params.append('prompt', 'none');
        }

        window.location.href = `${baseUrl}?${params.toString()}`;
    }

    getLoadingMessage(): string {
        if (this.sessionStorage.get('maarch_proconnect_code')) {
            return this.translate.instant('lang.waitingProConnectAUth');
        }

        return this.translate.instant('lang.loadingAuthWithProConnect');
    }
}
