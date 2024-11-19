<?php 

namespace App\Enums;

enum CategoryGenderEnum: string {

    case Masculine = 'masculine';
    case Feminine = 'feminine';

    public function singular(): string
    {
        return match ($this) {
            self::Masculine => 'جو',
            self::Feminine => 'جي',
        };
    }

    public function plural(): string
    {
        return match ($this) {
            self::Masculine => 'جا',
            self::Feminine => 'جون',
        };
    }

}