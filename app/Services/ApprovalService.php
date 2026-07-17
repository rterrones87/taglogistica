<?php

namespace App\Services;

use App\Models\Approval;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class ApprovalService {
    public function approve(Approval $approval, int $actorId, ?string $comment = null): Approval {
        return DB::transaction(function () use ($approval, $actorId, $comment) {
            if (!$approval->isPending()) return $approval;

            $approval->forceFill([
                'status'        => 'approved',
                'reviewed_by'   => $actorId,
                'reviewed_at'   => now(),
                'review_comment'=> $comment,
            ])->save();
            
            $approvable = $approval->approvable;
            if (method_exists($approvable, 'onApproved')) {
                $approvable->onApproved($approval);
            }

            Event::dispatch('approval.approved', $approval);
            return $approval;
        });
    }

    public function reject(Approval $approval, int $actorId, ?string $comment = null): Approval {
        return DB::transaction(function () use ($approval, $actorId, $comment) {
            if (!$approval->isPending()) return $approval;

            $approval->forceFill([
                'status'        => 'rejected',
                'reviewed_by'   => $actorId,
                'reviewed_at'   => now(),
                'review_comment'=> $comment,
            ])->save();

            $approvable = $approval->approvable;
            if (method_exists($approvable, 'onRejected')) {
                $approvable->onRejected($approval);
            }

            Event::dispatch('approval.rejected', $approval);
            return $approval;
        });
    }
}
