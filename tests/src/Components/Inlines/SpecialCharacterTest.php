<?php

namespace Erusev\Parsedown\Tests\Components\Inlines;

use Erusev\Parsedown\Components\Inlines\SpecialCharacter;
use Erusev\Parsedown\Parsing\Excerpt;
use Erusev\Parsedown\State;
use PHPUnit\Framework\TestCase;

final class SpecialCharacterTest extends TestCase
{
    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testSpecialCharacterAPI()
    {
        $SpecialCharacter = SpecialCharacter::build(new Excerpt('&nbsp;', 0), new State);

        $this->assertSame('nbsp', $SpecialCharacter->charCode());

        $SpecialCharacter = SpecialCharacter::build(new Excerpt('&#3C;', 0), new State);

        $this->assertSame('#3C', $SpecialCharacter->charCode());
    }
}
