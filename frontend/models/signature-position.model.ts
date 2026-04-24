export interface SignaturePositionInterface {
    sequence: number;
    page: number;
    positionX: number;
    positionY: number;
    mainDocument?: boolean;
    resId?: number;
    isFromTemplate?: boolean;
}

export class SignaturePosition implements SignaturePositionInterface {
    sequence: number = null;
    page: number = null;
    positionX: number = null;
    positionY: number = null;
    mainDocument?: boolean = false;
    resId?: number = null;
    isFromTemplate?: boolean = false;
}