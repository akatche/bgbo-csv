<?php

namespace App\Services\Notification;

use Illuminate\Support\Facades\App;

class Notification
{
    public function send($notificationType, $data): void
    {
        match ($notificationType) {
            'sms' => $this->sendSms($data),
            'email' => $this->sendEmail($data),
        };
    }

    public function sendSms(array $data): void
    {
        // This sleep is to simulate the time it takes to send an sms
        if (!App::runningUnitTests()){
            sleep(2);
        }
    }

    public function sendEmail(array $data): void
    {
        // This sleep is to simulate the time it takes to send an email
        if (!App::runningUnitTests()){
            sleep(1);
        }
    }
}
