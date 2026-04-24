<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Template File Info
 * @author dev@maarch.org
 */

namespace MaarchCourrier\DocumentConversion\Domain;

use JsonSerializable;

class ConvertedFileInfo implements JsonSerializable
{
    private ?int $pageCount = null;
    private string $fileContent;
    private string $format;

    public function getPageCount(): ?int
    {
        return $this->pageCount;
    }

    public function setPageCount(?int $pageCount): self
    {
        $this->pageCount = $pageCount;
        return $this;
    }

    public function getFileContent(): string
    {
        return $this->fileContent;
    }

    public function setFileContent(string $fileContent): self
    {
        $this->fileContent = $fileContent;
        return $this;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function setFormat(string $format): self
    {
        $this->format = $format;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'pageCount'   => $this->getPageCount(),
            'format'      => $this->getFormat(),
            'fileContent' => $this->getFileContent()
        ];
    }
}
