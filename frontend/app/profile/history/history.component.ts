import { Component, Input, OnInit, ViewChild } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { NotificationService } from '@service/notification/notification.service';
import { TranslateService } from '@ngx-translate/core';
import { FunctionsService } from '@service/functions.service';
import { HeaderService } from '@service/header.service';
import { MatLegacyTableDataSource as MatTableDataSource } from '@angular/material/legacy-table';
import { MatLegacyPaginator as MatPaginator } from '@angular/material/legacy-paginator';
import { MatSort } from '@angular/material/sort';
import { catchError, of, tap } from 'rxjs';

@Component({
    selector: 'app-profile-history',
    templateUrl: './history.component.html',
    styleUrls: ['./history.component.scss'],
})

export class ProfileHistoryComponent implements OnInit {
    @Input() userId: number = null;
    @Input() adminMode: boolean = false;

    @ViewChild('paginatorHistory', { static: false }) paginatorHistory: MatPaginator;
    @ViewChild('tableHistorySort', { static: false }) sortHistory: MatSort;

    displayedColumns: string[] = ['event_date', 'record_id', 'info'];

    dataSource: MatTableDataSource<unknown> = new MatTableDataSource<unknown>();
    histories: { event_date: string, record_id: string, info: string, remote_ip?: string }[] = [];

    currentYear: number = new Date().getFullYear();
    currentMonth: number = new Date().getMonth() + 1;
    minDate: Date = new Date();
    maxDate: Date = new Date();

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        public headerService: HeaderService,
        private notify: NotificationService,
        private functions: FunctionsService
    ) {}

    async ngOnInit(): Promise<void> {
        if (this.adminMode) {
            this.displayedColumns.push('remote_ip');
        }
        this.getHistories();
    }

    getHistories(): void {
        this.userId = this.functions.empty(this.userId) ? this.headerService.user.id : this.userId;
        const queryParam: string = this.adminMode ? 'adminMode=true' : '';

        this.http.get(`../rest/history/users/${this.userId}?${queryParam}`).pipe(
            tap((data: any) => {
                this.histories = data.histories;
                this.dataSource = new MatTableDataSource(this.histories);
                this.dataSource.sortingDataAccessor = this.functions.listSortingDataAccessor;
                this.dataSource.paginator = this.paginatorHistory;
                this.dataSource.sort = this.sortHistory;
                if (this.adminMode) {
                    this.minDate = new Date(this.currentYear + '-' + this.currentMonth + '-01');
                    this.maxDate = new Date(); // Today
                    this.filterStartDate();
                }
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();

    }

    applyFilter(filterValue: string): void {
        filterValue = filterValue.trim(); // Remove whitespace
        filterValue = filterValue.toLowerCase(); // MatTableDataSource defaults to lowercase matches
        this.dataSource.filter = filterValue;
    }

    filterStartDate(): void {
        this.dataSource.filterPredicate = (data: any) => {
            const eventDate = new Date(data.event_date);
            const maxDate = new Date(this.maxDate);

            // If admin mode is enabled, apply both minDate and maxDate constraints
            if (this.adminMode) {
                const minDate = new Date(this.minDate);
                return eventDate >= minDate && eventDate <= maxDate;
            }

            // Otherwise, only ensure the event does not exceed maxDate
            return eventDate <= maxDate;
        };

        // Update the filter to trigger re-evaluation
        this.dataSource.filter = this.maxDate.toISOString();
    }
}
