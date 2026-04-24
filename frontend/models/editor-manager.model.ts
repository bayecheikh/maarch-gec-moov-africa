export interface ResourceFileInterface {
    // file name
    name: string;
    //base64 encoded file
    content: string | ArrayBuffer;
    // file extension (txt, html, pdf, ...)
    format: string;
}


export interface EditorConfigInterface {

    // editor to show
    mode?: 'java' | 'onlyoffice' | 'collaboraOnline' | 'office365sharepoint';

    // ??
    async?: boolean;

    options: EditorOptionsInterface;
}

export interface EditorOptionsInterface {
    //resId
    objectId: number;

    // for attachment or for main document
    objectType: 'resourceModification' | 'attachmentModification' | 'templateModification' | 'templateCreation' | 'encodedResource' | 'templateEncoded';
}

export interface JavaEditorConfigInterface extends EditorConfigInterface{
    options: JavaEditorOptionsInterface;
}

export interface JavaEditorOptionsInterface extends EditorOptionsInterface {
    cookie: string;
    authToken: string;
}

export interface OnlyOfficeConfigInterface extends EditorConfigInterface {
    options: OnlyOfficeOptionsInterface;
}

export interface OnlyOfficeOptionsInterface extends EditorOptionsInterface  {
    docUrl: string;
    dataToMerge?: any;
}

export interface CollaboraOnlineConfigInterface extends EditorConfigInterface {
    options: CollaboraOnlineOptionsInterface;
}

export interface CollaboraOnlineOptionsInterface extends EditorOptionsInterface  {
    // ??
    content?: string;
    objectPath?: string
    dataToMerge?: any;
}

export interface SharepointConfigInterface extends EditorConfigInterface {
    options: SharepointOptionsInterface;
}

export interface SharepointOptionsInterface extends EditorOptionsInterface  {
    // ??
    content?: string;
    objectPath?: string
    dataToMerge?: any;
}


export class EditorConfig implements EditorConfigInterface  {

    mode: 'java' | 'onlyoffice' | 'collaboraOnline' | 'office365sharepoint' = null;
    async: boolean = false;
    options: EditorOptionsInterface = {
        objectId: null,
        objectType: null
    };

    constructor(json: EditorConfigInterface = null) {
        if (json) {
            Object.assign(this, json);
        }
    }
}

export class JavaEditorConfig extends EditorConfig implements JavaEditorConfigInterface {
    mode: 'java' | 'onlyoffice' | 'collaboraOnline' | 'office365sharepoint' = 'java';
    async: boolean = true;

    options: JavaEditorOptionsInterface = {
        objectId: null,
        objectType: null,
        cookie: '',
        authToken: ''
    };

    constructor(json: JavaEditorConfigInterface = null) {
        super(json);
        if (json) {
            Object.assign(this, json);
        }
    }
}

export class OnlyOfficeConfig extends EditorConfig implements OnlyOfficeConfigInterface {
    mode: 'java' | 'onlyoffice' | 'collaboraOnline' | 'office365sharepoint' = 'onlyoffice';
    async: boolean = false;
    options: OnlyOfficeOptionsInterface = {
        objectId: null,
        objectType: null,
        docUrl : '',
        dataToMerge: {}
    };

    constructor(json: OnlyOfficeConfigInterface = null) {
        super(json);
        if (json) {
            Object.assign(this, json);
        }
    }
}

export class CollaboraOnlineConfig extends EditorConfig implements CollaboraOnlineConfigInterface {
    mode: 'java' | 'onlyoffice' | 'collaboraOnline' | 'office365sharepoint' = 'collaboraOnline';
    async: boolean = false;
    options: CollaboraOnlineOptionsInterface = {
        objectId: null,
        objectType: null
    };
    constructor(json: CollaboraOnlineConfigInterface = null) {
        super(json);
        if (json) {
            Object.assign(this, json);
        }
    }
}

export class SharepointConfig extends EditorConfig implements SharepointConfigInterface {
    mode: 'java' | 'onlyoffice' | 'collaboraOnline' | 'office365sharepoint' = 'office365sharepoint';
    async: boolean = true;
    options: SharepointOptionsInterface = {
        objectId: null,
        objectType: null
    };
    constructor(json: SharepointConfigInterface = null) {
        super(json);
        if (json) {
            Object.assign(this, json);
        }
    }
}

