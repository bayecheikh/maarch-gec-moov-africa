import { ComponentFixture, fakeAsync, TestBed, tick } from '@angular/core/testing';
import { HttpClientTestingModule } from '@angular/common/http/testing';
import { TranslateLoader, TranslateModule, TranslateService } from '@ngx-translate/core';
import { MatLegacyDialog as MatDialog } from '@angular/material/legacy-dialog';
import { HeaderService } from '@service/header.service';
import { NotificationService } from '@service/notification/notification.service';
import { ScriptInjectorService } from '@service/script-injector.service';
import { Router } from '@angular/router';
import { FunctionsService } from '@service/functions.service';
import { AuthService } from '@service/auth.service';
import { Observable, of } from 'rxjs';
import { By } from '@angular/platform-browser';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { MatIconModule } from '@angular/material/icon';
import { MatButtonModule } from '@angular/material/button';
import { DebugElement } from '@angular/core';
import { OnlyOfficeOptionsInterface } from '@models/editor-manager.model';
import { EcplOnlyofficeViewerComponent } from '@plugins/onlyoffice-api-js/onlyoffice-viewer.component';
import * as langFrJson from '@langs/lang-fr.json';

class FakeLoader implements TranslateLoader {
    getTranslation(): Observable<any> {
        return of({ lang: langFrJson });
    }
}

