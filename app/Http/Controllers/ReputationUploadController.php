<?php

namespace App\Http\Controllers;

use App\Events\CustomerListProcessed;
use App\Http\Requests\ReputationRequest;
use App\Jobs\SendReviewMessageToUser;
use App\Models\CustomerReview;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use Illuminate\Bus\Batch;
use League\Csv\Writer;
use Illuminate\Support\Str;


class ReputationUploadController extends Controller
{
    /**
     * Handle the incoming request.
     * @throws \Throwable
     */
    public function __invoke(ReputationRequest $request){

        $path = $request->file('users')->store('reputation');

        $csv = Reader::createFromPath(storage_path("app/$path"), 'r');

        //Skip the header
        $csv->setHeaderOffset(0);

        // Create a batch instance so every record in this file is linked to the same batch id
        Bus::batch($this->getBatchData($csv))
            ->finally(function (Batch $batch) {

                event(new CustomerListProcessed($this->getOutputCSV($batch)));
            })
            ->dispatch();

        return to_route('customer.review');
    }

    private function getBatchData(Reader $csv) : array{
        $batchData = [];

        foreach ($csv as $record) {
            $batchData[] = new SendReviewMessageToUser($record);
        }

        return $batchData;
    }

    private function getOutputCSV(Batch $batch): string{
        //create a filename using str random and the batch id
        $filename = Str::random(40) . '.csv';
        $filepath = sprintf('app/public/%s', $filename);
        $writer = Writer::createFromPath(storage_path($filepath), 'w+');

        $writer->insertAll($this->getOutputData($batch));

        return Storage::disk('public')->url($filename);
    }

    private function getOutputData(Batch $batch):array{
        $headers = [['trans_type','trans_date','trans_time','cust_num','cust_fname','cust_email','cust_phone','sent','sent_using','reason']];

        $data = CustomerReview::where('batch_id',$batch->id)->get()->map(function (CustomerReview $user) {
            return [
                $user->original_data['trans_type'],
                $user->original_data['trans_date'],
                $user->original_data['trans_time'],
                $user->original_data['cust_num'],
                $user->original_data['cust_fname'],
                $user->original_data['cust_email'],
                $user->original_data['cust_phone'],
                var_export($user->sent, true),
                $user->sent_type,
                $user->reason
            ];
        })->toArray();

        return array_merge($headers,$data);
    }
}
