<?php

namespace MaarchCourrier\Template\Infrastructure\Repository;

use Exception;
use MaarchCourrier\Core\Domain\Template\Port\TemplateInterface;
use MaarchCourrier\Core\Domain\Template\Port\TemplateRepositoryInterface;
use MaarchCourrier\Template\Domain\Template;
use Template\models\TemplateModel;

class TemplateRepository implements TemplateRepositoryInterface
{
    /**
     * @throws Exception
     */
    public function getById(int $id): ?TemplateInterface
    {
        $template = TemplateModel::getById(['id' => $id]);

        if (empty($template)) {
            return null;
        }

        $signaturePositions = $template['signature_positions'] ?
            json_decode($template['signature_positions'], true) :
            [];

        return (new Template())
            ->setId($template['template_id'])
            ->setLabel($template['template_label'])
            ->setType($template['template_type'])
            ->setSignaturePositions($signaturePositions);
    }

    /**
     * @param int[] $ids
     * @returns TemplateInterface[]
     * @throws Exception
     */
    public function getByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $templatesDB = TemplateModel::get([
            'select' => ['*'],
            'where'  => ['template_id in (?)'],
            'data'   => [$ids]
        ]);

        $templates = [];

        foreach ($templatesDB as $template) {
            $signaturePositions = $template['signature_positions'] ?
                json_decode($template['signature_positions'], true) :
                [];

            $templates[$template['template_id']] = (new Template())
                ->setId($template['template_id'])
                ->setLabel($template['template_label'])
                ->setType($template['template_type'])
                ->setSignaturePositions($signaturePositions);
        }

        return $templates;
    }
}
