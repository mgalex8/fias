<?php

declare(strict_types=1);

namespace marvin255\fias\mapper\fias;

use marvin255\fias\mapper\AbstractMapper;
use marvin255\fias\mapper\field;

/**
 * Нормативные документы.
 */
class NormativeDocument extends AbstractMapper
{
    /**
     * @inheritdoc
     */
    protected function createFields(): array
    {
        return [
            'NORMDOCID' => new field\Line,
            'DOCNAME' => new field\Line,
            'DOCDATE' => new field\Date,
            'DOCNUM' => new field\Line,
            'DOCTYPE' => new field\Line,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getXmlPath(): string
    {
        return '/NormativeDocumentes/NormativeDocument';
    }

    /**
     * @inheritdoc
     */
    public function getSqlPrimary(): array
    {
        return ['NORMDOCID'];
    }
}
