import { Component, Inject, OnInit } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';
import { HttpClient } from '@angular/common/http';
import { MatLegacyDialog as MatDialog, MatLegacyDialogRef as MatDialogRef, MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA } from '@angular/material/legacy-dialog';
import { catchError, filter, tap } from 'rxjs/operators';
import { of } from 'rxjs';
import { NotificationService } from '@service/notification/notification.service';
import { DateOptionModalComponent } from './dateOption/date-option-modal.component';
import { FunctionsService } from '@service/functions.service';
import { ExternalSignatoryBookManagerService } from '@service/externalSignatoryBook/external-signatory-book-manager.service';
import { UserWorkflowInterface } from '@models/user-workflow.model';
import { SignaturePositionInterface } from '@models/signature-position.model';
import { DatePositionInterface } from '@models/date-position.model';
@Component({
    templateUrl: 'signature-position.component.html',
    styleUrls: ['signature-position.component.scss'],
    providers: [ExternalSignatoryBookManagerService]
})
export class SignaturePositionComponent implements OnInit {

    loading: boolean = true;

    pages: number[] = [];

    currentUser: number = 0;
    currentPage: number = null;

    currentSignature: { positionX: number, positionY: number } = {
        positionX: 0,
        positionY: 0
    };

    workingAreaWidth: number = 0;
    workingAreaHeight: number = 0;

    formatList: string[] = [
        'dd/MM/y',
        'dd-MM-y',
        'dd.MM.y',
        'd MMM y',
        'd MMMM y',
    ];

    datefonts: string[] = [
        'Arial',
        'Verdana',
        'Helvetica',
        'Tahoma',
        'Times New Roman',
        'Courier New',
    ];

    size = {
        'Arial': 15,
        'Verdana': 13,
        'Helvetica': 13,
        'Tahoma': 13,
        'Times New Roman': 15,
        'Courier New': 13
    };

    signList: SignaturePositionInterface[] = [];
    dateList: DatePositionInterface[] = [];

    imgContent: any = null;

    today: Date = new Date();
    localDate = this.translate.instant('lang.langISO');
    resizing: boolean = false;

    constructor(
        @Inject(MAT_DIALOG_DATA) public data: any,
        public translate: TranslateService,
        public http: HttpClient,
        public dialog: MatDialog,
        public dialogRef: MatDialogRef<SignaturePositionComponent>,
        public externalSignatoryBookManagerService: ExternalSignatoryBookManagerService,
        private notify: NotificationService,
        public functions: FunctionsService,
    ) { }

    async ngOnInit(): Promise<void> {
        this.currentPage = 1;
        await this.getPageAttachment().then(() => {
            setTimeout(() => {
                this.getAllUnits();
                if (this.data?.template && this.signList.length === 0) {
                    this.addSignUser();
                }
                this.currentUser = this.data.workflow.indexOf(this.data.workflow[0]);
            }, 100);
        });
    }

    getAllUnits(): void {
        if (this.data?.template) {
            this.data.workflow = [];
            this.data.signaturePositions.forEach((signature: SignaturePositionInterface) => {
                this.addSignUser(signature.sequence, signature.positionX, signature.positionY, signature.page);
            });
        } else {
            this.data.workflow.forEach((user: UserWorkflowInterface, index: number) => {
                if (user.signaturePositions?.length > 0) {
                    this.signList = this.signList.concat(user.signaturePositions.filter((pos: any) => pos.resId === this.data.resource.resId && pos.mainDocument === this.data.resource.mainDocument).map((pos: any) => ({
                        ...pos,
                        sequence : user.sequence
                    })));
                }
                if (user.datePositions?.length > 0) {
                    this.dateList = this.dateList.concat(user.datePositions.filter((pos: any) => pos.resId === this.data.resource.resId && pos.mainDocument === this.data.resource.mainDocument).map((pos: any) => ({
                        ...pos,
                        sequence : index
                    })));
                }
            });
        }
    }

    onSubmit(): void {
        this.dialogRef.close(this.formatData());
    }

    async getPageAttachment(): Promise<void> {
        if (this.data?.template) {
            const template: { id: number, base64FileContent: { changingThisBreaksApplicationSecurity: string } } =
            {
                id: this.data.template.id,
                base64FileContent: this.data.template.base64FileContent
            };

            const url = `../rest/content/thumbnail/${this.currentPage}`;
            const base64Data: string = template.base64FileContent.changingThisBreaksApplicationSecurity;
            const base64String: string = base64Data.split(',')[1];
            const body: { base64FileContent: string } = { base64FileContent: base64String };

            await this.handleApiRequest('POST', url, body);
        } else {
            const url: string = this.data.resource.mainDocument ? `../rest/resources/${this.data.resource.resId}/thumbnail/${this.currentPage}` : `../rest/attachments/${this.data.resource.resId}/thumbnail/${this.currentPage}`;
            await this.handleApiRequest('GET', url);
        }
    }

