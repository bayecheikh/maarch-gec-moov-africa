import { ComponentFixture, fakeAsync, flush, TestBed, tick } from '@angular/core/testing';
import { DelegatedUsersComponent } from '@appRoot/profile/parameters/delegated-users/delegated-users.component';
import { TranslateLoader, TranslateModule, TranslateService } from '@ngx-translate/core';
import { Observable, of } from 'rxjs';
import { HttpClient } from '@angular/common/http';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { DatePipe } from '@angular/common';
import { AdministrationService } from '@appRoot/administration/administration.service';
import { FoldersService } from '@appRoot/folder/folders.service';
import { FiltersListService } from '@service/filtersList.service';
import { PrivilegeService } from '@service/privileges.service';
import { NotificationService } from '@service/notification/notification.service';
import { MatLegacySnackBarModule } from '@angular/material/legacy-snack-bar';
import { LatinisePipe } from 'ngx-pipes';
import { ActionsService } from '@appRoot/actions/actions.service';
import { MAT_AUTOCOMPLETE_SCROLL_STRATEGY } from '@angular/material/autocomplete';
import { Overlay } from '@angular/cdk/overlay';
import { SharedModule } from '@appRoot/app-common.module';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { ConfirmComponent } from '@plugins/modal/confirm.component';
import { BrowserModule } from '@angular/platform-browser';
import * as langFrJson from '@langs/lang-fr.json';
import {
    ExternalSignatoryBookManagerService
} from "@service/externalSignatoryBook/external-signatory-book-manager.service";

class FakeLoader implements TranslateLoader {
    getTranslation(): Observable<any> {
        return of({ lang: langFrJson });
    }
}

describe('DelegatedUsersComponent', () => {
    let component: DelegatedUsersComponent;
    let fixture: ComponentFixture<DelegatedUsersComponent>;
    let translateService: TranslateService;
    let httpTestingController: HttpTestingController;

    beforeEach(async () => {
        await TestBed.configureTestingModule({
            imports: [
                MatLegacySnackBarModule,
                BrowserAnimationsModule,
                BrowserModule,
                SharedModule,
                HttpClientTestingModule,
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: FakeLoader },
                }),
            ],
            providers: [
                ConfirmComponent,
                ActionsService,
                TranslateService,
                FoldersService,
                PrivilegeService,
                FiltersListService,
                DatePipe,
                AdministrationService,
                NotificationService,
                LatinisePipe,
                HttpClient,
                {
                    provide: MAT_AUTOCOMPLETE_SCROLL_STRATEGY,
                    useFactory: (overlay: Overlay) => () => overlay.scrollStrategies.reposition(),
                    deps: [Overlay],
                },
                ExternalSignatoryBookManagerService
            ],
            declarations: [DelegatedUsersComponent]
        }).compileComponents();

        // Set lang
        translateService = TestBed.inject(TranslateService);
        translateService.use('fr');

        httpTestingController = TestBed.inject(HttpTestingController);
        fixture = TestBed.createComponent(DelegatedUsersComponent);
        component = fixture.componentInstance;
        fixture.detectChanges();

        component.delegatedUsers = [
            {
                id: 19,
                fullName: 'Barbara BAIN',
                checked: false
            },
            {
                id: 10,
                fullName: 'Patricia PETIT',
                checked: false
            }
        ];

        component.headerService.user.id = 23;
    });

    describe('Create component', () => {
        it('should create', () => {
            expect(component).toBeTruthy();
        });
    });

    describe('Display delegated users', () => {
        it('should display existing delegated users', fakeAsync(() => {
            const nativeElement = fixture.nativeElement;

            fixture.detectChanges();
            tick(100);

            const usersContainer = nativeElement.querySelector('.users-container');
            const userItems = usersContainer.querySelectorAll('.user-item');

            expect(userItems.length).toEqual(2);
        }));
    });

    describe('Add new user to the list', () => {
        it('should add new user to the list and verify the length', fakeAsync(() => {
            const nativeElement = fixture.nativeElement;

            fixture.detectChanges();
            tick(100);

            // add new user
            const newUser: { serialId: number, idToDisplay: string } = { serialId: 12, idToDisplay: 'Hamza HRAMCHI' };
            component.addUser(newUser);

            fixture.detectChanges();
            tick(100);

            const addReq = httpTestingController.expectOne(`../rest/users/${component.headerService.user.id}/signatorySubstitute`);
            expect(addReq.request.method).toBe('PUT');
            expect(addReq.request.body).toEqual({ destUser: newUser.serialId });
            addReq.flush({});

            fixture.detectChanges();
            tick(100);
            flush();

            // expect success notification message
            const hasSuccessGritter = document.querySelectorAll('.mat-snack-bar-container.success-snackbar').length;
            expect(hasSuccessGritter).toEqual(1);
            const notifContent = document.querySelector('.notif-container-content-msg #message-content').innerHTML;
            expect(notifContent).toEqual(component.translate.instant('lang.userAdded'));
            flush();

            fixture.detectChanges();
            tick(100);

            const usersContainer = nativeElement.querySelector('.users-container');
            const userItems = usersContainer.querySelectorAll('.user-item');

            expect(userItems.length).toEqual(3);
        }));
    });

    describe('Remove user from the list', () => {
        it('should remove the last user from the list and verify the length', fakeAsync(() => {
            const nativeElement = fixture.nativeElement;

            fixture.detectChanges();
            tick(100);

            const usersContainer = nativeElement.querySelector('.users-container');
            let userItems = usersContainer.querySelectorAll('.user-item');

            const deleteUserBtn = userItems[0].querySelector('.delete-user');

            deleteUserBtn.click();

            fixture.detectChanges();
            tick(300);

            // confirm deleting user
            component.dialogRef.close('ok');

            fixture.detectChanges();
            tick(300);

            const deleteReq = httpTestingController.expectOne(`../rest/users/${component.headerService.user.id}/signatorySubstitute`);
            expect(deleteReq.request.method).toBe('DELETE');
            deleteReq.flush({});

            fixture.detectChanges();
            tick(300);
            flush();

            // expect success notification message
            const hasSuccessGritter = document.querySelectorAll('.mat-snack-bar-container.success-snackbar').length;
            expect(hasSuccessGritter).toEqual(1);
            const notifContent = document.querySelector('.notif-container-content-msg #message-content').innerHTML;
            expect(notifContent).toEqual(component.translate.instant('lang.userDeleted'));
            flush();

            fixture.detectChanges();
            tick(300);

            userItems = usersContainer.querySelectorAll('.user-item');

            expect(userItems.length).toEqual(1);
        }));
    });

});
