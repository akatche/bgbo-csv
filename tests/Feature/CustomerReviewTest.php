<?php

namespace Tests\Feature;

use App\Events\CustomerListProcessed;
use App\Models\CustomerReview;
use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;


class CustomerReviewTest extends TestCase
{
    use RefreshDatabase;

    public function tearDown(): void
    {
        //Clean all non-desired files
        $files = Storage::disk('local')->allFiles('public');
        Storage::disk('local')->delete($files);

        $files = Storage::disk('local')->allFiles('reputation');
        Storage::disk('local')->delete($files);

        parent::tearDown();
    }

    public function test_users_list_its_a_required_field(): void
    {
        $response = $this->postJson('/api/reputation/upload');

        $response
            ->assertStatus(422)
            ->assertInvalid([
                'users' => 'The users field is required.',
            ]);
    }

    public function test_users_list_should_get_a_file(): void
    {
        $response = $this->postJson('/api/reputation/upload',['users' => 'not a file']);

        $response
            ->assertStatus(422)
            ->assertInvalid([
                'users' => 'The users field must be a file.',
            ]);
    }

    public function test_users_list_should_be_a_csv(): void
    {
        $file = UploadedFile::fake()->create('customers.pdf', 1, 'application/pdf');

        $response = $this->postJson('/api/reputation/upload',['users' => $file]);

        $response
            ->assertStatus(422)
            ->assertInvalid([
                'users' => 'The users field must be a file of type: csv.',
            ]);
    }

    public function test_that_jobs_were_added_into_the_batch(): void
    {
        Bus::fake();

        $file = UploadedFile::fake()->createWithContent('customers.csv', file_get_contents(base_path('tests/fixtures/customers.csv')));

        $this->postJson('/api/reputation/upload',['users' => $file]);

        Bus::assertBatched(function (PendingBatch $batch) {
            return $batch->jobs->count() === 10;
        });
    }

    public function test_downloadable_csv_is_returned_after_batch_finish_all_jobs(): void
    {
        Event::fake();

        Storage::fake('public');

        $file = UploadedFile::fake()->createWithContent('customers.csv', file_get_contents(base_path('tests/fixtures/customers.csv')));

        $this->postJson('/api/reputation/upload',['users' => $file]);

        Event::assertDispatched(function (CustomerListProcessed $event) {
            $filename = 'public/' . explode('/', $event->broadcastWith()['url'])[2];
            return $event->broadcastAs() === 'review-completed'
                && $event->broadcastOn()[0]->name === 'customer-review'
                && Storage::disk('local')->exists($filename);
        });
    }

    public function test_downloadable_csv_header_structure_is_correct(): void
    {
        Event::fake();

        Storage::fake('public');

        $file = UploadedFile::fake()->createWithContent('customers.csv', file_get_contents(base_path('tests/fixtures/customers.csv')));

        $this->postJson('/api/reputation/upload',['users' => $file]);

        Event::assertDispatched(function (CustomerListProcessed $event) {
            $filename = 'public/' . explode('/', $event->broadcastWith()['url'])[2];
            $reader = Reader::createFromString(Storage::disk('local')->get($filename));
            $reader->setHeaderOffset(0);
            $headers = ['trans_type','trans_date','trans_time','cust_num','cust_fname','cust_email','cust_phone','sent','sent_using','reason'];
            return $reader->getHeader() === $headers;
        });
    }

    public function test_csv_export_data_has_the_same_amount_of_records_than_the_original_file(): void
    {
        Event::fake();

        Storage::fake('public');

        $header = file_get_contents(base_path('tests/fixtures/customers_headers.csv'));
        $row1 = sprintf("sales,%s,13:00:00,10013,Bob,bob@gmail.com,123-123-1231", now()->format('Y-m-d'));
        $row2 = sprintf("sales,%s,13:00:00,10013,Bob,bob@gmail.com,123-123-1231", now()->format('Y-m-d'));
        $content = implode("\n", [$header, $row1,$row2]);

        $file = UploadedFile::fake()->createWithContent('customers.csv', $content);

        $this->postJson('/api/reputation/upload',['users' => $file]);

        Event::assertDispatched(function (CustomerListProcessed $event) {
            $filename = 'public/' . explode('/', $event->broadcastWith()['url'])[2];
            $reader = Reader::createFromString(Storage::disk('local')->get($filename));
            $reader->setHeaderOffset(0);
            $this->assertEquals(2, iterator_count($reader->getRecords()));
            return true;
        });
    }

