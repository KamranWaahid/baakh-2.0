<?php

namespace App\Rules;

use App\Models\Poets;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SlugRulePoet implements ValidationRule
{
    
    protected $ignoreId;

    public function __construct($ignoreId = null)
    {
        $this->ignoreId = $ignoreId;
    }


    public function passes($attribute, $value)
    {
        $query = Poets::where('poet_slug', $value);

        if ($this->ignoreId !== null) {
            $query->where('id', '!=', $this->ignoreId);
        }

        return !$query->exists();
    }
 

    /**
     * Run the validation rule.
     *
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        
        if (!$this->passes($attribute, $value)) {
            $fail('The :attribute has already been taken for an other poet.');
        }
    }
}
