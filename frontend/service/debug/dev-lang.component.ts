import { Component, Inject, OnInit } from '@angular/core';
import { MatLegacyDialogRef as MatDialogRef, MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA } from '@angular/material/legacy-dialog';
import { HttpClient } from '@angular/common/http';
import { catchError, finalize, tap, map } from 'rxjs/operators';
import { of } from 'rxjs';
import { NotificationService } from '../notification/notification.service';
import { TranslateService } from '@ngx-translate/core';

/**
 * Represents a type for missing language translations.
 *
 * This type is a nested record structure where:
 * - The first level keys are strings representing language codes or identifiers.
 * - The second level values are arrays of records.
 * - Each record in the array maps string keys to string values, representing translation keys and their corresponding missing translations.
 * - Ex : missingLang['en'] = [{ key: 'value' }];
 */
type MissingLang = Record<string, Record<string, string>[]>;

@Component({
    templateUrl: 'dev-lang.component.html',
    styleUrls: ['dev-lang.component.scss'],
})
export class DevLangComponent implements OnInit {
    /**
     * A record that maps language codes to their respective translation records.
     * Each translation record is a mapping of translation keys to their corresponding translated strings.
     *
     * Ex:
     * {
     *   "en": {
     *     "greeting": "Hello",
     *     "farewell": "Goodbye"
     *   },
     *   "fr": {
     *     "greeting": "Bonjour",
     *     "farewell": "Au revoir"
     *   }
     * }
     */
    allLang: Record<string, Record<string, string>> = {};

    missingLang: MissingLang = {};

    currentLang: string = 'en';

    loading: boolean = true;

    constructor(
        @Inject(MAT_DIALOG_DATA) public data: any,
        public dialogRef: MatDialogRef<DevLangComponent>,
        public http: HttpClient,
        public translate: TranslateService,
        private notify: NotificationService
    ) {
    }

    async ngOnInit(): Promise<void> {
        await this.getLangs();
    }

    getLangs(): Promise<MissingLang> {
        return new Promise((resolve) => {
            this.http.get('../rest/languages').pipe(
                map((data: { langs: Record<string, Record<string, string>>}) => data.langs),
                tap((data: Record<string, Record<string, string>>) => {
                    this.allLang = data;

                    Object.keys(this.allLang).forEach(langName => {
                        this.missingLang[langName] = Object.keys(this.allLang.fr).filter((keyLang: any) => Object.keys(this.allLang[langName]).indexOf(keyLang) === -1).map((keyLang: any) => ({
                            id: keyLang,
                            value: this.allLang.fr[keyLang] + '__TO_TRANSLATE'
                        }));
                    });
                    resolve(this.missingLang);
                }),
                finalize(() => this.loading = false),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    this.loading = false;
                    resolve({});
                    return of(false);
                })
            ).subscribe();
        });
    }

    openTranslation(text: string): void {
        window.open('https://translate.google.fr/?hl=fr#view=home&op=translate&sl=fr&tl=' + this.currentLang + '&text=' + text.replace('__TO_TRANSLATE', ''), '_blank');
    }

    setActiveLang(ev: any): void {
        this.currentLang = ev.tab.textLabel;
    }

    generateMissingLang(ignoreToTranslate = false): void {
        this.loading = true;
        const newLang = {};
        let mergedLang = this.allLang[this.currentLang];
        const regex = /__TO_TRANSLATE$/g;

        this.missingLang[this.currentLang].forEach(element => {
            if (element.value.match(regex) === null && ignoreToTranslate) {
                newLang[element.id] = element.value;
            } else if (!ignoreToTranslate) {
                newLang[element.id] = element.value;
            }
        });
        mergedLang = { ...mergedLang, ...newLang };

        this.http.put('../rest/languages', { langId: this.currentLang, jsonContent: mergedLang }).pipe(
            tap(() => {
                Object.keys(newLang).forEach(keyLang => {
                    delete this.allLang[this.currentLang][keyLang];

                    this.missingLang[this.currentLang] = this.missingLang[this.currentLang].filter((missLang: any) => missLang.id !== keyLang);
                    this.data.countMissingLang--;
                });
                this.dialogRef.close(this.data.countMissingLang);
                this.notify.success(this.translate.instant('lang.langVarSuccessfullyGenerated'));
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                this.loading = false;
                return of(false);
            })
        ).subscribe();
    }
}
