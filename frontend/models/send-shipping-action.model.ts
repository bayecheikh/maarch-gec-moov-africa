export interface ShippingConfigInterface {
    id: number,
    label: string;
    description: string;
    options: {
        shapingOptions: string[];
        sendMode: string;
        senderId?: string;
        senderLabel?: string;
    };
    fee: {
        totalShippingFee: number
    },
    account: {
        id: string;
        password: string;
    };
}

export interface AttachListInterface {
    [resIdMaster: number]: {
        [contactId: number]: AttachListProperties[];
    };
}

export interface AttachListProperties {
    res_id: number;
    res_id_master?: number;
    chrono: string;
    title: string;
    type: string;
    docserver_id?: string;
    integrations: string;
    contactLabel?: string;
    contactId?: string;
}
