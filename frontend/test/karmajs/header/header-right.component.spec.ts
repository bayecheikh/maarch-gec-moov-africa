import { ComponentFixture, fakeAsync, flush, TestBed, tick } from '@angular/core/testing';
import { HttpClientTestingModule } from '@angular/common/http/testing';
import { TranslateLoader, TranslateModule, TranslateService } from '@ngx-translate/core';
import { HeaderService } from '@service/header.service';
import { MatLegacyDialog as MatDialog, MatLegacyDialogModule as MatDialogModule } from '@angular/material/legacy-dialog';
import { MatLegacyInputModule as MatInputModule } from '@angular/material/legacy-input';
import { MatIconModule } from '@angular/material/icon';
import { MatMenuModule } from '@angular/material/menu';
import { MatButtonModule } from '@angular/material/button';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AppService } from '@service/app.service';
import { Menu, PrivilegeService } from '@service/privileges.service';
import { FunctionsService } from '@service/functions.service';
import { AuthService } from '@service/auth.service';
import { LocalStorageService } from '@service/local-storage.service';
import { SessionStorageService } from '@service/session-storage.service';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { Observable, of } from 'rxjs';
import { RouterTestingModule } from '@angular/router/testing';
import { AboutUsComponent } from '@appRoot/about-us.component';
import { RegisteredMailImportComponent } from '@appRoot/registeredMail/import/registered-mail-import.component';
import { HeaderRightComponent } from "@appRoot/header/header-right.component";
import * as langFrJson from "@langs/lang-fr.json";
import { NO_ERRORS_SCHEMA, Pipe, PipeTransform } from "@angular/core";

@Pipe({ name: 'sortBy' })
class SortByPipeMock implements PipeTransform {
    transform(array: any[], field: string): any[] {
        if (!array || !field) {
            return array;
        }
        return array.sort((a, b) => {
            if (a[field] < b[field]) return -1;
            if (a[field] > b[field]) return 1;
            return 0;
        });
    }
}

class FakeLoader implements TranslateLoader {
    getTranslation(): Observable<any> {
        return of({ lang: langFrJson });
    }
}

