import { ComponentFixture, TestBed, fakeAsync, tick } from '@angular/core/testing';
import { HttpClientTestingModule } from '@angular/common/http/testing';
import { TranslateLoader, TranslateModule, TranslateService } from '@ngx-translate/core';
import { NotificationService } from '@service/notification/notification.service';
import { FunctionsService } from '@service/functions.service';
import { HeaderService } from '@service/header.service';
import { MatLegacyTableModule as MatTableModule } from '@angular/material/legacy-table';
import { MatLegacyPaginatorModule as MatPaginatorModule } from '@angular/material/legacy-paginator';
import { MatSortModule } from '@angular/material/sort';
import { MatLegacyFormFieldModule as MatFormFieldModule } from '@angular/material/legacy-form-field';
import { MatLegacyInputModule as MatInputModule } from '@angular/material/legacy-input';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { FormsModule } from '@angular/forms';
import { NoopAnimationsModule } from '@angular/platform-browser/animations';
import { Observable, of, throwError } from 'rxjs';
import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { By } from '@angular/platform-browser';
import { MatNativeDateModule } from '@angular/material/core';
import { ProfileHistoryComponent } from '@appRoot/profile/history/history.component';
import * as langFrJson from '@langs/lang-fr.json';


class FakeLoader implements TranslateLoader {
    getTranslation(): Observable<any> {
        return of({ lang: langFrJson });
    }
}

