<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        $this->moveFiles(
            [
                1 => 'maintenance/quotations',
                2 => 'maintenance/evidence',
            ],
            [
                1 => 'maintenance/cotizacion',
                2 => 'maintenance/evidencias',
            ],
            false
        );
    }

    public function down(): void
    {
        $this->moveFiles(
            [
                1 => 'maintenance/cotizacion',
                2 => 'maintenance/evidencias',
            ],
            [
                1 => 'maintenance/quotations',
                2 => 'maintenance/evidence',
            ],
            true
        );
    }

    private function moveFiles(array $sources, array $destinations, bool $storeFullPath): void
    {
        DB::table('file_purchase_orders')
            ->select(['id', 'type', 'url'])
            ->orderBy('id')
            ->chunkById(100, function ($files) use ($sources, $destinations, $storeFullPath) {
                foreach ($files as $file) {
                    $filename = basename($file->url);
                    $source = str_contains($file->url, '/')
                        ? $file->url
                        : "{$sources[$file->type]}/{$filename}";
                    $destination = "{$destinations[$file->type]}/{$filename}";

                    if ($source !== $destination
                        && Storage::disk('public')->exists($source)
                        && ! Storage::disk('public')->exists($destination)) {
                        Storage::disk('public')->move($source, $destination);
                    }

                    DB::table('file_purchase_orders')
                        ->where('id', $file->id)
                        ->update([
                            'url' => $storeFullPath ? $destination : $filename,
                            'updated_at' => now(),
                        ]);
                }
            });
    }
};
