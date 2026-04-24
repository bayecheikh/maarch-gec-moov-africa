/**
 * Unit tests for AddUserEntityAsCopyActionComponent.
 */
import { ComponentFixture, fakeAsync, flush, TestBed, tick } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { TranslateModule } from '@ngx-translate/core';
import {
    MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA,
    MatLegacyDialogRef as MatDialogRef
} from '@angular/material/legacy-dialog';
import { RouterTestingModule } from '@angular/router/testing';
import { NoopAnimationsModule } from '@angular/platform-browser/animations';
import { By } from '@angular/platform-browser';
import { NotificationService } from '@service/notification/notification.service';
import {
    AddUserEntityAsCopyActionComponent
} from '@appRoot/actions/add-user-entity-as-copy/add-user-entity-as-copy.component';
import { FoldersService } from '@appRoot/folder/folders.service';
import { PrivilegeService } from '@service/privileges.service';
import { SharedModule } from '@appRoot/app-common.module';
import { DatePipe } from '@angular/common';
import { NoteEditorComponent } from '@appRoot/notes/note-editor.component';
import { ActionsService } from '@appRoot/actions/actions.service';
import { FiltersListService } from '@service/filtersList.service';
import {
    ExternalSignatoryBookManagerService
} from "@service/externalSignatoryBook/external-signatory-book-manager.service";
import { AdministrationService } from "@appRoot/administration/administration.service";

