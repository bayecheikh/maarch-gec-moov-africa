<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Create Or Update Watermark Configuration
 * @author dev@maarch.org
 */

namespace MaarchCourrier\Watermark\Application;

use MaarchCourrier\Authorization\Domain\Privilege\AdminParameterPrivilege;
use MaarchCourrier\Authorization\Domain\Privilege\AdminWatermarkAttachmentParametersPrivilege;
use MaarchCourrier\Authorization\Domain\Privilege\AdminWatermarkParametersPrivilege;
use MaarchCourrier\Authorization\Domain\Problem\ServiceForbiddenProblem;
use MaarchCourrier\Core\Domain\Authorization\Port\PrivilegeCheckerInterface;
use MaarchCourrier\Core\Domain\Configuration\Port\ConfigurationRepositoryInterface;
use MaarchCourrier\Core\Domain\Port\EnvironmentInterface;
use MaarchCourrier\Core\Domain\Problem\InvalidDateFormatProblem;
use MaarchCourrier\Core\Domain\Problem\InvalidHexColorProblem;
use MaarchCourrier\Core\Domain\Problem\InvalidNumericProblem;
use MaarchCourrier\Core\Domain\Problem\InvalidRgbColorArrayProblem;
use MaarchCourrier\Core\Domain\Problem\ParameterCannotBeEmptyProblem;
use MaarchCourrier\Core\Domain\Problem\ParameterIsEmptyOrNotATypeProblem;
use MaarchCourrier\Core\Domain\SignatureBook\Port\CreateOrUpdateSignatureBookWatermarkConfigFactoryInterface;
use MaarchCourrier\Core\Domain\User\Port\CurrentUserInterface;

class CreateOrUpdateWatermarkConfiguration
{
    public function __construct(
        private readonly PrivilegeCheckerInterface $privilegeChecker,
        private readonly CurrentUserInterface $currentUser,
        private readonly ConfigurationRepositoryInterface $configurationRepository,
        private readonly EnvironmentInterface $environment,
        private readonly CreateOrUpdateSignatureBookWatermarkConfigFactoryInterface $createOrUpdateSBWCFactory
    ) {
    }

    /**
     * @param array $body
     * @return array
     * @throws InvalidNumericProblem
     * @throws InvalidRgbColorArrayProblem
     * @throws ParameterCannotBeEmptyProblem
     * @throws ParameterIsEmptyOrNotATypeProblem
     */
    private function fieldControl(array $body): array
    {
        if (empty($body)) {
            throw new ParameterCanNotBeEmptyProblem('body');
        } elseif (empty($body['text'] ?? null) || !is_string($body['text'])) {
            throw new ParameterIsEmptyOrNotATypeProblem('text', 'string');
        } elseif (empty($body['font'] ?? null) || !is_string($body['font'])) {
            throw new ParameterIsEmptyOrNotATypeProblem('font', 'string');
        } elseif (empty($body['color'] ?? null) || !is_array($body['color']) || count($body['color']) != 3) {
            throw new InvalidRgbColorArrayProblem();
        }

        foreach ($body['color'] as $position => $color) {
            if (!is_numeric($color)) {
                throw new InvalidNumericProblem("color at position " . ($position + 1));
            }
        }

        foreach (['posX', 'posY', 'angle', 'opacity', 'size'] as $field) {
            if (!isset($body[$field]) || !is_numeric($body[$field])) {
                throw new InvalidNumericProblem($field);
            }
        }

        $body['enabled'] = $body['enabled'] ?? false;

        $allowedFields = ['enabled', 'posX', 'posY', 'angle', 'opacity', 'size', 'text', 'font', 'color'];
        return array_intersect_key($body, array_flip($allowedFields));
    }

    /**
     * @param array $body
     * @return void
     * @throws InvalidNumericProblem
     * @throws InvalidRgbColorArrayProblem
     * @throws ParameterCannotBeEmptyProblem
     * @throws ParameterIsEmptyOrNotATypeProblem
     * @throws ServiceForbiddenProblem|InvalidHexColorProblem|InvalidDateFormatProblem
     */
    public function execute(array $body): void
    {
        if (
            !$this->privilegeChecker->hasPrivilege($this->currentUser->getCurrentUser(), new AdminParameterPrivilege())
        ) {
            throw new ServiceForbiddenProblem();
        }

        $documentsWatermarkConfig = $this->fieldControl($body['documents'] ?? []);

        if (!empty($documentsWatermarkConfig)) {
            $configuration = $this->configurationRepository->getByPrivilege(new AdminWatermarkParametersPrivilege());
            if (empty($configuration)) {
                $this->configurationRepository->createByPrivilege(
                    new AdminWatermarkParametersPrivilege(),
                    $documentsWatermarkConfig
                );
            } else {
                $this->configurationRepository->updateByPrivilege(
                    new AdminWatermarkParametersPrivilege(),
                    $documentsWatermarkConfig
                );
            }
        }

        if (!empty($body['attachments'])) {
            $attachmentsWatermarkConfig = $this->fieldControl($body['attachments']);
            if (!empty($attachmentsWatermarkConfig)) {
                $configuration = $this->configurationRepository
                    ->getByPrivilege(new AdminWatermarkAttachmentParametersPrivilege());
                if (empty($configuration)) {
                    $this->configurationRepository->createByPrivilege(
                        new AdminWatermarkAttachmentParametersPrivilege(),
                        $attachmentsWatermarkConfig
                    );
                } else {
                    $this->configurationRepository->updateByPrivilege(
                        new AdminWatermarkAttachmentParametersPrivilege(),
                        $attachmentsWatermarkConfig
                    );
                }
            }
        }

        if (
            $this->environment->isNewInternalParapheurEnabled() &&
            isset($body['electronicSignature'])
        ) {
            $instance = $this->createOrUpdateSBWCFactory->create();
            $instance->execute($body['electronicSignature']);
        }
    }
}
