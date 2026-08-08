<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

final class DocumentNumberService
{
    public function next(string $type, int $year): string
    {
        DB::table('document_counters')->insertOrIgnore([
            'document_type' => $type,
            'year' => $year,
            'next_number' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $counter = DB::table('document_counters')
            ->where('document_type', $type)
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        $sequence = max(1, (int) ($counter?->next_number ?? 1));
        DB::table('document_counters')
            ->where('document_type', $type)
            ->where('year', $year)
            ->update(['next_number' => $sequence + 1, 'updated_at' => now()]);

        $prefix = match ($type) {
            'invoice' => 'RAC',
            'proforma' => 'PON',
            'delivery_note' => 'OTP',
            'payment' => 'UPL',
            'refund' => 'REF',
            'stock_receipt' => 'ULZ',
            'inventory_count' => 'POP',
            'warranty' => 'GAR',
            'receivable' => 'NAP',
            default => 'POR',
        };

        return sprintf('%s-%d-%06d', $prefix, $year, $sequence);
    }
}
