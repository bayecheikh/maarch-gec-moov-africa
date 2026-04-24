import { Component, Input, OnInit } from '@angular/core';
import { RagtimeService } from "@plugins/ragtime/ragtime.service";
import { MatSidenav } from "@angular/material/sidenav";
import { PluginManagerService } from "@service/plugin-manager.service";

@Component({
    selector: 'app-ragtime-button',
    templateUrl: 'ragtime-button.component.html',
    styleUrls: ['ragtime-button.component.scss'],
})
export class RagtimeButtonComponent implements OnInit {

    @Input() snavRight: MatSidenav;

    pluginUrl: string = '';

    startX: number = null;
    startY: number = null;
    isDragging: boolean = false;

    constructor(
        public ragtimeService: RagtimeService,
        private pluginManagerService: PluginManagerService
    ) { }

    async ngOnInit() {
        await this.pluginManagerService.fetchPlugins().then(() => {
            this.pluginUrl = this.pluginManagerService.getPluginUrl('maarch-plugins-ragtime');
        });
    }

    onMouseDown(event: MouseEvent): void {
        this.startX = event.clientX;
        this.startY = event.clientY;
        this.isDragging = false;
    }

    onMouseUp(event: MouseEvent): void {
        const deltaX = Math.abs(event.clientX - this.startX);
        const deltaY = Math.abs(event.clientY - this.startY);
        if (deltaX > 5 || deltaY > 5) {
            this.isDragging = true;
        }
    }

    openRagtime(): void {
        if (!this.isDragging) {
            this.snavRight.toggle();
        }
    }
}
