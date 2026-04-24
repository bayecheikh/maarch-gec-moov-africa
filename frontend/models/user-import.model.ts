export interface UserImportInterface {
    id: number;
    user_id: string;
    firstname: string;
    lastname: string;
    mail: string;
    phone: string;
}

export class UserImport implements UserImportInterface {
    id: number = null;
    user_id: string = '';
    firstname: string = '';
    lastname: string = '';
    mail: string = '';
    phone: string = '';

    constructor(json: any = null) {
        if (json) {
            Object.assign(this, json);
        }
    }
}