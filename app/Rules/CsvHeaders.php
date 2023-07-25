<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use League\Csv\Reader;

class CsvHeaders implements ValidationRule
{
    public function __construct(
        public array $headers
    ) {}


    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $reader = Reader::createFromPath(request()->file($attribute)->getRealPath());
        $reader->setHeaderOffset(0);

        if ($reader->getHeader() !== $this->headers) {
            $fail('The :attribute has the wrong header structure.');
        }
    }
}
