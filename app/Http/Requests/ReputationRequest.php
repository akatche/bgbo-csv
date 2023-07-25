<?php

namespace App\Http\Requests;

use App\Rules\CsvHeaders;
use Illuminate\Foundation\Http\FormRequest;

class ReputationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'users' => [
                'bail',
                'required',
                'file',
                'mimes:csv',
                new CsvHeaders(['trans_type','trans_date','trans_time','cust_num','cust_fname','cust_email','cust_phone'])
            ]
        ];
    }
}
