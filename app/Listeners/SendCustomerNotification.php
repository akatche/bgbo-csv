<?php

namespace App\Listeners;

use App\Events\NotifyUser;
use App\Events\CustomerHasBeenProcessed;
use App\Models\CustomerReview;
use App\Services\Notification\Notification;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;


class SendCustomerNotification implements ShouldQueue
{

    CONST COMPARE_DATE = '2023-03-05';
    CONST DAYS_TO_COMPARE = 7;
    private Notification $notification;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        $this->notification = new Notification();
    }

    /**
     * Handle the event.
     */
    public function handle(NotifyUser $event): void
    {
//        if($this->canUserBeNotified($event->customerReview)){
//            try {
//                match ($event->customerReview->sent_type) {
//                    'sms' => $this->notification->send('sms',$event->customerReview->toArray()),
//                    'email' => $this->notification->send('email',$event->customerReview->toArray()),
//                };
//
//                $event->customerReview->update([
//                    'sent' => true,
//                    'sent_at' => Carbon::now()
//                ]);
//            }catch (\Exception $exception){
//                $event->customerReview->update([
//                    'reason' => $exception->getMessage()
//                ]);
//            }
//        }

    }

    private function canUserBeNotified(CustomerReview $customerReview): bool{

        // This is the date we set to compare all records, is this was in the present I would have used Carbon::now()
        $compareDate = Carbon::parse(self::COMPARE_DATE);

        if($customerReview->date->diffInDays($compareDate) > self::DAYS_TO_COMPARE){
            $customerReview->update([
                'reason' => 'beyond days to compare'
            ]);
            return false;
        };

        if($this->hasUserAlreadyBeenNotified($customerReview)){
            $customerReview->update([
                'reason' => 'already notified'
            ]);
            return false;
        }

        return true;
    }

    private function hasUserAlreadyBeenNotified(CustomerReview $customerReview) : bool
    {
        // find unique records with the same email, phone or customer number where sent is true and date is within 7 days
        $customerReview = CustomerReview::where(function ($query) use ($customerReview) {
            $query->where('customer_email', $customerReview->customer_email)
                ->orWhere('customer_phone', $customerReview->customer_phone)
                ->orWhere('customer_number', $customerReview->customer_number);
        })
            ->where('sent', true)
            ->count();

        return $customerReview > 0;
    }
}
