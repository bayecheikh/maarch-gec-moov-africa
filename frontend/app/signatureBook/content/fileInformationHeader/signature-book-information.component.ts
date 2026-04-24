import { Component, Input, OnInit } from '@angular/core';
import { FunctionsService } from '@service/functions.service';
import { AttachmentInterface } from "@models/attachment.model";
import { LocalStorageService } from "@service/local-storage.service";

@Component({
    selector: 'app-maarch-sb-information',
    templateUrl: 'signature-book-information.component.html',
    styleUrls: ['signature-book-information.component.scss'],
})
export class MaarchSbInformationComponent implements OnInit {

    @Input() documentData: AttachmentInterface;
    @Input() position: 'left' | 'right' = 'right';

    title: string = '';
    label: string = '';
    bannerOpened: boolean = false;

    constructor(
        public functionsService: FunctionsService,
        public localStorage: LocalStorageService
    ) {
    }

    ngOnInit(): void {
        this.bannerOpened = this.getBlockState();
        this.setLabel();
        this.setTitle();
    }

    setLabel(): void {
        this.label = !this.functionsService.empty(this.documentData?.chrono)
            ? `${this.documentData?.chrono}: ${this.documentData?.title}`
            : `${this.documentData?.title}`;
    }

    setTitle(): void {
        this.title = `${this.label} (${this.documentData.typeLabel})`;
    }

    getBlockState(): boolean {
        const state: string = this.localStorage.get(`sb_info_banner_${this.position}_opened`);
        return (state === 'true');
    }

    changeBlockState(state: boolean) {
        this.bannerOpened = state;
        this.localStorage.save(`sb_info_banner_${this.position}_opened`, this.bannerOpened);
    }
}
