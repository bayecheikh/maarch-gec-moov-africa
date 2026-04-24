import { HttpClient } from '@angular/common/http';
import { Component, ElementRef, OnInit, ViewChild } from '@angular/core';
import { UntypedFormControl } from '@angular/forms';
import { ListDisplayInterface } from '@models/list-display.model';
import { TranslateService } from '@ngx-translate/core';
import { AppService } from '@service/app.service';
import { FunctionsService } from '@service/functions.service';
import { HeaderService } from '@service/header.service';
import { NotificationService } from '@service/notification/notification.service';
import { DndDropEvent } from 'ngx-drag-drop';
import { catchError, map, Observable, of, startWith, tap } from 'rxjs';

@Component({
    selector: 'app-folder-administration',
    templateUrl: './folder-administration.component.html',
    styleUrls: ['./folder-administration.component.scss']
})
export class FolderAdministrationComponent implements OnInit {

    @ViewChild('availableData', { static: false }) availableDataElement!: ElementRef;

    loading: boolean = true;

    displayMode: string = 'label';
    dataControl = new UntypedFormControl();
    filteredDataOptions: Observable<any[]>;

    foldersParameters: { listDisplay: ListDisplayInterface[] } = {
        listDisplay: []
    };

    templateDisplayedSecondaryData: number[] = [2, 3, 4, 5, 6, 7];
    selectedTemplateDisplayedSecondaryData: number = 7;
    selectedTemplateDisplayedSecondaryDataClone: number = 7;

    availableDataClone: ListDisplayInterface[] = [];
    displayedSecondaryData: ListDisplayInterface[] = [];
    displayedSecondaryDataClone: ListDisplayInterface[] = [];

    displayedMainData: ListDisplayInterface[] = [
        {
            'value': 'chronoNumberShort',
            'label': this.translate.instant('lang.chronoNumberShort'),
            'sample': 'MAARCH/2019A/1',
            'cssClasses': ['align_centerData', 'normalData'],
            'icon': ''
        },
        {
            'value': 'object',
            'label': this.translate.instant('lang.object'),
            'sample': this.translate.instant('lang.objectSample'),
            'cssClasses': ['longData'],
            'icon': ''
        }
    ];

