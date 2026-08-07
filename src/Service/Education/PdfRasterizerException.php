<?php

declare(strict_types=1);

namespace App\Service\Education;

use RuntimeException;

/**
 * Thrown by {@see PdfRasterizer} when poppler cannot read or render a document. The flatten handler catches it to mark
 * the document as failed, so a single unreadable upload never stops the queue.
 */
final class PdfRasterizerException extends RuntimeException
{
}
