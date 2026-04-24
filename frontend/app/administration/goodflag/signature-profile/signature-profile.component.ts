import { Component, Input } from '@angular/core';
import { SignatureProfileInterface } from "@models/goodflag.model";
import { FunctionsService } from "@service/functions.service";

@Component({
    selector: 'app-signature-profile',
    templateUrl: './signature-profile.component.html',
    styleUrls: ['./signature-profile.component.scss']
})
export class SignatureProfileComponent {
    @Input() signatureProfile: SignatureProfileInterface = {
        id: '',
        name: '',
        created: '',
        updated: '',
        documentType: '',
        signatureType: '',
        pdfSignatureImageText: '',
        forceScrollDocument: false
    };

    constructor(
        public functions: FunctionsService
    ) {
    }
}