    handleApiRequest(method: 'GET' | 'POST', url: string, body?: any): Promise<boolean> {
        return new Promise((resolve) => {
            let request;

            if (method === 'POST') {
                request = this.http.post(url, body);
            } else {
                request = this.http.get(url);
            }

            request.pipe(
                tap((data: { pageCount: number, format: string, fileContent: string }) => {
                    this.setPagesAndImgContent(data.pageCount, data.fileContent);
                    this.loading = false;
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    this.loading = false;
                    this.dialogRef.close();
                    resolve(false);
                    return of(false);
                })
            ).subscribe();
        })
    }

    setPagesAndImgContent(pageCount: number, fileContent: string): void {
        this.pages = Array.from({ length: pageCount }).map((_, i) => i + 1);
        this.imgContent = 'data:image/png;base64,' + fileContent;
        this.getImageDimensions(this.imgContent);
    }

    getImageDimensions(imgContent: any): void {
        const img = new Image();
        img.onload = (data: any) => {
            this.workingAreaWidth = data.target.naturalWidth;
            this.workingAreaHeight = data.target.naturalHeight;
        };
        if (document.getElementsByClassName('signatureContainer')[0] !== undefined) {
            img.src = imgContent;
        }
    }

    moveSign(event: any): void {
        const percentx: number = (event.x * 100) / this.workingAreaWidth;
        const percenty: number = (event.y * 100) / this.workingAreaHeight;
        this.signList.filter((item: SignaturePositionInterface) => item.sequence === this.data.workflow[this.currentUser].sequence && item.page === this.currentPage)[0].positionX = percentx;
        this.signList.filter((item: SignaturePositionInterface) => item.sequence === this.data.workflow[this.currentUser].sequence && item.page === this.currentPage)[0].positionY = percenty;
    }

    moveDate(event: any): void {
        const percentx: number = (event.x * 100) / this.workingAreaWidth;
        const percenty: number = (event.y * 100) / this.workingAreaHeight;
        this.dateList.filter((item: DatePositionInterface) => item.sequence === this.data.workflow[this.currentUser].sequence && item.page === this.currentPage)[0].positionX = percentx;
        this.dateList.filter((item: DatePositionInterface) => item.sequence === this.data.workflow[this.currentUser].sequence && item.page === this.currentPage)[0].positionY = percenty;
    }

    onResizeDateStop(event: any, index: number): void {
        this.dateList[index].height = (event.size.height * 100) / this.workingAreaHeight;
        this.dateList[index].width = (event.size.width * 100) / this.workingAreaWidth;
    }

    emptySign(): boolean {
        return this.signList.filter((item: SignaturePositionInterface) => item.sequence === this.data.workflow[this.currentUser]?.sequence && item.page === this.currentPage).length === 0;
    }

    emptyDate(): boolean {
        return this.dateList.filter((item: DatePositionInterface) => item.sequence === this.data.workflow[this.currentUser].sequence && item.page === this.currentPage).length === 0;
    }

    initSign(sequence: number = null, positionX: number = null, positionY: number = null, page: number = null): void {
        this.signList.push(
            {
                sequence: sequence === null ? this.data.workflow[this.currentUser].sequence : sequence,
                page: page === null ? this.currentPage : page,
                positionX: positionX === null ? 0 : positionX,
                positionY: positionY === null ? 0 : positionY
            }
        );
        document.getElementsByClassName('signatureContainer')[0].scrollTo(0, 0);
    }

    initDateBlock(): void {
        this.dateList.push(
            {
                sequence: this.data.workflow[this.currentUser].sequence,
                page: this.currentPage,
                font: 'Arial',
                size: 15,
                color: '#000000',
                format: 'd MMMM y',
                width: (130 * 100) / this.workingAreaWidth,
                height: (30 * 100) / this.workingAreaHeight,
                positionX: 0,
                positionY: 0
            }
        );
        document.getElementsByClassName('signatureContainer')[0].scrollTo(0, 0);
    }

    getUserSignPosPage(workflowIndex: number) {
        return this.signList.filter((item: SignaturePositionInterface) => item.sequence === workflowIndex);
    }

