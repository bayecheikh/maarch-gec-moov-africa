export interface DatePositionInterface {
    sequence: number;
    page: number;
    positionX: number;
    positionY: number;
    width: number;
    height: number;
    size: number;
    font: string;
    color: string;
    format: string;
}

export class DatePosition implements DatePositionInterface {
    sequence: number = null;
    page: number = null;
    positionX: number = null;
    positionY: number = null;
    width: number = null;
    height: number = null;
    size: number = null;
    font: string = '';
    color: string = '';
    format: string = '';
}