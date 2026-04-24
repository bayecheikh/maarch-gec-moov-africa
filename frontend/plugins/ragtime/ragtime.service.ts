import { Injectable } from '@angular/core';
import { NavigationEnd, Router } from "@angular/router";
import { HeaderService } from "@service/header.service";
import { PrivilegeService } from "@service/privileges.service";

@Injectable({
    providedIn: 'root',
})
export class RagtimeService {
    private resId: string | null = null;
    private firstname: string | null = null;

    showLogo = false;

    constructor(
        private router: Router,
        private headerService: HeaderService,
        private privilegeService: PrivilegeService
    ) {
        setTimeout(() => {
            this.checkProperUrl(this.router.url);
        }, 0);
        this.router.events.subscribe((val) => {
            if (val instanceof NavigationEnd) {
                this.checkProperUrl(val.url);
            }
        });
    }

    checkProperUrl(url : string) {
        this.headerService.sideNavRight?.close();
        if (url.includes('/process/users/')) {
            const resId = url.match(/resId\/[0-9]*/)[0].split('/')[1];
            this.setResId(resId);
            this.setFirstname(this.headerService.user.firstname);
            this.showLogo = this.privilegeService.hasCurrentUserPrivilege('ragtime_plugin');
        } else {
            this.showLogo = false;
        }
    }

    setResId(resId: string): void {
        this.resId = resId;
    }

    getResId(): string | null {
        return this.resId;
    }

    clearResId(): void {
        this.resId = null;
    }

    resetPanel() {
        if (!this.router.url.includes('/signatureBookNew/users/') && !this.router.url.includes('/signatureBook/users/')) {
            this.headerService.sideNavLeft?.open();
        }
    }

    setFirstname(firstname: string): void {
        this.firstname = firstname;
    }

    getFirstname(): string | null {
        return this.firstname;
    }
}