describe('ProfileHistoryComponent', () => {
    let component: ProfileHistoryComponent;
    let fixture: ComponentFixture<ProfileHistoryComponent>;
    let httpClientSpy: { get: jasmine.Spy };
    let notificationServiceSpy: jasmine.SpyObj<NotificationService>;
    let functionsServiceSpy: jasmine.SpyObj<FunctionsService>;
    let headerServiceSpy: any;
    let translateService: TranslateService;

    const mockHistoriesData = {
        histories: [
            { event_date: '2025-03-10T10:30:00', record_id: '1001', info: 'Login', remote_ip: '192.168.1.1' },
            { event_date: '2025-03-09T14:20:00', record_id: '1002', info: 'Update profile', remote_ip: '192.168.1.2' },
            { event_date: '2025-03-08T08:15:00', record_id: '1003', info: 'Password change', remote_ip: '192.168.1.3' }
        ]
    };

    beforeEach(async () => {
        httpClientSpy = jasmine.createSpyObj('HttpClient', ['get']);
        notificationServiceSpy = jasmine.createSpyObj('NotificationService', ['handleSoftErrors']);
        functionsServiceSpy = jasmine.createSpyObj('FunctionsService', ['empty', 'listSortingDataAccessor']);
        headerServiceSpy = {
            user: { id: 19 }
        };

        await TestBed.configureTestingModule({
            declarations: [ProfileHistoryComponent],
            imports: [
                HttpClientTestingModule,
                TranslateModule.forRoot(),
                MatTableModule,
                MatPaginatorModule,
                MatSortModule,
                MatFormFieldModule,
                MatInputModule,
                MatDatepickerModule,
                MatNativeDateModule,
                FormsModule,
                NoopAnimationsModule,
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: FakeLoader },
                })
            ],
            providers: [
                { provide: HttpClientTestingModule, useValue: {} },
                { provide: NotificationService, useValue: notificationServiceSpy },
                { provide: FunctionsService, useValue: functionsServiceSpy },
                { provide: HeaderService, useValue: headerServiceSpy }
            ],
            schemas: [CUSTOM_ELEMENTS_SCHEMA]
        }).compileComponents();

        // Set lang
        translateService = TestBed.inject(TranslateService);
        translateService.use('fr');

        fixture = TestBed.createComponent(ProfileHistoryComponent);
        component = fixture.componentInstance;
        component.http = httpClientSpy as any;
    });

    it('should create the component', () => {
        expect(component).toBeTruthy();
    });

    // Unit Tests (TU)

    describe('Unit Tests', () => {
        it('should initialize with default values', () => {
            expect(component.userId).toBeNull();
            expect(component.adminMode).toBeFalsy();
            expect(component.displayedColumns).toEqual(['event_date', 'record_id', 'info']);
            expect(component.dataSource).toBeDefined();
            expect(component.currentYear).toEqual(new Date().getFullYear());
            expect(component.currentMonth).toEqual(new Date().getMonth() + 1);
        });

        it('should add remote_ip to displayedColumns when adminMode is true', () => {
            component.adminMode = true;
            functionsServiceSpy.empty.and.returnValue(false);
            httpClientSpy.get.and.returnValue(of(mockHistoriesData));

            // Call ngOnInit directly without fixture.detectChanges() to avoid MatSort initialization issues
            component.ngOnInit();

            expect(component.displayedColumns).toEqual(['event_date', 'record_id', 'info', 'remote_ip']);
        });

        it('should use headerService user id when userId is null', fakeAsync(() => {
            functionsServiceSpy.empty.and.returnValue(true);
            httpClientSpy.get.and.returnValue(of(mockHistoriesData));

            component.userId = null;
            component.getHistories();

            fixture.detectChanges();
            tick();

            expect(functionsServiceSpy.empty).toHaveBeenCalledWith(null);
            expect(component.userId).toEqual(19);
        }));

        it('should use provided userId when not null', fakeAsync(() => {
            functionsServiceSpy.empty.and.returnValue(false);
            httpClientSpy.get.and.returnValue(of(mockHistoriesData));

            component.userId = 20;
            component.getHistories();

            fixture.detectChanges();
            tick();

            expect(functionsServiceSpy.empty).toHaveBeenCalledWith(20);
            expect(component.userId).toEqual(20);
        }));

        it('should include adminMode parameter when adminMode is true', fakeAsync(() => {
            functionsServiceSpy.empty.and.returnValue(false);
            httpClientSpy.get.and.returnValue(of(mockHistoriesData));

            component.adminMode = true;
            component.userId = 20;
            component.getHistories();

            fixture.detectChanges();
            tick();

            expect(httpClientSpy.get).toHaveBeenCalledWith('../rest/history/users/20?adminMode=true');
        }));

        it('should call handleSoftErrors when HTTP request fails', fakeAsync(() => {
            functionsServiceSpy.empty.and.returnValue(false);
            const errorResponse = { status: 500, message: 'Server error' };
            httpClientSpy.get.and.returnValue(throwError(errorResponse));

            component.getHistories();

            fixture.detectChanges();
            tick();

            expect(notificationServiceSpy.handleSoftErrors).toHaveBeenCalledWith(errorResponse);
        }));

        it('should apply filter correctly', () => {
            component.dataSource.data = mockHistoriesData.histories;

            component.applyFilter('login');

            expect(component.dataSource.filter).toEqual('login');
        });

        it('should setup date filtering when adminMode is true', fakeAsync(() => {
            functionsServiceSpy.empty.and.returnValue(false);
            httpClientSpy.get.and.returnValue(of(mockHistoriesData));
            component.adminMode = true;

            component.getHistories();

            fixture.detectChanges();
            tick();

            expect(component.minDate).toBeDefined();
            expect(component.maxDate).toBeDefined();
            const currentYear = new Date().getFullYear();
            const currentMonth = new Date().getMonth() + 1;
            const expectedMinDate = new Date(`${currentYear}-${currentMonth}-01`);
            expect(component.minDate.getFullYear()).toEqual(expectedMinDate.getFullYear());
            expect(component.minDate.getMonth()).toEqual(expectedMinDate.getMonth());
            expect(component.minDate.getDate()).toEqual(expectedMinDate.getDate());
        }));

        it('should filter dates correctly in adminMode', () => {
            const now: Date = new Date();
            component.adminMode = true;
            component.dataSource.data = mockHistoriesData.histories;
            component.minDate = new Date(now.getFullYear(), now.getMonth(), 1);
            component.maxDate = now;

            spyOnProperty(component.dataSource, 'filter', 'set').and.callThrough();
            component.filterStartDate();

            expect(component.dataSource.filterPredicate).toBeDefined();
            expect(component.dataSource.filter).toBeDefined();
        });
    });

    // Functional Tests (TF)

    describe('Functional Tests', () => {
        beforeEach(() => {
            functionsServiceSpy.empty.and.returnValue(false);
            functionsServiceSpy.listSortingDataAccessor.and.returnValue('');
            httpClientSpy.get.and.returnValue(of(mockHistoriesData));
        });

        it('should display data in the table', fakeAsync(() => {
            functionsServiceSpy.empty.and.returnValue(true);
            httpClientSpy.get.and.returnValue(of(mockHistoriesData));

            component.userId = null;
            component.displayedColumns = ['event_date', 'record_id', 'info', 'remote_ip'];

            component.getHistories();

            fixture.detectChanges();
            tick();

            expect(functionsServiceSpy.empty).toHaveBeenCalledWith(null);
            expect(component.userId).toEqual(19);

            expect(component.histories.length).toBe(3);
            expect(component.dataSource.data.length).toBe(3);

            const nativeElement = fixture.nativeElement;
            const cells = nativeElement.querySelectorAll('mat-cell');
            const rows = nativeElement.querySelectorAll('mat-row');


            // Check if correct number of rows and cells are rendered
            expect(rows.length).toBe(3);
            expect(cells.length).toBe(12); // 3 rows * 4 columns

            // Check if data is rendered correctly in each row
            expect(rows[0].innerText.replace(/\s+/g, ' ').trim()).toEqual('10/03/2025 10:30 1001 Login 192.168.1.1');
            expect(rows[1].innerText.replace(/\s+/g, ' ').trim()).toEqual('09/03/2025 14:20 1002 Update profile 192.168.1.2');
            expect(rows[2].innerText.replace(/\s+/g, ' ').trim()).toEqual('08/03/2025 08:15 1003 Password change 192.168.1.3');

            // Check if data is rendered correctly in each cell
            expect(cells[0].innerText.trim()).toEqual('10/03/2025 10:30'); // event_date
            expect(cells[1].innerText.trim()).toEqual('1001'); // record_id
            expect(cells[2].innerText.trim()).toEqual('Login'); // info
            expect(cells[3].innerText.trim()).toEqual('192.168.1.1'); // remote_ip
        }));

        it('should render correct number of columns in normal mode', fakeAsync(() => {
            component.adminMode = false;
            component.ngOnInit();

            fixture.detectChanges();
            tick();

            const headerRow = fixture.debugElement.query(By.css('mat-header-row'));
            expect(headerRow).toBeTruthy();

            const headerCells = fixture.debugElement.queryAll(By.css('mat-header-cell'));
            expect(headerCells.length).toBe(3); // event_date, record_id, info
        }));

        it('should apply filter when search input changes', fakeAsync(() => {
            component.ngOnInit();

            fixture.detectChanges();
            tick();

            spyOn(component, 'applyFilter');
            const inputElement = fixture.debugElement.query(By.css('input[matInput]'));
            inputElement.triggerEventHandler('keyup', { target: { value: 'login' } });

            fixture.detectChanges();
            tick();

            expect(component.applyFilter).toHaveBeenCalledWith('login');
        }));

        it('should render date picker in admin mode', fakeAsync(() => {
            component.adminMode = true;

            fixture.detectChanges();
            tick();

            const datePicker = fixture.debugElement.query(By.css('mat-datepicker'));
            expect(datePicker).toBeTruthy();
        }));

        it('should not render date picker in normal mode', fakeAsync(() => {
            component.adminMode = false;

            fixture.detectChanges();
            tick();

            const datePicker = fixture.debugElement.query(By.css('mat-datepicker'));
            expect(datePicker).toBeFalsy();
        }));

        it('should render paginator with correct options in admin mode', fakeAsync(() => {
            functionsServiceSpy.empty.and.returnValue(true);
            httpClientSpy.get.and.returnValue(of(mockHistoriesData));

            component.userId = null;
            component.displayedColumns = ['event_date', 'record_id', 'info', 'remote_ip'];

            component.getHistories();

            fixture.detectChanges();
            tick();

            const paginator = fixture.nativeElement.querySelector('mat-paginator');

            expect(paginator).toBeTruthy();
            expect(paginator.attributes['ng-reflect-page-size'].value).toEqual('10');
            expect(paginator.attributes['ng-reflect-length'].value).toEqual('100');

            // check if cells are rendered
            const cells = fixture.debugElement.queryAll(By.css('mat-cell'));
            expect(cells.length).toBeGreaterThan(0);
        }));
    });
});
