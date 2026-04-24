import { Directive, ElementRef, HostListener, Input, AfterViewInit } from '@angular/core';

@Directive({
    selector: '[appScrollable]',
    exportAs: 'appScrollable'
})
export class ScrollableDirective implements AfterViewInit {
    @Input() scrollDistance: number = 200; // Default scroll distance
    @Input() itemSelector: string = ''; // CSS selector for child items to check visibility

    disableLeftScroll: boolean = true; // Control visibility of left scroll button
    disableRightScroll: boolean = true; // Control visibility of right scroll button

    constructor(private el: ElementRef) {}

    ngAfterViewInit(): void {
        this.checkScrollButtons();
    }

    // Scroll left by a specified distance
    scrollLeft(): void {
        const element: HTMLElement = this.el.nativeElement;
        element.scrollBy({ left: -this.scrollDistance, behavior: 'smooth' });
        this.checkScrollButtons();
    }

    // Scroll right by a specified distance
    scrollRight(): void {
        const element: HTMLElement = this.el.nativeElement;
        element.scrollBy({ left: this.scrollDistance, behavior: 'smooth' });
        this.checkScrollButtons();
    }

    // Check if scroll buttons should be enabled or disabled based on the container scroll position
    checkScrollButtons(): void {
        const element: HTMLElement = this.el.nativeElement;

        if (this.itemSelector) {
            const firstItem: HTMLElement | null = element.querySelector(`${this.itemSelector}:first-child`);
            const lastItem: HTMLElement | null = element.querySelector(`${this.itemSelector}:last-child`);

            if (firstItem && lastItem) {
                const isFirstItemVisible: boolean = firstItem.getBoundingClientRect().left >= element.getBoundingClientRect().left;
                const isLastItemVisible: boolean = lastItem.getBoundingClientRect().right <= element.getBoundingClientRect().right;

                this.disableLeftScroll = isFirstItemVisible;
                this.disableRightScroll = isLastItemVisible;
            }
        }
    }

    // Listen to scroll events on the container and update button visibility
    @HostListener('scroll', ['$event'])
    onScroll(): void {
        this.checkScrollButtons();
    }
}
