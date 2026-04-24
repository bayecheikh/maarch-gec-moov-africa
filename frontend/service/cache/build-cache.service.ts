import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, of } from 'rxjs';
import { catchError, map } from 'rxjs/operators';

/**
 * Interface defining the build information structure
 * Contains timestamp and optional branch and commit details
 */
interface BuildInfo {
    buildTimestamp: string;
    branch?: string;
    commit?: string;
}

/**
 * Service responsible for detecting application build changes
 * Helps implement automatic refresh when new versions are deployed
 */
@Injectable({
    providedIn: 'root'
})
export class BuildCacheService {
    private readonly BUILD_INFO_KEY = 'maarch_build_info';
    private readonly CHECK_FLAG_KEY = 'maarch_check_in_progress';
    private readonly buildInfoUrl = 'assets/build-info.json';

    constructor(private http: HttpClient) {}

    /**
     * Initializes the build verification process
     * @returns Promise resolving to boolean indicating if normal loading should continue
     */
    public initBuildCheck(): Promise<boolean> {
        // Prevent infinite reload loops
        if (sessionStorage.getItem(this.CHECK_FLAG_KEY) === 'true') {
            sessionStorage.removeItem(this.CHECK_FLAG_KEY);
            return Promise.resolve(true);
        }

        return this.checkBuildChanged().toPromise();
    }

    /**
     * Checks if the application build has changed since the last visit
     * @returns Observable<boolean> indicating if normal loading should continue
     */
    private checkBuildChanged(): Observable<boolean> {
        sessionStorage.setItem(this.CHECK_FLAG_KEY, 'true');

        // Add random parameter to bypass HTTP cache
        const cacheBuster = `?_=${Date.now()}`;

        return this.http.get<BuildInfo>(`${this.buildInfoUrl}${cacheBuster}`).pipe(
            map(buildInfo => {
                // Retrieve stored information
                const storedInfoStr = localStorage.getItem(this.BUILD_INFO_KEY);
                let storedInfo: BuildInfo | null = null;

                if (storedInfoStr) {
                    try {
                        storedInfo = JSON.parse(storedInfoStr);
                    } catch (e) {
                        console.error('Error parsing stored build info');
                    }
                }

                console.debug('Current build:', buildInfo);
                console.debug('Stored build:', storedInfo);

                // If no stored info or change detected
                if (!storedInfo || storedInfo.buildTimestamp !== buildInfo.buildTimestamp) {
                    console.debug('Build change detected');

                    // Save new information
                    localStorage.setItem(this.BUILD_INFO_KEY, JSON.stringify(buildInfo));

                    // If not the first visit, force a reload
                    if (storedInfo) {
                        this.clearCacheAndReload();
                        return false; // Application will restart
                    }
                }

                sessionStorage.removeItem(this.CHECK_FLAG_KEY);
                return true; // Continue normal loading
            }),
            catchError(error => {
                console.error('Error while checking build:', error);
                sessionStorage.removeItem(this.CHECK_FLAG_KEY);
                return of(true); // Continue in case of error
            })
        );
    }

    /**
     * Clears caches and reloads the application
     */
    public clearCacheAndReload(): void {
        console.debug('Clearing cache and reloading...');

        // 1. Clear browser caches
        this.clearBrowserCaches().then(() => {
            // 2. Reload the application
            window.location.reload();
        });
    }

    /**
     * Clears different browser caches
     * @returns Promise that resolves when cache clearing is complete
     */
    private clearBrowserCaches(): Promise<void> {
        const promises: Promise<any>[] = [];

        /*
        * 1. Cache API (Service Worker)
        */
        if (window.caches) {
            const cachePromise = window.caches.keys().then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cacheName => {
                        console.debug(`Deleting cache: ${cacheName}`);
                        return window.caches.delete(cacheName);
                    })
                );
            });
            promises.push(cachePromise);
        }

        // Define cache patterns to remove and patterns to preserve
        const cachePatterns: string[] = ['pdftron', 'fortify', 'ragtime', 'webviewer', '_chunk'];
        const preservePatterns: string[] = ['user_preferences', 'auth', 'language', 'settings', 'token', 'lang_'];

        /*
        * 2. Clean cache entries in localStorage
        */
        const shouldRemoveKey = (key: string): boolean => {
            // Case-insensitive check for 'cache'
            const lowerKey: string = key.toLowerCase();

            // Don't remove if it matches preserve patterns
            if (preservePatterns.some(pattern => lowerKey.includes(pattern.toLowerCase()))) {
                return false;
            }

            // Check for any cache patterns
            if (
                lowerKey.includes('cache') ||
                cachePatterns.some(pattern => lowerKey.includes(pattern.toLowerCase()))
            ) {
                return true;
            }

            return false; // Default to not removing if no patterns match
        };

        const cacheKeysToRemove = Object.keys(localStorage).filter(shouldRemoveKey);

        cacheKeysToRemove.forEach(key => {
            console.debug(`Removing localStorage key: ${key}`);
            localStorage.removeItem(key);
        });

        // 3. Clear sessionStorage (except our flag)
        const currentFlag = sessionStorage.getItem(this.CHECK_FLAG_KEY);
        sessionStorage.clear();
        if (currentFlag) {
            sessionStorage.setItem(this.CHECK_FLAG_KEY, currentFlag);
        }

        return Promise.all(promises)
            .then(() => {
                console.debug('Cache cleaning completed');
            })
            .catch(err => {
                console.error('Error while cleaning caches', err);
            });
    }
}