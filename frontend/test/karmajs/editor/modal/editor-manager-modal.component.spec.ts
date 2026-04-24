import { ComponentFixture, TestBed } from '@angular/core/testing';
import { TranslateModule, TranslateLoader, TranslateService } from '@ngx-translate/core';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { Observable, of } from 'rxjs';
import * as langFrJson from '@langs/lang-fr.json';
import { EditorManagerModalComponent } from '@appRoot/editor/modal/editor-manager-modal.component';
import { MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA, MatLegacyDialogModule as MatDialogModule , MatLegacyDialogRef as MatDialogRef } from "@angular/material/legacy-dialog";
import { SharedModule } from '@appRoot/app-common.module';


class FakeLoader implements TranslateLoader {
    getTranslation(): Observable<any> {
        return of({ lang: langFrJson });
    }
}

describe('EditorManagerModalComponent', () => {
    let component: EditorManagerModalComponent;
    let fixture: ComponentFixture<EditorManagerModalComponent>;
    let translate: TranslateService;
    let dialogRef: jasmine.SpyObj<MatDialogRef<EditorManagerModalComponent>>;

    const mockDialogData = {
        resId: 100,
        isAttachment: true,
        typeLabel: 'simple_attachment',
        createVersion: { enabled: true, default: false },
        unannotatedVersion: true
    };

    beforeEach(async () => {
        const dialogRefSpy = jasmine.createSpyObj('MatDialogRef', ['close']);

        await TestBed.configureTestingModule({
            imports: [
                MatDialogModule,
                SharedModule,
                BrowserAnimationsModule,
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: FakeLoader }
                })
            ],
            declarations: [EditorManagerModalComponent],
            providers: [
                { provide: MAT_DIALOG_DATA, useValue: mockDialogData },
                { provide: MatDialogRef, useValue: dialogRefSpy }
            ]
        }).compileComponents();

        translate = TestBed.inject(TranslateService);
        dialogRef = TestBed.inject(MatDialogRef) as jasmine.SpyObj<MatDialogRef<EditorManagerModalComponent>>;
    });

    beforeEach(() => {
        fixture = TestBed.createComponent(EditorManagerModalComponent);
        component = fixture.componentInstance;
        translate.use('fr');
        fixture.detectChanges();
    });

    it('should create', () => {
        expect(component).toBeTruthy();
    });

    it('should have correct input data', () => {
        expect(component.data).toEqual(mockDialogData);
    });

    it('should close modal with id', () => {
        const testId = 'test123';
        component.closeModal(testId);
        expect(dialogRef.close).toHaveBeenCalledWith(testId);
    });

    it('should initialize with empty title', () => {
        expect(component.title).toBe('');
    });
});