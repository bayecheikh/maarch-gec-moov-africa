import { ComponentFixture, TestBed, fakeAsync, tick } from '@angular/core/testing';
import { ActionsService } from '@appRoot/actions/actions.service';
import { TranslateService, TranslateModule, TranslateLoader } from '@ngx-translate/core';
import { NotificationService } from '@service/notification/notification.service';
import { Router } from '@angular/router';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { By } from '@angular/platform-browser';
import { Observable, of } from 'rxjs';
import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { RouterTestingModule } from '@angular/router/testing';
import { SignatureBookHeaderComponent } from '@appRoot/signatureBook/header/signature-book-header.component';
import { SignatureBookService } from '@appRoot/signatureBook/signature-book.service';
import { SharedModule } from '@appRoot/app-common.module';
import * as langFrJson from '@langs/lang-fr.json';

class FakeLoader implements TranslateLoader {
    getTranslation(): Observable<any> {
        return of({ lang: langFrJson });
    }
}

describe('SignatureBookHeaderComponent', () => {
    let component: SignatureBookHeaderComponent;
    let fixture: ComponentFixture<SignatureBookHeaderComponent>;
    let signatureBookServiceSpy: jasmine.SpyObj<SignatureBookService>;
    let actionsServiceSpy: jasmine.SpyObj<ActionsService>;
    let notificationServiceSpy: jasmine.SpyObj<NotificationService>;
    let routerSpy: jasmine.SpyObj<Router>;
    let translateService: TranslateService;

    beforeEach(async () => {
        // Create spies
        signatureBookServiceSpy = jasmine.createSpyObj('SignatureBookService',
            ['toggleSelection', 'isSelectedResource'],
            {
                basketLabel: 'Parapheur électronique',
                resourcesListIds: [100, 200, 300],
                selectedMailsCount: 2,
                isCurrentResourceSelected: false
            }
        );

        actionsServiceSpy = jasmine.createSpyObj('ActionsService',
            ['goToResource', 'stopRefreshResourceLock', 'unlockResource']
        );

        notificationServiceSpy = jasmine.createSpyObj('NotificationService', ['error']);
        routerSpy = jasmine.createSpyObj('Router', ['navigate']);

        // Configure spy return values
        actionsServiceSpy.goToResource.and.returnValue(of([100, 200, 300]));
        actionsServiceSpy.unlockResource.and.returnValue(Promise.resolve(true));
        signatureBookServiceSpy.toggleSelection.and.returnValue(Promise.resolve(true));
        signatureBookServiceSpy.isSelectedResource.and.returnValue(false);

        await TestBed.configureTestingModule({
            imports: [
                RouterTestingModule,
                SharedModule,
                BrowserAnimationsModule,
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: FakeLoader },
                })
            ],
            declarations: [
                SignatureBookHeaderComponent
            ],
            providers: [
                { provide: SignatureBookService, useValue: signatureBookServiceSpy },
                { provide: ActionsService, useValue: actionsServiceSpy },
                { provide: NotificationService, useValue: notificationServiceSpy },
                { provide: Router, useValue: routerSpy },
                TranslateService
            ],
            schemas: [CUSTOM_ELEMENTS_SCHEMA] // For custom elements like mat-icon with SVG
        }).compileComponents();

        // Set lang
        translateService = TestBed.inject(TranslateService);
        translateService.use('fr');

        fixture = TestBed.createComponent(SignatureBookHeaderComponent);
        component = fixture.componentInstance;

        // Setup initial component inputs
        component.resId = 200;
        component.userId = 10;
        component.groupId = 5;
        component.basketId = 3;
        component.canGoToNext = true;
        component.canGoToPrevious = true;

        fixture.detectChanges();
    });

    // Unit Tests
    describe('Unit Tests', () => {
        it('should create the component', () => {
            expect(component).toBeTruthy();
        });

        it('should initialize with default values', () => {
            const freshComponent = new SignatureBookHeaderComponent(
                signatureBookServiceSpy,
                actionsServiceSpy,
                translateService,
                notificationServiceSpy,
                routerSpy
            );

            expect(freshComponent.canGoToPrevious).toBeFalse();
            expect(freshComponent.canGoToNext).toBeFalse();
            expect(freshComponent.resId).toBeNull();
            expect(freshComponent.userId).toBeNull();
            expect(freshComponent.groupId).toBeNull();
            expect(freshComponent.basketId).toBeNull();
        });

        it('should toggle resource selection', async () => {
            await component.toggleCurrentResource(false);

            expect(signatureBookServiceSpy.toggleSelection).toHaveBeenCalledWith(
                false, 10, 5, 3, 200
            );
            expect(signatureBookServiceSpy.isCurrentResourceSelected).toBeFalse();
        });

        it('should navigate to next resource successfully', () => {
            spyOn(component.setNextPrevEvent, 'emit');
            component.goToResource('next');

            expect(actionsServiceSpy.goToResource).toHaveBeenCalledWith(
                [100, 200, 300], 10, 5, 3
            );
            expect(routerSpy.navigate).toHaveBeenCalledWith(
                ['/signatureBookNew/users/10/groups/5/baskets/3/resources/300']
            );
            expect(component.setNextPrevEvent.emit).toHaveBeenCalled();
        });

        it('should navigate to previous resource successfully', () => {
            spyOn(component.setNextPrevEvent, 'emit');
            component.goToResource('previous');

            expect(actionsServiceSpy.goToResource).toHaveBeenCalledWith(
                [100, 200, 300], 10, 5, 3
            );
            expect(routerSpy.navigate).toHaveBeenCalledWith(
                ['/signatureBookNew/users/10/groups/5/baskets/3/resources/100']
            );
            expect(component.setNextPrevEvent.emit).toHaveBeenCalled();
        });

        it('should show error when no available resources', () => {
            // Set up with no available resources
            actionsServiceSpy.goToResource.and.returnValue(of([]));

            component.goToResource('next');

            expect(notificationServiceSpy.error).toHaveBeenCalledWith(component.translate.instant('lang.warnResourceLockedByUser'));
            expect(routerSpy.navigate).not.toHaveBeenCalled();
        });

        it('should navigate to basket list', () => {
            component.backToBasket();

            expect(routerSpy.navigate).toHaveBeenCalledWith(
                ['/basketList/users/10/groups/5/baskets/3']
            );
        });

        it('should navigate to home and unlock resource', () => {
            spyOn(component, 'unlockResource').and.callThrough();

            component.goToHome();

            expect(routerSpy.navigate).toHaveBeenCalledWith(['/home']);
            expect(component.unlockResource).toHaveBeenCalled();
        });

        it('should unlock resource properly', async () => {
            await component.unlockResource();

            expect(actionsServiceSpy.stopRefreshResourceLock).toHaveBeenCalled();
            expect(actionsServiceSpy.unlockResource).toHaveBeenCalledWith(
                10, 5, 3, [200], '/basketList/users/10/groups/5/baskets/3'
            );
        });

        it('should handle edge case when navigating past the end of list', fakeAsync(() => {
            // Set current resource to last in list
            component.resId = 300;

            fixture.detectChanges();
            tick();

            component.goToResource('next');

            expect(notificationServiceSpy.error).toHaveBeenCalledWith(component.translate.instant('lang.warnResourceLockedByUser'));
            expect(routerSpy.navigate).not.toHaveBeenCalled();
        }));

        it('should handle edge case when navigating before the start of list', fakeAsync(() => {
            // Set current resource to first in list
            component.resId = 100;

            fixture.detectChanges();
            tick();

            component.goToResource('previous');

            expect(notificationServiceSpy.error).toHaveBeenCalledWith(component.translate.instant('lang.warnResourceLockedByUser'));
            expect(routerSpy.navigate).not.toHaveBeenCalled();
        }));
    });

    // Template Tests (TF)
    describe('Template Tests', () => {
        it('should render logo and basket label', () => {
            const basketLabel = fixture.debugElement.query(By.css('.basket-label'));
            expect(basketLabel.nativeElement.textContent).toContain('Parapheur électronique');

            const logo = fixture.debugElement.query(By.css('.maarch-logo'));
            expect(logo).toBeTruthy();
        });

        it('should emit toggleResListDrawer when stream button is clicked', fakeAsync(() => {
            spyOn(component.toggleResListDrawer, 'emit');

            fixture.detectChanges();
            tick();

            const streamButton = fixture.debugElement.query(By.css('.signatorybook-header-left-stream button'));
            streamButton.triggerEventHandler('click', null);

            expect(component.toggleResListDrawer.emit).toHaveBeenCalled();
        }));

        it('should call goToHome when logo button is clicked', fakeAsync(() => {
            spyOn(component, 'goToHome');

            fixture.detectChanges();
            tick();

            const logoButton = fixture.debugElement.query(By.css('.maarch-logo'));
            logoButton.triggerEventHandler('click', null);

            expect(component.goToHome).toHaveBeenCalled();
        }));

        it('should display proper button labels for navigation', () => {
            const prevButton = fixture.debugElement.query(By.css('.prev-button'));
            expect(prevButton.nativeElement.textContent).toContain(component.translate.instant('lang.prevParaph'));

            const nextButton = fixture.debugElement.query(By.css('.next-button'));
            expect(nextButton.nativeElement.textContent).toContain(component.translate.instant('lang.nextParaph'));
        });

        it('should disable previous button when canGoToPrevious is false', fakeAsync(() => {
            component.canGoToPrevious = false;

            fixture.detectChanges();
            tick();

            const prevButton = fixture.debugElement.query(By.css('.prev-button'));
            expect(prevButton.attributes['disabled']).toBeDefined();
        }));

        it('should disable next button when canGoToNext is false', fakeAsync(() => {
            component.canGoToNext = false;

            fixture.detectChanges();
            tick();

            const nextButton = fixture.debugElement.query(By.css('.next-button'));
            expect(nextButton.attributes['disabled']).toBeDefined();
        }));

        it('should call goToResource with "previous" when previous button is clicked', fakeAsync(() => {
            spyOn(component, 'goToResource');

            const prevButton = fixture.debugElement.query(By.css('.prev-button'));
            prevButton.triggerEventHandler('click', { stopPropagation: () => {} });

            fixture.detectChanges();
            tick();

            expect(component.goToResource).toHaveBeenCalledWith('previous');
        }));

        it('should call goToResource with "next" when next button is clicked', fakeAsync(() => {
            spyOn(component, 'goToResource');

            const nextButton = fixture.debugElement.query(By.css('.next-button'));
            nextButton.triggerEventHandler('click', { stopPropagation: () => {} });

            fixture.detectChanges();
            tick();

            expect(component.goToResource).toHaveBeenCalledWith('next');
        }));

        it('should call backToBasket when close button is clicked', fakeAsync(() => {
            spyOn(component, 'backToBasket');

            const closeButton = fixture.debugElement.query(By.css('.signatorybook-header-right > button'));
            closeButton.triggerEventHandler('click', null);

            fixture.detectChanges();
            tick();

            expect(component.backToBasket).toHaveBeenCalled();
        }));

        it('should display checkbox for mass selection', () => {
            const checkbox = fixture.debugElement.query(By.css('.checkbox-mass-sign'));
            expect(checkbox).toBeTruthy();
        });

        it('should reflect current resource selection state in checkbox', fakeAsync(() => {
            signatureBookServiceSpy.isSelectedResource.and.returnValue(true);

            fixture.detectChanges();
            tick();

            const checkbox = fixture.debugElement.query(By.css('.checkbox-mass-sign input'));
            expect(checkbox.properties['checked']).toBeTrue();
        }));

        it('should call toggleCurrentResource when checkbox is changed', fakeAsync(() => {
            spyOn(component, 'toggleCurrentResource');

            const checkbox = fixture.debugElement.query(By.css('.checkbox-mass-sign'));
            checkbox.triggerEventHandler('change', { checked: true });

            fixture.detectChanges();
            tick();

            expect(component.toggleCurrentResource).toHaveBeenCalledWith(true);
        }));

        it('should display badge with selected mails count', fakeAsync(() => {
            const checkbox = fixture.debugElement.query(By.css('.checkbox-mass-sign'));

            expect(checkbox.attributes['ng-reflect-content']).toBe('2');
            expect(checkbox.attributes['title']).toEqual(component.translate.instant('lang.markAsTargetActionMass'));
        }));

        it('should properly apply aria labels for accessibility', () => {
            const streamDiv = fixture.debugElement.query(By.css('.signatorybook-header-left-stream'));
            expect(streamDiv.attributes['aria-label']).toBe(component.translate.instant('lang.toggleResListDrawer'));

            const labelDiv = fixture.debugElement.query(By.css('.signatorybook-header-left-label'));
            expect(labelDiv.attributes['aria-label']).toBe('Parapheur électronique');
        });

        it('should properly apply title attributes for tooltips', () => {
            const streamButton = fixture.debugElement.query(By.css('.signatorybook-header-left-stream button'));
            expect(streamButton.attributes['title']).toBe(component.translate.instant('lang.toggleResListDrawer'));

            const logoButton = fixture.debugElement.query(By.css('.maarch-logo'));
            expect(logoButton.attributes['title']).toBe(component.translate.instant('lang.home'));

            const prevButton = fixture.debugElement.query(By.css('.prev-button'));
            expect(prevButton.attributes['title']).toBe(component.translate.instant('lang.prevParaph'));

            const nextButton = fixture.debugElement.query(By.css('.next-button'));
            expect(nextButton.attributes['title']).toBe(component.translate.instant('lang.nextParaph'));
        });
    });
});