    public function test_csv_export_data_has_a_right_structure_on_valid_rows_with_all_fields_completed(): void
    {
        Event::fake();

        Storage::fake('public');

        $header = file_get_contents(base_path('tests/fixtures/customers_headers.csv'));
        $date = now()->format('Y-m-d');
        $row1 = sprintf("sales,%s,13:00:00,10013,Bob,bob@gmail.com,123-123-1231", $date);
        $content = implode("\n", [$header, $row1]);

        $file = UploadedFile::fake()->createWithContent('customers.csv', $content);

        $this->postJson('/api/reputation/upload',['users' => $file]);

        Event::assertDispatched(function (CustomerListProcessed $event) use($date) {
            $filename = 'public/' . explode('/', $event->broadcastWith()['url'])[2];
            $reader = Reader::createFromString(Storage::disk('local')->get($filename));
            $reader->setHeaderOffset(0);

            foreach ($reader->getRecords() as $record){
                $this->assertEquals($record['trans_type'],'sales');
                $this->assertEquals($record['trans_date'],$date);
                $this->assertEquals($record['trans_time'],'13:00:00');
                $this->assertEquals($record['cust_num'],'10013');
                $this->assertEquals($record['cust_fname'],'Bob');
                $this->assertEquals($record['cust_email'],'bob@gmail.com');
                $this->assertEquals($record['cust_phone'],'123-123-1231');
                $this->assertEquals($record['sent'],'true');
                $this->assertEquals($record['sent_using'],'sms');
                $this->assertEquals($record['reason'],'');
            }

            return true;
        });
    }

    public function test_csv_export_data_has_a_right_structure_on_valid_rows_with_only_email_completed(): void
    {
        Event::fake();

        Storage::fake('public');

        $header = file_get_contents(base_path('tests/fixtures/customers_headers.csv'));
        $date = now()->format('Y-m-d');
        $row1 = sprintf("sales,%s,13:00:00,10013,Bob,bob@gmail.com,", $date);
        $content = implode("\n", [$header, $row1]);

        $file = UploadedFile::fake()->createWithContent('customers.csv', $content);

        $this->postJson('/api/reputation/upload',['users' => $file]);

        Event::assertDispatched(function (CustomerListProcessed $event) use($date) {
            $filename = 'public/' . explode('/', $event->broadcastWith()['url'])[2];
            $reader = Reader::createFromString(Storage::disk('local')->get($filename));
            $reader->setHeaderOffset(0);

            foreach ($reader->getRecords() as $record){
                $this->assertEquals($record['trans_type'],'sales');
                $this->assertEquals($record['trans_date'],$date);
                $this->assertEquals($record['trans_time'],'13:00:00');
                $this->assertEquals($record['cust_num'],'10013');
                $this->assertEquals($record['cust_fname'],'Bob');
                $this->assertEquals($record['cust_email'],'bob@gmail.com');
                $this->assertEquals($record['cust_phone'],'');
                $this->assertEquals($record['sent'],'true');
                $this->assertEquals($record['sent_using'],'email');
                $this->assertEquals($record['reason'],'');
            }

            return true;
        });
    }