    availableData: ListDisplayInterface[] = [
        {
            'value': 'getPriority',
            'label': this.translate.instant('lang.getPriority'),
            'sample': this.translate.instant('lang.getPrioritySample'),
            'cssClasses': ['align_leftData'],
            'icon': 'fa-traffic-light'
        },
        {
            'value': 'getCategory',
            'label': this.translate.instant('lang.getCategory'),
            'sample': this.translate.instant('lang.incoming'),
            'cssClasses': ['align_leftData'],
            'icon': 'fa-exchange-alt'
        },
        {
            'value': 'getDoctype',
            'label': this.translate.instant('lang.getDoctype'),
            'sample': this.translate.instant('lang.getDoctypeSample'),
            'cssClasses': ['align_leftData'],
            'icon': 'fa-suitcase'
        },
        {
            'value': 'getAssignee',
            'label': this.translate.instant('lang.getAssignee'),
            'sample': this.translate.instant('lang.getAssigneeSample'),
            'cssClasses': ['align_leftData'],
            'icon': 'fa-sitemap'
        },
        {
            'value': 'getRecipients',
            'label': this.translate.instant('lang.getRecipients'),
            'sample': 'Patricia PETIT',
            'cssClasses': ['align_leftData'],
            'icon': 'fa-user'
        },
        {
            'value': 'getSenders',
            'label': this.translate.instant('lang.getSenders'),
            'sample': 'Alain DUBOIS (MAARCH)',
            'cssClasses': ['align_leftData'],
            'icon': 'fa-book'
        },
        {
            'value': 'getCreationAndProcessLimitDates',
            'label': this.translate.instant('lang.getCreationAndProcessLimitDates'),
            'sample': this.translate.instant('lang.getCreationAndProcessLimitDatesSample'),
            'cssClasses': ['align_leftData'],
            'icon': 'fa-calendar'
        },
        {
            'value': 'getVisaWorkflow',
            'label': this.translate.instant('lang.getVisaWorkflow'),
            'sample': '<i color="accent" class="fa fa-check"></i> Barbara BAIN -> <i class="fa fa-hourglass-half"></i> <b>Bruno BOULE</b> -> <i class="fa fa-hourglass-half"></i> Patricia PETIT',
            'cssClasses': ['align_leftData'],
            'icon': 'fa-list-ol'
        },
        {
            'value': 'getSignatories',
            'label': this.translate.instant('lang.getSignatories'),
            'sample': 'Denis DAULL, Patricia PETIT',
            'cssClasses': ['align_leftData'],
            'icon': 'fa-certificate'
        },
        {
            'value': 'getModificationDate',
            'label': this.translate.instant('lang.getModificationDate'),
            'sample': '01-01-2019',
            'cssClasses': ['align_leftData'],
            'icon': 'fa-calendar-check'
        },
        {
            'value': 'getOpinionLimitDate',
            'label': this.translate.instant('lang.getOpinionLimitDate'),
            'sample': '01-01-2019',
            'cssClasses': ['align_leftData'],
            'icon': 'fa-stopwatch'
        },
        {
            'value': 'getParallelOpinionsNumber',
            'label': this.translate.instant('lang.getParallelOpinionsNumber'),
            'sample': this.translate.instant('lang.getParallelOpinionsNumberSample'),
            'cssClasses': ['align_leftData'],
            'icon': 'fa-comment-alt'
        },
        {
            'value': 'getFolders',
            'label': this.translate.instant('lang.getFolders'),
            'sample': this.translate.instant('lang.getFoldersSample'),
            'cssClasses': ['align_leftData'],
            'icon': 'fa-folder'
        },
        {
            'value': 'getResId',
            'label': this.translate.instant('lang.getResId'),
            'sample': this.translate.instant('lang.getResIdSample'),
            'cssClasses': ['align_leftData'],
            'icon': 'fa-envelope'
        }, {
            'value': 'getBarcode',
            'label': this.translate.instant('lang.getBarcode'),
            'sample': this.translate.instant('lang.getBarcodeSample'),
            'cssClasses': ['align_leftData'],
            'icon': 'fa-barcode'
        }
    ];

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        public appService: AppService,
        public headerService: HeaderService,
        private notify: NotificationService,
        private functions: FunctionsService
    ) { }

    async ngOnInit(): Promise<void> {
        this.headerService.setHeader(this.translate.instant('lang.folderAdministration'));
        await this.initCustomFields();
        await this.getTemplate();

        this.availableDataClone = JSON.parse(JSON.stringify(this.availableData));
        this.selectedTemplateDisplayedSecondaryDataClone = this.selectedTemplateDisplayedSecondaryData;
        this.displayedSecondaryDataClone = JSON.parse(JSON.stringify(this.displayedSecondaryData));

        setTimeout(() => {
            this.filteredDataOptions = this.dataControl.valueChanges
                .pipe(
                    startWith(''),
                    map(value => this._filterData(value))
                );
        }, 0);

        this.loading = false;
    }

    initCustomFields(): Promise<ListDisplayInterface[]> {
        return new Promise((resolve) => {
            this.http.get('../rest/customFields').pipe(
                map((customData: any) => {
                    customData.customFields = customData.customFields.map((info: any) => ({
                        'value': 'indexingCustomField_' + info.id,
                        'label': info.label,
                        'sample': this.translate.instant('lang.customField') + info.id,
                        'cssClasses': ['align_leftData'],
                        'icon': 'fa-hashtag'
                    }));
                    return customData.customFields;
                }),
                tap((customs: ListDisplayInterface[]) => {
                    this.availableData = this.availableData.concat(customs);
                    resolve(this.availableData);
                }),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    resolve([]);
                    return of(false);
                })
            ).subscribe();
        });
    }

    toggleData(): void {
        this.dataControl.disabled ? this.dataControl.enable() : this.dataControl.disable();
        this.displayMode = this.displayMode === 'label' ? 'sample' : 'label';

    }

    setStyle(item: any, value: string): void {
        const typeFont = value.split('_');
        if (typeFont.length === 2) {
            item.cssClasses.forEach((element: any, it: number) => {
                if (element.includes(typeFont[0]) && element !== value) {
                    item.cssClasses.splice(it, 1);
                }
            });
        }

        const index = item.cssClasses.indexOf(value);

        if (index === -1) {
            item.cssClasses.push(value);
        } else {
            item.cssClasses.splice(index, 1);
        }
    }

    addData(id: any): void {
        const i = this.availableData.map((e: any) => e.value).indexOf(id);

        this.displayedSecondaryData.push(this.availableData.filter((item: any) => item.value === id)[0]);
        this.availableData.splice(i, 1);

        this.availableDataElement?.nativeElement?.blur();
        this.dataControl.setValue('');
    }

    removeData(rmData: any, i: number) {
        this.availableData.push(rmData);
        this.displayedSecondaryData.splice(i, 1);
        this.dataControl.setValue('');
    }

    removeAllData() {
        this.displayedSecondaryData = this.displayedSecondaryData.concat();
        this.availableData = this.availableData.concat(this.displayedSecondaryData);
        this.dataControl.setValue('');
        this.displayedSecondaryData = [];
    }

    onDrop(dndDrop: DndDropEvent): void {
        let index = dndDrop.index;

        if (typeof index === 'undefined') {
            index = this.displayedSecondaryData.length;
        }

        this.displayedSecondaryData.splice(index, 0, dndDrop.data);
    }

    onDragged(item: any, data: any[]) {
        const index = data.indexOf(item);
        data.splice(index, 1);
    }

    getTemplate(): Promise<ListDisplayInterface[]> {
        this.displayedSecondaryData = [];
        return new Promise((resolve) => {
            this.http.get('../rest/folders/configuration').pipe(
                tap((templateData: any) => {
                    if (!this.functions.empty(templateData.configuration)) {
                        this.selectedTemplateDisplayedSecondaryData = templateData.configuration.listDisplay.templateColumns;
    
                        templateData.configuration.listDisplay.subInfos.forEach((element: any) => {
                            this.addData(element.value);
                            this.displayedSecondaryData[this.displayedSecondaryData.length - 1].cssClasses = element.cssClasses;
                        });
                    }
                    resolve(this.displayedSecondaryData);
                }),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    resolve([]);
                    return of(false);
                })
            ).subscribe();
        });
    }

    saveTemplate(): void {
        const objToSend = {
            templateColumns: this.selectedTemplateDisplayedSecondaryData,
            subInfos: this.displayedSecondaryData
        };

        this.http.put('../rest/configurations/admin_folders', { 'listDisplay': objToSend }).pipe(
            tap(() => {
                this.displayedSecondaryDataClone = JSON.parse(JSON.stringify(this.displayedSecondaryData));
                this.foldersParameters.listDisplay = this.displayedSecondaryData;
                this.selectedTemplateDisplayedSecondaryDataClone = JSON.parse(JSON.stringify(this.selectedTemplateDisplayedSecondaryData));
                this.notify.success(this.translate.instant('lang.modificationsProcessed'));
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    checkModif(): boolean {
        if (JSON.stringify(this.displayedSecondaryData) === JSON.stringify(this.displayedSecondaryDataClone) && JSON.stringify(this.selectedTemplateDisplayedSecondaryData) === JSON.stringify(this.selectedTemplateDisplayedSecondaryDataClone)) {
            return true;
        } else {
            return false;
        }
    }

    cancelModification(): void {
        this.displayedSecondaryData = JSON.parse(JSON.stringify(this.displayedSecondaryDataClone));
        this.availableData = JSON.parse(JSON.stringify(this.availableDataClone));
        this.selectedTemplateDisplayedSecondaryData = JSON.parse(JSON.stringify(this.selectedTemplateDisplayedSecondaryDataClone));
        this.dataControl.setValue('');
    }

    hasFolder(): boolean {
        if (this.displayedSecondaryData.map((data: any) => data.value).indexOf('getFolders') > -1) {
            return true;
        } else {
            return false;
        }
    }

    private _filterData(value: any): ListDisplayInterface[] {
        let filterValue = '';

        if (typeof value === 'string') {
            filterValue = value.toLowerCase();
        } else if (value !== null) {
            filterValue = value.label.toLowerCase();
        }
        return this.availableData.filter((option: any) => option.label.toLowerCase().includes(filterValue));
    }

}
