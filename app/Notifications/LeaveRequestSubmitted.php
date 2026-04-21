<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Notifications\Notification;

class LeaveRequestSubmitted extends Notification
{
    public function __construct(private LeaveRequest $leaveRequest) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $lr = $this->leaveRequest;

        return [
            'type'             => 'leave_applied',
            'message'          => "{$lr->employee->name} applied for {$lr->leaveType->name} leave.",
            'employee_name'    => $lr->employee->name,
            'leave_type'       => $lr->leaveType->name,
            'start_date'       => $lr->start_date->toDateString(),
            'end_date'         => $lr->end_date->toDateString(),
            'leave_request_id' => $lr->id,
        ];
    }
}
