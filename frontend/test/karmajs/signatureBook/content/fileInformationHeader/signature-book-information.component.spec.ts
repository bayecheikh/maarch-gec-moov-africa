import { ComponentFixture, fakeAsync, TestBed, tick } from '@angular/core/testing';
import { TranslateModule } from '@ngx-translate/core';
import { HttpClientTestingModule } from '@angular/common/http/testing';
import { LocalStorageService } from '@service/local-storage.service';
import { FunctionsService } from '@service/functions.service';
import { By } from '@angular/platform-browser';
import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { MaarchSbInformationComponent } from '@appRoot/signatureBook/content/fileInformationHeader/signature-book-information.component';
import { AttachmentInterface } from '@models/attachment.model';
import { SharedModule } from '@appRoot/app-common.module';
import { FullDatePipe } from '@plugins/fullDate.pipe';

describe('MaarchSbInformationComponent', () => {
    let component: MaarchSbInformationComponent;
    let fixture: ComponentFixture<MaarchSbInformationComponent>;
    let localStorage: jasmine.SpyObj<LocalStorageService>;
    let functionsService: jasmine.SpyObj<FunctionsService>;

    const mockDocumentData: AttachmentInterface = {
        resId: 100,
        resIdMaster: 90,
        signedResId: 1,
        chrono: 'MAARCH/2024/001',
        title: 'Test Document',
        type: 'simple_attachment',
        typeLabel: 'simple_attachment',
        canConvert: true,
        canDelete: true,
        canUpdate: true,
        hasDigitalSignature: true,
        fileInformation: {
            typistId: 0,
            typistLabel: 'Barbara BAIN',
            creationDate: '',
            format: 'docx',
            version: 1
        },
        resourceUrn: 'rest/attachment/100/content',
        isAttachment: true,
        externalDocumentId: 0,
        visaWorkflow: [],
        isAnnotated: false
    };

    beforeEach(async () => {
        const localStorageSpy = jasmine.createSpyObj('LocalStorageService', ['get', 'save']);
        const functionsSpy = jasmine.createSpyObj('FunctionsService', ['empty']);

        await TestBed.configureTestingModule({
            imports: [
                HttpClientTestingModule,
                TranslateModule.forRoot(),
                SharedModule
            ],
            declarations: [MaarchSbInformationComponent],
            providers: [
                { provide: LocalStorageService, useValue: localStorageSpy },
                { provide: FunctionsService, useValue: functionsSpy },
                FullDatePipe
            ],
            schemas: [CUSTOM_ELEMENTS_SCHEMA]
        }).compileComponents();

        localStorage = TestBed.inject(LocalStorageService) as jasmine.SpyObj<LocalStorageService>;
        functionsService = TestBed.inject(FunctionsService) as jasmine.SpyObj<FunctionsService>;
    });

    beforeEach(() => {
        fixture = TestBed.createComponent(MaarchSbInformationComponent);
        component = fixture.componentInstance;
        component.documentData = mockDocumentData;
        component.position = 'right';
        functionsService.empty.and.returnValue(false);
    });

    describe('Component Initialization', () => {
        it('should create', () => {
            expect(component).toBeTruthy();
        });

        it('should init with correct input values', () => {
            fixture.detectChanges();
            expect(component.documentData).toBeTruthy();
            expect(component.position).toBe('right');
        });

        it('should initialize banner state from localStorage', () => {
            fixture.detectChanges();
            expect(component.bannerOpened).toBeFalsy();
            expect(localStorage.get).toHaveBeenCalled();
        });
    });

    describe('Label and Title Management', () => {
        it('should set label with chrono and title', () => {
            fixture.detectChanges();
            expect(component.label).toBe('MAARCH/2024/001: Test Document');
        });

        it('should set label with only title when no chrono', () => {
            component.documentData.chrono = '';
            fixture.detectChanges();
            expect(component.label).toBe(': Test Document');
        });

        it('should set title correctly', () => {
            fixture.detectChanges();
            expect(component.title).toBeTruthy();
        });
    });

    describe('Template Integration', () => {
        it('should display document label', () => {
            mockDocumentData.chrono = 'MAARCH/2024/001';
            fixture.detectChanges();
            const labelEl = fixture.debugElement.query(By.css('.subject'));
            expect(labelEl.nativeElement.textContent).toContain('MAARCH/2024/001: Test Document');
        });

        it('should display file information', fakeAsync(() => {
            component.bannerOpened = true;

            fixture.detectChanges();
            tick(300);
            const squareBtn = fixture.nativeElement.querySelector('.mat-icon-square');
            squareBtn.click();

            fixture.detectChanges();

            const contentItems = fixture.nativeElement.querySelectorAll('.content-item');

            const fileVersion = contentItems[2];
            expect(fileVersion.querySelector('.content-item-value').innerText.trim()).toEqual('1');

            const fileFormat = contentItems[3];
            expect(fileFormat.querySelector('.content-item-value').innerText.trim()).toEqual('docx');
        }));

        it('should toggle banner on click', () => {
            fixture.detectChanges();
            const squareBtn = fixture.nativeElement.querySelector('.mat-icon-square');
            squareBtn.click();

            fixture.detectChanges();

            expect(localStorage.save).toHaveBeenCalled();
            expect(component.bannerOpened).toBeDefined();
        });
    });
});