import { TranslateService } from "@ngx-translate/core";
import { FunctionsService } from "@service/functions.service";
import { NotificationService } from "@service/notification/notification.service";
import { Attachment } from "./attachment.model";
import { SignatureBookConfigInterface } from "./signature-book.model";

export interface MaarchPluginFortifyInterface {
    functions: FunctionsService;
    notification: NotificationService;
    translate: { service: TranslateService, currentLang: string };
    pluginUrl: string;
    additionalInfo: {
        resources: Attachment[];
        sender: string;
        externalUserId: number;
        signatureBookConfig: SignatureBookConfigInterface,
        digitalCertificate: boolean,
        applyTimestamp: boolean
    };
}

export interface WorkflowItemsInterface {
    // External user id
    userId: number;

    // User mode: 'sign' or 'visa'
    mode: string;

    signatureMode: string;

    processDate?: string;

    signaturePositions: any[];
}