import { Component, Input } from '@angular/core';
import { ConsentPageInterface } from "@models/goodflag.model";
import { FunctionsService } from "@service/functions.service";

@Component({
    selector: 'app-consent-page',
    templateUrl: './consent-page.component.html',
    styleUrls: ['./consent-page.component.scss']
})
export class ConsentPageComponent {
    @Input() consentPage: ConsentPageInterface = {
        id: '',
        name: '',
        created: '',
        updated: '',
        stepType: '',
        signingMode: '',
        authenticateUser: false,
        allowOrganization: false,
        strictCertificateControl: false,
        keystoreTypes: []
    };

    constructor(
        public functions: FunctionsService,
    ) {
    }
}
