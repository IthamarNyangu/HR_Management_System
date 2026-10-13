<?php

namespace App\Notifications;

use App\Models\FuneralClaim;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FuneralClaimDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly FuneralClaim $claim, private readonly string $decision) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => 'Funeral claim '.str($this->decision)->headline(), 'message' => "{$this->claim->reference_no} has been {$this->decision}.", 'claim_id' => $this->claim->id, 'reference_no' => $this->claim->reference_no, 'url' => route('funeral-claims.show', $this->claim)];
    }
}
