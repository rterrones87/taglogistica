<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class FileWorkOrder extends Model
{
    use HasFactory;

    const DISK_FILE = 'work_orders';

    protected $fillable = [
        'work_order_id',
        'url',
    ];

    protected $casts = [
        'type' => 'integer',
    ];


    public static function storeFile(UploadedFile $file, array &$storedFiles): string
    {
        $disk = self::DISK_FILE;
        $filename = $file->store('', $disk);

        if (! is_string($filename)) {
            throw new UnprocessableEntityHttpException('No fue posible guardar el archivo de la orden de compra.');
        }

        $storedFiles[] = [
            'disk' => $disk,
            'name' => $filename,
        ];

        return $filename;
    }


}
