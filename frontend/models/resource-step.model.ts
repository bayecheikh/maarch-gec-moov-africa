export interface ResourceStepInterface {
    /**
     * User/contact id
     */
    correspondentId: number;

    /**
     * Resource id
     */
    resId: number;

    /**
     * Indicates whether the the main document
     */
    mainDocument: boolean;

    /**
     * The identifier of the user in the external signatory book
     */
    externalId: string | number;

    /**
     * The order of the user in the workflow
     */
    sequence: number;

    /**
     * User role : 'visa', 'vign'
     */
    action: string;

    /**
     * Signature mode
     */
    signatureMode: string;

    /**
     * Signature positions
     */
    signaturePositions?: any[];

    /**
     * Date positions
     */
    datePositions?: any[];

    /**
     * Information related to OTP users
     */
    externalInformations: object;

    /**
     * User step for Goodflag signaturebook
     */
    step?: number;

    /**
     * Indicates whether a signature is required for a certain process or operation.
     */
    isSignatureRequired?: boolean;
}

export class ResourceStep implements ResourceStepInterface {
    correspondentId = null;
    resId = null;
    mainDocument = false;
    externalId = null;
    sequence = null;
    action = '';
    signatureMode = '';
    signaturePositions = [];
    datePositions = [];
    externalInformations = {};
    step: number = 1;
    isSignatureRequired?: boolean = false;

    constructor(json: any = null) {
        if (json) {
            Object.assign(this, json);
        }
    }
}