    public function test_csv_export_data_has_a_right_structure_on_valid_rows_with_only_phone_completed(): void
    {
        Event::fake();

        Storage::fake('public');

        $header = file_get_contents(base_path('tests/fixtures/customers_headers.csv'));
        $date = now()->format('Y-m-d');
        $row1 = sprintf("sales,%s,13:00:00,10013,Bob,,123-123-1231", $date);
        $content = implode("\n", [$header, $row1]);

        $file = UploadedFile::fake()->createWithContent('customers.csv', $content);

        $this->postJson('/api/reputation/upload',['users' => $file]);

        Event::assertDispatched(function (CustomerListProcessed $event) use($date) {
            $filename = 'public/' . explode('/', $event->broadcastWith()['url'])[2];
            $reader = Reader::createFromString(Storage::disk('local')->get($filename));
            $reader->setHeaderOffset(0);

            foreach ($reader->getRecords() as $record){
                $this->assertEquals($record['trans_type'],'sales');
                $this->assertEquals($record['trans_date'],$date);
                $this->assertEquals($record['trans_time'],'13:00:00');
                $this->assertEquals($record['cust_num'],'10013');
                $this->assertEquals($record['cust_fname'],'Bob');
                $this->assertEquals($record['cust_email'],'');
                $this->assertEquals($record['cust_phone'],'123-123-1231');
                $this->assertEquals($record['sent'],'true');
                $this->assertEquals($record['sent_using'],'sms');
                $this->assertEquals($record['reason'],'');
            }

            return true;
        });
    }

    public function test_csv_export_data_has_a_right_structure_on_non_valid_rows_because_they_are_beyond_7_days(): void
    {
        Event::fake();

        Storage::fake('public');

        $header = file_get_contents(base_path('tests/fixtures/customers_headers.csv'));
        $date = now()->subDays(8)->format('Y-m-d');
        $row1 = sprintf("sales,%s,13:00:00,10013,Bob,bob@gmail.com,123-123-1231", $date);
        $content = implode("\n", [$header, $row1]);

        $file = UploadedFile::fake()->createWithContent('customers.csv', $content);

        $this->postJson('/api/reputation/upload',['users' => $file]);

        Event::assertDispatched(function (CustomerListProcessed $event) use($date) {
            $filename = 'public/' . explode('/', $event->broadcastWith()['url'])[2];
            $reader = Reader::createFromString(Storage::disk('local')->get($filename));
            $reader->setHeaderOffset(0);

            foreach ($reader->getRecords() as $record){
                $this->assertEquals($record['trans_type'],'sales');
                $this->assertEquals($record['trans_date'],$date);
                $this->assertEquals($record['trans_time'],'13:00:00');
                $this->assertEquals($record['cust_num'],'10013');
                $this->assertEquals($record['cust_fname'],'Bob');
                $this->assertEquals($record['cust_email'],'bob@gmail.com');
                $this->assertEquals($record['cust_phone'],'123-123-1231');
                $this->assertEquals($record['sent'],'false');
                $this->assertEquals($record['sent_using'],'sms');
                $this->assertEquals($record['reason'],'beyond days to compare');
            }

            return true;
        });
    }

    public function test_csv_export_data_has_a_right_structure_on_non_valid_rows_because_they_have_duplicate_data(): void
    {
        Event::fake();

        Storage::fake('public');

        $header = file_get_contents(base_path('tests/fixtures/customers_headers.csv'));
        $date = now()->format('Y-m-d');
        $row1 = sprintf("sales,%s,13:00:00,10013,Bob,bob@gmail.com,123-123-1231", $date);
        $row2 = sprintf("sales,%s,13:00:00,10014,Bob,bob@gmail.com,123-123-1232", $date);
        $content = implode("\n", [$header, $row1, $row2]);

        $file = UploadedFile::fake()->createWithContent('customers.csv', $content);

        $this->postJson('/api/reputation/upload',['users' => $file]);

        Event::assertDispatched(function (CustomerListProcessed $event) use($date) {
            $filename = 'public/' . explode('/', $event->broadcastWith()['url'])[2];
            $reader = Reader::createFromString(Storage::disk('local')->get($filename));
            $reader->setHeaderOffset(0);

            foreach ($reader->getRecords() as $index => $record){
                if($index === 1){
                    $this->assertEquals($record['trans_type'],'sales');
                    $this->assertEquals($record['trans_date'],$date);
                    $this->assertEquals($record['trans_time'],'13:00:00');
                    $this->assertEquals($record['cust_num'],'10013');
                    $this->assertEquals($record['cust_fname'],'Bob');
                    $this->assertEquals($record['cust_email'],'bob@gmail.com');
                    $this->assertEquals($record['cust_phone'],'123-123-1231');
                    $this->assertEquals($record['sent'],'true');
                    $this->assertEquals($record['sent_using'],'sms');
                    $this->assertEquals($record['reason'],'');
                }
                if($index === 2){
                    $this->assertEquals($record['trans_type'],'sales');
                    $this->assertEquals($record['trans_date'],$date);
                    $this->assertEquals($record['trans_time'],'13:00:00');
                    $this->assertEquals($record['cust_num'],'10014');
                    $this->assertEquals($record['cust_fname'],'Bob');
                    $this->assertEquals($record['cust_email'],'bob@gmail.com');
                    $this->assertEquals($record['cust_phone'],'123-123-1232');
                    $this->assertEquals($record['sent'],'false');
                    $this->assertEquals($record['sent_using'],'sms');
                    $this->assertEquals($record['reason'],'already notified');
                }
            }

            return true;
        });
    }