    selectUser(workflowIndex: number): void {
        this.currentUser = workflowIndex;
    }

    getUserName(sequence: number): string {
        return this.data.workflow.find((user: UserWorkflowInterface) => user.sequence === sequence)?.labelToDisplay;
    }

    async goToSignUserPage(workflowIndex: number, page: number, isEvent: boolean = false): Promise<void> {
        if (isEvent) {
            this.loading = true;
        }
        this.currentUser = workflowIndex;
        this.currentPage = page;
        await this.getPageAttachment();
        document.getElementsByClassName('signatureContainer')[0].scrollTop = 0;
        this.loading = false;
    }

    imgLoaded(): void {
        this.loading = false;
    }

    deleteSign(index: number): void {
        this.signList.splice(index, 1);

        if (this.data?.template) {
            this.data.workflow.splice(index, 1);
            this.reOrderData();
        }
    }

    deleteDate(index: number): void {
        this.dateList.splice(index, 1);
    }

    formatData(): { signaturePositions: SignaturePositionInterface[], datePositions: DatePositionInterface[] } {
        const objToSend: { signaturePositions: SignaturePositionInterface[], datePositions: DatePositionInterface[] } = {
            signaturePositions: [],
            datePositions: []
        };
        this.data.workflow.forEach((element: UserWorkflowInterface) => {
            if (this.signList.filter((item: SignaturePositionInterface) => item.sequence === element.sequence).length > 0) {
                objToSend['signaturePositions'] = objToSend['signaturePositions'].concat(this.signList.filter((item: SignaturePositionInterface) => item.sequence === element.sequence));
            }
            if (this.dateList.filter((item: DatePositionInterface) => item.sequence === element.sequence).length > 0) {
                objToSend['datePositions'] = objToSend['datePositions'].concat(this.dateList.filter((item: DatePositionInterface) => item.sequence === element.sequence));
            }
        });
        return objToSend;
    }

    getUserPages(): (DatePositionInterface | SignaturePositionInterface)[] {
        return this.signList.concat(this.dateList);
    }

    hasSign(userSequence: number, page: number): boolean {
        return this.signList.filter((item: SignaturePositionInterface) => item.sequence === userSequence && item.page === page).length > 0;
    }

    hasDate(userSequence: number, page: number): boolean {
        return this.dateList.filter((item: DatePositionInterface) => item.sequence === userSequence && item.page === page).length > 0;
    }

    openDateSettings(index: number): void {
        const dialogRef = this.dialog.open(DateOptionModalComponent, {
            panelClass: 'maarch-modal',
            // disableClose: true,
            width: '500px',
            data: {
                currentDate : this.dateList[index]
            }
        });
        dialogRef.afterClosed().pipe(
            filter((res: any) => !this.functions.empty(res)),
            tap((res: any) => {
                this.dateList[index] = res;
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    userHasSignature(): boolean {
        const currentUserSequence: number = this.data.workflow[this.currentUser]?.sequence
        return this.signList.filter((sign: SignaturePositionInterface) => sign.sequence === currentUserSequence).length > 0;
    }

    getUserRole(user: { role: string, currentRole: string }): string {
        let role: string = user.role ?? user.currentRole;
        if (['visa', 'sign'].indexOf(role) > -1) {
            role = `${role}User`;
        }
        return this.translate.instant('lang.' + role);
    }

    addSignUser(sequence: number = null, positionX: number = null, positionY: number = null, page: number = null): void {
        this.data.workflow.push({
            sequence: sequence === null ? this.data.workflow.length : sequence,
            labelToDisplay: `Signataire #${this.data.workflow.length + 1}`,
            currentRole: "sign",
        });

        this.currentUser = this.data.workflow.length - 1;
        this.initSign(sequence, positionX, positionY, page);
    }

    deleteSignUser(userIndex: number): void {
        this.signList.splice(userIndex, 1);
        this.data.workflow.splice(userIndex, 1);
        this.reOrderData();
    }

    reOrderData(): void {
        (this.data.workflow as UserWorkflowInterface[]).forEach((user: UserWorkflowInterface, index: number) => {
            user.labelToDisplay = `Signataire #${index + 1}`;
            user.sequence = index;
        });

        this.signList.forEach((sign: SignaturePositionInterface, index: number) => {
            sign.sequence = index;
        });

        this.currentUser = this.data.workflow.length - 1;
    }

    getTitle(): string {
        return this.data?.template ? this.data.template.title : this.data.resource.title;
    }
}
