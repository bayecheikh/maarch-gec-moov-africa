import { ComponentFixture, TestBed, fakeAsync, tick } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { ReactiveFormsModule, UntypedFormBuilder } from '@angular/forms';
import { TranslateService, TranslateModule, TranslateLoader, TranslatePipe } from '@ngx-translate/core';
import { FunctionsService } from '@service/functions.service';
import { NotificationService } from '@service/notification/notification.service';
import { MatLegacyDialog } from '@angular/material/legacy-dialog';
import { NoopAnimationsModule } from '@angular/platform-browser/animations';
import { MatLegacyFormFieldModule } from '@angular/material/legacy-form-field';
import { MatLegacyInputModule } from '@angular/material/legacy-input';
import { MatIconModule } from '@angular/material/icon';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatNativeDateModule } from '@angular/material/core';
import { MatLegacyProgressSpinnerModule } from '@angular/material/legacy-progress-spinner';
import { MatExpansionModule } from '@angular/material/expansion';
import { Observable, of } from 'rxjs';
import { CUSTOM_ELEMENTS_SCHEMA, Pipe, PipeTransform } from '@angular/core';
import { By } from '@angular/platform-browser';
import { UserAcceTokenInterface, UserAdministrationAccesTokenComponent } from '@appRoot/administration/user/access-token/user-administration-access-token.component';
import * as langFrJson from '@langs/lang-fr.json';

@Pipe({
    name: 'fullDate'
})
class MockFullDatePipe implements PipeTransform {
    transform(value: any): any {
        return value ? `Formatted: ${value}` : '';
    }
}

class FakeLoader implements TranslateLoader {
    getTranslation(): Observable<any> {
        return of({ lang: langFrJson });
    }
}

