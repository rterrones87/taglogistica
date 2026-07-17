<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use App\Traits\HasMexicoTimezone;


class Approval extends Model {

    use HasFactory;
    use HasMexicoTimezone;

    protected $fillable = [
        'kind',
        'scope_id',
        'status',
        'requested_by',
        'reviewed_by',
        'reviewed_at',
        'review_comment',
        'snapshot',
        'metadata'
    ];

    protected $casts = [
        'snapshot'=>'array',
        'metadata'=>'array',
        'reviewed_at'=>'datetime'
    ];

    protected $appends = ['kind_humanize'];

    public function approvable() { return $this->morphTo(); }

    public function isPending(): bool  { return $this->status === 'pending'; }
    public function isApproved(): bool { return $this->status === 'approved'; }
    public function isRejected(): bool { return $this->status === 'rejected'; }

    public function isApprovedOrPending(): bool
    {
        return in_array($this->status, ['approved', 'pending'], true);
    }

    public function approveNow(?int $actorId = null): self
    {
        DB::transaction(function () use ($actorId) {
            $fresh = self::whereKey($this->getKey())->lockForUpdate()->first();

            if (! $fresh || ! $fresh->isPending()) {
                return;
            }

            $fresh->forceFill([
                'status'         => 'approved',
                'reviewed_by'    => $actorId ?? auth()->id(),
                'reviewed_at'    => now(),
                'review_comment' => 'Auto aprobado',
            ])->save();

            $owner = $fresh->approvable;
            if ($owner && method_exists($owner, 'onApproved')) {
                $owner->onApproved($fresh);
            }

            Event::dispatch('approval.approved', $fresh);

            $this->setRawAttributes($fresh->getAttributes(), true);
        });

        return $this;
    }

    public function getKindHumanizeAttribute(): string
    {
        $map = [
            'initial_diesel_required' => 'Diesel inicial requerido',
            'initial_expenses'        => 'Gastos iniciales',
            'extra_expenses'          => 'Gastos extras',
            'maintenance_expenses'    => 'Gastos de mantenimiento',
            'extra_diesel'            => 'Diesel extra',
            'extra_booth'             => 'Caseta extra',
            'tire_expenses'           => 'Cambio de llanta'
        ];

        return $map[$this->kind] ?? ucfirst(str_replace('_', ' ', $this->kind));
    }

}