describe('EcplOnlyofficeViewerComponent DOM Tests', () => {
    let component: EcplOnlyofficeViewerComponent;
    let fixture: ComponentFixture<EcplOnlyofficeViewerComponent>;
    let headerService: jasmine.SpyObj<HeaderService>;
    let functionsService: jasmine.SpyObj<FunctionsService>;
    let scriptInjectorService: jasmine.SpyObj<ScriptInjectorService>;
    let authService: jasmine.SpyObj<AuthService>;
    let router: jasmine.SpyObj<Router>;
    let notifyService: jasmine.SpyObj<NotificationService>;
    let dialogMock: jasmine.SpyObj<MatDialog>;
    let el: DebugElement;
    let translateService: TranslateService;


    // Mock data for component inputs
    const mockParams: OnlyOfficeOptionsInterface = {
        objectId: 1,
        objectType: 'resourceModification',
        docUrl: 'rest/onlyOffice/resourceModification',
        dataToMerge: {}
    };

    const mockFile = {
        name: 'test',
        format: 'docx',
        type: null,
        contentMode: 'base64',
        content: null,
        src: null
    };

    const mockUser = {
        id: 1,
        firstname: 'John',
        lastname: 'Doe'
    };

    beforeEach(async () => {
        // Create spy objects for all dependencies
        headerService = jasmine.createSpyObj('HeaderService', ['sideNavLeft'], {
            user: mockUser,
            hideSideBar: false
        });
        functionsService = jasmine.createSpyObj('FunctionsService', ['empty']);
        scriptInjectorService = jasmine.createSpyObj('ScriptInjectorService', ['loadJsScript']);
        authService = jasmine.createSpyObj('AuthService', ['resetTimer']);
        router = jasmine.createSpyObj('Router', [], {
            url: '/document/view'
        });
        notifyService = jasmine.createSpyObj('NotificationService', ['error', 'handleErrors']);
        dialogMock = jasmine.createSpyObj('MatDialog', ['open']);

        functionsService.empty.and.callFake((value) => {
            return value === null || value === undefined || value === '';
        });

        dialogMock.open.and.returnValue({
            afterClosed: () => of('ok'),
            close: jasmine.createSpy('close'),
            updateSize: jasmine.createSpy('updateSize'),
            updatePosition: jasmine.createSpy('updatePosition'),
            addPanelClass: jasmine.createSpy('addPanelClass'),
            removePanelClass: jasmine.createSpy('removePanelClass')
        } as any);

        // Define jQuery mock for DOM manipulation tests
        (window as any).$ = jasmine.createSpy('$').and.returnValue({
            css: jasmine.createSpy('css')
        });

        // Configure TestBed with necessary modules and services
        await TestBed.configureTestingModule({
            declarations: [EcplOnlyofficeViewerComponent],
            imports: [
                HttpClientTestingModule,
                BrowserAnimationsModule,
                MatIconModule,
                MatButtonModule,
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: FakeLoader },
                }),
            ],
            providers: [
                { provide: HeaderService, useValue: headerService },
                { provide: FunctionsService, useValue: functionsService },
                { provide: ScriptInjectorService, useValue: scriptInjectorService },
                { provide: AuthService, useValue: authService },
                { provide: Router, useValue: router },
                { provide: MatDialog, useValue: dialogMock },
                { provide: NotificationService, useValue: notifyService },
                TranslateService
            ]
        }).compileComponents();

        // Set lang
        translateService = TestBed.inject(TranslateService);
        translateService.use('fr');

        fixture = TestBed.createComponent(EcplOnlyofficeViewerComponent);
        component = fixture.componentInstance;
        el = fixture.debugElement;

        // Configure component inputs
        component.params = mockParams;
        component.file = { ...mockFile };
    });

    /**
     * Test: Verify the presence and state of the loading message
     *
     * This test checks that:
     * 1. The loading message is displayed when loading=true
     * 2. The loading message is not displayed when loading=false
     * 3. The translation pipe is correctly applied to the message
    */
    it('should show loading message only when loading is true', fakeAsync(() => {
        // Set loading to true and trigger change detection
        component.loading = true;

        fixture.detectChanges();
        tick();

        // Check that loading element is visible
        let loadingElement = el.query(By.css('div[class="loading"]'));
        expect(loadingElement).toBeTruthy();
        expect(loadingElement.nativeElement.textContent).toContain(component.translate.instant('lang.checkOnlyofficeServer'));

        // Set loading to false and trigger change detection
        component.loading = false;
        fixture.detectChanges();

        // Check that loading element is no longer visible
        loadingElement = el.query(By.css('div[class="loading"]'));
        expect(loadingElement).toBeNull();
    }));

    /**
     * Test: Verify the close editor button visibility and functionality
     *
     * This test checks that:
     * 1. The close button is visible when hideCloseEditor=false
     * 2. The close button is hidden when hideCloseEditor=true
     * 3. The button is disabled when loading=true
     * 4. The quit method is called when the button is clicked
    */
    it('should handle the close editor button correctly', fakeAsync(() => {
        // Initially show close button
        component.hideCloseEditor = false;
        component.loading = false;

        fixture.detectChanges();
        tick();

        // Verify button is present and enabled
        let closeButton = el.query(By.css('.onlyofficeButton_fullscreen'));
        expect(closeButton).toBeTruthy();
        expect(closeButton.nativeElement.disabled).toBeFalse();

        // Test button click functionality
        spyOn(component, 'quit');
        closeButton.triggerEventHandler('click', null);
        expect(component.quit).toHaveBeenCalled();

        // Test button disabled state
        component.loading = true;

        fixture.detectChanges();
        tick();

        closeButton = el.query(By.css('.onlyofficeButton_fullscreen'));
        expect(closeButton.nativeElement.disabled).toBeTrue();

        // Test button hidden state
        component.hideCloseEditor = true;

        fixture.detectChanges();
        tick();

        closeButton = el.query(By.css('.onlyofficeButton_fullscreen'));
        expect(closeButton).toBeNull();
    }));

    /**
     * Test: Verify the fullscreen button visibility, state, and functionality
     *
     * This test checks that:
     * 1. The fullscreen button is visible when hideFullscreenButton=false
     * 2. The fullscreen button is hidden when hideFullscreenButton=true
     * 3. The button is disabled when loading=true
     * 4. The button shows the correct icon based on fullscreen state
     * 5. The openFullscreen method is called when the button is clicked
     * 6. The button has appropriate CSS classes based on state
     */
    it('should handle the fullscreen button correctly', fakeAsync(() => {
        // Initially show fullscreen button
        component.hideFullscreenButton = false;
        component.loading = false;
        component.fullscreenMode = false;

        fixture.detectChanges();
        tick();

        // Verify button is present and enabled with correct icon
        let fullscreenButton = el.query(By.css('.onlyofficeButton_close'));
        expect(fullscreenButton).toBeTruthy();
        expect(fullscreenButton.nativeElement.disabled).toBeFalse();

        // Check expand icon when not in fullscreen
        const expandIcon = fullscreenButton.query(By.css('.fa-expand'));
        expect(expandIcon).toBeTruthy();

        // Test fullscreen mode icon change
        component.fullscreenMode = true;

        fixture.detectChanges();
        tick();

        fullscreenButton = el.query(By.css('.onlyofficeButton_close'));
        const compressIcon = fullscreenButton.query(By.css('.fa-compress'));
        expect(compressIcon).toBeTruthy();

        // Test button click functionality
        spyOn(component, 'openFullscreen');
        fullscreenButton.triggerEventHandler('click', null);
        expect(component.openFullscreen).toHaveBeenCalled();

        // Test button disabled state
        component.loading = true;

        fixture.detectChanges();
        tick();

        fullscreenButton = el.query(By.css('.onlyofficeButton_close'));
        expect(fullscreenButton.nativeElement.disabled).toBeTrue();

        // Test button hidden state
        component.hideFullscreenButton = true;

        fixture.detectChanges();
        tick();
        fullscreenButton = el.query(By.css('.onlyofficeButton_close'));
        expect(fullscreenButton).toBeNull();

        // Test CSS classes for fullscreen state
        component.hideFullscreenButton = false;
        component.fullscreenMode = true;

        fixture.detectChanges();
        tick();

        fullscreenButton = el.query(By.css('.onlyofficeButton_close'));
        expect(fullscreenButton.nativeElement.classList.contains('fullScreen')).toBeTrue();
    }));

    /**
     * Test: Verify the collapse button visibility, state, and functionality
     *
     * This test checks that:
     * 1. The collapse button is visible when hideCollapseButton=false
     * 2. The collapse button is hidden when hideCollapseButton=true
     * 3. The button is disabled when loading=true
     * 4. Clicking the button toggles hideButtons state
     * 5. Clicking the button calls formatAppToolsCss with correct parameters
    */
    it('should handle the collapse button correctly', fakeAsync(() => {
        // Initially show collapse button
        component.hideCollapseButton = false;
        component.loading = false;
        component.hideButtons = false;
        component.fullscreenMode = false;

        fixture.detectChanges();
        tick();

        // Verify button is present and enabled
        let collapseButton = el.query(By.css('.onlyofficeButton_hide'));
        expect(collapseButton).toBeTruthy();
        expect(collapseButton.nativeElement.disabled).toBeFalse();

        // Test button click functionality
        spyOn(component, 'formatAppToolsCss');
        collapseButton.triggerEventHandler('click', null);
        expect(component.hideButtons).toBeTrue();
        expect(component.formatAppToolsCss).toHaveBeenCalledWith('default', true);

        // Reset for next test
        component.hideButtons = false;
        (component.formatAppToolsCss as jasmine.Spy).calls.reset();
        component.fullscreenMode = true;

        fixture.detectChanges();
        tick();

        // Test button click with fullscreen mode
        collapseButton = el.query(By.css('.onlyofficeButton_hide'));
        collapseButton.triggerEventHandler('click', null);
        expect(component.hideButtons).toBeTrue();
        expect(component.formatAppToolsCss).toHaveBeenCalledWith('fullscreen', true);

        // Test button disabled state
        component.loading = true;

        fixture.detectChanges();
        tick();

        collapseButton = el.query(By.css('.onlyofficeButton_hide'));
        expect(collapseButton.nativeElement.disabled).toBeTrue();

        // Test button hidden state
        component.hideCollapseButton = true;

        fixture.detectChanges();
        tick();

        collapseButton = el.query(By.css('.onlyofficeButton_hide'));
        expect(collapseButton).toBeNull();
    }));

    /**
     * Test: Verify CSS classes applied to buttons in different states
     *
     * This test checks that:
     * 1. Button has correct CSS classes based on fullscreen mode
     * 2. Button has correct CSS classes based on hideButtons state
    */
    it('should apply correct CSS classes to buttons based on component state', fakeAsync(() => {
        // Test fullscreen state classes
        component.fullscreenMode = true;
        component.hideButtons = false;

        fixture.detectChanges();
        tick();

        const buttons = el.queryAll(By.css('.onlyofficeButton_fullscreen, .onlyofficeButton_close, .onlyofficeButton_hide'));
        buttons.forEach(button => {
            expect(button.nativeElement.classList.contains('fullScreen')).toBeTrue();
            expect(button.nativeElement.classList.contains('buttonsHide')).toBeFalse();
        });

        // Test hideButtons state classes
        component.hideButtons = true;

        fixture.detectChanges();
        tick();

        const hiddenButtons = el.queryAll(By.css('.onlyofficeButton_fullscreen, .onlyofficeButton_close, .onlyofficeButton_hide'));
        hiddenButtons.forEach(button => {
            expect(button.nativeElement.classList.contains('buttonsHide')).toBeTrue();
        });
    }));

    /**
     * Test: Verify z-index styling for buttons in indexing route
     *
     * This test checks that:
     * 1. Buttons have correct z-index when in fullscreen mode and on indexing route
     * 2. Buttons do not have special z-index in normal conditions
    */
    it('should apply correct z-index to buttons when in fullscreen mode on indexing route', fakeAsync(() => {
        // Setup router URL to include indexing
        Object.defineProperty(router, 'url', {
            get: () => '/indexing/document'
        });

        // Set fullscreen mode
        component.fullscreenMode = true;

        fixture.detectChanges();
        tick();

        // Check all buttons have z-index: 3
        const buttons = el.queryAll(By.css('.onlyofficeButton_fullscreen, .onlyofficeButton_close, .onlyofficeButton_hide'));
        buttons.forEach(button => {
            expect(button.nativeElement.style.zIndex).toBe('3');
        });

        // Change URL to non-indexing route
        Object.defineProperty(router, 'url', {
            get: () => '/document/view'
        });

        fixture.detectChanges();
        tick();

        // Check buttons do not have z-index: 3
        const normalButtons = el.queryAll(By.css('.onlyofficeButton_fullscreen, .onlyofficeButton_close, .onlyofficeButton_hide'));
        normalButtons.forEach(button => {
            expect(button.nativeElement.style.zIndex).not.toBe('3');
        });
    }));

    /**
     * Test: Verify buttons have correct titles based on state
     *
     * This test checks that:
     * 1. Close button has correct translation key for title
     * 2. Fullscreen button title changes based on fullscreen state
     * 3. Collapse button title changes based on hideButtons state
    */
    it('should display correct button titles based on component state', fakeAsync(() => {
        // Initial state setup
        component.fullscreenMode = false;
        component.hideButtons = false;

        fixture.detectChanges();
        tick();

        // Check close button title
        const closeButton = el.query(By.css('.onlyofficeButton_fullscreen'));
        expect(closeButton.nativeElement.title).toBe(component.translate.instant('lang.closeEditor'));

        // Check fullscreen button title when not in fullscreen
        const fullscreenButton = el.query(By.css('.onlyofficeButton_close'));
        expect(fullscreenButton.nativeElement.title).toBe(component.translate.instant('lang.openFullscreen'));

        // Check collapse button title when not hidden
        const collapseButton = el.query(By.css('.onlyofficeButton_hide'));
        expect(collapseButton.nativeElement.title).toBe(component.translate.instant('lang.hideTool'));

        // Change state
        component.fullscreenMode = true;
        component.hideButtons = true;

        fixture.detectChanges();
        tick();

        // Check fullscreen button title when in fullscreen
        const updatedFullscreenButton = el.query(By.css('.onlyofficeButton_close'));
        expect(updatedFullscreenButton.nativeElement.title).toBe(component.translate.instant('lang.closeFullscreen'));

        // Check collapse button title when hidden
        const updatedCollapseButton = el.query(By.css('.onlyofficeButton_hide'));
        expect(updatedCollapseButton.nativeElement.title).toBe(component.translate.instant('lang.showTool'));
    }));


    it('should use correct Material Design components for buttons', fakeAsync(() => {
        fixture.detectChanges();
        tick();

        // Check all buttons are mat-mini-fab
        const buttons = el.queryAll(By.css('button'));

        buttons.forEach(button => {
            expect(button.nativeElement.classList).toContain('mdc-fab--mini');
        });

        component.fullscreenMode = false;

        fixture.detectChanges();
        tick();

        const expandIcon = el.query(By.css('.onlyofficeButton_close mat-icon'));
        expect(expandIcon).toBeTruthy();
        expect(expandIcon.attributes.class).toContain('fa-expand')

        component.fullscreenMode = true;

        fixture.detectChanges();
        tick();

        const compressIcon = el.query(By.css('.onlyofficeButton_close mat-icon'));
        expect(compressIcon).toBeTruthy();
        expect(expandIcon.attributes.class).toContain('fa-compress');
    }));
});