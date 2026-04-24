import { ComponentFixture, TestBed, fakeAsync, tick } from '@angular/core/testing';
import { TranslateService, TranslateModule, TranslateLoader } from '@ngx-translate/core';
import { HttpClientTestingModule } from '@angular/common/http/testing';
import { MatLegacyDialog as MatDialog } from '@angular/material/legacy-dialog';
import { Router } from '@angular/router';
import { HeaderService } from '@service/header.service';
import { DomSanitizer } from '@angular/platform-browser';
import { NotificationService } from '@service/notification/notification.service';
import { FunctionsService } from '@service/functions.service';
import { AuthService } from '@service/auth.service';
import { MatIconModule } from '@angular/material/icon';
import { By } from '@angular/platform-browser';
import { Observable, of } from 'rxjs';
import { NO_ERRORS_SCHEMA } from '@angular/core';
import { CollaboraOnlineViewerComponent } from '@plugins/collabora-online/collabora-online-viewer.component';
import * as langFrJson from '@langs/lang-fr.json';

class FakeLoader implements TranslateLoader {
    getTranslation(): Observable<any> {
        return of({ lang: langFrJson });
    }
}

describe('CollaboraOnlineViewerComponent DOM Tests', () => {
    let component: CollaboraOnlineViewerComponent;
    let fixture: ComponentFixture<CollaboraOnlineViewerComponent>;
    let mockHeaderService: jasmine.SpyObj<HeaderService>;
    let mockDialog: jasmine.SpyObj<MatDialog>;
    let mockNotify: jasmine.SpyObj<NotificationService>;
    let mockRouter: jasmine.SpyObj<Router>;
    let mockAuthService: jasmine.SpyObj<AuthService>;
    let mockFunctionsService: jasmine.SpyObj<FunctionsService>;
    let mockSanitizer: jasmine.SpyObj<DomSanitizer>;
    let translateService: TranslateService;

    beforeEach(async () => {
        // Create mock services
        mockHeaderService = jasmine.createSpyObj('HeaderService', ['sideNavLeft'], {
            sideNavLeft: { open: jasmine.createSpy('open'), close: jasmine.createSpy('close') },
            hideSideBar: false
        });

        mockDialog = jasmine.createSpyObj('MatDialog', ['open']);
        mockNotify = jasmine.createSpyObj('NotificationService', ['error', 'handleErrors']);
        mockRouter = jasmine.createSpyObj('Router', [], { url: '/test' });
        mockAuthService = jasmine.createSpyObj('AuthService', ['resetTimer']);
        mockFunctionsService = jasmine.createSpyObj('FunctionsService', ['empty']);
        mockSanitizer = jasmine.createSpyObj('DomSanitizer', ['bypassSecurityTrustResourceUrl']);

        await TestBed.configureTestingModule({
            declarations: [
                CollaboraOnlineViewerComponent
            ],
            imports: [
                HttpClientTestingModule,
                MatIconModule,
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: FakeLoader },
                }),
            ],
            providers: [
                { provide: HeaderService, useValue: mockHeaderService },
                { provide: MatDialog, useValue: mockDialog },
                { provide: NotificationService, useValue: mockNotify },
                { provide: Router, useValue: mockRouter },
                { provide: AuthService, useValue: mockAuthService },
                { provide: FunctionsService, useValue: mockFunctionsService },
                { provide: DomSanitizer, useValue: mockSanitizer },
                TranslateService
            ],
            schemas: [NO_ERRORS_SCHEMA] // Ignore unknown elements and attributes
        }).compileComponents();

        // Set lang
        translateService = TestBed.inject(TranslateService);
        translateService.use('fr');

        fixture = TestBed.createComponent(CollaboraOnlineViewerComponent);
        component = fixture.componentInstance;

        // Setup default component properties
        component.file = { format: 'docx' };
        component.params = {
            objectId: 1,
            objectType: 'resourceModification',
        };
        component.allowedExtension = ['docx', 'xlsx', 'pptx'];

        // Mock sanitizer to return the URL as is
        mockSanitizer.bypassSecurityTrustResourceUrl.and.callFake(url => url);

        // Mock functions service to return false for empty checks
        mockFunctionsService.empty.and.returnValue(false);

        // Setup dialog mock to return an observable when opened
        mockDialog.open.and.returnValue({
            afterClosed: () => of('ok')
        } as any);

        fixture.detectChanges();
    });

    // Test if loading message is displayed correctly
    it('should display loading message when loading is true', fakeAsync(() => {
        // Set loading to true
        component.loading = true;

        fixture.detectChanges();
        tick();

        // Check if loading div is visible
        const loadingElement = fixture.debugElement.query(By.css('div'));
        expect(loadingElement).toBeTruthy();
        expect(loadingElement.styles['display']).toBe('block');
    }));

    // Test if close button is visible based on hideCloseEditor flag
    it('should show close button when hideCloseEditor is false', fakeAsync(() => {
        // Set required properties
        component.hideCloseEditor = false;
        component.loading = false;

        fixture.detectChanges();
        tick();

        // Check if close button is visible
        const closeButton = fixture.debugElement.query(By.css('.collaboraOnlineButton_fullscreen'));
        expect(closeButton).toBeTruthy();
    }));

    // Test if close button is hidden when hideCloseEditor is true
    it('should hide close button when hideCloseEditor is true', fakeAsync(() => {
        // Set hideCloseEditor to true
        component.hideCloseEditor = true;

        fixture.detectChanges();
        tick();

        // Check if close button is hidden
        const closeButton = fixture.debugElement.query(By.css('.collaboraOnlineButton_fullscreen'));
        expect(closeButton).toBeFalsy();
    }));

    // Test if fullscreen button is visible based on hideFullscreenButton flag
    it('should show fullscreen button when hideFullscreenButton is false', fakeAsync(() => {
        // Set required properties
        component.hideFullscreenButton = false;
        component.loading = false;

        fixture.detectChanges();
        tick();

        // Check if fullscreen button is visible
        const fullscreenButton = fixture.debugElement.query(By.css('.collaboraOnlineButton_close'));
        expect(fullscreenButton).toBeTruthy();
    }));

    // Test if fullscreen button is hidden when hideFullscreenButton is true
    it('should hide fullscreen button when hideFullscreenButton is true', fakeAsync(() => {
        // Set hideFullscreenButton to true
        component.hideFullscreenButton = true;

        fixture.detectChanges();
        tick();

        // Check if fullscreen button is hidden
        const fullscreenButton = fixture.debugElement.query(By.css('.collaboraOnlineButton_close'));
        expect(fullscreenButton).toBeFalsy();
    }));

    // Test if collapse button is visible based on hideCollapseButton flag
    it('should show collapse button when hideCollapseButton is false', fakeAsync(() => {
        // Set required properties
        component.hideCollapseButton = false;
        component.loading = false;

        fixture.detectChanges();
        tick();

        // Check if collapse button is visible
        const collapseButton = fixture.debugElement.query(By.css('.collaboraOnlineButton_hide'));
        expect(collapseButton).toBeTruthy();
    }));

    // Test if collapse button is hidden when hideCollapseButton is true
    it('should hide collapse button when hideCollapseButton is true', fakeAsync(() => {
        // Set hideCollapseButton to true
        component.hideCollapseButton = true;

        fixture.detectChanges();
        tick();

        // Check if collapse button is hidden
        const collapseButton = fixture.debugElement.query(By.css('.collaboraOnlineButton_hide'));
        expect(collapseButton).toBeFalsy();
    }));

    // Test if iframe is not displayed when editorUrl is empty
    it('should not display iframe when editorUrl is empty', fakeAsync(() => {
        // Set editorUrl to empty string
        component.editorUrl = '';

        fixture.detectChanges();
        tick();

        // Check if iframe is not visible
        const iframe = fixture.debugElement.query(By.css('iframe'));
        expect(iframe).toBeFalsy();
    }));

    // Test the classes on buttons when in fullscreen mode
    it('should apply fullScreen class to buttons when in fullscreen mode', fakeAsync(() => {
        // Set properties for fullscreen mode
        component.hideCloseEditor = false;
        component.hideFullscreenButton = false;
        component.hideCollapseButton = false;
        component.fullscreenMode = true;
        component.loading = false;

        fixture.detectChanges();
        tick();

        // Check if buttons have fullScreen class
        const closeButton = fixture.debugElement.query(By.css('.collaboraOnlineButton_fullscreen'));
        const fullscreenButton = fixture.debugElement.query(By.css('.collaboraOnlineButton_close'));
        const collapseButton = fixture.debugElement.query(By.css('.collaboraOnlineButton_hide'));

        expect(closeButton.classes['fullScreen']).toBeTruthy();
        expect(fullscreenButton.classes['fullScreen']).toBeTruthy();
        expect(collapseButton.classes['fullScreen']).toBeTruthy();
    }));

    // Test the classes on buttons when hideButtons is true
    it('should apply buttonsHide class to buttons when hideButtons is true', fakeAsync(() => {
        // Set properties for hidden buttons
        component.hideCloseEditor = false;
        component.hideFullscreenButton = false;
        component.hideCollapseButton = false;
        component.hideButtons = true;
        component.loading = false;

        fixture.detectChanges();
        tick();

        // Check if buttons have buttonsHide class
        const closeButton = fixture.debugElement.query(By.css('.collaboraOnlineButton_fullscreen'));
        const fullscreenButton = fixture.debugElement.query(By.css('.collaboraOnlineButton_close'));
        const collapseButton = fixture.debugElement.query(By.css('.collaboraOnlineButton_hide'));

        expect(closeButton.classes['buttonsHide']).toBeTruthy();
        expect(fullscreenButton.classes['buttonsHide']).toBeTruthy();
        expect(collapseButton.classes['buttonsHide']).toBeTruthy();
    }));

    // Test if the correct icon is shown in fullscreen button based on fullscreenMode
    it('should display correct icon in fullscreen button based on fullscreen mode', fakeAsync(() => {
        // Set properties for testing both modes
        component.hideFullscreenButton = false;
        component.loading = false;

        // Test normal mode (expand icon)
        component.fullscreenMode = false;

        fixture.detectChanges();
        tick();

        let fullscreenIcon = fixture.debugElement.query(By.css('.collaboraOnlineButton_close .fas'));
        expect(fullscreenIcon.classes['fa-expand']).toBeTruthy();
        expect(fullscreenIcon.classes['fa-compress']).toBeFalsy();

        // Test fullscreen mode (compress icon)
        component.fullscreenMode = true;
        fixture.detectChanges();
        fullscreenIcon = fixture.debugElement.query(By.css('.collaboraOnlineButton_close .fas'));
        expect(fullscreenIcon.classes['fa-expand']).toBeFalsy();
        expect(fullscreenIcon.classes['fa-compress']).toBeTruthy();
    }));

    // Test button click handlers
    it('should call quit method when close button is clicked', fakeAsync(() => {
        // Spy on component quit method
        spyOn(component, 'quit');

        // Setup button to be visible
        component.hideCloseEditor = false;
        component.loading = false;

        fixture.detectChanges();
        tick();

        // Trigger click event
        const closeButton = fixture.debugElement.query(By.css('.collaboraOnlineButton_fullscreen'));
        closeButton.triggerEventHandler('click', null);

        expect(component.quit).toHaveBeenCalled();
    }));

    it('should call openFullscreen method when fullscreen button is clicked', fakeAsync(() => {
        // Spy on component openFullscreen method
        spyOn(component, 'openFullscreen');

        // Setup button to be visible
        component.hideFullscreenButton = false;
        component.loading = false;

        fixture.detectChanges();
        tick();

        // Trigger click event
        const fullscreenButton = fixture.debugElement.query(By.css('.collaboraOnlineButton_close'));
        fullscreenButton.triggerEventHandler('click', null);

        expect(component.openFullscreen).toHaveBeenCalled();
    }));

    it('should toggle hideButtons and call formatAppToolsCss when collapse button is clicked', fakeAsync(() => {
        // Spy on component formatAppToolsCss method
        spyOn(component, 'formatAppToolsCss');

        // Setup initial state
        component.hideCollapseButton = false;
        component.loading = false;
        component.hideButtons = false;
        component.fullscreenMode = false;

        fixture.detectChanges();
        tick();

        // Trigger click event
        const collapseButton = fixture.debugElement.query(By.css('.collaboraOnlineButton_hide'));
        collapseButton.triggerEventHandler('click', null);

        // Check if hideButtons was toggled and formatAppToolsCss was called
        expect(component.hideButtons).toBe(true);
        expect(component.formatAppToolsCss).toHaveBeenCalledWith('default', true);
    }));

    // Test quit method with dialog flow
    it('should open confirm dialog and close editor when quit is called and confirmed', fakeAsync(() => {
        // Spy on closeEditor method
        spyOn(component, 'closeEditor');
        spyOn(component, 'formatAppToolsCss');

        // Call quit method
        component.quit();
        tick();

        // Verify dialog was opened
        expect(mockDialog.open).toHaveBeenCalled();

        // Verify closeEditor was called (mocked dialog returns 'ok')
        expect(component.closeEditor).toHaveBeenCalled();
        expect(component.formatAppToolsCss).toHaveBeenCalledWith('default');
    }));

    // Test fullscreen toggle functionality
    it('should toggle fullscreen mode and trigger appropriate actions when openFullscreen is called', () => {
        // Setup jQuery mock for iframe CSS manipulation
        const mockJQuery = jasmine.createSpy('$').and.returnValue({
            css: jasmine.createSpy('css')
        });
        (window as any).$ = mockJQuery;

        // Spy on event emitter
        spyOn(component.triggerModeModified, 'emit');
        spyOn(component.triggerFullScreen, 'emit');
        spyOn(component, 'formatAppToolsCss');

        // Test entering fullscreen mode
        component.fullscreenMode = false;
        component.openFullscreen();

        expect(component.fullscreenMode).toBe(true);
        expect(component.triggerModeModified.emit).toHaveBeenCalledWith(true);
        expect(mockHeaderService.sideNavLeft.close).toHaveBeenCalled();
        expect(component.formatAppToolsCss).toHaveBeenCalledWith('fullscreen');
        expect(component.triggerFullScreen.emit).toHaveBeenCalled();

        // Reset spies
        (component.triggerModeModified.emit as jasmine.Spy).calls.reset();
        (mockHeaderService.sideNavLeft.close as jasmine.Spy).calls.reset();
        (component.formatAppToolsCss as jasmine.Spy).calls.reset();
        (component.triggerFullScreen.emit as jasmine.Spy).calls.reset();

        // Test exiting fullscreen mode
        component.openFullscreen();

        expect(component.fullscreenMode).toBe(false);
        expect(component.triggerModeModified.emit).toHaveBeenCalledWith(false);
        expect(mockHeaderService.sideNavLeft.open).toHaveBeenCalled();
        expect(component.formatAppToolsCss).toHaveBeenCalledWith('default');
        expect(component.triggerFullScreen.emit).toHaveBeenCalled();
    });

    // Test z-index behavior for buttons when in indexing route
    it('should apply z-index 3 to buttons when in fullscreen mode and in indexing route', fakeAsync(() => {
        // Mock router URL to include 'indexing'
        Object.defineProperty(mockRouter, 'url', { value: '/indexing/123', writable: true });

        // Setup fullscreen mode
        component.fullscreenMode = true;
        component.hideCloseEditor = false;
        component.hideFullscreenButton = false;
        component.hideCollapseButton = false;
        component.loading = false;

        fixture.detectChanges();
        tick();

        // Check z-index on buttons
        const closeButton = fixture.debugElement.query(By.css('.collaboraOnlineButton_fullscreen'));
        const fullscreenButton = fixture.debugElement.query(By.css('.collaboraOnlineButton_close'));
        const collapseButton = fixture.debugElement.query(By.css('.collaboraOnlineButton_hide'));

        expect(closeButton.styles['z-index']).toBe('3');
        expect(fullscreenButton.styles['z-index']).toBe('3');
        expect(collapseButton.styles['z-index']).toBe('3');
    }));
});