import { Component, OnInit } from '@angular/core';
import { FunctionsService } from "@service/functions.service";
import { HttpClient } from "@angular/common/http";
import { NotificationService } from "@service/notification/notification.service";
import { ActivatedRoute, Router } from "@angular/router";
import { AppService } from '@service/app.service';
import { TranslateService } from '@ngx-translate/core';
import { HeaderService } from '@service/header.service';
import { ConsentPageInterface, GoodFlagTemplateInterface, SignatureProfileInterface } from '@models/goodflag.model';
import { catchError, finalize, tap } from 'rxjs/operators';
import { of } from 'rxjs';

@Component({
    selector: 'app-goodflag',
    templateUrl: './goodflag-administration.component.html',
    styleUrls: ['./goodflag-administration.component.scss']
})
export class GoodflagAdministrationComponent implements OnInit {

    loading: boolean = false;
    creationMode: boolean = true;

    goodflagTemplate: GoodFlagTemplateInterface = {
        id: null,
        label: '',
        description: '',
        signatureProfileId: '',
        consentPageId: ''
    }

    goodflagTemplateClone: GoodFlagTemplateInterface = {
        id: null,
        label: '',
        description: '',
        signatureProfileId: '',
        consentPageId: ''
    }

    consentPages: ConsentPageInterface[] = [];
    consentPagesClone: ConsentPageInterface[] = [];
    selectedConentPage: ConsentPageInterface = null;

    signatureProfiles: SignatureProfileInterface[] = [];
    signatureProfilesClone: SignatureProfileInterface[] = [];
    selectedSignatureProfile: SignatureProfileInterface = null;

    constructor(
        public functions: FunctionsService,
        public appService: AppService,
        public headerService: HeaderService,
        public translate: TranslateService,
        private http: HttpClient,
        private notifications: NotificationService,
        private route: ActivatedRoute,
        private router: Router
    ) {
    }

    async ngOnInit(): Promise<void> {
        this.loading = true;
        await Promise.all([this.getConsentPages(), this.getSignatureProfiles()]);
        this.route.params.subscribe(async params => {
            if (typeof params['id'] === 'undefined') {
                this.headerService.setHeader(this.translate.instant('lang.goodflagCreation'));
                this.goodflagTemplateClone = JSON.parse(JSON.stringify(this.goodflagTemplate));
                this.creationMode = true;
                this.loading = false;
            } else {
                this.goodflagTemplate.id = params['id'];
                this.headerService.setHeader(this.translate.instant('lang.goodflagModification'));
                this.creationMode = false;
                this.http.get(`../rest/goodflag/templates/${params['id']}`).pipe(
                    tap((data: GoodFlagTemplateInterface) => {
                        if (!this.functions.empty(data)) {
                            this.goodflagTemplate = data;
                            this.selectedSignatureProfile = this.signatureProfiles.find(profile => profile.id === this.goodflagTemplate.signatureProfileId);
                            this.selectedConentPage = this.consentPages.find(page => page.id === this.goodflagTemplate.consentPageId);
                        }
                        this.goodflagTemplateClone = JSON.parse(JSON.stringify(this.goodflagTemplate));
                    }),
                    finalize(() => this.loading = false),
                    catchError((err: any) => {
                        this.notifications.handleErrors(err);
                        return of(false);
                    })
                ).subscribe();
            }
        });
    }

    getConsentPages(): Promise<void> {
        return new Promise((resolve) => {
            this.http.get('../rest/goodflag/consentPages').pipe(
                tap((data: ConsentPageInterface[]) => {
                    this.consentPages = Object.values(data) ?? [];
                    if (this.consentPages.length > 0 && this.creationMode) {
                        this.selectedConentPage = this.consentPages[0];
                        this.goodflagTemplate.consentPageId = this.selectedConentPage.id;
                    }
                    this.consentPagesClone = JSON.parse(JSON.stringify(this.consentPages));
                    resolve();
                }),
                catchError((err: any) => {
                    this.notifications.handleErrors(err);
                    this.router.navigate(['/administration/goodflag']);
                    this.loading = false;
                    return of(false);
                })
            ).subscribe();
        })
    }

    getSignatureProfiles(): Promise<void> {
        return new Promise((resolve) => {
            this.http.get('../rest/goodflag/signatureProfiles').pipe(
                tap((data: SignatureProfileInterface[]) => {
                    this.signatureProfiles = Object.values(data) ?? [];
                    if (this.signatureProfiles.length > 0 && this.creationMode) {
                        this.selectedSignatureProfile = this.signatureProfiles[0];
                        this.goodflagTemplate.signatureProfileId = this.selectedSignatureProfile.id;
                    }
                    this.signatureProfilesClone = JSON.parse(JSON.stringify(this.signatureProfiles));
                    resolve();
                }),
                catchError((err: any) => {
                    this.notifications.handleErrors(err);
                    this.router.navigate(['/administration/goodflag']);
                    this.loading = false;
                    return of(false);
                })
            ).subscribe();
        })
    }

    onSubmit(): void {
        this.loading = true;
        const url: string = this.creationMode ? '../rest/goodflag/templates' : `../rest/goodflag/templates/${this.goodflagTemplate.id}`;
        const method: string = this.creationMode ? 'post' : 'put';
        this.http[method](url, this.goodflagTemplate).pipe(
            tap(() => {
                this.router.navigate(['/administration/goodflag']).then(() => {
                    this.notifications.success(this.translate.instant('lang.modificationsProcessed'));
                    this.router.navigate(['/administration/goodflag']);
                });
            }),
            finalize(() => this.loading = false),
            catchError((err) => {
                this.notifications.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    isModified(): boolean {
        return !this.loading && (
            JSON.stringify(this.goodflagTemplate) !== JSON.stringify(this.goodflagTemplateClone) ||
            JSON.stringify(this.consentPages) !== JSON.stringify(this.consentPagesClone) ||
            JSON.stringify(this.signatureProfiles) !== JSON.stringify(this.signatureProfilesClone)
        );
    }

    cancelModification(): void {
        this.goodflagTemplate = JSON.parse(JSON.stringify(this.goodflagTemplateClone));
        this.consentPages = JSON.parse(JSON.stringify(this.consentPagesClone));
        this.signatureProfiles = JSON.parse(JSON.stringify(this.signatureProfilesClone));
        this.selectedSignatureProfile = this.signatureProfiles.find(profile => profile.id === this.goodflagTemplate.signatureProfileId);
        this.selectedConentPage = this.consentPages.find(page => page.id === this.goodflagTemplate.consentPageId);
    }

    changeSignatureProfile(id: string): void {
        this.selectedSignatureProfile = this.signatureProfiles.find(profile => profile.id === id);
    }

    changeConsentPage(id: string): void {
        this.selectedConentPage = this.consentPages.find(page => page.id === id);
    }
}
