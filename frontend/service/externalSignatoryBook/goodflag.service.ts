import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { catchError, of, tap } from 'rxjs';
import { NotificationService } from '@service/notification/notification.service';
import { TranslateService } from '@ngx-translate/core';
import { UserWorkflow, UserWorkflowInterface } from '@models/user-workflow.model';
import { FunctionsService } from '@service/functions.service';
import { GoodFlagTemplateInterface } from "@models/goodflag.model";
import { AuthService } from "@service/auth.service";

@Injectable({
    providedIn: 'root'
})

export class GoodflagService {

    autocompleteUsersRoute: string = '/rest/autocomplete/goodflag';

    canCreateUser: boolean = false;
    canSynchronizeSignatures: boolean = false;
    canViewWorkflow: boolean = true;
    canCreateTile: boolean = false;
    canAddExternalUser: boolean = true;
    canManageSignaturesPositions: boolean = true;
    canAddDateBlock: boolean = false;
    canAddSteps: boolean = true;
    isMappingWithTechnicalStatus: boolean = false;

    userWorkflow = new UserWorkflow();
    signatureModes: string[] = ['sign'];
    workflowTypes: any[] = [];
    otpConnectors: any[] = [];

    constructor(
        private http: HttpClient,
        private notify: NotificationService,
        private translate: TranslateService,
        private functions: FunctionsService,
        private authService: AuthService
    ) {
        this.canAddExternalUser = this.authService.externalSignatoryBook.optionOtp;
    }

    getWorkflowDetails(): Promise<GoodFlagTemplateInterface[]> {
        return new Promise((resolve) => {
            this.http.get('../rest/goodflag/templates').pipe(
                tap((templates: GoodFlagTemplateInterface[]) => {
                    this.workflowTypes = templates;
                    resolve(templates);
                }),
                catchError((err: any) => {
                    resolve([]);
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        })
    }

    getUserAvatar(): Promise<any> {
        return new Promise((resolve) => {
            this.http.get('assets/goodflag.jpg', { responseType: 'blob' }).pipe(
                tap((response: any) => {
                    const reader = new FileReader();
                    reader.readAsDataURL(response);
                    reader.onloadend = () => {
                        resolve(reader.result as any);
                    };
                }),
                catchError(err => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    getOtpConfig(): Promise<any> {
        return new Promise((resolve) => {
            this.otpConnectors = [
                {
                    id: 1,
                    label: this.translate.instant('lang.otpGoodflag'),
                    type: 'goodflag'
                }
            ];
            resolve(this.otpConnectors);
        });
    }

    loadListModel() {
        return new Promise((resolve) => {
            resolve([]);
        });
    }

    loadWorkflow(resId: number, type: string) {
        return new Promise((resolve) => {
            this.http.get(`../rest/documents/${resId}/goodFlagWorkflow?type=${type}`).pipe(
                tap((data: any) => {
                    resolve({ workflow: data });
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    resolve(null);
                    return of(false);
                })
            ).subscribe();
        });
    }

    getAutocompleteDatas(data: any): Promise<any> {
        return new Promise((resolve) => {
            this.http.get(`..${this.autocompleteUsersRoute}`, {
                params: {
                    'search': data.user.mail,
                    'excludeAlreadyConnected': 'true'
                }
            })
                .pipe(
                    tap((result: GoodflagCorrespondentInterface[]) => {
                        resolve(result);
                    }),
                    catchError((err: any) => {
                        this.notify.handleSoftErrors(err);
                        resolve(null);
                        return of(false);
                    })
                ).subscribe();
        });
    }

    linkAccountToSignatoryBook(): Promise<any> {
        return new Promise((resolve) => {
            resolve([]);
        });
    }

    unlinkSignatoryBookAccount(): Promise<any> {
        return new Promise((resolve) => {
            resolve([]);
        });
    }

    createExternalSignatoryBookAccount() {
        // STAND BY
    }

    checkInfoExternalSignatoryBookAccount(): Promise<any> {
        return new Promise((resolve) => {
            resolve([]);
        });
    }

    setExternalInformation(item: any): Promise<UserWorkflowInterface> {
        const objeToSend: any = {
            ...item,
            id: item.id,
            item_id: item.id,
            idToDisplay: `${item.firstname} ${item.lastname}`,
            role: 'sign',
            isValid: true,
            hasPrivilege: true,
            signatureModes: this.signatureModes,
            availableRoles: this.signatureModes
        };

        objeToSend.externalId = {
            goodflag: item?.externalId?.goodflag
        };

        return objeToSend;
    }

    getRessources(additionalsInfos: any): any[] {
        return additionalsInfos.attachments.map((e: any) => e.res_id);
    }

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    isValidParaph(additionalsInfos: any = null, workflow: any[] = [], resourcesToSign = [], userOtps = []) {
        return (additionalsInfos.attachments.length > 0 && workflow.length > 0) && userOtps.length === 0 && this.workflowTypes.length > 0 && this.signatureModes.length > 0;
    }

    canAttachSummarySheet(visaWorkflow: any[]): boolean {
        if (visaWorkflow.length > 0) {
            // If an external OTP FAST user exists, the summary sheet cannot be attached
            if (visaWorkflow.filter((item: any) => !this.functions.empty(item?.externalInformations)).length > 0 && visaWorkflow.filter((item: any) => item.externalInformations?.type === 'fast').length >= 1) {
                return false;
            }
        }
        return true;
    }

    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    synchronizeSignatures(data: any) {
    }

    downloadProof(goodflagWorkflowId: string): Promise<boolean> {
        return new Promise((resolve) => {
            this.http.get(`../rest/goodflag/${goodflagWorkflowId}/downloadEvidenceCertificate`, { responseType: 'json' }).pipe(
                tap((data: any) => {
                    const downloadLink = document.createElement('a');
                    downloadLink.href = `data:application/pdf;base64,${data.encodedDocument}`;
                    downloadLink.setAttribute('download', data.filename);
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    resolve(false);
                    return of(false);
                })
            ).subscribe();
        });
    }
}

interface GoodflagCorrespondentInterface {
    id: number;
    idToDisplay: string;
    firstname: string;
    lastname: string;
    email: string;
    type: string;
    phoneNumber: string
    country: string;
}
