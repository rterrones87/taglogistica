<?php

namespace App\Traits;

use App\Models\Approval;

trait HasApproval 
{
    public function approvals() {
        return $this->morphMany(Approval::class, 'approvable');
    }

    public function requestApproval(string $kind, int $userId, ?array $snapshot = null, ?array $meta = null, ?int $scopeId = null): Approval {

        return $this->approvals()->create([
            'kind'         => $kind,
            'scope_id'     => $scopeId,
            'requested_by' => $userId,
            'status'       => 'pending',
            'snapshot'     => $snapshot,
            'metadata'     => $meta,
        ]);
    }

    public function approvalOf(string $kind, ?int $scopeId = null) {
        return $this->approvals()
            ->where('kind', $kind)
            ->when($scopeId !== null, fn($q) => $q->where('scope_id', $scopeId))
            ->latest('created_at')
            ->first();
    }

    public function getApprovalsMapAttribute()
    {
        if (! $this->relationLoaded('approvals')) {
            return null;
        }

        return $this->approvals
            ->sortByDesc('created_at')
            ->unique('kind')
            ->pluck('status', 'kind'); 
    }
}