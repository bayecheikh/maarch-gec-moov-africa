export interface ListDisplayInterface {
    icon: string;
    label: string;
    sample: string;
    value: string;
    cssClasses: string[];
}

export class ListDisplay implements ListDisplayInterface {
    icon: string = '';
    label: string = '';
    sample: string = '';
    value: string = '';
    cssClasses: string[] = [];
}