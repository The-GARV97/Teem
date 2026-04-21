<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Notifications\Notification;

class LeaveRequestReviewed extends Notification
{
    public function __construct(
        private LeaveRequest $leaveRequest,
        private string $status,
        private ?string $rejectionReason = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $lr = $this->leaveRequest;
        $statusLabel = ucfirst($this->status);
        $message = "Your {$lr->leaveType->name} leave request has been {$statusLabel}.";

        return [
            'type'             => 'leave_reviewed',
            'message'          => $message,
            'status'           => $this->status,
            'rejection_reason' => $this->rejectionReason,
            'leave_request_id' => $lr->id,
        ];
    }
}
