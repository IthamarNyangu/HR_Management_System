<?php

namespace App\Notifications;

use App\Models\FuneralClaim;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FuneralClaimApprovalRequiredNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly FuneralClaim $claim) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => 'Funeral claim awaiting approval', 'message' => "{$this->claim->reference_no} for {$this->claim->deceased_name} is ready for approval.", 'claim_id' => $this->claim->id, 'reference_no' => $this->claim->reference_no, 'url' => route('funeral-claims.show', $this->claim)];
    }
}
