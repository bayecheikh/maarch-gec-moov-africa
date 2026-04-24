import { HttpClient } from '@angular/common/http';
import { Component, EventEmitter, Input, OnInit, Output } from '@angular/core';
import { ActionsService } from '@appRoot/actions/actions.service';
import { UserStampInterface } from '@models/user-stamp.model';
import { HeaderService } from '@service/header.service';
import { NotificationService } from '@service/notification/notification.service';
import { SignatureBookService } from '../signature-book.service';

@Component({
    selector: 'app-maarch-sb-stamps',
    templateUrl: 'signature-book-stamps.component.html',
    styleUrls: ['signature-book-stamps.component.scss'],
})
export class SignatureBookStampsComponent implements OnInit {

    @Input() userId: number;

    @Output() stampsLoaded: EventEmitter<UserStampInterface> = new EventEmitter();

    loading: boolean = true;

    constructor(
        public http: HttpClient,
        public signatureBookService: SignatureBookService,
        public headerService: HeaderService,
        private notificationService: NotificationService,
        private actionsService: ActionsService
    ) {}

    async ngOnInit(): Promise<void> {
        await this.getUserSignatures();
    }

    async getUserSignatures() {
        /*
        * In case of delegation
        * set the delegated user id
        */
        const userId: number = this.headerService.user.id === this.userId ? this.userId : this.headerService.user.id;
        await this.signatureBookService.getUserSignatures(userId).then(() => {
            this.stampsLoaded.emit(this.signatureBookService.userStamps[0] ?? null);
        });
    }

    signWithStamp(stamp: UserStampInterface) {
        this.actionsService.emitActionWithData({
            id: 'selectedStamp',
            data: stamp
        });
    }
}
