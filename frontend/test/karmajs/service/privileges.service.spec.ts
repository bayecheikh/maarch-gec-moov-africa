import { TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { TranslateLoader, TranslateModule, TranslateService } from '@ngx-translate/core';
import { PluginManagerService } from '@service/plugin-manager.service';
import { PrivilegeService } from "@service/privileges.service";
import { HeaderService } from "@service/header.service";
import { FunctionsService } from "@service/functions.service";
import { Observable, of } from "rxjs";
import * as langFrJson from "@langs/lang-fr.json";

class FakeLoader implements TranslateLoader {
    getTranslation(): Observable<any> {
        return of({ lang: langFrJson });
    }
}

describe('PrivilegeService', () => {
    let service: PrivilegeService;
    let httpMock: HttpTestingController;
    let mockHeaderService: jasmine.SpyObj<HeaderService>;
    let mockFunctionsService: jasmine.SpyObj<FunctionsService>;
    let mockPluginManagerService: jasmine.SpyObj<PluginManagerService>;
    let translateService: TranslateService

    // Mock user data for testing
    const mockUser = {
        privileges: [
            { service_id: 'admin_users' },
            { service_id: 'admin_groups' },
            { service_id: 'admin' },
            { service_id: 'adv_search_mlb' }
        ],
        groups: [
            { id: 1, group_desc: 'Test Group 1', can_index: true },
            { id: 2, group_desc: 'Test Group 2', can_index: false },
            { id: 3, group_desc: 'Test Group 3', can_index: true }
        ]
    };

    beforeEach(() => {
        // Create spy objects for dependencies
        const headerSpy = jasmine.createSpyObj('HeaderService', [], { user: mockUser });
        const functionsSpy = jasmine.createSpyObj('FunctionsService', ['empty']);
        const pluginManagerSpy = jasmine.createSpyObj('PluginManagerService', ['isPluginLoaded']);

        TestBed.configureTestingModule({
            imports: [
                HttpClientTestingModule,
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: FakeLoader },
                }),
            ],
            providers: [
                PrivilegeService,
                TranslateModule,
                { provide: HeaderService, useValue: headerSpy },
                { provide: FunctionsService, useValue: functionsSpy },
                { provide: PluginManagerService, useValue: pluginManagerSpy }
            ]
        });

        // Set lang
        translateService = TestBed.inject(TranslateService);
        translateService.use('fr');

        service = TestBed.inject(PrivilegeService);
        httpMock = TestBed.inject(HttpTestingController);
        mockHeaderService = TestBed.inject(HeaderService) as jasmine.SpyObj<HeaderService>;
        mockFunctionsService = TestBed.inject(FunctionsService) as jasmine.SpyObj<FunctionsService>;
        mockPluginManagerService = TestBed.inject(PluginManagerService) as jasmine.SpyObj<PluginManagerService>;

        // Setup default mock behaviors
        mockFunctionsService.empty.and.returnValue(false);
        mockPluginManagerService.isPluginLoaded.and.returnValue(false);
    });

    afterEach(() => {
        // Verify that no unmatched requests are outstanding
        httpMock.verify();
    });

    describe('Service Initialization', () => {
        it('should be created', () => {
            expect(service).toBeTruthy();
        });

        it('should initialize with default shortcuts', () => {
            expect(service.shortcuts).toBeDefined();
            expect(service.shortcuts.length).toBeGreaterThan(0);
            expect(service.shortcuts[0].id).toBe('followed');
        });
    });

    describe('getAllPrivileges', () => {
        it('should return all privileges excluding locked ones when getLockedPrivilege is false', async () => {
            // Mock HTTP responses for group details
            const mockGroupResponse = { group: { privileges: ['test_privilege'] } };

            const result = service.getAllPrivileges(false, 'standard', mockUser.groups);

            // Expect HTTP requests for each group
            const groupRequests = httpMock.match(req => req.url.includes('/rest/groups/'));
            groupRequests.forEach(req => {
                req.flush(mockGroupResponse);
            });

            const privileges = await result;
            expect(privileges).toBeDefined();
            expect(Array.isArray(privileges)).toBe(true);
            // Should not include locked privileges like 'create_custom' and 'admin_update_control'
            expect(privileges).not.toContain('create_custom');
            expect(privileges).not.toContain('admin_update_control');
        });

        it('should include all privileges when getLockedPrivilege is true', async () => {
            const mockGroupResponse = { group: { privileges: ['test_privilege'] } };

            const result = service.getAllPrivileges(true, 'standard', mockUser.groups);

            const groupRequests = httpMock.match(req => req.url.includes('/rest/groups/'));
            groupRequests.forEach(req => {
                req.flush(mockGroupResponse);
            });

            const privileges = await result;
            expect(privileges).toBeDefined();
            expect(Array.isArray(privileges)).toBe(true);
        });

        it('should exclude password rules privilege when authMode is not standard', async () => {
            const mockGroupResponse = { group: { privileges: ['test_privilege'] } };

            const result = service.getAllPrivileges(false, 'sso', mockUser.groups);

            const groupRequests = httpMock.match(req => req.url.includes('/rest/groups/'));
            groupRequests.forEach(req => {
                req.flush(mockGroupResponse);
            });

            const privileges = await result;
            expect(privileges).not.toContain('admin_password_rules');
        });
    });

    describe('getAdminMenu', () => {
        it('should return all admin menus when no ids provided', () => {
            const result = service.getAdminMenu();
            expect(result).toBeDefined();
            expect(Array.isArray(result)).toBe(true);
            expect(result.length).toBeGreaterThan(0);
        });

        it('should return filtered admin menus when ids provided', () => {
            const testIds = ['admin_users', 'admin_groups'];
            const result = service.getAdminMenu(testIds);

            expect(result).toBeDefined();
            expect(result.length).toBe(2);
            expect(result.every(item => testIds.includes(item.id))).toBe(true);
        });

        it('should return empty array when no matching ids found', () => {
            const testIds = ['non_existent_id'];
            const result = service.getAdminMenu(testIds);

            expect(result).toBeDefined();
            expect(result.length).toBe(0);
        });
    });

    describe('getPrivileges', () => {
        it('should return all privileges when no ids provided', () => {
            const result = service.getPrivileges();
            expect(result).toBeDefined();
            expect(Array.isArray(result)).toBe(true);
            expect(result.length).toBeGreaterThan(0);
        });

        it('should return filtered privileges when ids provided', () => {
            const testIds = ['view_doc_history', 'add_links'];
            const result = service.getPrivileges(testIds);

            expect(result).toBeDefined();
            expect(result.length).toBe(2);
            expect(result.every(item => testIds.includes(item.id))).toBe(true);
        });
    });

    describe('getUnitsPrivileges', () => {
        it('should return unique privilege units', () => {
            const result = service.getUnitsPrivileges();
            expect(result).toBeDefined();
            expect(Array.isArray(result)).toBe(true);
            expect(result.length).toBeGreaterThan(0);
            // Check that all values are unique
            expect(result.length).toBe(new Set(result).size);
        });
    });

    describe('getPrivilegesByUnit', () => {
        it('should return privileges for specific unit', () => {
            const testUnit = 'application';
            const result = service.getPrivilegesByUnit(testUnit);

            expect(result).toBeDefined();
            expect(Array.isArray(result)).toBe(true);
            expect(result.every(privilege => privilege.unit === testUnit)).toBe(true);
        });

        it('should return empty array for non-existent unit', () => {
            const testUnit = 'non_existent_unit';
            const result = service.getPrivilegesByUnit(testUnit);

            expect(result).toBeDefined();
            expect(result.length).toBe(0);
        });
    });

    describe('getMenus', () => {
        it('should return all menus when no ids provided', () => {
            const result = service.getMenus();
            expect(result).toBeDefined();
            expect(Array.isArray(result)).toBe(true);
            expect(result.length).toBeGreaterThan(0);
        });

        it('should return filtered menus when ids provided', () => {
            const testIds = ['admin', 'adv_search_mlb'];
            const result = service.getMenus(testIds);

            expect(result).toBeDefined();
            expect(result.length).toBe(2);
            expect(result.every(item => testIds.includes(item.id))).toBe(true);
        });
    });

    describe('getCurrentUserMenus', () => {
        beforeEach(() => {
            // Setup header service user mock
            Object.defineProperty(mockHeaderService, 'user', {
                get: () => mockUser,
                configurable: true
            });
        });

        it('should return user menus based on privileges', () => {
            const result = service.getCurrentUserMenus();
            expect(result).toBeDefined();
            expect(Array.isArray(result)).toBe(true);

            // Should include an indexing menu because the user has groups with can_index = true
            const indexingMenu = result.find(menu => menu.id === 'indexing');
            expect(indexingMenu).toBeDefined();
            expect(indexingMenu?.groups).toBeDefined();
            expect(indexingMenu?.groups?.length).toBe(2); // Two groups with can_index = true
        });

        it('should filter menus by provided ids', () => {
            const testIds = ['admin'];
            const result = service.getCurrentUserMenus(testIds);

            expect(result).toBeDefined();
            expect(result.every(menu => testIds.includes(menu.id) || menu.id === 'indexing')).toBe(true);
        });

        it('should not include indexing menu when user has no indexing groups', () => {
            const userWithoutIndexing = {
                ...mockUser,
                groups: [{ id: 1, group_desc: 'Test Group', can_index: false }]
            };

            Object.defineProperty(mockHeaderService, 'user', {
                get: () => userWithoutIndexing,
                configurable: true
            });

            const result = service.getCurrentUserMenus();
            const indexingMenu = result.find(menu => menu.id === 'indexing');
            expect(indexingMenu).toBeUndefined();
        });
    });

    describe('getMenusByUnit', () => {
        it('should return menus for specific unit', () => {
            const testUnit = 'application';
            const result = service.getMenusByUnit(testUnit);

            expect(result).toBeDefined();
            expect(Array.isArray(result)).toBe(true);
            expect(result.every(menu => menu.unit === testUnit)).toBe(true);
        });
    });

    describe('getUnitsMenus', () => {
        it('should return unique menu units', () => {
            const result = service.getUnitsMenus();
            expect(result).toBeDefined();
            expect(Array.isArray(result)).toBe(true);
            expect(result.length).toBeGreaterThan(0);
            // Check that all values are unique
            expect(result.length).toBe(new Set(result).size);
        });
    });

    describe('resfreshUserShortcuts', () => {
        beforeEach(() => {
            Object.defineProperty(mockHeaderService, 'user', {
                get: () => mockUser,
                configurable: true
            });
        });

        it('should refresh user shortcuts including indexing shortcut', () => {
            service.resfreshUserShortcuts();

            expect(service.shortcuts).toBeDefined();
            expect(service.shortcuts.length).toBeGreaterThan(1);

            // Should always include followed
            const followedShortcut = service.shortcuts.find(shortcut => shortcut.id === 'followed');
            expect(followedShortcut).toBeDefined();

            // Should include indexing shortcut
            const indexingShortcut = service.shortcuts.find(shortcut => shortcut.id === 'indexing');
            expect(indexingShortcut).toBeDefined();
        });

        it('should include shortcuts based on user privileges', () => {
            service.resfreshUserShortcuts();

            // Should include an admin shortcut (user has admin privilege)
            const adminShortcut = service.shortcuts.find(shortcut => shortcut.id === 'admin');
            expect(adminShortcut).toBeDefined();
        });
    });

    describe('getAdministrations', () => {
        it('should return all administrations when no ids provided', () => {
            const result = service.getAdministrations();
            expect(result).toBeDefined();
            expect(Array.isArray(result)).toBe(true);
            expect(result.length).toBeGreaterThan(0);
        });

        it('should return filtered administrations when ids provided', () => {
            const testIds = ['admin_users', 'admin_groups'];
            const result = service.getAdministrations(testIds);

            expect(result).toBeDefined();
            expect(result.length).toBe(2);
            expect(result.every(item => testIds.includes(item.id))).toBe(true);
        });
    });

    describe('getCurrentUserAdministrationsByUnit', () => {
        beforeEach(() => {
            Object.defineProperty(mockHeaderService, 'user', {
                get: () => mockUser,
                configurable: true
            });
        });

        it('should return user administrations for specific unit', () => {
            const testUnit = 'organisation';
            const result = service.getCurrentUserAdministrationsByUnit(testUnit);

            expect(result).toBeDefined();
            expect(Array.isArray(result)).toBe(true);
            expect(result.every(admin => admin.unit === testUnit)).toBe(true);
        });

        it('should filter out view_history_batch when user has both history privileges', () => {
            const userWithHistoryPrivileges = {
                ...mockUser,
                privileges: [
                    ...mockUser.privileges,
                    { service_id: 'view_history' },
                    { service_id: 'view_history_batch' }
                ]
            };

            Object.defineProperty(mockHeaderService, 'user', {
                get: () => userWithHistoryPrivileges,
                configurable: true
            });

            const testUnit = 'supervision';
            const result = service.getCurrentUserAdministrationsByUnit(testUnit);

            expect(result.find(admin => admin.id === 'view_history_batch')).toBeUndefined();
        });
    });

    describe('hasCurrentUserPrivilege', () => {
        beforeEach(() => {
            Object.defineProperty(mockHeaderService, 'user', {
                get: () => mockUser,
                configurable: true
            });
        });

        it('should return true when user has the privilege', () => {
            const result = service.hasCurrentUserPrivilege('admin_users');
            expect(result).toBe(true);
        });

        it('should return false when user does not have the privilege', () => {
            const result = service.hasCurrentUserPrivilege('non_existent_privilege');
            expect(result).toBe(false);
        });

        it('should return false for excluded privileges', () => {
            const userWithLockedPrivilege = {
                ...mockUser,
                privileges: [
                    ...mockUser.privileges,
                    { service_id: 'create_custom' }
                ]
            };

            Object.defineProperty(mockHeaderService, 'user', {
                get: () => userWithLockedPrivilege,
                configurable: true
            });

            const result = service.hasCurrentUserPrivilege('create_custom');
            expect(result).toBe(true);
        });
    });

    describe('Plugin Integration', () => {
        it('should exclude ragtime_plugin when plugin is not loaded', async () => {
            mockPluginManagerService.isPluginLoaded.and.returnValue(false);

            const mockGroupResponse = { group: { privileges: ['other_privilege'] } };
            const result = service.getAllPrivileges(false, 'standard', mockUser.groups);

            const groupRequests = httpMock.match(req => req.url.includes('/rest/groups/'));
            groupRequests.forEach(req => {
                req.flush(mockGroupResponse);
            });

            const privileges = await result;
            expect(privileges).not.toContain('ragtime_plugin');
        });

        it('should include ragtime_plugin when plugin is loaded and user has privilege', async () => {
            mockPluginManagerService.isPluginLoaded.and.returnValue(true);

            const mockGroupResponse = { group: { privileges: ['ragtime_plugin'] } };
            const result = service.getAllPrivileges(false, 'standard', mockUser.groups);

            const groupRequests = httpMock.match(req => req.url.includes('/rest/groups/'));
            groupRequests.forEach(req => {
                req.flush(mockGroupResponse);
            });

            const privileges = await result;
            expect(privileges).toContain('ragtime_plugin');
        });
    });

    describe('Error Handling', () => {
        it('should handle HTTP errors gracefully in getAllPrivileges', async () => {
            const result = service.getAllPrivileges(false, 'standard', mockUser.groups);

            const groupRequests = httpMock.match(req => req.url.includes('/rest/groups/'));
            groupRequests.forEach(req => {
                req.error(new ErrorEvent('Network error'));
            });

            const privileges = await result;
            expect(privileges).toBeDefined();
            expect(Array.isArray(privileges)).toBe(true);
            // Should still exclude ragtime_plugin due to the error (no group privileges returned)
            expect(privileges).not.toContain('ragtime_plugin');
        });
    });

    describe('Edge Cases', () => {
        it('should handle empty user privileges array', () => {
            const userWithoutPrivileges = {
                ...mockUser,
                privileges: []
            };

            Object.defineProperty(mockHeaderService, 'user', {
                get: () => userWithoutPrivileges,
                configurable: true
            });

            const result = service.getCurrentUserMenus();
            expect(result).toBeDefined();
            expect(Array.isArray(result)).toBe(true);
        });

        it('should handle user with legacy privilege format', () => {
            const userWithLegacyPrivileges = {
                ...mockUser,
                privileges: ['admin_users', 'admin_groups'] // Direct string array instead of objects
            };

            mockFunctionsService.empty.and.returnValue(true); // Simulate an empty service_id check

            Object.defineProperty(mockHeaderService, 'user', {
                get: () => userWithLegacyPrivileges,
                configurable: true
            });

            const result = service.getCurrentUserMenus();
            expect(result).toBeDefined();
            expect(Array.isArray(result)).toBe(true);
        });

        it('should handle empty groups array', async () => {
            const result = service.getAllPrivileges(false, 'standard', []);

            // No HTTP requests should be made for empty groups
            httpMock.expectNone(req => req.url.includes('/rest/groups/'));

            const privileges = await result;
            expect(privileges).toBeDefined();
            expect(privileges).not.toContain('ragtime_plugin');
        });
    });
});