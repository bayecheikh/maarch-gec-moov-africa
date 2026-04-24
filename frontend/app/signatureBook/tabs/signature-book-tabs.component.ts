import { Component, Input, OnInit } from '@angular/core';
import { ActionsService } from '@appRoot/actions/actions.service';

import { Attachment, AttachmentInterface } from '@models/attachment.model';
import { FunctionsService } from '@service/functions.service';
import { SignatureBookService } from "@appRoot/signatureBook/signature-book.service";
import { MessageActionInterface } from '@models/actions.model';

@Component({
    selector: 'app-maarch-sb-tabs',
    templateUrl: 'signature-book-tabs.component.html',
    styleUrls: ['signature-book-tabs.component.scss'],
})
export class MaarchSbTabsComponent implements OnInit {
    @Input() documents: Attachment[] = [];
    @Input() position: 'left' | 'right' = 'right';

    selectedId: number = 0;
    loadingDocument: boolean = false;

    constructor(
        public functionsService: FunctionsService,
        private actionsService: ActionsService,
        public signatureBookService: SignatureBookService,
    ) {

        this.actionsService.catchActionWithData().subscribe((event: MessageActionInterface) => {
            if (event.id === 'documentLoaded') {
                this.loadingDocument = false;
            }
        });
    }

    ngOnInit(): void {
        if (this.documents.length > 0) {
            this.selectDocument(this.selectedId, this.documents[0]);
        }
    }

    selectDocument(i: number, attachment: AttachmentInterface): void {
        this.selectedId = i;
        this.loadingDocument = true;

        if (this.position === 'left') {
            this.signatureBookService.toolBarActive = false;
            this.signatureBookService.selectedAttachment.index = i;
            this.signatureBookService.selectedAttachment.attachment = attachment;
        } else if (this.position === 'right') {
            this.signatureBookService.selectedDocToSign.index = i;
            this.signatureBookService.selectedDocToSign.attachment = attachment;
        }

        this.actionsService.emitActionWithData({
            id: 'attachmentSelected',
            data: {
                attachment: attachment,
                position: this.position,
                resIndex: this.selectedId
            },
        });
    }
}
