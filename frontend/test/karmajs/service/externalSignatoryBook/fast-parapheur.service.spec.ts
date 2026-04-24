import { TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';
import { NotificationService } from '@service/notification/notification.service';
import { TranslateService } from '@ngx-translate/core';
import { FunctionsService } from '@service/functions.service';
import { FastParapheurService } from "@service/externalSignatoryBook/fast-parapheur.service";

describe('FastParapheurService', () => {
    let service: FastParapheurService;
    let httpMock: HttpTestingController;

    // Mock services
    const notifyMock = {
        handleErrors: jasmine.createSpy('handleErrors'),
        handleSoftErrors: jasmine.createSpy('handleSoftErrors'),
        success: jasmine.createSpy('success')
    };
    const translateMock = {
        instant: (key: string) => key
    };
    const functionsMock = {
        empty: (value: any) => value === null || value === undefined || value.length === 0
    };

    beforeEach(() => {
        TestBed.configureTestingModule({
            imports: [HttpClientTestingModule],
            providers: [
                FastParapheurService,
                { provide: NotificationService, useValue: notifyMock },
                { provide: TranslateService, useValue: translateMock },
                { provide: FunctionsService, useValue: functionsMock }
            ]
        });

        service = TestBed.inject(FastParapheurService);
        httpMock = TestBed.inject(HttpTestingController);
    });

    afterEach(() => {
        httpMock.verify();
    });

    /**
     * UNIT TESTS (TU)
     */
    describe('TU - Unit tests', () => {

        it('should fetch workflow details successfully', async () => {
            const mockData = {
                workflowTypes: [{ id: 'b_pdf', label: 'BUREAUTIQUE_PDF' }],
                signatureModes: [{ id: 'sign' }],
                otpStatus: true
            };

            const promise = service.getWorkflowDetails();
            const req = httpMock.expectOne('../rest/fastParapheurWorkflowDetails');
            expect(req.request.method).toBe('GET');
            req.flush(mockData);

            const result = await promise;
            expect(result.types.length).toBe(1);
            expect(service.signatureModes).toEqual(['sign']);
            expect(service.canAddExternalUser).toBe(true);
        });

        it('should handle workflow details error', async () => {
            const promise = service.getWorkflowDetails();
            const req = httpMock.expectOne('../rest/fastParapheurWorkflowDetails');
            req.error(new ErrorEvent('Network error'));

            const result = await promise;
            expect(result).toBeNull();
            expect(notifyMock.handleErrors).toHaveBeenCalled();
        });

        it('should load OTP config', async () => {
            const result = await service.getOtpConfig();
            expect(result.length).toBe(1);
            expect(result[0].type).toBe('fast');
        });

        it('should get ressources IDs from attachments', () => {
            const attachments = { attachments: [{ res_id: 101 }, { res_id: 202 }] };
            const result = service.getRessources(attachments);
            expect(result).toEqual([101, 202]);
        });

        it('should validate paraph when conditions met', () => {
            service.workflowTypes = [{ id: '1', label: 'BUREAUTIQUE_PDF' }];
            service.signatureModes = ['sign', 'visa'];
            const result = service.isValidParaph({ attachments: [{}] }, [{}], [], []);
            expect(result).toBe(true);
        });

        it('should allow summary sheet if no external OTP FAST user exists', () => {
            const result = service.canAttachSummarySheet([]);
            expect(result).toBe(true);
        });

        it('should block summary sheet if external FAST user exists', () => {
            const mockWorkflow = [
                { externalInformations: { type: 'fast' } }
            ];
            spyOn(functionsMock, 'empty').and.returnValue(false);
            const result = service.canAttachSummarySheet(mockWorkflow);
            expect(result).toBe(false);
        });
    });

    /**
     * FUNCTIONAL TESTS (TF)
     * Here we simulate the complete flow for certain methods.
     */
    describe('TF - Functional tests', () => {

        it('should fetch user avatar and return base64', async () => {
            const mockBlob = new Blob(['avatar'], { type: 'image/png' });

            const promise = service.getUserAvatar();
            const req = httpMock.expectOne('assets/fast.png');
            expect(req.request.method).toBe('GET');
            expect(req.request.responseType).toBe('blob');
            req.flush(mockBlob);

            const result = await promise;
            expect(typeof result).toBe('string'); // base64 encoded string
        });

        it('should link account to FastParapheur successfully', async () => {
            const promise = service.linkAccountToSignatoryBook({
                email: 'maarch@fast.fr',
                idToDisplay: 'Fast User'
            }, 123);
            const req = httpMock.expectOne('../rest/users/123/linkToFastParapheur');
            expect(req.request.method).toBe('PUT');
            req.flush({});

            const result = await promise;
            expect(result).toBe(true);
            expect(notifyMock.success).toHaveBeenCalled();
        });

        it('should unlink account successfully', async () => {
            const promise = service.unlinkSignatoryBookAccount(123);
            const req = httpMock.expectOne('../rest/users/123/unlinkToFastParapheur');
            req.flush({});

            const result = await promise;
            expect(result).toBe(true);
        });

        it('should load workflow', async () => {
            const mockWorkflow = [
                {
                    "mode": "init",
                    "order": 1,
                    "status": "Préparé",
                    "userId": null,
                    "isSystem": false,
                    "processDate": "2025-08-13 15:38:13",
                    "userDisplay": "Utilisateur Fast Parapheur (DEPOT MAARCH)"
                },
                {
                    "mode": null,
                    "order": 2,
                    "status": "Envoyé pour signature",
                    "userId": null,
                    "isSystem": true,
                    "processDate": "2025-08-13 15:38:13",
                    "userDisplay": "Action Système"
                },
                {
                    "mode": "visa",
                    "order": 3,
                    "status": "Refusé",
                    "userId": 19,
                    "isSystem": false,
                    "processDate": "2025-08-13 15:55:41",
                    "userDisplay": "SIGNATAIRE MAARCH (Barbara BAIN)"
                },
                {
                    "mode": "sign",
                    "order": 4,
                    "status": "Refusé",
                    "userId": null,
                    "isSystem": false,
                    "processDate": "2025-08-13 15:55:41",
                    "userDisplay": "Utilisateur Fast Parapheur (Jérôme SIGNATAIRE)"
                }
            ];
            const promise = service.loadWorkflow(456, 'attachment');
            const req = httpMock.expectOne('../rest/documents/456/fastParapheurWorkflow?type=attachment');
            req.flush(mockWorkflow);

            const result = await promise;

            expect(result).toEqual({ workflow: mockWorkflow });
        });
    });
});