    public function test_a_record_is_created_per_csv_row(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->createWithContent('customers.csv', file_get_contents(base_path('tests/fixtures/customers.csv')));

        $rows = count(file(base_path('tests/fixtures/customers.csv'))) - 1;

        $this->postJson('/api/reputation/upload',['users' => $file]);

        $this->assertDatabaseCount('customer_reviews', $rows);
    }

    public function test_record_can_only_be_sent_if_it_has_been_created_during_the_past_7_days(): void
    {
        Event::fake();

        Storage::fake('public');

        //I´ve created all edge test cases in the same test
        $header = file_get_contents(base_path('tests/fixtures/customers_headers.csv'));
        $row = sprintf("sales,%s,13:00:00,10013,Bob,bob@gmail.com,123-123-1233", now()->subDays(8)->format('Y-m-d'));
        $row2 = sprintf("sales,%s,13:00:00,10014,Bob,bob2@gmail.com,123-123-1234", now()->subDays(7)->format('Y-m-d'));
        $row3 = sprintf("sales,%s,13:00:00,10015,Bob,bob3@gmail.com,123-123-1235", now()->subDays(6)->format('Y-m-d'));
        $content = implode("\n", [$header, $row, $row2, $row3]);

        $file = UploadedFile::fake()->createWithContent('customers.csv', $content);

        $this->postJson('/api/reputation/upload',['users' => $file]);

        $this->assertDatabaseCount('customer_reviews', 3);

        $this->assertDatabaseHas('customer_reviews', [
            'customer_email' => 'bob@gmail.com',
            'sent' => 0,
            'reason' => 'beyond days to compare'
        ]);

        $this->assertDatabaseHas('customer_reviews', [
            'customer_email' => 'bob2@gmail.com',
            'sent' => 1,
        ]);

        $this->assertDatabaseHas('customer_reviews', [
            'customer_email' => 'bob3@gmail.com',
            'sent' => 1,
        ]);
    }

    public function test_if_phone_and_email_is_provided_phone_is_used_to_communicate(): void
    {
        Event::fake();

        Storage::fake('public');

        $header = file_get_contents(base_path('tests/fixtures/customers_headers.csv'));
        $row = sprintf("sales,%s,13:00:00,10013,Bob,bob@gmail.com,123-123-1235", now()->format('Y-m-d'));
        $content = implode("\n", [$header, $row]);

        $file = UploadedFile::fake()->createWithContent('customers.csv', $content);

        $this->postJson('/api/reputation/upload',['users' => $file]);

        $this->assertDatabaseCount('customer_reviews', 1);

        $this->assertDatabaseHas('customer_reviews', [
            'customer_email' => 'bob@gmail.com',
            'sent' => 1,
            'sent_type' => 'sms'
        ]);
    }

    public function test_if_only_phone_is_provided_phone_is_used_to_communicate(): void
    {
        Event::fake();

        Storage::fake('public');

        $header = file_get_contents(base_path('tests/fixtures/customers_headers.csv'));
        $row = sprintf("sales,%s,13:00:00,10013,Bob,,123-123-1235", now()->format('Y-m-d'));
        $content = implode("\n", [$header, $row]);

        $file = UploadedFile::fake()->createWithContent('customers.csv', $content);

        $this->postJson('/api/reputation/upload',['users' => $file]);

        $this->assertDatabaseCount('customer_reviews', 1);

        $this->assertDatabaseHas('customer_reviews', [
            'customer_number' => '10013',
            'sent' => 1,
            'sent_type' => 'sms'
        ]);
    }

