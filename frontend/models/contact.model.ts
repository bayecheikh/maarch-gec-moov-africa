import { UntypedFormControl } from "@angular/forms";

export interface AddressContactInterface {
    addressNumber: { confidential: boolean, value: string },
    addressStreet: { confidential: boolean, value: string },
    addressPostcode: { confidential: boolean, value: string },
    addressTown: { confidential: boolean, value: string },
    addressCountry: { confidential: boolean, value: string },
    addressAdditional1: { confidential: boolean, value: string },
    addressAdditional2: { confidential: boolean, value: string },
    sector: { confidential: boolean, value: string },
}

export interface ContactFormInterface {
    id: string;
    unit: string;
    label: string;
    desc?: string;
    type: string;
    control: UntypedFormControl;
    required: boolean;
    display: boolean;
    filling: boolean;
    confidential?: boolean;
    values: { id: string, label: string } []
}