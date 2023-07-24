<?php

namespace App\Jobs;

use App\Events\NotifyUser;
use App\Events\CustomerHasBeenProcessed;
use App\Models\CustomerReview;
use App\Services\Notification\Notification;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\App;

class SendReviewMessageToUser implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels, InteractsWithQueue, Batchable;

    CONST COMPARE_DATE = '2023-03-05';
    CONST DAYS_TO_COMPARE = 7;

    public array $data;
    private Notification $notification;

    /**
     * Create a new job instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->notification = new Notification();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $customerReview = CustomerReview::create([
            'batch_id' => $this->batch()->id,
            'transaction_type' => $this->data['trans_type'],
            'date' => Carbon::createFromFormat('Y-m-d H:i:s', sprintf('%s %s',$this->data['trans_date'],$this->data['trans_time'])),
            'customer_number' => $this->data['cust_num'],
            'customer_name' => $this->data['cust_fname'],
            'customer_email' => $this->isValidEmail($this->data['cust_email']) ? $this->data['cust_email'] : null,
            'customer_phone' => $this->getParsedPhoneNumber($this->data['cust_phone']),
            'sent_type' => $this->selectSentType($this->data),
            'original_data' => $this->data
        ]);

        event(new NotifyUser($customerReview));

        $this->sendNotification($customerReview);
    }

    private function selectSentType($record) : ?string{

        $email = $this->isValidEmail($record['cust_email']);
        $phone = $this->isValidPhoneNumber($this->getParsedPhoneNumber($this->data['cust_phone']));

        if ($email && $phone) {
            return 'sms';
        }

        if ($phone) {
            return 'sms';
        }

        if ($email) {
            return 'email';
        }

        return null;
    }

    private function sendNotification(CustomerReview $customerReview): void
    {
        try {
            if($this->canUserBeNotified($customerReview)){
                // I used this approach to mock the notification service, but on a real project I would have used
                // the Laravel´s Notification Facade to keep everything on one place
                match ($customerReview->sent_type) {
                    'sms' => $this->notification->send('sms',$customerReview->toArray()),
                    'email' => $this->notification->send('email',$customerReview->toArray()),
                };

                $customerReview->update([
                    'sent' => true,
                    'sent_at' => Carbon::now()
                ]);
            }
        }catch (\Exception $exception){
            $customerReview->update([
                'reason' => $exception->getMessage()
            ]);
        }finally {
            event(new CustomerHasBeenProcessed($customerReview->fresh()));
        }
    }

    private function canUserBeNotified(CustomerReview $customerReview): bool{

        // This is the date we set to compare all records, is this was in the present I would have used Carbon::now()
        $compareDate = App::runningUnitTests() ? Carbon::now() : Carbon::parse(self::COMPARE_DATE);

        if(is_null($customerReview->customer_phone) && is_null($customerReview->customer_email)){
            $customerReview->update([
                'reason' => 'no email or phone'
            ]);
            return false;
        }

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
        // find unique records with the same email, phone or customer number where sent is true
        $customerReview = CustomerReview::where(function ($query) use ($customerReview) {
            $query->where('customer_email', $customerReview->customer_email)
                ->orWhere('customer_phone', $customerReview->customer_phone)
                ->orWhere('customer_number', $customerReview->customer_number);
        })
            ->where('batch_id', $customerReview->batch_id)
            ->where('sent', true)
            ->count();

        return $customerReview > 0;
    }

    private function getParsedPhoneNumber(string $phoneNumber): ?string{
        //remove all non-numeric characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        //validate us phone number with 10 digits
        if($this->isValidPhoneNumber($phoneNumber)){
            return $phoneNumber;
        }

        return null;
    }

    private function isValidPhoneNumber($number) : bool{
        return (bool) preg_match('/^[0-9]{10}+$/', $number);
    }

    private function isValidEmail($email) : bool{
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}
