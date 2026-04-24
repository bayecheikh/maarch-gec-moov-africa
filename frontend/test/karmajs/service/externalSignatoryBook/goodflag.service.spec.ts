import { TestBed } from '@angular/core/testing';
import { HttpClientTestingModule, HttpTestingController } from '@angular/common/http/testing';

import { NotificationService } from '@service/notification/notification.service';
import { TranslateService } from '@ngx-translate/core';
import { FunctionsService } from '@service/functions.service';
import { AuthService } from '@service/auth.service';
import { GoodFlagTemplateInterface } from '@models/goodflag.model';
import { GoodflagService } from "@service/externalSignatoryBook/goodflag.service";

describe('GoodflagService', () => {
    let service: GoodflagService;
    let httpMock: HttpTestingController;
    let notificationService: jasmine.SpyObj<NotificationService>;
    let translateService: jasmine.SpyObj<TranslateService>;
    let functionsService: jasmine.SpyObj<FunctionsService>;
    let authService: jasmine.SpyObj<AuthService>;

    // Mock data for testing
    const mockTemplates: GoodFlagTemplateInterface[] = [
        {
            id: 'gd-1',
            label: 'Template 1',
            description: 'Test template 1',
            signatureProfileId: 'SP1234',
            consentPageId: 'CP1234'
        },
        {
            id: 'gd-2',
            label: 'Template 2',
            description: 'Test template 2',
            signatureProfileId: 'SP12345',
            consentPageId: 'CP12345'
        },
    ];

    const mockCorrespondents = [
        {
            id: 1,
            idToDisplay: 'Barbara BAIN',
            firstname: 'Barbara',
            lastname: 'BAIN',
            email: 'bbain@maarch.org',
            type: 'external',
            phoneNumber: '+1234567890',
            country: 'US'
        }
    ];

    const mockWorkflowData = {
        workflow: [
            {
                "id": "usr_5uRRaAeJmBkHUrk8wSApUZDd",
                "mode": "sign",
                "step": 1,
                "type": "user",
                "required": true,
                "processedDate": null
            }
        ]
    };

    beforeEach(() => {
        // Create spies for all injected services
        const notificationSpy = jasmine.createSpyObj('NotificationService', ['handleSoftErrors', 'handleErrors']);
        const translateSpy = jasmine.createSpyObj('TranslateService', ['instant']);
        const functionsSpy = jasmine.createSpyObj('FunctionsService', ['empty']);
        const authSpy = jasmine.createSpyObj('AuthService', [], {
            externalSignatoryBook: { optionOtp: true }
        });

        TestBed.configureTestingModule({
            imports: [HttpClientTestingModule],
            providers: [
                GoodflagService,
                { provide: NotificationService, useValue: notificationSpy },
                { provide: TranslateService, useValue: translateSpy },
                { provide: FunctionsService, useValue: functionsSpy },
                { provide: AuthService, useValue: authSpy }
            ]
        });

        service = TestBed.inject(GoodflagService);
        httpMock = TestBed.inject(HttpTestingController);
        notificationService = TestBed.inject(NotificationService) as jasmine.SpyObj<NotificationService>;
        translateService = TestBed.inject(TranslateService) as jasmine.SpyObj<TranslateService>;
        functionsService = TestBed.inject(FunctionsService) as jasmine.SpyObj<FunctionsService>;
        authService = TestBed.inject(AuthService) as jasmine.SpyObj<AuthService>;

        // Setup default spy returns
        translateService.instant.and.returnValue('OTP Goodflag');
    });

    afterEach(() => {
        // Verify that no unmatched requests remain
        httpMock.verify();
    });

    describe('Service Initialization', () => {
        it('should be created', () => {
            expect(service).toBeTruthy();
        });

        it('should initialize with correct default values', () => {
            expect(service.canCreateUser).toBeFalse();
            expect(service.canSynchronizeSignatures).toBeFalse();
            expect(service.canViewWorkflow).toBeTrue();
            expect(service.canCreateTile).toBeFalse();
            expect(service.canAddExternalUser).toBeTrue();
            expect(service.canManageSignaturesPositions).toBeTrue();
            expect(service.canAddDateBlock).toBeFalse();
            expect(service.canAddSteps).toBeTrue();
            expect(service.isMappingWithTechnicalStatus).toBeFalse();
            expect(service.signatureModes).toEqual(['sign']);
        });

        it('should set canAddExternalUser based on auth service configuration', () => {
            expect(service.canAddExternalUser).toBe(authService.externalSignatoryBook.optionOtp);
        });
    });

    describe('getWorkflowDetails', () => {
        it('should successfully fetch workflow templates', async () => {
            // Arrange
            const promise = service.getWorkflowDetails();

            // Act
            const req = httpMock.expectOne('../rest/goodflag/templates');
            expect(req.request.method).toBe('GET');
            req.flush(mockTemplates);

            // Assert
            const result = await promise;
            expect(result).toEqual(mockTemplates);
            expect(service.workflowTypes).toEqual(mockTemplates);
        });
    });

    describe('getUserAvatar', () => {
        it('should successfully fetch and convert avatar to base64', async () => {
            // Arrange
            const mockBlob = new Blob(['test'], { type: 'image/jpeg' });
            const promise = service.getUserAvatar();

            // Act
            const req = httpMock.expectOne('assets/goodflag.jpg');
            expect(req.request.method).toBe('GET');
            expect(req.request.responseType).toBe('blob');
            req.flush(mockBlob);

            // Assert
            const result = await promise;
            expect(result).toContain('data:'); // Base64 data URL format
        });
    });

    describe('getOtpConfig', () => {
        it('should return OTP connector configuration', async () => {
            // Act
            const result = await service.getOtpConfig();

            // Assert
            expect(result).toEqual([{
                id: 1,
                label: 'OTP Goodflag',
                type: 'goodflag'
            }]);
            expect(service.otpConnectors).toEqual(result);
            expect(translateService.instant).toHaveBeenCalledWith('lang.otpGoodflag');
        });
    });

    describe('loadListModel', () => {
        it('should return empty array', async () => {
            // Act
            const result = await service.loadListModel();

            // Assert
            expect(result).toEqual([]);
        });
    });

    describe('loadWorkflow', () => {
        it('should successfully load workflow for a document', async () => {
            // Arrange
            const resId = 123;
            const type = 'attachment';
            const promise = service.loadWorkflow(resId, type);

            // Act
            const req = httpMock.expectOne(`../rest/documents/${resId}/goodFlagWorkflow?type=${type}`);
            expect(req.request.method).toBe('GET');
            req.flush(mockWorkflowData.workflow);

            // Assert
            const result = await promise;
            expect(result).toEqual({ workflow: mockWorkflowData.workflow });
        });
    });

    describe('getAutocompleteDatas', () => {
        it('should successfully fetch autocomplete data', async () => {
            // Arrange
            const searchData = { user: { mail: 'bbain@maarch.org' } };
            const promise = service.getAutocompleteDatas(searchData);

            // Act
            const req = httpMock.expectOne(req =>
                req.url.includes('/rest/autocomplete/goodflag') &&
                req.params.get('search') === 'bbain@maarch.org' &&
                req.params.get('excludeAlreadyConnected') === 'true'
            );
            expect(req.request.method).toBe('GET');
            req.flush(mockCorrespondents);

            // Assert
            const result = await promise;
            expect(result).toEqual(mockCorrespondents);
        });
    });

    describe('linkAccountToSignatoryBook', () => {
        it('should return empty array', async () => {
            // Act
            const result = await service.linkAccountToSignatoryBook();

            // Assert
            expect(result).toEqual([]);
        });
    });

    describe('unlinkSignatoryBookAccount', () => {
        it('should return empty array', async () => {
            // Act
            const result = await service.unlinkSignatoryBookAccount();

            // Assert
            expect(result).toEqual([]);
        });
    });

    describe('checkInfoExternalSignatoryBookAccount', () => {
        it('should return empty array', async () => {
            // Act
            const result = await service.checkInfoExternalSignatoryBookAccount();

            // Assert
            expect(result).toEqual([]);
        });
    });

    describe('setExternalInformation', () => {
        it('should transform item into UserWorkflowInterface format', async () => {
            // Arrange
            const inputItem = {
                id: 1,
                firstname: 'Barbara',
                lastname: 'BAIN',
                externalId: { goodflag: 'gf123' }
            };

            // Act
            const result = await service.setExternalInformation(inputItem);

            // Assert
            expect(result.id).toBe(1);
            expect(result.item_id).toBe(1);
            expect(result.idToDisplay).toBe('Barbara BAIN');
            expect(result.role).toBe('sign');
            expect(result.isValid).toBeTrue();
            expect(result.hasPrivilege).toBeTrue();
            expect(result.signatureModes).toEqual(['sign']);
            expect(result.availableRoles).toEqual(['sign']);
            expect(result.externalId).toEqual({ goodflag: 'gf123' });


        });

        it('should handle item without externalId', async () => {
            // Arrange
            const inputItem = {
                id: 2,
                firstname: 'Barbara',
                lastname: 'BAIN'
            };

            // Act
            const result = await service.setExternalInformation(inputItem);

            // Assert
            expect(result.externalId).toEqual({ goodflag: undefined });
        });
    });

    describe('getRessources', () => {
        it('should extract resource IDs from attachments', () => {
            // Arrange
            const additionalsInfos = {
                attachments: [
                    { res_id: 1, name: 'doc1.pdf' },
                    { res_id: 2, name: 'doc2.pdf' },
                    { res_id: 3, name: 'doc3.pdf' }
                ]
            };

            // Act
            const result = service.getRessources(additionalsInfos);

            // Assert
            expect(result).toEqual([1, 2, 3]);
        });

        it('should return empty array when no attachments', () => {
            // Arrange
            const additionalsInfos = { attachments: [] };

            // Act
            const result = service.getRessources(additionalsInfos);

            // Assert
            expect(result).toEqual([]);
        });
    });

    describe('isValidParaph', () => {
        beforeEach(() => {
            // Setup service with required data
            service.workflowTypes = mockTemplates;
        });

        it('should return true when all conditions are met', () => {
            // Arrange
            const additionalsInfos = { attachments: [{ res_id: 1 }] };
            const workflow = [{
                "id": "usr_5uRRaAeJmBkHUrk8wSApUZDd",
                "mode": "sign",
                "step": 1,
                "type": "goodflagUser",
                "required": true,
                "processedDate": null
            }];
            const resourcesToSign = [];
            const userOtps = [];

            // Act
            const result = service.isValidParaph(additionalsInfos, workflow, resourcesToSign, userOtps);

            // Assert
            expect(result).toBeTrue();
        });

        it('should return false when no attachments', () => {
            // Arrange
            const additionalsInfos = { attachments: [] };
            const workflow = [{
                "id": "usr_5uRRaAeJmBkHUrk8wSApUZDd",
                "mode": "sign",
                "step": 1,
                "type": "goodflagUser",
                "required": true,
                "processedDate": null
            }];
            const resourcesToSign = [];
            const userOtps = [];

            // Act
            const result = service.isValidParaph(additionalsInfos, workflow, resourcesToSign, userOtps);

            // Assert
            expect(result).toBeFalse();
        });

        it('should return false when workflow is empty', () => {
            // Arrange
            const additionalsInfos = { attachments: [{ res_id: 1 }] };
            const workflow = [];
            const resourcesToSign = [];
            const userOtps = [];

            // Act
            const result = service.isValidParaph(additionalsInfos, workflow, resourcesToSign, userOtps);

            // Assert
            expect(result).toBeFalse();
        });
    });

    describe('canAttachSummarySheet', () => {
        beforeEach(() => {
            functionsService.empty.and.returnValue(false);
        });

        it('should return true when workflow is empty', () => {
            // Act
            const result = service.canAttachSummarySheet([]);

            // Assert
            expect(result).toBeTrue();
        });

        it('should return true when no external users with fast type', () => {
            // Arrange
            const visaWorkflow = [
                { externalInformations: { type: 'goodflagOtp' } }
            ];

            // Act
            const result = service.canAttachSummarySheet(visaWorkflow);

            // Assert
            expect(result).toBeTrue();
        });

        it('should return true when users have no external information', () => {
            // Arrange
            functionsService.empty.and.returnValue(true);
            const visaWorkflow = [
                { externalInformations: null },
                { externalInformations: undefined }
            ];

            // Act
            const result = service.canAttachSummarySheet(visaWorkflow);

            // Assert
            expect(result).toBeTrue();
        });
    });

    describe('synchronizeSignatures', () => {
        it('should be defined as a method', () => {
            // Act & Assert
            expect(service.synchronizeSignatures).toBeDefined();
            expect(typeof service.synchronizeSignatures).toBe('function');

            // Should not throw when called
            expect(() => service.synchronizeSignatures({})).not.toThrow();
        });
    });

    describe('downloadProof', () => {
        let createElementSpy: jasmine.Spy;
        let appendChildSpy: jasmine.Spy;
        let clickSpy: jasmine.Spy;

        beforeEach(() => {
            // Mock DOM manipulation
            const mockLink = {
                href: '',
                setAttribute: jasmine.createSpy('setAttribute'),
                click: jasmine.createSpy('click')
            };

            createElementSpy = spyOn(document, 'createElement').and.returnValue(mockLink as any);
            appendChildSpy = spyOn(document.body, 'appendChild');
            clickSpy = mockLink.click;
        });

        it('should successfully download proof certificate', async () => {
            // Arrange
            const goodflagWorkflowId = 'workflow123';
            const mockResponse = {
                encodedDocument: 'base64encodedpdf',
                filename: 'certificate.pdf'
            };
            const promise = service.downloadProof(goodflagWorkflowId);

            // Act
            const req = httpMock.expectOne(`../rest/goodflag/${goodflagWorkflowId}/downloadEvidenceCertificate`);
            expect(req.request.method).toBe('GET');
            expect(req.request.responseType).toBe('json');
            req.flush(mockResponse);

            // Assert
            const result = await promise;
            expect(result).toBeTrue();
            expect(createElementSpy).toHaveBeenCalledWith('a');
            expect(appendChildSpy).toHaveBeenCalled();
            expect(clickSpy).toHaveBeenCalled();
        });

        it('should handle error when downloading proof fails', async () => {
            // Arrange
            const goodflagWorkflowId = 'workflow123';
            const promise = service.downloadProof(goodflagWorkflowId);

            // Act
            const req = httpMock.expectOne(`../rest/goodflag/${goodflagWorkflowId}/downloadEvidenceCertificate`);
            req.error(new ErrorEvent('Network error'));

            // Assert
            const result = await promise;
            expect(result).toBeFalse();
            expect(notificationService.handleSoftErrors).toHaveBeenCalled();
        });
    });

    describe('Error Handling', () => {
        it('should handle HTTP errors gracefully across all methods', async () => {
            // Start the async operations without awaiting
            const workflowDetailsPromise = service.getWorkflowDetails();
            const loadWorkflowPromise = service.loadWorkflow(123, 'attachment');
            const autocompletePromise = service.getAutocompleteDatas({ user: { mail: 'bbain@maarch.org' } });
            const downloadProofPromise = service.downloadProof('gd1234');

            // Wait a tick to ensure HTTP requests are initiated
            await new Promise(resolve => setTimeout(resolve, 0));

            // Match and error all pending HTTP requests
            const requests = httpMock.match(() => true);
            expect(requests.length).toBe(4); // Verify we have 4 requests

            requests.forEach(req => {
                req.error(new ErrorEvent('Test error'));
            });

            // Wait for all promises to resolve
            const results = await Promise.all([
                workflowDetailsPromise,
                loadWorkflowPromise,
                autocompletePromise,
                downloadProofPromise
            ]);

            // Verify that all methods handled errors gracefully
            expect(results[0]).toEqual([]); // getWorkflowDetails returns templates or resolves to falsy on error
            expect(results[1]).toBeNull(); // loadWorkflow returns null on error
            expect(results[2]).toBeNull(); // getAutocompleteDatas returns null on error
            expect(results[3]).toBeFalse(); // downloadProof returns false on error

            // Verify error handling was called for each HTTP error
            expect(notificationService.handleSoftErrors).toHaveBeenCalledTimes(4);
            expect(notificationService.handleErrors).toHaveBeenCalledTimes(0);
        });
    });

});