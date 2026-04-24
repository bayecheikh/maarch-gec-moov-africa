import { Component, Inject, ViewChild } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';
import {
    MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA,
    MatLegacyDialog as MatDialog,
    MatLegacyDialogRef as MatDialogRef
} from '@angular/material/legacy-dialog';
import { EditorManagerComponent } from '../editor-manager.component';

@Component({
    templateUrl: 'editor-manager-modal.component.html',
    styleUrls: ['editor-manager-modal.component.scss'],
})
export class EditorManagerModalComponent {

    @ViewChild('editorManager', { static: false }) editorManager: EditorManagerComponent;

    title: string = '';
    unannotatedVersion: boolean = true;

    constructor(
        public translate: TranslateService,
        public dialog: MatDialog,
        public dialogRef: MatDialogRef<EditorManagerModalComponent>,
        @Inject(MAT_DIALOG_DATA) public data: any
    ) { }

    closeModal(id: string) {
        this.dialogRef.close(id);
    }
}