describe('HeaderRightComponent', () => {
    // TestFixture (TF) setup
    let component: HeaderRightComponent;
    let fixture: ComponentFixture<HeaderRightComponent>;

    // Mock services
    let headerServiceMock: jasmine.SpyObj<HeaderService>;
    let appServiceMock: jasmine.SpyObj<AppService>;
    let privilegeServiceMock: jasmine.SpyObj<PrivilegeService>;
    let functionsServiceMock: jasmine.SpyObj<FunctionsService>;
    let authServiceMock: jasmine.SpyObj<AuthService>;
    let localStorageMock: jasmine.SpyObj<LocalStorageService>;
    let sessionStorageMock: jasmine.SpyObj<SessionStorageService>;
    let dialogMock: jasmine.SpyObj<MatDialog>;
    let translateService: TranslateService;
    let navigateSpy: any;
    let router: Router;

    const mockUser = {
        firstname: 'Barbara',
        lastname: 'BAIN',
        groups: [{ group_desc: 'ADMIN_N1' }],
        entities: [{ entity_label: 'PJS' }]
    };

    const mockMenus: Menu[] = [
        {
            'id': 'adv_search_mlb',
            'label': 'lang.search',
            'comment': 'lang.search',
            'route': '/search',
            'style': 'fa fa-search',
            'unit': 'application',
            'shortcut': true
        },
        {
            'id': 'entities_print_sep_mlb',
            'label': 'lang.entitiesSeparator',
            'comment': 'lang.entitiesSeparator',
            'route': '/separators/print',
            'style': 'fa fa-print',
            'unit': 'entities',
            'shortcut': false
        },
        {
            'id': 'registered_mail_mass_import',
            'label': 'lang.importRegisteredMails',
            'comment': 'lang.importRegisteredMails',
            'route': 'RegisteredMailImportComponent__modal',
            'style': 'fas fa-dolly-flatbed',
            'unit': 'registeredMails',
            'shortcut': false
        }
    ];

    beforeEach(async () => {
        // Create spies for all services
        headerServiceMock = jasmine.createSpyObj('HeaderService', [], { user: mockUser });
        appServiceMock = jasmine.createSpyObj('AppService', ['getViewMode']);
        privilegeServiceMock = jasmine.createSpyObj('PrivilegeService', ['getCurrentUserMenus']);
        functionsServiceMock = jasmine.createSpyObj('FunctionsService', ['empty']);
        authServiceMock = jasmine.createSpyObj('AuthService', ['logoutUser', 'canLogOut']);
        localStorageMock = jasmine.createSpyObj('LocalStorageService', ['get', 'save']);
        sessionStorageMock = jasmine.createSpyObj('SessionStorageService', ['get', 'save']);
        dialogMock = jasmine.createSpyObj('MatDialog', ['open']);

        // Configure mock return values
        privilegeServiceMock.getCurrentUserMenus.and.returnValue(mockMenus);
        functionsServiceMock.empty.and.callFake((obj) => !obj || Object.keys(obj).length === 0);
        appServiceMock.getViewMode.and.returnValue(false);
        authServiceMock.canLogOut.and.returnValue(true);
        localStorageMock.get.and.returnValue(null);
        dialogMock.open.and.returnValue({
            afterClosed: () => of(true),
            close: jasmine.createSpy('close'),
            updateSize: jasmine.createSpy('updateSize'),
            updatePosition: jasmine.createSpy('updatePosition'),
            addPanelClass: jasmine.createSpy('addPanelClass'),
            removePanelClass: jasmine.createSpy('removePanelClass')
        } as any);

        await TestBed.configureTestingModule({
            declarations: [HeaderRightComponent, SortByPipeMock],
            imports: [
                HttpClientTestingModule,
                MatDialogModule,
                MatInputModule,
                MatIconModule,
                MatMenuModule,
                MatButtonModule,
                FormsModule,
                BrowserAnimationsModule,
                RouterTestingModule,
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: FakeLoader },
                }),
            ],
            providers: [
                { provide: HeaderService, useValue: headerServiceMock },
                { provide: AppService, useValue: appServiceMock },
                { provide: PrivilegeService, useValue: privilegeServiceMock },
                { provide: FunctionsService, useValue: functionsServiceMock },
                { provide: AuthService, useValue: authServiceMock },
                { provide: LocalStorageService, useValue: localStorageMock },
                { provide: SessionStorageService, useValue: sessionStorageMock },
                { provide: MatDialog, useValue: dialogMock },
                Router,
                TranslateService
            ],
            schemas: [NO_ERRORS_SCHEMA] // Ignore unknown elements and attributes
        }).compileComponents();

        // Set lang
        translateService = TestBed.inject(TranslateService);
        translateService.use('fr');
    });

    beforeEach(() => {
        // Check that the navigation was triggered
        router = TestBed.inject(Router);
        navigateSpy = spyOn(router, 'navigate');

        fixture = TestBed.createComponent(HeaderRightComponent);
        component = fixture.componentInstance;
        fixture.detectChanges();
    });

    // TestUnit (TU) tests
    describe('Initialization', () => {
        it('should create the component', () => {
            expect(component).toBeTruthy();
        });

        it('should initialize with default values', () => {
            expect(component.searchTarget).toBe('');
            expect(component.hideSearch).toBe(true);
            expect(component.selectedQuickSearchTarget).toBe('searchTerm');
            expect(component.menus).toEqual(mockMenus);
        });

        it('should load quick search target from localStorage if available', () => {
            localStorageMock.get.and.returnValue('fullText');
            component.ngOnInit();
            expect(component.selectedQuickSearchTarget).toBe('fullText');
        });

        it('should define quick search targets correctly', () => {
            expect(component.quickSearchTargets.length).toBe(4);
            expect(component.quickSearchTargets.map(t => t.id)).toContain('searchTerm');
            expect(component.quickSearchTargets.map(t => t.id)).toContain('recipients');
            expect(component.quickSearchTargets.map(t => t.id)).toContain('senders');
            expect(component.quickSearchTargets.map(t => t.id)).toContain('fullText');
        });
    });

    describe('Menu Navigation', () => {
        it('should open IndexingGroupModalComponent when indexing shortcut has multiple groups', () => {
            const advSearchMlbShortcut = mockMenus.find(menu => menu.id === 'adv_search_mlb');
            component.gotToMenu(advSearchMlbShortcut);
        });

        it('should navigate to route for regular shortcuts', () => {
            const searchShortcut: Menu = mockMenus.find(menu => menu.id === 'adv_search_mlb');

            component.gotToMenu(searchShortcut);

            expect(navigateSpy).toHaveBeenCalledWith([searchShortcut.route]);
        });

        it('should open RegisteredMailImportComponent for special routes', () => {
            const registeredMailShortcut = mockMenus.find(menu => menu.id === 'registered_mail_mass_import');
            component.gotToMenu(registeredMailShortcut);
            expect(dialogMock.open).toHaveBeenCalledWith(
                RegisteredMailImportComponent,
                jasmine.objectContaining({
                    disableClose: true,
                    width: '99vw',
                    maxWidth: '99vw',
                    panelClass: 'maarch-full-height-modal'
                })
            );
        });
    });

    describe('Search Functionality', () => {
        it('should toggle search visibility when showSearchInput is called', fakeAsync(() => {
            const initialValue = component.hideSearch;

            fixture.detectChanges();
            tick();

            component.showSearchInput();
            expect(component.hideSearch).toBe(!initialValue);
        }));

        it('should navigate to search with correct params when goTo is called', fakeAsync(() => {
            component.searchTarget = 'test query';
            component.selectedQuickSearchTarget = 'fullText';

            fixture.detectChanges();
            tick();

            component.goTo();
            expect(navigateSpy).toHaveBeenCalledWith(
                ['/search'],
                { queryParams: { target: 'fullText', value: 'test query' } }
            );
        }));

        it('should return correct search bar visibility based on privileges and current route', () => {
            component.router= jasmine.createSpyObj('Router', ['navigate'], {
                get url() {
                    return '/home';
                }
            });

            expect(component.hideSearchBar()).toBeTrue();


            // simulate being on a search page
            component.router = jasmine.createSpyObj('Router', ['navigate'], {
                get url() {
                    return '/search';
                }
            });

            expect(component.hideSearchBar()).toBeFalse();

            // simulate missing privilege
            privilegeServiceMock.getCurrentUserMenus.and.returnValue(mockMenus.filter(menu => menu.id !== 'adv_search_mlb'));
            expect(component.hideSearchBar()).toBeFalse();
        });
    });

    describe('Quick Search Target Management', () => {
        it('should set target and save to localStorage', () => {
            component.setTarget('recipients');
            expect(component.selectedQuickSearchTarget).toBe('recipients');
            expect(localStorageMock.save).toHaveBeenCalledWith('quickSearchTarget', 'recipients');
        });

        it('should return correct target description', () => {
            component.selectedQuickSearchTarget = 'senders';
            expect(component.getTargetDesc()).toBe(component.quickSearchTargets.find(item => item.id === 'senders').desc);
        });

        it('should return correct target icon', () => {
            component.selectedQuickSearchTarget = 'fullText';
            expect(component.getTargetIcon()).toBe(component.quickSearchTargets.find(item => item.id === 'fullText').icon);
        });
    });

    describe('User Information and Utilities', () => {
        it('should open AboutUsComponent when openAboutModal is called', () => {
            component.openAboutModal();
            expect(dialogMock.open).toHaveBeenCalledWith(
                AboutUsComponent,
                jasmine.objectContaining({
                    panelClass: 'maarch-modal',
                    autoFocus: false,
                    disableClose: false
                })
            );
        });

        it('should show logout button based on auth service', () => {
            authServiceMock.canLogOut.and.returnValue(true);
            expect(component.showLogout()).toBeTrue();

            authServiceMock.canLogOut.and.returnValue(false);
            expect(component.showLogout()).toBeFalse();
        });
    });

    // TestFixture (TF) integration tests
    describe('Component Integration', () => {
        it('should properly initialize with services', () => {
            expect(privilegeServiceMock.getCurrentUserMenus).toHaveBeenCalled();
            expect(localStorageMock.get).toHaveBeenCalledWith('quickSearchTarget');
        });

        it('should correctly display user information', () => {
            const element = fixture.debugElement.nativeElement;
            expect(element.textContent).toContain(mockUser.firstname);
            expect(element.textContent).toContain(mockUser.lastname.toUpperCase());
        });

        it('should display quick search targets menu when button is clicked and toggle quick search target', fakeAsync(() => {
            localStorageMock.save.and.callFake((key, value) => {
                localStorageMock.get.and.returnValue(value);
            });

            const nativeElement = fixture.nativeElement;

            let selectedQuickSearchTarget = nativeElement.querySelector('button[name="selectedQuickSearchTarget"]');
            expect(selectedQuickSearchTarget.value).toBe('searchTerm');

            const chevronButton = nativeElement.querySelector('button[name="chevron-down"]');

            chevronButton.click();

            fixture.detectChanges();
            tick();

            const quickSearchTargetsMenu: Element = document.getElementsByClassName('quickSearchTargets')[0];
            const menuContent = quickSearchTargetsMenu.querySelector('.mat-mdc-menu-content');
            const menuItems = menuContent.querySelectorAll('button');
            expect(menuItems.length).toBe(4);

            menuItems[3].click();

            fixture.detectChanges();

            selectedQuickSearchTarget = nativeElement.querySelector('button[name="selectedQuickSearchTarget"]');
            expect(selectedQuickSearchTarget.value).toEqual('fullText');

            flush();

            expect(component.localStorage.get('quickSearchTarget')).toEqual('fullText');
        }));

        it('should display menu when button is clicked', fakeAsync(() => {
            const nativeElement = fixture.nativeElement;

            const menu = nativeElement.querySelector('button[name="menu"]');
            menu.click();

            fixture.detectChanges();
            tick();

            const headerMaarchShortcut: Element = document.getElementsByClassName('headerMaarchShortcut')[0];
            const menuContent = headerMaarchShortcut.querySelector('.mat-mdc-menu-content');
            const menuItems = menuContent.querySelectorAll('button');
            expect(menuItems.length).toBe(3);
        }));
    });
});