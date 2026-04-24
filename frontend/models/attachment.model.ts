import { SignatureBookContentHeaderInterface, StampInterface } from "@models/signature-book.model";
import { WorkflowItemsInterface } from "./maarch-plugin-fortify-model";
import { SignaturePositionInterface } from "./signature-position.model";
import { UntypedFormControl } from "@angular/forms";

export interface AttachmentInterface {
    /**
     * identifier for the attachment
     */
    resId: number;

    /**
     * identifier for the master resource (main document)
     */
    resIdMaster: number;

    /**
     * identifier for the signed version of the attachment.
     */
    signedResId: number;

    /**
     * chrono for the attachment
     */
    chrono: string;

    /**
     * Title or name of the attachment
     */
    title: string;

    /**
     * Type of the attachment
     */
    type: string;

    /**
     * Human-readable label for the attachment type
     */
    typeLabel: string;

    /**
     * Boolean indicating whether the attachment can be converted
     */
    canConvert: boolean;

    /**
     * Boolean indicating whether the attachment can be deleted
     */
    canDelete: boolean;

    /**
     * Boolean indicating whether the attachment can be updated
     */
    canUpdate: boolean;

    /**
     * Boolean indicating whether the attachment is already signed with certificate
     */
    hasDigitalSignature: boolean;

    /**
     * Urn of document content
     */
    resourceUrn: string;

    stamps?: StampInterface[];

    isAttachment: boolean;

    /**
     * External resId
     */
    externalDocumentId: number | null;
    fileInformation: SignatureBookContentHeaderInterface

    /**
     * Visa workflow
     */
    visaWorkflow: WorkflowItemsInterface[];

    signaturePositions?: SignaturePositionInterface[];

    /**
     * Document annotations
     */
    annotations?: Blob;

    /**
     * Versions of document
     */
    versions?: DocumentVersionsInterface[];

    /**
     * If the document is annotated
     */
    isAnnotated: boolean;

    isConverted?: boolean;
}

export class Attachment implements AttachmentInterface {

    resId: number = null;
    resIdMaster: number = null;
    signedResId: number = null;
    chrono: string = null;
    title: string = '';
    type: string = '';
    typeLabel: string = null;
    canConvert: boolean = false;
    canDelete: boolean = false;
    canUpdate: boolean = false;
    hasDigitalSignature: boolean = false;
    resourceUrn: string = '';
    stamps?: StampInterface[] = [];
    isAttachment: boolean = false;
    externalDocumentId: number = null;
    fileInformation: SignatureBookContentHeaderInterface = null;
    visaWorkflow: WorkflowItemsInterface[] = [];
    signaturePositions?: SignaturePositionInterface[];
    fileContentWithAnnotations?: Blob = null;
    versions?: DocumentVersionsInterface[] = [];
    isAnnotated: boolean = false;
    isConverted?: boolean = false;

    constructor(json: any = null) {
        if (json) {
            Object.assign(this, json);
            if (this.resId) {
                const type = json.isAttachment ? 'attachments' : 'resources';
                this.resourceUrn = `rest/${type}/${json.resId}/content`;
            }
        }
    }
}

export interface DocumentVersionsInterface {
    resId: number;
    relation: number;
}

export interface AttachmentTypeInterface {
    typeId: UntypedFormControl,
    label: UntypedFormControl,
    visible: UntypedFormControl,
    chrono: UntypedFormControl,
    emailLink: UntypedFormControl,
    signable: UntypedFormControl,
    inSignatureBook: UntypedFormControl,
    icon: UntypedFormControl,
    versionEnabled: UntypedFormControl,
    newVersionDefault: UntypedFormControl,
    signedByDefault: UntypedFormControl
}
