export interface GoodFlagTemplateInterface {
    id: string;
    label: string;
    description: string;
    signatureProfileId: string;
    consentPageId: string;
}

export interface GoodFlagConfigurationInterface {
    url: string;
    accessToken: string;
    accessTokenAlreadySet: boolean;
    options: {
        optionOtp: boolean;
        validityPeriod: number;
        invitePeriod: number;
        workflowFinishedStatus: string;
        workflowStoppedStatus: string;
    }
}

export interface ConsentPageInterface {
    id: string;
    name: string;
    created: string;
    updated: string;
    stepType: string;
    signingMode: string;
    authenticateUser: boolean;
    allowOrganization: boolean;
    strictCertificateControl: boolean;
    keystoreTypes: string[];
}

export interface SignatureProfileInterface {
    id: string;
    name: string;
    created: string;
    updated: string;
    documentType: string;
    signatureType: string;
    pdfSignatureImageText: string;
    forceScrollDocument: boolean;
}