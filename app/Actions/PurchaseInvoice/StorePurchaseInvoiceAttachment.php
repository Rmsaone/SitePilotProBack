<?php

declare(strict_types=1);

namespace App\Actions\PurchaseInvoice;

use App\Models\InvoiceOther;
use App\Models\InvoiceSubcontractor;
use App\Models\InvoiceSupplier;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Stores the scanned invoice (PDF / image) on the private disk.
 */
final class StorePurchaseInvoiceAttachment
{
    public const string DISK = 'local';

    public static function store(
        InvoiceSupplier|InvoiceSubcontractor|InvoiceOther $invoice,
        ?UploadedFile $file,
    ): void {
        if (! $file instanceof UploadedFile) {
            return;
        }

        $previous = $invoice->attachment_path;

        $invoice->update([
            'attachment_path' => $file->store("invoices/{$invoice->getTable()}/{$invoice->id}", self::DISK),
            'attachment_name' => $file->getClientOriginalName(),
        ]);

        if ($previous !== null) {
            Storage::disk(self::DISK)->delete($previous);
        }
    }

    public static function download(
        InvoiceSupplier|InvoiceSubcontractor|InvoiceOther $invoice,
    ): StreamedResponse {
        abort_if($invoice->attachment_path === null, Response::HTTP_NOT_FOUND);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk(self::DISK);

        return $disk->download($invoice->attachment_path, $invoice->attachment_name);
    }
}
