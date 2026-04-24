import { UntypedFormControl } from "@angular/forms";

/**
 * Interface representing the structure for watermark configuration settings.
 */
export interface WatermarkInterface {
    enabled: UntypedFormControl;
    text: UntypedFormControl;
    posX: UntypedFormControl;
    posY: UntypedFormControl;
    angle: UntypedFormControl;
    opacity: UntypedFormControl;
    font: UntypedFormControl;
    size: UntypedFormControl;
    color: UntypedFormControl;
    dateFormat?: UntypedFormControl;
}

/**
 * Configuration interface for defining watermark properties in a visual or text-based application.
 *
 * @interface WatermarkConfig
 * @property {boolean} enabled - Indicates whether the watermark feature is enabled.
 * @property {string} text - The text content to be displayed as the watermark.
 * @property {number} posX - The x-coordinate of the watermark's position, relative to the canvas or container.
 * @property {number} posY - The y-coordinate of the watermark's position, relative to the canvas or container.
 * @property {number} angle - The rotation angle of the watermark in degrees.
 * @property {number} opacity - The transparency level of the watermark, typically between 0 (completely transparent) and 1 (completely opaque).
 * @property {string} font - The font type or style to be used for the watermark text.
 * @property {number} size - The size of the font for the watermark text.
 * @property {string | number[]} color - The color of the watermark text, which can be a string representing a color name or hexadecimal value, or an array of numeric values for RGB or RGBA representation.
 * @property {string} [dateFormat] - An optional property specifying the date format to be used in the watermark, if applicable.
 */
export interface WatermarkConfig {
    enabled: boolean;
    text: string;
    posX: number;
    posY: number;
    angle: number;
    opacity: number;
    font: string;
    size: number;
    color: string | number[];
    dateFormat?: string;
}