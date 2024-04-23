<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SlugRule implements ValidationRule
{
    protected $language;
    protected $modelClass;
    protected $ignoreId;
    protected $fieldName;

    public function __construct($language, $modelClass, $fieldName = 'slug', $ignoreId = null)
    {
        $this->language = $language;
        $this->modelClass = $modelClass;
        $this->ignoreId = $ignoreId;
        $this->fieldName = $fieldName;
    }


    public function passes($attribute, $value)
    {
        $model = new $this->modelClass;

        $query = $model->where($this->fieldName, $value)->where('lang', $this->language);

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
            $fail('The :attribute has already been taken for this language.');
        }
    }

    
}
