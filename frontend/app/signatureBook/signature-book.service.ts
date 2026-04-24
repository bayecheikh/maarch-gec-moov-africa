import { HttpClient } from "@angular/common/http";
import { Injectable } from "@angular/core";
import { Attachment } from "@models/attachment.model";
import { ResourcesList } from "@models/resources-list.model";
import { FiltersListService } from "@service/filtersList.service";
import { HeaderService } from "@service/header.service";
import { NotificationService } from "@service/notification/notification.service";
import { catchError, filter, map, of, tap } from "rxjs";
import { mapAttachment } from "./signature-book.utils";
import { SelectedAttachment, SignatureBookConfig, SignatureBookConfigInterface } from "@models/signature-book.model";
import { TranslateService } from "@ngx-translate/core";
import { BasketGroupListActionInterface, VisaCircuitActionObjectInterface } from "@models/actions.model";
import { FunctionsService } from "@service/functions.service";
import { UserWorkflowInterface } from "@models/user-workflow.model";
import { UserStampInterface } from "@models/user-stamp.model";
import { Router } from '@angular/router';

@Injectable({
    providedIn: 'root'
})
export class SignatureBookService {
    canUpdateResources: boolean = false;
    canAddAttachments: boolean = false;
    toolBarActive: boolean = false;

    resourcesListIds: number[] = [];

    docsToSign: Attachment[] = [];
    docsToSignClone: Attachment[] = [];

    resourcesAttached: Attachment[] = [];

    basketLabel: string = '';

    config: SignatureBookConfig = new SignatureBookConfig();

    isCurrentResourceSelected: boolean = false;

    selectedAttachment: SelectedAttachment = new SelectedAttachment();

    selectedDocToSign: SelectedAttachment = new SelectedAttachment();

    selectedResources: Attachment[] = [];

    selectedMailsCount: number = 0;

    basketGroupActions: BasketGroupListActionInterface[] = [];

    currentWorkflowRole: string = '';

    docsToSignWithStamps: Attachment[] = [];

    currentUserIndex: number = null;

    userStamps: UserStampInterface[] = [];

    attachmentsTypes: any[] = [];

    readonly VALID_ROUTES = {
        BASKET_LIST: 'basketList',
        PROCESS_USERS: '/process/users'
    } as const;

    timestampConfig: { enabled: boolean, autoApply: boolean, using: string } = {
        enabled: false,
        autoApply: false,
        using: ''
    };

    mpAPiPlugin: { version: string, time: string } = null;

    pluginsVersionsWarning: string = '';

    canCheckInconsistentPlugin: boolean = true;

    constructor(
        public translate: TranslateService,
        private http: HttpClient,
        private notifications: NotificationService,
        private filtersListService: FiltersListService,
        private headerService: HeaderService,
        private functions: FunctionsService,
        private router: Router,
    ) {
    }

    resetSelection(): void {
        this.isCurrentResourceSelected = false;
        this.selectedAttachment = new SelectedAttachment();
        this.selectedDocToSign = new SelectedAttachment();
        this.selectedResources = [];
        this.userStamps = [];
        this.attachmentsTypes = [];
        this.selectedMailsCount = 0;
    }

    getInternalSignatureBookConfig(): Promise<SignatureBookConfigInterface | null> {
        return new Promise((resolve) => {
            this.http.get('../rest/signatureBook/config').pipe(
                tap((config: SignatureBookConfigInterface) => {
                    this.config = new SignatureBookConfig(config);
                    resolve(config);
                }),
                catchError((err: any) => {
                    this.notifications.handleSoftErrors(err);
                    resolve(null);
                    return of(false);
                })
            ).subscribe();
        })
    }

