<?php

namespace Tests\Legacy\Services\Parser\Factories;

use Coyote\Services\Parser\Factories\SigFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Legacy\TestCase;

class SigFactoryTest extends TestCase
{
    use WithFaker;

    public function testParseSignatureWithLineBreakings()
    {
        $input = "one\ntwo";

        $parser = new SigFactory($this->app);

        $this->assertEquals("one<br />\ntwo", trim($parser->parse($input)));
    }
}
