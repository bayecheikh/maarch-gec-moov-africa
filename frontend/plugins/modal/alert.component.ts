import { Component, Inject, OnInit } from '@angular/core';
import {
    MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA,
    MatLegacyDialogRef as MatDialogRef
} from '@angular/material/legacy-dialog';
import { TranslateService } from '@ngx-translate/core';
import { AuthService } from '@service/auth.service';
import { FunctionsService } from '@service/functions.service';

@Component({
    templateUrl: 'alert.component.html',
    styleUrls: ['alert.component.scss']
})
export class AlertComponent implements OnInit {
    public authService: AuthService;

    constructor(
        public translate: TranslateService,
        public dialogRef: MatDialogRef<AlertComponent>,
        public functions: FunctionsService,
        @Inject(MAT_DIALOG_DATA) public data: any,
    ) {
        if (this.functions.empty(this.data.mode)) {
            this.data.mode = 'info';
        }

        if (this.functions.empty(this.data.msg)) {
            this.data.msg = '';
        }
    }

    ngOnInit(): void {
        if (this.data?.isCounter !== undefined) {
            let timeLeft: number = 10; // secondes
            this.data.msg = this.translate.instant('lang.inactivityWarning', { counter: timeLeft });
            const interval = setInterval(() => {
                if (timeLeft > 0) {
                    timeLeft--;
                    this.data.msg = this.translate.instant('lang.inactivityWarning', { counter: timeLeft });
                } else {
                    clearInterval(interval);
                }
            }, 1000);
        }
    }

    close(message: string = '') {
        this.dialogRef.close(this.data?.isCounter !== undefined ? 'resetTimer' : message);
    }
}
