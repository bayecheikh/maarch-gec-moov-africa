<?php

/**
 * Copyright Maarch since 2008 under licence GPLv3.
 * See LICENCE.txt file at the root folder for more details.
 * This file is part of Maarch software.
 *
 */

/**
 * @brief Maileva Shipping Fee Calculation class
 * @author dev@maarch.org
 */

namespace MaarchCourrier\ExternalExport\Application\Maileva;

use Exception;
use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentFileRetrieverFactoryInterface;
use MaarchCourrier\Core\Domain\Attachment\Port\AttachmentInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\MainResourceInterface;
use MaarchCourrier\Core\Domain\MainResource\Port\RetrieveMainResourceFileFactoryInterface;
use MaarchCourrier\DocumentStorage\Domain\Port\FileSystemServiceInterface;
use MaarchCourrier\ExternalExport\Domain\Maileva\MailevaTemplate;

class MailevaShippingFeeCalculation
{
    public function __construct(
        private readonly RetrieveMainResourceFileFactoryInterface $retrieveMainResourceFileFactory,
        private readonly AttachmentFileRetrieverFactoryInterface $attachmentFileRetrieverFactory,
        private readonly FileSystemServiceInterface $fileSystemService
    ) {
    }

    /**
     * Calculates the total shipping fee for an array of grouped documents.
     *
     * The provided array should be structured such that documents are grouped by recipient contact IDs
     * and by main resource IDs. For each group, the method iterates over the documents,
     * retrieves the corresponding file information and the number of pages, and sums the pages for the mailing.
     * Once the total page count is obtained for the mailing, the fee is computed.
     *
     * @param MailevaTemplate $template The template containing fee configuration data
     * (first page price, next page price, postage price, and printing options such as duplex mode).
     *
     * @param array<int, array<int|string, array<MainResourceInterface|AttachmentInterface>>> $groupedMailings
     *  Array grouped by MainResource IDs (integer keys) and recipient contact IDs (integer or string keys),
     *  where each element is an array of documents implementing either MainResourceInterface or AttachmentInterface.
     *
     * @return float The total shipping fee computed across all mailings.
     * @throws Exception If an error occurs during file processing.
     */
    public function calculateTotalFee(
        MailevaTemplate $template,
        array $groupedMailings
    ): float {
        $totalFee = 0.0;

        $isDuplex = in_array('duplexPrinting', $template->getOptions()['shapingOptions'] ?? []);

        foreach ($groupedMailings as $recipientContactIds) {
            /**
             * Keep the total count for any contacts that will get the document,
             * so we do not rebuild the path and re-read the file.
             */
            $mainResourceTotalPageCount = 0;

            foreach ($recipientContactIds as $documents) {
                $mailingPageCount = 0;
                $mailingSheetCount = 0;

                foreach ($documents as $document) {
                    // If MainResource was previously loaded and acquired it's number of pages.
                    // Attachment can only have one associative contact
                    if ($mainResourceTotalPageCount > 0) {
                        $mailingPageCount += $mainResourceTotalPageCount;
                        if ($isDuplex) {
                            $mailingSheetCount += ceil($mainResourceTotalPageCount / 2);
                        } else {
                            $mailingSheetCount += $mainResourceTotalPageCount;
                        }
                        continue;
                    }

                    $fileInfo = match (true) {
                        $document instanceof MainResourceInterface =>
                        $this->retrieveMainResourceFileFactory::create()
                            ->getResourceFile($document->getResId(), false),
                        $document instanceof AttachmentInterface => $this->attachmentFileRetrieverFactory::create()
                            ->getById($document->getResId(), false),
                        default => null
                    };

                    if ($fileInfo === null) {
                        continue;
                    }

                    $filePath = match (true) {
                        gettype($fileInfo) == 'object' => $fileInfo->getPathInfo()['dirname'] . '/' .
                            $fileInfo->getPathInfo()['basename'],
                        gettype($fileInfo) == 'array' => $fileInfo['path']
                    };

                    $pages = $this->fileSystemService->getNumberOfPagesFromPdfFile($filePath);
                    if ($document instanceof MainResourceInterface && empty($mainResourceTotalPageCount)) {
                        $mainResourceTotalPageCount = $pages;
                    }
                    $mailingPageCount += $pages;
                    if ($isDuplex) {
                        $mailingSheetCount += ceil($pages / 2);
                    } else {
                        $mailingSheetCount += $pages;
                    }
                }

                if ($mailingPageCount > 0) {
                    $totalFee += $this->calculateResourceFee($template, $mailingPageCount, $mailingSheetCount);
                }
            }
        }

        return $totalFee;
    }

    /**
     * Calculates the shipping fee for single mailing (pli) based on the total number of pages.
     *
     * The fee calculation for each mailing is defined as:
     *
     *   Fee = First page price + (next page price × (total pages in mailing – 1)) +
     *         (postage price × ceil(total sheets in mailing / 4))
     *
     *
     * @param MailevaTemplate $template The template containing fee configuration values.
     * @param int $totalPliPageCount The total number of pages in the mailing.
     * @param int $totalPliSheetCount The total number of sheets in the mailing.
     *
     * @return float The shipping fee calculated for the mailing.
     */
    private function calculateResourceFee(
        MailevaTemplate $template,
        int $totalPliPageCount,
        int $totalPliSheetCount
    ): float {
        if ($template->getOptions()['sendMode'] === 'ere') {
            return (float)($template->getFee()['ereSendingPrice'] ?? 0.0);
        } else {
            $firstPagePrice = (float)($template->getFee()['firstPagePrice'] ?? 0.0);
            $nextPagePrice = (float)($template->getFee()['nextPagePrice'] ?? 0.0);
            $postagePrice = (float)($template->getFee()['postagePrice'] ?? 0.0);

            $additionalPages = max(0, $totalPliPageCount - 1);
            $postageCost = $postagePrice * ceil($totalPliSheetCount / 4);

            return $firstPagePrice + ($nextPagePrice * $additionalPages) + $postageCost;
        }
    }
}
