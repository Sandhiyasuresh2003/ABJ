<?php

namespace App\Http\Controllers;

use App\Mail\RequestStatusMail;
use App\Models\LeaveRequest;
use App\Models\PermissionRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class RequestReviewController extends Controller
{
    public function __invoke(Request $httpRequest, string $kind, int $id, string $decision): View
    {
        $model = $kind === 'leave' ? LeaveRequest::class : PermissionRequest::class;
        $item = $model::with('employee')->findOrFail($id);
        $error = null;

        if ($httpRequest->isMethod('post')) {
            try {
                DB::transaction(function () use ($model, $id, $kind, $decision, &$item): void {
                    $item = $model::with('employee')->lockForUpdate()->findOrFail($id);
                    if ($item->status !== 'Pending') {
                        return;
                    }

                    abort_if(blank($item->employee?->email), 422, 'The employee needs an email address before this request can be reviewed.');

                    $item->update([
                        'status' => $decision === 'approve' ? 'Approved' : 'Rejected',
                        'reviewed_at' => now(),
                        'reviewed_by' => User::where('email', config('requests.md_email'))->value('id'),
                    ]);
                    Mail::to($item->employee->email)->send(new RequestStatusMail($item, $kind));
                });
            } catch (TransportExceptionInterface $exception) {
                report($exception);
                $item->refresh();
                $error = 'The notification email could not be sent. Your decision was not saved. Please try again after the administrator checks the email settings.';
            }
        }

        return view('requests.review', [
            'item' => $item,
            'requestType' => $kind,
            'decision' => $decision,
            'actionUrl' => $httpRequest->fullUrl(),
            'error' => $error,
        ]);
    }
}
