<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasMexicoTimezone;

class MaintenanceEvidence extends Model
{
    use HasFactory;
    use HasMexicoTimezone;

    protected $table = 'maintenance_evidences';

    protected $fillable = [
        'maintenance_id',
        'file_name',
        'zombie'
    ];

    public function maintenance()
    {
        return $this->belongsTo(Maintenance::class);
    }
}
