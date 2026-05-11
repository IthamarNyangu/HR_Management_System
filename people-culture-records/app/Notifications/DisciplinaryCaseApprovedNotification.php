<?php

namespace App\Notifications;

use App\Models\DisciplinaryCase;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DisciplinaryCaseApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly DisciplinaryCase $disciplinaryCase)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Disciplinary case approved',
            'message' => "{$this->disciplinaryCase->reference_no} has been approved and is now active.",
            'case_id' => $this->disciplinaryCase->id,
            'reference_no' => $this->disciplinaryCase->reference_no,
            'url' => route('disciplinary-cases.show', $this->disciplinaryCase),
        ];
    }
}