    public function test_if_only_email_is_provided_email_is_used_to_communicate(): void
    {
        Event::fake();

        Storage::fake('public');

        $header = file_get_contents(base_path('tests/fixtures/customers_headers.csv'));
        $row = sprintf("sales,%s,13:00:00,10013,Bob,bob@gmail.com,", now()->format('Y-m-d'));
        $content = implode("\n", [$header, $row]);

        $file = UploadedFile::fake()->createWithContent('customers.csv', $content);

        $this->postJson('/api/reputation/upload',['users' => $file]);

        $this->assertDatabaseCount('customer_reviews', 1);

        $this->assertDatabaseHas('customer_reviews', [
            'customer_number' => '10013',
            'sent' => 1,
            'sent_type' => 'email'
        ]);
    }

    public function test_records_with_same_email_should_only_be_sent_once(): void
    {
        Event::fake();

        Storage::fake('public');

        $header = file_get_contents(base_path('tests/fixtures/customers_headers.csv'));
        $row = sprintf("sales,%s,13:00:00,10013,Bob,bob@gmail.com,123-123-1230", now()->format('Y-m-d'));
        $row2 = sprintf("sales,%s,13:00:00,10014,Bob,bob@gmail.com,123-123-1231", now()->format('Y-m-d'));
        $content = implode("\n", [$header, $row, $row2]);

        $file = UploadedFile::fake()->createWithContent('customers.csv', $content);

        $this->postJson('/api/reputation/upload',['users' => $file]);

        $this->assertDatabaseHas('customer_reviews', [
            'customer_number' => '10013',
            'sent' => 1
        ]);

        $this->assertDatabaseHas('customer_reviews', [
            'customer_number' => '10014',
            'sent' => 0,
            'reason' => 'already notified'
        ]);
    }

    public function test_records_with_same_customer_number_should_only_be_sent_once(): void
    {
        Event::fake();

        Storage::fake('public');

        $header = file_get_contents(base_path('tests/fixtures/customers_headers.csv'));
        $row = sprintf("sales,%s,13:00:00,10013,Bob,bob@gmail.com,123-123-1230", now()->format('Y-m-d'));
        $row2 = sprintf("sales,%s,13:00:00,10013,Bob,bob2@gmail.com,123-123-1231", now()->format('Y-m-d'));
        $content = implode("\n", [$header, $row, $row2]);

        $file = UploadedFile::fake()->createWithContent('customers.csv', $content);

        $this->postJson('/api/reputation/upload',['users' => $file]);

        $this->assertDatabaseHas('customer_reviews', [
            'customer_email' => 'bob@gmail.com',
            'sent' => 1
        ]);

        $this->assertDatabaseHas('customer_reviews', [
            'customer_email' => 'bob2@gmail.com',
            'sent' => 0,
            'reason' => 'already notified'
        ]);
    }

    public function test_records_with_same_phone_number_should_only_be_sent_once(): void
    {
        Event::fake();

        Storage::fake('public');

        $header = file_get_contents(base_path('tests/fixtures/customers_headers.csv'));
        $row = sprintf("sales,%s,13:00:00,10013,Bob,bob@gmail.com,123-123-1231", now()->format('Y-m-d'));
        $row2 = sprintf("sales,%s,13:00:00,10014,Bob,bob2@gmail.com,123-123-1231", now()->format('Y-m-d'));
        $content = implode("\n", [$header, $row, $row2]);

        $file = UploadedFile::fake()->createWithContent('customers.csv', $content);

        $this->postJson('/api/reputation/upload',['users' => $file]);

        $this->assertDatabaseHas('customer_reviews', [
            'customer_number' => '10013',
            'sent' => 1
        ]);

        $this->assertDatabaseHas('customer_reviews', [
            'customer_number' => '10014',
            'sent' => 0,
            'reason' => 'already notified'
        ]);
    }

