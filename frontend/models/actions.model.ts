import { ResourceInformationInterface } from "@models/resource-information.model";
import { AttachmentInterface } from "@models/attachment.model";
import { ShippingDataInterface } from "@models/shipping.model";

export interface ActionHistoryInterface {
    id: number,
    label: string,
}

export interface ActionInterface {
    id: number,
    label: string,
    component: string,
    categories: string[],
    categoryUse: string[],
    enabled?: boolean,
    default: boolean,
    parameters?: any
}

export interface DataToSendActionInterface {
    note: {
        content: string,
        entities: any[]
    },
    resources: any[]
    data: any;
}

export interface ActionsAdminListInterface {
    id: number,
    label_action: string,
    component: string,
    keyword: string,
    history: string,
    id_status: string,
    is_system: string,
    action_page: string,
    parameters: JSON,
    where_clause: string,
    used_in_basketlist: boolean,
    used_in_action_page: boolean,
    default_action_list: boolean,
    statuses: any[],
    redirects: {
        entity_id: string,
        action_id: number,
        keyword: string,
        redirect_mode: string
    }[],
    checked: boolean,
}

export interface BasketGroupListActionInterface {
    id: number,
    type: string,
    actionPage: string,
    defaultActionList: boolean,
}

export interface ActionPagesInterface {
    id: string,
    label: string,
    name: string,
    component: string,
    category: 'application' | 'acknowledgementReceipt' | 'externalSignatoryBook' | 'visa' | 'avis' | 'maileva' | 'alfresco' | 'multigest' | 'registeredMail' | 'recordManagement' | 'diffusionList',
    description: string,
}

export interface ActionAdminInfoInterface {
    id: string,
    label_action: string,
    keyword: string,
    component: string,
    history: string,
    id_status: string,
    is_system: boolean,
    action_page: string,
    actionPageId: string,
    actionPageGroup: string,
    actionCategories?: string[],
    parameters: {
        lockVisaCircuit?: boolean,
        keepDestForRedirection?: boolean,
        keepCopyForRedirection?: boolean,
        keepOtherRoleForRedirection?: boolean,
        filterAbsentUsers?: boolean,
        digitalCertificateByDefault?: boolean,
        inSignatureBook?: boolean;

        fillRequiredFields?: {
            id: string,
            value: string
        }[],
        requiredFields?: {
            id: string,
            value: string
        }[],
        canAddCopies?: boolean,
        mode?: string,
        intermediateStatus?: {
            actionStatus: string,
            mailevaStatus: {
                id: string,
                label: string,
                actionStatus: string,
                disabled: false
            }[],
        },
        finalStatus?: {
            actionStatus: string,
            mailevaStatus: {
                id: string,
                label: string,
                actionStatus: string,
                disabled: false
            }[],
        },
        errorStatus?: {
            actionStatus: string,
            mailevaStatus: {
                id: string,
                label: string,
                actionStatus: string,
                disabled: false
            }[],
        },
        successStatus?: {
            actionStatus: string,
            mailevaStatus: {
                id: string,
                label: string,
                actionStatus: string,
                disabled: false
            }[],
        }
    }
}

export interface VisaCircuitActionDataToSendInterface extends DataToSendActionInterface {
    data: VisaCircuitActionObjectInterface;
}

export interface VisaCircuitActionObjectInterface {
    digitalCertificate: boolean;

    [key: number]: {
        resId: number;
        resIdMaster: number;
        isAttachment: boolean;
        documentId: number;
        cookieSession: string;
        hashSignature: string;
        signatureContentLength: number;
        signatureFieldName: string;
        signature: any[];
        certificate: string;
        tmpUniqueId: string;
        fileContentWithAnnotations: string;
    }[],
}

export interface ContinueVisaCircuitStampsInterface {
    encodedImage: string;
    width: string;
    height: string;
    positionX: string;
    positionY: string;
    type: string;
    page: number;
}

export interface MessageActionInterface {
    id: string;
    data?: any;
}

export class MessageAction implements MessageActionInterface {
    id: string = '';
    data?: any = null;

    constructor() {
    }
}

export interface DatasActionSendInterface {
    title?: string;
    resIds: number[];
    selectedRes?: number[];
    resource: ResourceInformationInterface,
    action: ActionInterface,
    userId: number,
    groupId: number,
    basketId: number,
    indexActionRoute: string,
    processActionRoute: string,
    additionalInfo: {
        showToggle: boolean,
        inLocalStorage: boolean,
        canGoToNextRes: boolean,
        inSignatureBook: boolean
    },
    docsToSign?: AttachmentInterface[],
    shippingData?: ShippingDataInterface,
}


