export interface ShippingInterface {
    id?: number;
    label: string;
    description: string;
    options: { shapingOptions: string[], sendMode: string, senderId?: string, senderLabel?: string };
    fee: { firstPagePrice: number, nextPagePrice: number, postagePrice: number, ereSendingPrice: number };
    account: { id: string, password: string };
    entities: any[];
    senders: { id: string, label: string }[];
    subscribed?: boolean;
}

export interface ShippingDataInterface {
    id: number;
    sendingId: string;
    creationDate: string;
    sendDate: string;
    recipients: string[][];
    sender: string;
    sendMode?: string;
    recipientId?: string;
}

export interface ShippingHistoryInterface {
    status: string;
    eventType: string;
    eventDate: string;
}

export interface ShippingAttachmentInterface {
    resId: number;
    title: string;
    filesize: number;
}

export interface ShippingStatusesInterface {
    id: string;
    identifier: number;
    label_status: string;
}

export interface ShippingSendersInterface {
    id: string;
    firstname: string;
    lastname: string;
    email: string;
    company: string;
}