import { Pipe, PipeTransform } from '@angular/core';
import { LatinisePipe } from 'ngx-pipes';
import { FunctionsService } from '@service/functions.service';

@Pipe({ name: 'highlight' })
export class HighlightPipe implements PipeTransform {

    constructor(
        private latinisePipe: LatinisePipe,
        public functions: FunctionsService
    ) { }

    transform(text: string, args: string = '') {
        let formatedText: string = '';

        if (typeof text === 'string' && typeof args === 'string') {
            const index = this.latinisePipe.transform(text.toLowerCase()).indexOf(this.latinisePipe.transform(args.toLowerCase()));
            if (index >= 0) {
                formatedText = this.escapeHtml(text.substring(0, index)) +
                "<span class='highlightResult'>" +
                this.escapeHtml(text.substring(index, index + args.length)) +
                "</span>" +
                this.escapeHtml(text.substring(index + args.length));
            }
        }
        return !this.functions.empty(formatedText) ? formatedText : text;
    }

    /**
     * Escapes HTML special characters in a string to prevent XSS attacks.
     * @param text The string to escape.
     * @returns The escaped string.
     */
    private escapeHtml(text: string): string {
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
}
