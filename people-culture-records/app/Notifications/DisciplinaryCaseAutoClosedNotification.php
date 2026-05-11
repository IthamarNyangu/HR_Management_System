<?php

namespace App\Notifications;

use App\Models\DisciplinaryCase;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DisciplinaryCaseAutoClosedNotification extends Notification
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
            'title' => 'Disciplinary case auto-closed',
            'message' => "{$this->disciplinaryCase->reference_no} was automatically closed because its expiry date has passed.",
            'case_id' => $this->disciplinaryCase->id,
            'reference_no' => $this->disciplinaryCase->reference_no,
            'url' => route('disciplinary-cases.show', $this->disciplinaryCase),
        ];
    }
}
