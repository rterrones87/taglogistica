<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Approval;
use App\Services\ApprovalService;

class ApprovalController extends Controller
{

    public function index() {
        $estado = request("estado")?request("estado"):"pending";
        return Approval::where('status', $estado)->orderBy('id','desc')->get(); 
    }
    
    public function approve(Approval $approval, ApprovalService $svc) {
        $svc->approve($approval, auth()->id(), request('comment'));
        return response()->json(null, 200);
    }

    public function reject(Approval $approval, ApprovalService $svc) {
        $svc->reject($approval, auth()->id(), request('comment'));
        return response()->json(null, 200);
    }
}