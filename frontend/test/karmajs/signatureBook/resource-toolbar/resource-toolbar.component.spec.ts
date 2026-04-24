import { ComponentFixture, fakeAsync, flush, TestBed, tick } from '@angular/core/testing';
import { HttpClientTestingModule } from '@angular/common/http/testing';
import { TranslateLoader, TranslateModule, TranslateService } from '@ngx-translate/core';
import { PrivilegeService } from '@service/privileges.service';
import { NotificationService } from '@service/notification/notification.service';
import { MatLegacyDialog as MatDialog } from '@angular/material/legacy-dialog';
import { HeaderService } from '@service/header.service';
import { FunctionsService } from '@service/functions.service';
import { Router } from '@angular/router';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { RouterTestingModule } from '@angular/router/testing';
import { Observable, of, throwError } from 'rxjs';
import { By } from '@angular/platform-browser';
import { HttpClient } from '@angular/common/http';
import { ActionsService } from '@appRoot/actions/actions.service';
import { SignatureBookService } from '@appRoot/signatureBook/signature-book.service';
import { ResourceToolbarComponent } from '@appRoot/signatureBook/resourceToolbar/resource-toolbar.component';
import { DashboardResumeComponent } from '@appRoot/dashboard-resume/dashboard-resume.component';
import { NotesListComponent } from '@appRoot/notes/notes-list.component';
import { VisaWorkflowComponent } from '@appRoot/visa/visa-workflow.component';
import { IndexingFormComponent } from '@appRoot/indexation/indexing-form/indexing-form.component';
import { LinkedResourceListComponent } from '@appRoot/linkedResource/linked-resource-list.component';
import { DiffusionsListComponent } from '@appRoot/diffusions/diffusions-list.component';
import { SentResourceListComponent } from '@appRoot/sentResource/sent-resource-list.component';
import { AvisWorkflowComponent } from '@appRoot/avis/avis-workflow.component';
import { AttachmentsListComponent } from '@appRoot/attachments/attachments-list.component';
import { HistoryComponent } from '@appRoot/history/history.component';
import * as langFrJson from '@langs/lang-fr.json';
import { SharedModule } from '@appRoot/app-common.module';
import { LatinisePipe } from 'ngx-pipes';
import { ContactService } from "@service/contact.service";

class FakeLoader implements TranslateLoader {
    getTranslation(): Observable<any> {
        return of({ lang: langFrJson });
    }
}

