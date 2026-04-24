import { Attachment, AttachmentInterface } from "@models/attachment.model";

export interface StampInterface {
    /**
     * base64 image
     */
    base64Image?: string,

    base64Url?: string

    /**
     * stamp width (percentage of page width)
     */
    width: number,
    /**
     * stamp height (percentage of page height)
     */
    height: number,
    /**
     * X position (percentage relative of page width)
     */
    positionX: number,
    /**
     * Y position (percentage relative of page height)
     */
    positionY: number,
    /**
     * page of stamp located
     */
    page: number
    /**
     * sequence of stamp
     */
    sequence?: number;
}

export interface SignatureBookConfigInterface {
    isNewInternalParaph: boolean;
    url: string;
}

export class SignatureBookConfig implements SignatureBookConfigInterface {
    isNewInternalParaph: boolean = false;
    url: string = '';

    constructor(json: any = null) {
        if (json) {
            Object.assign(this, json);
        }
    }
}

export interface SelectedAttachmentInterface {
    index: number;
    attachment: AttachmentInterface;
}

export class SelectedAttachment implements SelectedAttachmentInterface {
    index: number = null;
    attachment: AttachmentInterface = null;

    constructor(json: any = null) {
        if (json) {
            Object.assign(this, json);
        }
    }
}

export interface  SignatureBookContentHeaderInterface {
    typistId: number;
    typistLabel: string;
    creationDate: string;
    format: string;
    version: number;
}

export class SignatureBookContentHeader implements SignatureBookContentHeaderInterface {
    typistId: number = null;
    typistLabel: string = '';
    creationDate: string = '';
    format: string = '';
    version: number = null;

    constructor(json = null) {
        if (json) {
            Object.assign(this, json)
        }
    }
}

/**
 * Formated response for initDocuments() from signature-book.service
 */
export interface SignatureBookDataReturnInterface {
    resourcesToSign: Attachment[],
    resourcesAttached: Attachment[],
    canUpdateResources: boolean;
    canAddAttachments: boolean;
}
