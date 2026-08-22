<?php

namespace Tests\Unit;

use App\Support\JsonColumn;
use Tests\TestCase;

class JsonColumnTest extends TestCase
{
    public function test_valid_json_and_decoded_arrays_are_not_malformed(): void
    {
        $this->assertFalse(JsonColumn::isMalformed(null));
        $this->assertFalse(JsonColumn::isMalformed(''));
        $this->assertFalse(JsonColumn::isMalformed('{"region":"سنڌ"}'));
        $this->assertFalse(JsonColumn::isMalformed(['region' => 'سنڌ']));
        $this->assertFalse(JsonColumn::isMalformed('[]'));
    }

    public function test_eloquent_array_cast_extra_is_not_stringified(): void
    {
        $sense = new \App\Models\LughatSense();
        $sense->setRawAttributes(['id' => 1, 'extra' => '{"region":"سنڌ"}'], true);

        $this->assertIsArray($sense->extra);
        $this->assertFalse(JsonColumn::isMalformed($sense->getRawOriginal('extra')));

        $broken = new \App\Models\LughatSense();
        $broken->setRawAttributes(['id' => 2, 'extra' => '{not-json'], true);
        $this->assertTrue(JsonColumn::isMalformed($broken->getRawOriginal('extra')));
    }
}