describe('AddUserEntityAsCopyActionComponent', () => {
    let component: AddUserEntityAsCopyActionComponent;
    let fixture: ComponentFixture<AddUserEntityAsCopyActionComponent>;
    let httpMock: HttpTestingController;
    let notifyService: NotificationService;
    let dialogRefSpy: any;
    let privilegeSpy: jasmine.Spy;

    const mockDialogData = {
        resIds: [100, 101],
        action: { label: 'Ajouter en copie un utilisateur / une entité' },
        additionalInfo: {
            showToggle: true,
            canGoToNextRes: true,
            inLocalStorage: false
        },
        processActionRoute: '../rest/resourcesList/users/19/groups/4/baskets/53/actions/552'
    };

    beforeEach(async () => {
        dialogRefSpy = jasmine.createSpyObj('MatDialogRef', ['close']);

        await TestBed.configureTestingModule({
            imports: [
                HttpClientTestingModule,
                TranslateModule.forRoot(),
                RouterTestingModule,
                NoopAnimationsModule,
                SharedModule
            ],
            declarations: [AddUserEntityAsCopyActionComponent, NoteEditorComponent],
            providers: [
                FiltersListService,
                ActionsService,
                FoldersService,
                PrivilegeService,
                DatePipe,
                NoteEditorComponent,
                { provide: MAT_DIALOG_DATA, useValue: mockDialogData },
                { provide: MatDialogRef, useValue: dialogRefSpy },
                {
                    provide: NotificationService,
                    useValue: {
                        handleSoftErrors: jasmine.createSpy('handleSoftErrors'),
                        error: jasmine.createSpy('error')
                    }
                },
                ExternalSignatoryBookManagerService,
                AdministrationService
            ]
        }).compileComponents();

        httpMock = TestBed.inject(HttpTestingController);
        notifyService = TestBed.inject(NotificationService);
    });

    beforeEach(() => {
        fixture = TestBed.createComponent(AddUserEntityAsCopyActionComponent);
        component = fixture.componentInstance;
        fixture.detectChanges();
    });

    afterEach(() => {
        httpMock.verify();
    });

    describe('Component Initialization', () => {
        it('should create component', () => {
            expect(component).toBeTruthy();
        });

        it('should initialize with correct values', () => {
            expect(component.showToggle).toBe(true);
            expect(component.canGoToNextRes).toBe(true);
            expect(component.inLocalStorage).toBe(false);
            expect(component.loading).toBe(false);
        });
    });

    describe('User / Entity Management', () => {
        it('should add user / entity', () => {
            /**
             * Represents a new user to be added as a copy.
             *
             * @property {number} serialId - The serial ID of the user.
             * @property {string} type - The type of the entity, e.g., 'user'.
             * @property {string} idToDisplay - The ID to display for the user.
             * @property {string} descriptionToDisplay - The description to display for the user.
             */
            const newEntity = {
                serialId: 1,
                type: 'user',
                idToDisplay: 'user1',
                descriptionToDisplay: 'User One',
                status: 'ABS'
            };

            component.addElem(newEntity);
            expect(component.usersEntitiesAsCopy.length).toBe(1);
            expect(component.usersEntitiesAsCopy[0].id).toBe(1);
        });

        it('should not add duplicate user / entity', () => {
            /**
             * Represents an entity with details to be displayed.
             *
             * @property {number} serialId - The serial ID of the entity.
             * @property {string} type - The type of the entity.
             * @property {string} idToDisplay - The ID to be displayed for the entity.
             * @property {string} descriptionToDisplay - The description to be displayed for the entity.
             */
            const entity = {
                serialId: 1,
                type: 'entity',
                idToDisplay: 'Pôle social',
                descriptionToDisplay: 'Pôle social',
                status: 'OK'
            };

            component.addElem(entity);
            component.addElem(entity);
            expect(component.usersEntitiesAsCopy.length).toBe(1);
        });

        it('should delete user / entity', () => {
            component.usersEntitiesAsCopy = [{
                id: 1,
                type: 'user',
                idToDisplay: 'user1',
                descriptionToDisplay: 'User One',
                status: 'OK'
            }];

            component.deleteItem({ id: 1, type: 'user' });
            expect(component.usersEntitiesAsCopy.length).toBe(0);
        });
    });

    describe('Form Validation', () => {
        it('should validate form only with users selected', () => {
            expect(component.isValidAction()).toBeFalsy();

            component.usersEntitiesAsCopy = [{
                id: 1,
                type: 'user',
                idToDisplay: 'Barbara BAIN',
                descriptionToDisplay: 'Pôle jeunesse et sport',
                status: 'OK'
            }];

            expect(component.isValidAction()).toBeTruthy();
        });
    });

    describe('Action Execution', () => {
        it('should execute action successfully', fakeAsync(() => {
            component.noteEditor = TestBed.inject(NoteEditorComponent);

            fixture.detectChanges();
            tick(300);

            component.executeAction([100]);

            const req = httpMock.expectOne(mockDialogData.processActionRoute);
            expect(req.request.method).toBe('PUT');
            req.flush(null);

            expect(dialogRefSpy.close).toHaveBeenCalledWith([100]);
        }));

        it('should handle error on action execution', fakeAsync(() => {
            component.noteEditor = TestBed.inject(NoteEditorComponent);

            fixture.detectChanges();
            tick(300);

            component.executeAction([100]);

            const req = httpMock.expectOne(mockDialogData.processActionRoute);
            req.error(new ErrorEvent('Document out of perimeter'));

            expect(notifyService.handleSoftErrors).toHaveBeenCalled();
        }));
    });

    describe('Template Integration', () => {
        it('should disable submit button when invalid', () => {
            const submitBtn = fixture.debugElement.query(By.css('button[color="primary"]'));
            expect(submitBtn.nativeElement.disabled).toBeTruthy();
        });

        it('should enable submit when form valid', () => {
            component.usersEntitiesAsCopy = [{
                id: 1,
                type: 'user',
                idToDisplay: 'Barbara BAIN',
                descriptionToDisplay: 'Pôle jeunesse et sport',
                status: 'OK'
            }];
            component.loading = false;
            fixture.detectChanges();

            const submitBtn = fixture.debugElement.query(By.css('button[color="primary"]'));
            expect(submitBtn.nativeElement.disabled).toBeFalsy();
        });
    });

    describe('Privilege Checks', () => {
        beforeEach(() => {
            spyOn(component.privilegesService, 'hasCurrentUserPrivilege').and.callFake((privilege: string) => {
                // Mock to return true only for specific privileges to simulate conditional checks
                return privilege === 'update_diffusion_process' || privilege === 'update_diffusion_details';
            });
        });

        it('should check process privileges', () => {
            Object.defineProperty(component.router, 'url', {
                get: () => 'process/users/'
            });

            expect(component.hasPrivilege()).toBeTrue();

            // Ensure that privilege check for 'update_diffusion_process' was made
            expect(component.privilegesService.hasCurrentUserPrivilege)
                .toHaveBeenCalledWith('update_diffusion_process');
        });

        it('should check resources privileges', () => {
            Object.defineProperty(component.router, 'url', {
                get: () => '/resources'
            });

            expect(component.hasPrivilege()).toBeTrue();

            // Ensure that privilege check for 'update_diffusion_details' was made
            expect(component.privilegesService.hasCurrentUserPrivilege)
                .toHaveBeenCalledWith('update_diffusion_details');
        });

        it('should check default privilege case', () => {
            Object.defineProperty(component.router, 'url', {
                get: () => '/basketList/users/23/groups/15/baskets/53'
            });

            expect(component.hasPrivilege()).toBeTrue();

            // Ensure privilege checks for default route
            expect(component.privilegesService.hasCurrentUserPrivilege)
                .toHaveBeenCalledWith('admin_users');
        });
    });

    describe('Privilege Message Display', () => {
        beforeEach(() => {
            component.noteEditor = TestBed.inject(NoteEditorComponent);
            privilegeSpy = spyOn(component.privilegesService, 'hasCurrentUserPrivilege')
                .and.returnValue(false);
        });

        it('should not show error message when user has privileges', fakeAsync(() => {
            privilegeSpy.and.returnValue(true);

            fixture.detectChanges();

            const req = httpMock.expectOne('../rest/entities');
            expect(req.request.method).toBe('GET');
            req.flush([]);

            flush();

            const req2 = httpMock.expectOne('../rest/parameters/noteVisibilityOnAction');
            expect(req2.request.method).toBe('GET');
            req2.flush({ parameter: { param_value_int: 1 } });

            fixture.detectChanges();

            const newEntity = {
                serialId: 1,
                type: 'user',
                idToDisplay: 'Barbara BAIN',
                descriptionToDisplay: 'Pôle jeunesse et sport',
                status: 'ABS'
            };

            component.addElem(newEntity);

            fixture.detectChanges();
            tick(100);

            const abs = fixture.nativeElement.querySelector('mat-icon[name="abs"]');
            expect(abs).toBeTruthy();

            httpMock.verify();

            const errorMessage = fixture.debugElement.query(By.css('app-maarch-message'));
            expect(errorMessage).toBeFalsy();
        }));

        it('should show error message when user has no privileges', fakeAsync(() => {
            privilegeSpy.and.returnValue(false);

            fixture.detectChanges();
            tick(100);

            const errorMessage = fixture.debugElement.query(By.css('app-maarch-message'));
            expect(errorMessage).toBeTruthy();

            httpMock.verify();
        }));
    });
});