    initDocuments(userId: number, groupId: number, basketId: number, resId: number): Promise<{
        resourcesToSign: Attachment[],
        resourcesAttached: Attachment[]
    } | null> {
        return new Promise((resolve) => {
            this.http.get(`../rest/signatureBook/users/${userId}/groups/${groupId}/baskets/${basketId}/resources/${resId}`).pipe(
                map((data: any) => {
                    // Mapping resources to sign
                    const resourcesToSign: Attachment[] = data?.resourcesToSign?.map((resource: any) => mapAttachment(resource)) ?? [];

                    // Mapping resources attached as annex
                    const resourcesAttached: Attachment[] = data?.resourcesAttached?.map((attachment: any) => mapAttachment(attachment)) ?? [];

                    // Mapping cloned resources to set stamps
                    this.docsToSignClone = JSON.parse(JSON.stringify(this.docsToSignClone.concat(resourcesToSign)));

                    this.currentWorkflowRole = data.currentWorkflowRole;

                    const otherData = data;
                    delete otherData.resourcesAttached;
                    delete otherData.resourcesToSign;
                    delete otherData.currentWorkflowRole;

                    return { resourcesToSign: resourcesToSign, resourcesAttached: resourcesAttached, ...otherData };
                }),
                tap((data: { resourcesToSign: Attachment[], resourcesAttached: Attachment[] }) => {
                    resolve(data);
                }),
                catchError((err: any) => {
                    this.notifications.handleErrors(err);
                    resolve(null);
                    return of(false);
                })
            ).subscribe();
        });
    }

    getResourcesBasket(userId: number, groupId: number, basketId: number, limit: number, page: number): Promise<ResourcesList[] | []> {
        return new Promise((resolve) => {
            const offset = page * limit;
            const filters: string = this.filtersListService.getUrlFilters();

            this.http.get(`../rest/resourcesList/users/${userId}/groups/${groupId}/baskets/${basketId}?limit=${limit}&offset=${offset}${filters}`).pipe(
                map((result: any) => {
                    this.resourcesListIds = result.allResources;
                    this.basketLabel = result.basketLabel;

                    if (result.defaultAction.data?.actions?.length > 0) {
                        this.basketGroupActions = JSON.parse(JSON.stringify(result.defaultAction.data.actions));
                    }

                    const resourcesList: ResourcesList[] = result.resources.map((resource: any) => new ResourcesList({
                        resId: resource.resId,
                        subject: resource.subject,
                        chrono: resource.chrono,
                        statusImage: resource.statusImage,
                        statusLabel: resource.statusLabel,
                        priorityColor: resource.priorityColor,
                        mailTracking: resource.mailTracking,
                        creationDate: resource.creationDate,
                        processLimitDate: resource.processLimitDate,
                        isLocked: resource.isLocked,
                        locker: resource.locker
                    }));
                    return resourcesList;
                }),
                tap((data: any) => {
                    resolve(data);
                }),
                catchError((err: any) => {
                    this.notifications.handleSoftErrors(err);
                    resolve([]);
                    return of(false);
                })
            ).subscribe();
        });
    }

    toggleMailTracking(resource: ResourcesList) {
        if (!resource.mailTracking) {
            this.followResources(resource);
        } else {
            this.unFollowResources(resource);
        }
    }