    public function test_records_with_same_email_and_customer_number_should_only_be_sent_once(): void
    {
        Event::fake();

        Storage::fake('public');

        $header = file_get_contents(base_path('tests/fixtures/customers_headers.csv'));
        $row = sprintf("sales,%s,13:00:00,10013,Bob,bob@gmail.com,123-123-1231", now()->format('Y-m-d'));
        $row2 = sprintf("sales,%s,13:00:00,10013,Bob,bob@gmail.com,123-123-1232", now()->format('Y-m-d'));
        $content = implode("\n", [$header, $row, $row2]);

        $file = UploadedFile::fake()->createWithContent('customers.csv', $content);

        $this->postJson('/api/reputation/upload',['users' => $file]);

        $this->assertDatabaseHas('customer_reviews', [
            'customer_phone' => '1231231231',
            'sent' => 1
        ]);

        $this->assertDatabaseHas('customer_reviews', [
            'customer_phone' => '1231231232',
            'sent' => 0,
            'reason' => 'already notified'
        ]);
    }

    public function test_records_with_same_email_and_phone_number_should_only_be_sent_once(): void
    {
        Event::fake();

        Storage::fake('public');

        $header = file_get_contents(base_path('tests/fixtures/customers_headers.csv'));
        $row = sprintf("sales,%s,13:00:00,10013,Bob,bob@gmail.com,123-123-1231", now()->format('Y-m-d'));
        $row2 = sprintf("sales,%s,13:00:00,10014,Bob,bob@gmail.com,123-123-1231", now()->format('Y-m-d'));
        $content = implode("\n", [$header, $row, $row2]);

        $file = UploadedFile::fake()->createWithContent('customers.csv', $content);

        $this->postJson('/api/reputation/upload',['users' => $file]);

        $this->assertDatabaseHas('customer_reviews', [
            'customer_number' => '10013',
            'sent' => 1
        ]);

        $this->assertDatabaseHas('customer_reviews', [
            'customer_number' => '10014',
            'sent' => 0,
            'reason' => 'already notified'
        ]);
    }

    public function test_records_with_same_customer_number_and_phone_number_should_only_be_sent_once(): void
    {
        Event::fake();

        Storage::fake('public');

        $header = file_get_contents(base_path('tests/fixtures/customers_headers.csv'));
        $row = sprintf("sales,%s,13:00:00,10013,Bob,bob@gmail.com,123-123-1231", now()->format('Y-m-d'));
        $row2 = sprintf("sales,%s,13:00:00,10013,Bob,bob2@gmail.com,123-123-1231", now()->format('Y-m-d'));
        $content = implode("\n", [$header, $row, $row2]);

        $file = UploadedFile::fake()->createWithContent('customers.csv', $content);

        $this->postJson('/api/reputation/upload',['users' => $file]);

        $this->assertDatabaseHas('customer_reviews', [
            'customer_email' => 'bob@gmail.com',
            'sent' => 1
        ]);

        $this->assertDatabaseHas('customer_reviews', [
            'customer_email' => 'bob2@gmail.com',
            'sent' => 0,
            'reason' => 'already notified'
        ]);
    }

    public function test_records_with_same_email_and_customer_number_and_phone_number_should_only_be_sent_once(): void
    {
        Event::fake();

        Storage::fake('public');

        $header = file_get_contents(base_path('tests/fixtures/customers_headers.csv'));
        $row = sprintf("sales,%s,13:00:00,10013,Bob,bob@gmail.com,123-123-1231", now()->format('Y-m-d'));
        $row2 = sprintf("sales,%s,13:00:00,10013,Bob,bob@gmail.com,123-123-1231", now()->format('Y-m-d'));
        $content = implode("\n", [$header, $row, $row2]);

        $file = UploadedFile::fake()->createWithContent('customers.csv', $content);

        $this->postJson('/api/reputation/upload',['users' => $file]);

        $this->assertDatabaseHas('customer_reviews', [
            'customer_email' => 'bob@gmail.com',
            'sent' => 1
        ]);

        $this->assertDatabaseHas('customer_reviews', [
            'customer_email' => 'bob@gmail.com',
            'sent' => 0,
            'reason' => 'already notified'
        ]);
    }
}