describe('UserAdministrationAccesTokenComponent', () => {
    let component: UserAdministrationAccesTokenComponent;
    let fixture: ComponentFixture<UserAdministrationAccesTokenComponent>;
    let httpTestingController: HttpTestingController;
    let notificationServiceSpy: jasmine.SpyObj<NotificationService>;
    let functionsServiceSpy: jasmine.SpyObj<FunctionsService>;
    let dialogSpy: jasmine.SpyObj<MatLegacyDialog>;
    let translateService: TranslateService;

    const mockUserId: number = 123;
    const mockToken: UserAcceTokenInterface = {
        token: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUz',
        creationDate: '2025-02-01',
        expirationDate: '2025-04-30',
        lastUsedDate: '2025-03-10'
    };

    const mockEmptyToken: UserAcceTokenInterface = {
        token: '',
        creationDate: '',
        expirationDate: '',
        lastUsedDate: ''
    };

    beforeEach(async () => {
        // Create spies for services
        notificationServiceSpy = jasmine.createSpyObj('NotificationService', ['success', 'handleSoftErrors']);
        functionsServiceSpy = jasmine.createSpyObj('FunctionsService', ['empty']);
        dialogSpy = jasmine.createSpyObj('MatDialog', ['open']);

        await TestBed.configureTestingModule({
            declarations: [
                UserAdministrationAccesTokenComponent,
                MockFullDatePipe,
                TranslatePipe
            ],
            imports: [
                HttpClientTestingModule,
                ReactiveFormsModule,
                NoopAnimationsModule,
                MatLegacyFormFieldModule,
                MatLegacyInputModule,
                MatIconModule,
                MatDatepickerModule,
                MatNativeDateModule,
                MatLegacyProgressSpinnerModule,
                MatExpansionModule,
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: FakeLoader },
                }),
            ],
            providers: [
                UntypedFormBuilder,
                TranslateService,
                { provide: NotificationService, useValue: notificationServiceSpy },
                { provide: FunctionsService, useValue: functionsServiceSpy },
                { provide: MatLegacyDialog, useValue: dialogSpy }
            ],
            schemas: [CUSTOM_ELEMENTS_SCHEMA]
        }).compileComponents();

        // Set lang
        translateService = TestBed.inject(TranslateService);
        translateService.use('fr');
    });

    beforeEach(() => {
        fixture = TestBed.createComponent(UserAdministrationAccesTokenComponent);
        component = fixture.componentInstance;
        component.userId = mockUserId;
        httpTestingController = TestBed.inject(HttpTestingController);
    });

    afterEach(() => {
        httpTestingController.verify();
    });

    // UNIT TESTS (TU)

    describe('Unit Tests', () => {
        it('should create the component', () => {
            expect(component).toBeTruthy();
        });

        it('should initialize with proper values', () => {
            expect(component.newAccessTokenCreated).toBeFalse();
            expect(component.minDate).toBeDefined();
            expect(component.maxDate).toBeDefined();
        });

        it('should properly set up form controls', () => {
            component.accessTokenFormGroup = component._formBuilder.group(component.accessTokenConfig)
            expect(component.accessTokenFormGroup.controls['expirationDate']).toBeDefined();
            expect(component.accessTokenFormGroup.controls['expirationDate'].validator).not.toBeNull();
        });

        it('should handle getUserAccessToken success', fakeAsync(() => {
            const mockResponse = {
                token: {
                    token: mockToken.token,
                    creation_date: mockToken.creationDate,
                    expiration_date: mockToken.expirationDate,
                    last_used_date: mockToken.lastUsedDate
                }
            };

            let result: boolean | undefined;
            component.getUserAccessToken().then(res => result = res);

            const req = httpTestingController.expectOne(`../rest/users/${mockUserId}/tokens`);
            expect(req.request.method).toEqual('GET');
            req.flush(mockResponse);

            tick();

            expect(result).toBeTrue();
            expect(component.accessToken).toEqual(mockToken);
            expect(component.loading).toBeFalse();
        }));

        it('should successfully generate an access token', fakeAsync(() => {
            component.accessTokenFormGroup = component._formBuilder.group(component.accessTokenConfig)
            const today = new Date();
            const futureDate = new Date(today);
            futureDate.setDate(today.getDate() + 30);
            component.accessTokenFormGroup.controls['expirationDate'].setValue(futureDate);

            // Setup spying on getUserAccessToken
            spyOn(component, 'getUserAccessToken').and.returnValue(Promise.resolve(true));

            component.generateAccessToken();

            fixture.detectChanges();
            tick();

            expect(component.loading).toBeTrue();

            // Mock the HTTP request
            const req = httpTestingController.expectOne(`../rest/users/${mockUserId}/tokens`);
            expect(req.request.method).toEqual('POST');
            expect(req.request.body).toEqual({ expirationDate: futureDate });
            req.flush({});

            tick();

            // Verify that the access token was generated successfully
            expect(component.getUserAccessToken).toHaveBeenCalled();
            expect(component.newAccessTokenCreated).toBeTrue();
            expect(component.accessTokenFormGroup.controls['expirationDate'].value).toBe('');
            expect(notificationServiceSpy.success).toHaveBeenCalledWith(component.translate.instant('lang.accessTokenCreated'));
        }));

        it('should copy token to clipboard', fakeAsync(() => {
            // Skip ngOnInit HTTP request
            spyOn(component, 'getUserAccessToken').and.returnValue(Promise.resolve(true));

            // Setup clipboard spy before calling the method
            const clipboardSpy = jasmine.createSpyObj('clipboard', ['writeText']);
            clipboardSpy.writeText.and.returnValue(Promise.resolve());

            // Mock the navigator.clipboard API
            Object.defineProperty(navigator, 'clipboard', {
                value: clipboardSpy,
                writable: true,
                configurable: true
            });

            // Test the copy functionality
            const testToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3';
            component.copyToken(testToken);

            // Handle async operations
            fixture.detectChanges();
            tick();

            // Verify expectations
            expect(clipboardSpy.writeText).toHaveBeenCalledWith(testToken);
        }));
    });

    // FUNCTIONAL TESTS (TF)

    describe('Functional Tests', () => {
        beforeEach(() => {
            // Skip HTTP request by mocking getUserAccessToken
            spyOn(component, 'getUserAccessToken').and.returnValue(Promise.resolve(true));

            // Trigger initial render
            fixture.detectChanges();
        });

        it('should display loading spinner when loading is true', fakeAsync(() => {
            // Find spinner
            const spinner = fixture.debugElement.query(By.css('mat-spinner'));
            expect(spinner).toBeTruthy();

            // Verify loading state persists
            expect(component.loading).toBeTrue();

            // Complete any pending async operations
            tick();
        }));

        it('should disable expansion panel when token exists', () => {
            component.accessToken = mockToken;

            functionsServiceSpy.empty.and.returnValue(false);

            fixture.detectChanges();

            // Find the expansion panel
            const panel = fixture.debugElement.query(By.css('mat-expansion-panel'));

            // Verify that the panel is disabled
            expect(panel.componentInstance.disabled).toBeTrue();
        });

        it('should enable expansion panel when token is empty', () => {
            component.accessToken = mockEmptyToken;
            component.loading = false;

            functionsServiceSpy.empty.and.returnValue(true);

            fixture.detectChanges();

            // Find the expansion panel
            const panel = fixture.debugElement.query(By.css('mat-expansion-panel'));

            // Verify that the panel is enabled
            expect(panel.componentInstance.disabled).toBeFalse();
        });

        it('should show token information when token exists', fakeAsync(() => {
            component.accessToken = mockToken;

            functionsServiceSpy.empty.and.returnValue(false);

            fixture.detectChanges();
            tick();

            // Find the token container
            const tokenContainer = fixture.debugElement.query(By.css('.access-token-container'));
            expect(tokenContainer).toBeTruthy();

            // Find the token input field
            const dateElements = tokenContainer.queryAll(By.css('.row span'));

            // Verify that the date elements are present
            expect(dateElements.length).toBeGreaterThan(0);
        }));

        it('should show "New Access Token Created" message when a new token is created', fakeAsync(() => {
            component.newAccessTokenCreated = true;

            fixture.detectChanges();
            tick();

            // Find the message container
            const message = fixture.debugElement.query(By.css('app-maarch-message'));

            // Verify that the message is displayed
            expect(message).toBeTruthy();

            // Check the message content
            const messageText = message.nativeElement.content.trim();
            expect(messageText).toContain(component.translate.instant('lang.newAccessTokenCreated'));

            // Find the token input field
            const tokenInput = fixture.debugElement.query(By.css('input[readonly]'));
            // Verify that the token input field is present
            expect(tokenInput).toBeTruthy();

            // Check the token input field type
            expect(tokenInput.attributes['type']).toBe('password');

            // Check the token input field title
            expect(tokenInput.attributes['title']).toBe(component.translate.instant('lang.accessToken'));
        }));

        it('should toggle token visibility when show/hide button is clicked', fakeAsync(() => {
            component.newAccessTokenCreated = true;
            component.accessToken = mockToken;

            fixture.detectChanges();
            tick();

            // Find the token input field
            const tokenInput = fixture.debugElement.query(By.css('input[readonly]'));

            // Check the token input field type
            expect(tokenInput.attributes['type']).toBe('password');

            // Find the toggle button
            const toggleButton = fixture.nativeElement.querySelector('button[name=showHideToken]');

            // Simulate a click event on the toggle button
            toggleButton.click();

            fixture.detectChanges();
            tick();

            expect(component.hideToken).toBeFalse();

            // Check the token input field value
            expect(tokenInput.attributes['ng-reflect-value'].trim()).toBe(mockToken.token);

            // Check the token input field type
            expect(tokenInput.attributes['type']).toBe('text');

        }));

        it('should call copyToken when copy button is clicked', fakeAsync(() => {
            component.newAccessTokenCreated = true;
            component.accessToken = mockToken;

            fixture.detectChanges();
            tick();

            spyOn(component, 'copyToken');

            // Find the copy button
            const copyButton = fixture.nativeElement.querySelector('button[name=copyToken]');
            copyButton.click();

            fixture.detectChanges();

            // Verify that copyToken was called with the correct token
            expect(component.copyToken).toHaveBeenCalledWith(mockToken.token);
        }));

        it('should call revokeAccessToken when delete button is clicked', fakeAsync(() => {
            component.accessToken = mockToken;
            component.loading = false;
            functionsServiceSpy.empty.and.returnValue(false);

            fixture.detectChanges();
            tick();

            spyOn(component, 'revokeAccessToken');

            // Find the delete button
            const revokeAccessTokenButton = fixture.nativeElement.querySelector('button[name=revokeAccessToken]');

            // Verify that the button is present
            expect(revokeAccessTokenButton).toBeTruthy();

            revokeAccessTokenButton.click();

            fixture.detectChanges();
            tick();

            expect(component.revokeAccessToken).toHaveBeenCalled();
        }));

        it('should correctly revoke token when confirmed', fakeAsync(() => {
            // Mock dialog behavior
            dialogSpy.open.and.returnValue({
                afterClosed: () => of('ok')
            } as any);

            component.revokeAccessToken();

            expect(dialogSpy.open).toHaveBeenCalled();

            // Mock the HTTP request
            const req = httpTestingController.expectOne(`../rest/users/${mockUserId}/tokens`);
            expect(req.request.method).toEqual('DELETE');
            req.flush({});

            tick();

            // Verify that the access token was revoked successfully
            expect(component.accessToken).toEqual(mockEmptyToken);
            expect(component.newAccessTokenCreated).toBeFalse();
            expect(component.accessTokenFormGroup.controls['expirationDate'].value).toBe('');
            expect(notificationServiceSpy.success).toHaveBeenCalledWith(component.translate.instant('lang.accessTokenDeleted'));
        }));

        it('should handle revoke token error', fakeAsync(() => {
            dialogSpy.open.and.returnValue({
                afterClosed: () => of('ok')
            } as any);

            component.revokeAccessToken();

            // Mock the HTTP request
            const req = httpTestingController.expectOne(`../rest/users/${mockUserId}/tokens`);
            req.error(new ErrorEvent('Cannot revoke the access token'));

            tick();

            // Verify that the error was handled
            expect(notificationServiceSpy.handleSoftErrors).toHaveBeenCalled();
            expect(component.loading).toBeFalse();
        }));

        it('should not revoke token when dialog is canceled', fakeAsync(() => {
            dialogSpy.open.and.returnValue({
                afterClosed: () => of('cancel')
            } as any);

            component.revokeAccessToken();

            tick();

            httpTestingController.expectNone(`../rest/users/${mockUserId}/tokens`);
        }));
    });
});
