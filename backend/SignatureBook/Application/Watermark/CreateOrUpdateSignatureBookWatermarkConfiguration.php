<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Create Or Update SignatureBook Watermark Configuration
 * @author dev@maarch.org
 */

namespace MaarchCourrier\SignatureBook\Application\Watermark;

use MaarchCourrier\Core\Domain\Problem\InvalidDateFormatProblem;
use MaarchCourrier\Core\Domain\Problem\InvalidHexColorProblem;
use MaarchCourrier\Core\Domain\Problem\InvalidNumericProblem;
use MaarchCourrier\Core\Domain\Problem\ParameterCannotBeEmptyProblem;
use MaarchCourrier\Core\Domain\Problem\ParameterIsEmptyOrNotATypeProblem;
use MaarchCourrier\SignatureBook\Domain\Watermark\Port\SignatureBookWatermarkConfigurationServiceInterface;

class CreateOrUpdateSignatureBookWatermarkConfiguration
{
    protected const DATE_FORMAT = [
        'd/m/Y H:i:s',
        'm/d/Y H:i:s',
        'Y-m-d H:i:s',
        'd-m-Y H:i',
        'm-d-Y H:i',
        'Y/m/d H:i',
    ];

    public function __construct(
        private readonly SignatureBookWatermarkConfigurationServiceInterface $electronicWatermarkConfigurationService
    ) {
    }

    /**
     * @param array $body
     * @return array
     * @throws InvalidDateFormatProblem
     * @throws InvalidHexColorProblem
     * @throws InvalidNumericProblem
     * @throws ParameterCannotBeEmptyProblem
     * @throws ParameterIsEmptyOrNotATypeProblem
     */
    private function fieldControl(array $body): array
    {
        if (empty($body)) {
            throw new ParameterCanNotBeEmptyProblem(_ELECTRONIC_SIGNATURE . '-> body');
        } elseif (empty($body['text'] ?? null) || !is_string($body['text'])) {
            throw new ParameterIsEmptyOrNotATypeProblem(_ELECTRONIC_SIGNATURE . '-> text', 'string');
        } elseif (empty($body['font'] ?? null) || !is_string($body['font'])) {
            throw new ParameterIsEmptyOrNotATypeProblem(_ELECTRONIC_SIGNATURE . '-> font', 'string');
        } elseif (empty($body['dateFormat'] ?? null) || !is_string($body['dateFormat'])) {
            throw new ParameterIsEmptyOrNotATypeProblem(_ELECTRONIC_SIGNATURE . '-> dateFormat', 'string');
        } elseif (!in_array($body['dateFormat'], self::DATE_FORMAT)) {
            throw new InvalidDateFormatProblem($body['dateFormat'], self::DATE_FORMAT);
        } elseif (
            empty($body['color'] ?? null) || !is_string($body['color']) ||
            !filter_var($body['color'], FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/^#[0-9A-Fa-f]{6}$/']])
        ) {
            throw new InvalidHexColorProblem(_ELECTRONIC_SIGNATURE);
        }

        foreach (['posX', 'posY', 'angle', 'opacity', 'size'] as $field) {
            if (!isset($body[$field]) || !is_numeric($body[$field])) {
                throw new InvalidNumericProblem(_ELECTRONIC_SIGNATURE . " -> $field");
            }
        }

        $body['enabled'] = $body['enabled'] ?? false;

        $allowedFields = ['enabled', 'posX', 'posY', 'angle', 'opacity', 'size', 'text', 'font', 'color', 'dateFormat'];
        return array_intersect_key($body, array_flip($allowedFields));
    }

    /**
     * @param array $body
     * @return void
     * @throws InvalidDateFormatProblem
     * @throws InvalidHexColorProblem
     * @throws InvalidNumericProblem
     * @throws ParameterCannotBeEmptyProblem
     * @throws ParameterIsEmptyOrNotATypeProblem
     */
    public function execute(array $body): void
    {
        $body = $this->fieldControl($body);
        $this->electronicWatermarkConfigurationService->loadConfig();

        $body['fontColor'] = $body['color'];
        $body['fontSize'] = $body['size'];
        $body['fontOpacity'] = $body['opacity'];
        $body['xPosition'] = $body['posX'];
        $body['yPosition'] = $body['posY'];

        unset(
            $body['color'],
            $body['size'],
            $body['opacity'],
            $body['posX'],
            $body['posY']
        );

        $this->electronicWatermarkConfigurationService->update($body);
    }
}