    followResources(resource: ResourcesList): void {
        this.http.post('../rest/resources/follow', { resources: [resource.resId] }).pipe(
            tap(() => {
                this.headerService.nbResourcesFollowed++;
                resource.mailTracking = !resource.mailTracking;
            }),
            catchError((err: any) => {
                this.notifications.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    unFollowResources(resource: ResourcesList): void {
        this.http.delete('../rest/resources/unfollow', { body: { resources: [resource.resId] } }).pipe(
            tap(() => {
                this.headerService.nbResourcesFollowed--;
                resource.mailTracking = !resource.mailTracking;
            }),
            catchError((err: any) => {
                this.notifications.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    downloadProof(data: { resId: number, chrono: string }, isAttachment: boolean): Promise<boolean> {
        const type: string = isAttachment ? 'attachments' : 'resources';
        return new Promise((resolve) => {
            this.http.get(`../rest/${type}/${data.resId}/proofSignature`, { responseType: 'blob' as 'json' })
                .pipe(
                    tap((result: any) => {
                        let chronoOrResId: string = data.resId.toString();
                        if (!this.functions.empty(data.chrono)) {
                            chronoOrResId = data.chrono.replace(/\//g, '_');
                        }
                        const filename = 'proof_' + chronoOrResId + '.' + result.type.replace('application/', '');
                        const downloadLink = document.createElement('a');
                        downloadLink.href = window.URL.createObjectURL(result);
                        downloadLink.setAttribute('download', filename);
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        resolve(true);
                    }),
                    catchError((err: any) => {
                        if (err.status === 400) {
                            this.notifications.handleErrors(this.translate.instant('lang.externalIdNotFoundProblemProof'));
                        } else {
                            this.notifications.handleSoftErrors(err);
                        }
                        resolve(false);
                        return of(false);
                    })
                ).subscribe();
        });
    }

    async toggleSelection(checked: boolean, userId: number, groupId: number, basketId: number, resId: number): Promise<boolean> {
        if (checked) {
            const res: Attachment[] = (await this.initDocuments(userId, groupId, basketId, resId)).resourcesToSign;
            this.selectedResources = this.selectedResources.concat(res);
            if (res.length === 0) {
                return false;
            }
        } else {
            this.selectedResources = this.selectedResources.filter((doc: Attachment) => doc.resIdMaster !== resId);
        }
        this.selectedMailsCount = [...new Set(this.selectedResources.map((attach: Attachment) => attach.resIdMaster))].length;
        return true;
    }

    isSelectedResource(resId: number): boolean {
        return this.selectedResources.find((doc: Attachment) => doc.resIdMaster === resId) !== undefined;
    }

    getAllDocsToSign(): Attachment[] {
        this.docsToSign.forEach((resource: Attachment) => {
            const findResource: Attachment = this.selectedResources.find((doc: Attachment) => doc.resId === resource.resId);
            if (findResource === undefined) {
                this.selectedResources.push(resource);
            } else {
                const index: number = this.selectedResources.indexOf(findResource);
                this.selectedResources[index] = resource;
            }
        });

        this.selectedResources.forEach((resource: Attachment) => {
            if (this.docsToSignWithStamps.find((doc: Attachment) => doc.resId == resource.resId) !== undefined) {
                resource.stamps = this.docsToSignWithStamps.find((doc: Attachment) => doc.resId == resource.resId)?.stamps ?? [];
            }
        })

        // Filter the selectedResources array to remove duplicate entries based on resId
        this.selectedResources = this.selectedResources.filter((resource: Attachment, index: number, self: Attachment[]) =>
            // Keep the current resource only if it is the first occurrence of this resId in the array
            index === self.findIndex((t) => t.resId === resource.resId)
        );


        return this.selectedResources;
    }

    getCurrentUserIndex(resId: number): void {
        this.http.get(`../rest/resources/${resId}/visaCircuit`).pipe(
            map((data: { circuit: UserWorkflowInterface[] }) => data.circuit),
            tap((workflow: UserWorkflowInterface[]) => {
                const currentUser: UserWorkflowInterface[] = workflow.filter((user: UserWorkflowInterface) => this.functions.empty(user.process_date));
                this.currentUserIndex = currentUser[0] !== undefined ? workflow.indexOf(currentUser[0]) : null;
            }),
            catchError((err: any) => {
                this.notifications.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    getSignatureContent(contentUrl: string): Promise<string> {
        return new Promise((resolve) => {
            this.http
                .get(contentUrl, { responseType: 'blob' })
                .pipe(
                    tap(async (res: Blob) => {
                        resolve(await this.functions.blobToBase64(res));
                    }),
                    catchError((err: any) => {
                        this.notifications.handleSoftErrors(err.error.errors);
                        resolve('');
                        return of(false);
                    })
                )
                .subscribe();
        });
    }

    getUserSignatures(userId: number): Promise<UserStampInterface[]> {
        return new Promise<UserStampInterface[]>((resolve) => {
            this.http.get<UserStampInterface[]>(`../rest/users/${userId}/visaSignatures`).pipe(
                map((signatures: any) => {
                    const stamps: UserStampInterface[] = signatures.map((sign: any) => {
                        return {
                            id: sign.id,
                            userId: sign.user_serial_id,
                            title: sign.signature_label,
                            contentUrl: `../rest/users/${userId}/signatures/${sign.id}/content`
                        }
                    });
                    return stamps;
                }),
                tap((stamps: UserStampInterface[]) => {
                    this.userStamps = stamps
                    resolve(this.userStamps);
                }),
                catchError((err: any) => {
                    this.notifications.handleSoftErrors(err);
                    resolve([]);
                    return of(false);
                })
            ).subscribe();
        });
    }

    async createNewVersion(resource: Attachment, encodedFile: string, format: string): Promise<number> {
        const attachmentData = await this.getAttachment(resource.resId);
        if (!this.functions.empty(attachmentData)) {
            return new Promise((resolve) => {
                attachmentData['originId'] = attachmentData['originId'] !== null ? attachmentData['originId'] : attachmentData['resId'];
                attachmentData['encodedFile'] = encodedFile
                attachmentData['relation'] = attachmentData['relation'] + 1;
                attachmentData['format'] = format;
                delete attachmentData['resId'];
                this.http.post('../rest/attachments?withTemplate=true', attachmentData).pipe(
                    tap((data: { id: number }) => {
                        resolve(data.id);
                    }),
                    catchError((err: any) => {
                        this.notifications.handleSoftErrors(err);
                        resolve(resource.resId);
                        return of(false);
                    })
                ).subscribe();
            });
        }
    }

    loadAttachmentTypes(): Promise<boolean> {
        return new Promise((resolve) => {
            this.http.get('../rest/attachmentsTypes').pipe(
                tap((data: any) => {
                    Object.keys(data.attachmentsTypes).forEach(templateType => {
                        this.attachmentsTypes.push({
                            ...data.attachmentsTypes[templateType],
                            id: templateType
                        });
                    });
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notifications.handleSoftErrors(err);
                    resolve(false);
                    return of(false);
                })
            ).subscribe();
        });
    }

    getAttachment(resId: number): Promise<any> {
        return new Promise((resolve) => {
            this.http.get(`../rest/attachments/${resId}`).pipe(
                tap((data) => {
                    resolve(data);
                }),
                catchError((err: any) => {
                    this.notifications.handleSoftErrors(err);
                    resolve(null);
                    return of(false);
                })
            ).subscribe();
        })
    }

    /**
     * Formats the input data for sending in a visa circuit action.
     *
     * @param {Attachment[]} data - An array of items to be formatted. Each item should represent a document or attachment.
     * @returns {Promise<VisaCircuitActionObjectInterface>} A promise that resolves to an object where:
     *   - Keys are the main document resource IDs (resIdMaster or resId)
     *   - Values are arrays of formatted items associated with that main document
     *
     * @description
     * This method processes the input data array and organizes it by main document ID.
     * It also handles the conversion of file content with annotations to base64 format.
     *
     * The resulting structure is suitable for visa circuit actions, where multiple
     * items (like attachments) may be associated with a single main document.
     */
    async formatDataToSend(data: any[]): Promise<VisaCircuitActionObjectInterface> {

        const formatedData: VisaCircuitActionObjectInterface = {} as VisaCircuitActionObjectInterface;

        for (const item of data) {
            const mainDocResId: number = item.resIdMaster ?? item.resId;
            if (this.functions.empty(formatedData[mainDocResId])) {
                formatedData[mainDocResId] = [];
            }

            if (!this.functions.empty(item?.fileContentWithAnnotations)) {
                const base64: string = await this.functions.blobToBase64(item.fileContentWithAnnotations)
                item.fileContentWithAnnotations = base64.split(',')[1];
            }

            if (this.functions.empty(item?.isAnnotated)) {
                if (this.functions.empty(item?.fileContentWithAnnotations) || item.hasDigitalSignature) {
                    item.isAnnotated = false;
                } else {
                    item.isAnnotated = this.docsToSign.find((doc: Attachment) => doc.resId === item.resId)?.isAnnotated;
                }
            }

            formatedData[mainDocResId].push(item);
        }

        return formatedData;
    }

    getDocsToSign(): Attachment[] {
        return this.selectedResources.length === 0 ? this.docsToSign : this.getAllDocsToSign();
    }

    loadAttachments(userId: number, groupId: number, basketId: number, resId: number): Promise<{
        resourcesToSign: Attachment[],
        resourcesAttached: Attachment[]
    }> {
        return new Promise((resolve) => {
            this.http.get(`../rest/signatureBook/users/${userId}/groups/${groupId}/baskets/${basketId}/resources/${resId}`).pipe(
                tap((data: { resourcesToSign: Attachment[], resourcesAttached: Attachment[] }) => {
                    resolve(data);
                }),
                catchError((err: any) => {
                    this.notifications.handleSoftErrors(err);
                    resolve(null);
                    return of(false);
                })
            ).subscribe();

        })
    }

    /**
     * Deletes a resource from either `resourcesAttached` or `resourcesToSign` if it exists.
     * @param resId - The ID of the resource to be deleted.
     */
    deleteResource(resId: number): boolean {
        this.removeFromArray(this.resourcesAttached, resId);
        return this.removeFromArray(this.docsToSign, resId);
    }

    /**
     * Checks if the current route matches one of the defined valid routes.
     *
     * @return {boolean} True if the current route matches a valid route, otherwise false.
     */
    isValidRoute(): boolean {
        return this.router.url.includes(this.VALID_ROUTES.BASKET_LIST) ||
            this.router.url.includes(this.VALID_ROUTES.PROCESS_USERS);
    }

    /**
     * Retrieves the configuration for the timestamp functionality.
     *
     * The method fetches the settings related to timestamping, including whether it is enabled,
     * whether it is applied automatically, and the method used for timestamping.
     *
     * @return {Promise<{ enabled: boolean, autoApply: boolean, using: string }>} A promise that resolves to an object containing timestamp settings.
     */
    getTimestampConfig(): Promise<{ enabled: boolean, autoApply: boolean, using: string }> {
        return new Promise((resolve) => {
            this.http.get(`${this.config.url}/rest/signatureModes`).pipe(
                tap((data: any) => {
                    const timestamp: {
                        enabled: boolean,
                        autoApply: boolean,
                        using: string
                    } = data.find((mode: {
                        id: string,
                        timestamp: { enabled: boolean, autoApply: boolean, using: string }
                    }) => mode.id === 'rgs_2stars_timestamped')?.timestamp;
                    if (!this.functions.empty(timestamp)) {
                        this.timestampConfig = timestamp;
                    }
                    resolve(this.timestampConfig);
                }),
                catchError((err: any) => {
                    this.notifications.handleSoftErrors(err);
                    resolve(this.timestampConfig);
                    return of(false);
                })
            ).subscribe();
        })
    }

    /**
     * Fetches the Maarch Parapheur API information, including its version and timestamp.
     *
     * @return {Promise<{ version: string, time: string }>} A promise that resolves to an object containing the API version and time. If an error occurs, resolves to null.
     */
    getMaarchParapheurApiInfo(): Promise<{ version: string, time: string }> {
        return new Promise((resolve) => {
            this.http.get('../rest/signatureBook/version').pipe(
                filter((res: any) => res),
                tap((res: any) => {
                    this.mpAPiPlugin = res;
                    resolve(res);
                }),
                catchError(() => {
                    resolve(null);
                    return of(false);
                })
            ).subscribe();
        })
    }

    /**
     * Checks the consistency of plugin versions against a specified Maarch Courrier version.
     *
     * @param {string} mcVersion - The Maarch Courrier version to compare against, formatted as "major.minor".
     * @param {Object} pluginVersions - An object containing versions of various plugins.
     * @param {string} [pluginVersions.fortify] - The version of the Fortify plugin, formatted as "major.minor".
     * @param {string} [pluginVersions.pdftron] - The version of the PDFTron plugin, formatted as "major.minor".
     * @param {string} [pluginVersions.mpApi] - The version of the MP API plugin, formatted as "major.minor".
     * @return {Object} An object containing the consistency result.
     * @return {boolean} return.isConsistent - Indicates whether all plugin versions are consistent with the Maarch Courrier version.
     * @return {string} return.maarchVersion - The Maarch Courrier version provided for comparison.
     * @return {Object} return.inconsistentPlugins - A record of plugins with inconsistent versions.
     * @return {Object.<string, { version: string, reason: string }>} return.inconsistentPlugins - The key is the plugin name, and the value contains the plugin's version and the reason for inconsistency.
     * @throws {Error} If the Maarch Courrier version is not provided or improperly formatted.
     */
    checkVersionsConsistency(mcVersion: string, pluginVersions: {
        fortify?: string,
        pdftron?: string,
        mpApi?: string
    }): {
            isConsistent: boolean,
            maarchVersion: string,
            inconsistentPlugins: Record<string, { version: string, reason: string }>
        } {

        if (!mcVersion || typeof mcVersion !== 'string') {
            throw new Error('Maarch Courrier version required');
        }

        // Extraction des versions majeure et mineure de Maarch Courrier
        const mcParts = mcVersion.split('.');
        if (mcParts.length < 2) {
            throw new Error('Maarch Courrier version invalid');
        }

        const mcMajor: string = mcParts[0];
        const mcMinor: string = mcParts[1];

        // Objet pour stocker les plugins avec des versions incohérentes
        const inconsistentPlugins: Record<string, { version: string, reason: string }> = {};

        // Vérification pour chaque plugin
        Object.entries(pluginVersions).forEach(([pluginName, version]) => {
            if (!version || version === this.translate.instant('lang.undefined')) return;

            const pluginParts = version.split('.');

            if (pluginParts.length < 2) return;

            const pluginMajor = pluginParts[0];
            const pluginMinor = pluginParts[1];

            // Vérifier si les versions majeure et mineure correspondent
            if (pluginMajor !== mcMajor || pluginMinor !== mcMinor) {
                inconsistentPlugins[pluginName] = {
                    version,
                    reason: `${this.translate.instant('lang.inconsistentPluginMsg')} ${mcVersion}`
                };
            }
        });

        const result: {
            isConsistent: boolean,
            maarchVersion: string,
            inconsistentPlugins: Record<string, { version: string, reason: string }>
        } = {
            isConsistent: Object.keys(inconsistentPlugins).length === 0,
            maarchVersion: mcVersion,
            inconsistentPlugins
        };

        this.displayVersionWarning(result);

        return result;
    }

    /**
     * Displays a version warning message if plugin versions are not consistent.
     *
     * @param {Object} versionCheckResult The result of the version check.
     * @param {boolean} versionCheckResult.isConsistent Indicates whether all plugins are consistent with the expected version.
     * @param {string} versionCheckResult.maarchVersion The version of the base application (Maarch).
     * @param {Object} versionCheckResult.inconsistentPlugins A record of plugins with inconsistent versions. Each entry contains the plugin name as the key and an object with the plugin version and the reason for inconsistency.
     * @return {void} Does not return a value. Updates the `pluginsVersionsWarning` property with the generated warning message or clears it if the plugin versions are consistent.
     */
    displayVersionWarning(versionCheckResult: {
        isConsistent: boolean;
        maarchVersion: string;
        inconsistentPlugins: Record<string, { version: string; reason: string }>;
    }): void {
        this.pluginsVersionsWarning = '';

        if (versionCheckResult.isConsistent) {
            this.pluginsVersionsWarning = '';
            sessionStorage.removeItem('ignorePluginsWarning');
            return;
        }

        let message = `<b>${this.translate.instant('lang.inconsistentPluginMsg')} ${versionCheckResult.maarchVersion} :</b><br><br>`;

        Object.entries(versionCheckResult.inconsistentPlugins).forEach(([pluginName, info]) => {
            const readableName = {
                'fortify': 'Fortify',
                'pdftron': 'PDFTRON (Apryse)',
                'mpApi': 'MP API'
            }[pluginName] || pluginName;

            message += `- ${readableName} :  ${info.version}<br>`;
        });

        message += `<br>${this.translate.instant('lang.inconsistentPluginsWarning')}`;

        this.pluginsVersionsWarning = message;
    }

    /**
     * Removes an attachment from the given array if it exists.
     * @param array - The array from which the resource should be removed.
     * @param resId - The ID of the resource to be deleted.
     * @returns {boolean} - `true` if the resource was removed, otherwise `false`.
     */
    private removeFromArray(array: Attachment[], resId: number): boolean {
        const index = array.findIndex((attachment: Attachment) => attachment.resId === resId);
        if (index !== -1) {
            array.splice(index, 1);
            return true;
        }
        return false;
    }
}