describe('ResourceToolbarComponent', () => {
    let component: ResourceToolbarComponent;
    let fixture: ComponentFixture<ResourceToolbarComponent>;
    let httpClientSpy: any;
    let translateService: TranslateService;
    let privilegeServiceSpy: any;
    let notificationServiceSpy: any;
    let dialogSpy: any;
    let actionsServiceSpy: any;
    let headerServiceSpy: any;
    let functionsServiceSpy: any;
    let signatureBookServiceSpy: any;

    beforeEach(async () => {
        // Set up spies
        httpClientSpy = jasmine.createSpyObj('HttpClient', ['get']);
        privilegeServiceSpy = jasmine.createSpyObj('PrivilegeService', ['hasCurrentUserPrivilege']);
        notificationServiceSpy = jasmine.createSpyObj('NotificationService', ['handleErrors', 'handleSoftErrors']);
        dialogSpy = jasmine.createSpyObj('MatDialog', ['open']);
        actionsServiceSpy = jasmine.createSpyObj('ActionsService', ['stopRefreshResourceLock', 'unlockResource', 'lockResource']);
        headerServiceSpy = { user: { id: 1 } };
        functionsServiceSpy = jasmine.createSpyObj('FunctionsService', ['empty']);
        signatureBookServiceSpy = { canAddAttachments: true, currentWorkflowRole: '' };

        // Configure spies
        privilegeServiceSpy.hasCurrentUserPrivilege.and.returnValue(true);
        functionsServiceSpy.empty.and.callFake((val) => !val);
        httpClientSpy.get.and.returnValue(of({ modelId: 1 }));
        dialogSpy.open.and.returnValue({
            afterClosed: () => of('ok')
        });

        await TestBed.configureTestingModule({
            imports: [
                HttpClientTestingModule,
                RouterTestingModule,
                BrowserAnimationsModule,
                SharedModule,
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: FakeLoader },
                })
            ],
            declarations: [
                ResourceToolbarComponent,
                DashboardResumeComponent,
                HistoryComponent,
                NotesListComponent,
                VisaWorkflowComponent,
                IndexingFormComponent,
                LinkedResourceListComponent,
                DiffusionsListComponent,
                SentResourceListComponent,
                AvisWorkflowComponent,
                AttachmentsListComponent,
            ],
            providers: [
                { provide: HttpClient, useValue: httpClientSpy },
                { provide: PrivilegeService, useValue: privilegeServiceSpy },
                { provide: NotificationService, useValue: notificationServiceSpy },
                { provide: MatDialog, useValue: dialogSpy },
                { provide: ActionsService, useValue: actionsServiceSpy },
                { provide: HeaderService, useValue: headerServiceSpy },
                { provide: FunctionsService, useValue: functionsServiceSpy },
                { provide: SignatureBookService, useValue: signatureBookServiceSpy },
                TranslateService,
                LatinisePipe,
                Router,
                ContactService
            ]
        }).compileComponents();

        // Set lang
        translateService = TestBed.inject(TranslateService);
        translateService.use('fr');

        fixture = TestBed.createComponent(ResourceToolbarComponent);
        component = fixture.componentInstance;

        // Initialize component properties
        component.resId = 123;
        component.userId = 1;
        component.groupId = 10;
        component.basketId = 20;

        fixture.detectChanges();
    });

    // UNIT TESTS (TypeScript)
    describe('Unit Tests', () => {
        it('should create the component', () => {
            expect(component).toBeTruthy();
        });

        it('should initialize with default values', () => {
            expect(component.currentTool).toBe('visaCircuit');
            expect(component.processTool.length).toBeGreaterThan(0);
        });

        it('should call loadBadges on init', () => {
            spyOn(component, 'loadBadges');
            component.ngOnInit();
            expect(component.loadBadges).toHaveBeenCalled();
        });

        it('should change active tab', async () => {
            spyOn(component, 'getResourceInformation').and.returnValue(Promise.resolve(42));
            await component.changeTab('info');
            expect(component.currentTool).toBe('info');
            expect(component.modelId).toBe(42);
        });

        it('should get resource information', async () => {
            httpClientSpy.get.and.returnValue(of({ modelId: 42 }));
            const result = await component.getResourceInformation();
            expect(result).toBe(42);
            expect(httpClientSpy.get).toHaveBeenCalledWith('../rest/resources/123?light=true');
        });

        it('should handle error in getResourceInformation', async () => {
            httpClientSpy.get.and.returnValue(throwError({ error: 'Error' }));
            const result = await component.getResourceInformation();
            expect(result).toBe(false);
            expect(notificationServiceSpy.handleErrors).toHaveBeenCalled();
        });

        it('should load badges', () => {
            const badgeData = {
                dashboard: 5,
                history: 10,
                notes: 3
            };
            httpClientSpy.get.and.returnValue(of(badgeData));

            component.loadBadges();

            expect(httpClientSpy.get).toHaveBeenCalledWith('../rest/resources/123/items');
            expect(component.processTool.find(tool => tool.id === 'dashboard')?.count).toBe(5);
            expect(component.processTool.find(tool => tool.id === 'history')?.count).toBe(10);
            expect(component.processTool.find(tool => tool.id === 'notes')?.count).toBe(3);
            // Tools not in badgeData should have count 0
            expect(component.processTool.find(tool => tool.id === 'attachments')?.count).toBe(0);
        });

        it('should handle error in loadBadges', () => {
            httpClientSpy.get.and.returnValue(throwError({ error: 'Error' }));
            component.loadBadges();
            expect(notificationServiceSpy.handleSoftErrors).toHaveBeenCalled();
        });

        it('should refresh badge count for specific tool', () => {
            component.refreshBadge(42, 'notes');
            expect(component.processTool.find(tool => tool.id === 'notes')?.count).toBe(42);
        });

        it('should check if diffusionList tool is modified', () => {
            component.currentTool = 'diffusionList';
            component.appDiffusionsList = {
                isModified: jasmine.createSpy().and.returnValue(true)
            } as any;

            expect(component.isToolModified()).toBe(true);
        });

        it('should check if visaCircuit tool is modified', () => {
            component.currentTool = 'visaCircuit';
            component.appVisaWorkflow = {
                isModified: jasmine.createSpy().and.returnValue(true),
                workflowParametersNotValid: jasmine.createSpy().and.returnValue(false)
            } as any;

            expect(component.isToolModified()).toBe(true);

            // Should return false if workflow parameters are not valid
            component.appVisaWorkflow.workflowParametersNotValid = jasmine.createSpy().and.returnValue(true);
            expect(component.isToolModified()).toBe(false);
        });

        it('should check if notes tool is modified', () => {
            component.currentTool = 'notes';
            component.appNotesList = {
                isModified: jasmine.createSpy().and.returnValue(true)
            } as any;

            expect(component.isToolModified()).toBe(true);
        });

        it('should save diffusionList tool', async () => {
            component.currentTool = 'diffusionList';
            component.appDiffusionsList = {
                saveListinstance: jasmine.createSpy().and.returnValue(Promise.resolve(true))
            } as any;

            spyOn(component, 'loadBadges');
            await component.saveTool();

            expect(component.appDiffusionsList.saveListinstance).toHaveBeenCalled();
            expect(component.loadBadges).toHaveBeenCalled();
        });

        it('should save visaCircuit tool when confirmed', fakeAsync(() => {
            // Setup
            component.currentTool = 'visaCircuit';
            component.appVisaWorkflow = {
                saveVisaWorkflow: jasmine.createSpy().and.returnValue(Promise.resolve(true)),
                getWorkflow: jasmine.createSpy().and.returnValue([{ process_date: null, item_id: 2 }]),
                cancelModifications: jasmine.createSpy()
            } as any;

            spyOn(component, 'loadBadges');

            const navigateSpy = spyOn(TestBed.inject(Router), 'navigate');

            // Execute
            component.saveTool();
            tick();

            // Assert
            expect(actionsServiceSpy.stopRefreshResourceLock).toHaveBeenCalled();
            expect(actionsServiceSpy.unlockResource).toHaveBeenCalled();
            expect(component.appVisaWorkflow.saveVisaWorkflow).toHaveBeenCalled();
            expect(navigateSpy).toHaveBeenCalledWith([
                '/basketList/users/1/groups/10/baskets/20'
            ]);
            expect(component.loadBadges).toHaveBeenCalled();

            // Clean up
            flush();
        }));

        it('should update current workflow user role', () => {
            component.appVisaWorkflow = {
                visaWorkflow: {
                    items: [
                        { process_date: '2025-04-07', currentRole: 'sign' },
                        { process_date: null, currentRole: 'sign' }
                    ]
                }
            } as any;

            component.updateCurrentWorkflowUserRole();
            expect(signatureBookServiceSpy.currentWorkflowRole).toBe('sign');
        });
    });

    // TEMPLATE TESTS (TF)
    describe('Template Tests', () => {
        it('should render all tool buttons', () => {
            const toolButtons = fixture.debugElement.queryAll(By.css('.process-tool-module'));
            expect(toolButtons.length).toEqual(component.processTool.length);
        });

        it('should highlight active tool button', () => {
            component.currentTool = 'history';
            fixture.detectChanges();

            const activeButton = fixture.debugElement.query(By.css('.process-tool-module-active'));
            expect(activeButton).toBeTruthy();
            expect(activeButton.nativeElement.textContent).toContain(component.translate.instant('lang.history'));
        });

        it('should display badge for items with content', () => {
            // Set count for history tool
            component.processTool.find(t => t.id === 'history')!.count = 5;
            fixture.detectChanges();

            const historyToolElement = fixture.debugElement.queryAll(By.css('.process-tool-module'))
                .find(el => el.nativeElement.textContent.includes(component.translate.instant('lang.history')));

            const badge = historyToolElement?.query(By.css('.has-content'));
            expect(badge).toBeTruthy();
        });

        it('should not display badge for items without content', () => {
            // Set count for history tool to 0
            component.processTool.find(t => t.id === 'history')!.count = 0;
            fixture.detectChanges();

            const historyToolElement = fixture.debugElement.queryAll(By.css('.process-tool-module'))
                .find(el => el.nativeElement.textContent.includes(component.translate.instant('lang.history')));

            const badge = historyToolElement?.query(By.css('.has-content'));
            expect(badge).toBeFalsy();
        });

        it('should display save button when tool is modified', () => {
            spyOn(component, 'isToolModified').and.returnValue(true);
            fixture.detectChanges();

            const saveButton = fixture.debugElement.query(By.css('button[mat-fab]'));
            expect(saveButton).toBeTruthy();
        });

        it('should not display save button when tool is not modified', () => {
            spyOn(component, 'isToolModified').and.returnValue(false);
            fixture.detectChanges();

            const saveButton = fixture.debugElement.query(By.css('button[mat-fab]'));
            expect(saveButton).toBeFalsy();
        });

        it('should call changeTab when clicking on a tool button', () => {
            spyOn(component, 'changeTab');

            const historyToolElement = fixture.debugElement.queryAll(By.css('.process-tool-module'))
                .find(el => el.nativeElement.textContent.includes(component.translate.instant('lang.history')));

            historyToolElement?.triggerEventHandler('click', null);

            expect(component.changeTab).toHaveBeenCalledWith('history');
        });

        it('should not call changeTab when clicking on a disabled tool button', fakeAsync(() => {
            spyOn(component, 'changeTab');

            // Disable the history tool
            component.processTool.find(t => t.id === 'history')!.disabled = true;

            fixture.detectChanges();
            tick();

            const historyToolElement = fixture.debugElement.queryAll(By.css('.process-tool-module'))
                .find(el => el.nativeElement.textContent.includes(component.translate.instant('lang.history')));

            historyToolElement?.triggerEventHandler('click', null);

            expect(component.changeTab).not.toHaveBeenCalled();
        }));

        it('should call saveTool when clicking on save button', fakeAsync(() => {
            spyOn(component, 'isToolModified').and.returnValue(true);
            spyOn(component, 'saveTool');

            fixture.detectChanges();
            tick();

            const saveButton = fixture.debugElement.query(By.css('button[mat-fab]'));
            saveButton.triggerEventHandler('click', null);

            expect(component.saveTool).toHaveBeenCalled();
        }));

        it('should display the correct child component based on currentTool', fakeAsync(() => {
            // Test for history component
            component.currentTool = 'history';

            fixture.detectChanges();
            tick();

            expect(fixture.debugElement.query(By.css('app-history-list'))).toBeTruthy();
            expect(fixture.debugElement.query(By.css('app-notes-list'))).toBeFalsy();

            // Test for emails component
            component.currentTool = 'emails';

            fixture.detectChanges();
            tick();

            expect(fixture.debugElement.query(By.css('app-history-list'))).toBeFalsy();
            expect(fixture.debugElement.query(By.css('app-sent-resource-list'))).toBeTruthy();
        }));

        it('should apply special class when history tool is active', fakeAsync(() => {
            component.currentTool = 'history';

            fixture.detectChanges();
            tick();

            const container = fixture.debugElement.query(By.css('.toolbar-container'));
            expect(container.classes['toolbar-container-no-padding']).toBeTruthy();

            component.currentTool = 'emails';
            fixture.detectChanges();
            tick();

            expect(container.classes['toolbar-container-no-padding']).toBeFalsy();
        }));
    });
});