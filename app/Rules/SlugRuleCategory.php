<?php

namespace App\Rules;

use App\Models\Categories;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SlugRuleCategory implements ValidationRule
{
    protected $ignoreId;

    
    public function __construct($ignoreId = null)
    {
        $this->ignoreId = $ignoreId;
    }


    public function passes($attribute, $value)
    {
        

        $query = Categories::where('slug', $value);

        if ($this->ignoreId !== null) {
            $query->where('id', '!=', $this->ignoreId);
        }

        return !$query->exists();
    }
 

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->passes($attribute, $value)) {
            $fail('The :attribute has already been taken for this language.');
        }
    }
}
