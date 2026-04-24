import { AttachmentInterface } from "@models/attachment.model";

export interface ResourceInformationInterface {
    resId: number;
    barcode: string;
    binding: string;
    checked: boolean;
    chrono: string;
    closing_date: string;
    confidentiality: string;
    destination: number;
    countAttachments: number;
    countNotes: number;
    countSentResources: number;
    creationDate: string;
    display: DisplayItem[];
    docsToSign?: AttachmentInterface[];
    folders: any[];
    hasDocument: boolean;
    integrations: {
        inShipping?: boolean;
        inSignatureBook?: boolean;
    };
    isLocked: boolean;
    mailTracking: boolean;
    priorityColor: string;
    processLimitDate: string;
    retentionFrozen: boolean;
    statusImage: string;
    statusLabel: string;
    subject: string;
    template: string;
    encodedFile: string;
    customFields?: Array<any>;
    registeredMail_type?: string;
    registeredMail_warranty?: string;
    registeredMail_issuingSite?: string;
    registeredMail_letter?: string;
    registeredMail_recipient?: string;
    registeredMail_reference?: string;

}

export interface DisplayItem {
    value: string;
    label: string;
    sample: string;
    cssClasses: string[];
    icon: string;
}