<?php

namespace Erusev\Parsedown\Tests\Components\Inlines;

use Erusev\Parsedown\Components\Inlines\Markup;
use Erusev\Parsedown\Parsing\Excerpt;
use Erusev\Parsedown\State;
use PHPUnit\Framework\TestCase;

final class MarkupTest extends TestCase
{
    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testMarkupAPI()
    {
        $Markup = Markup::build(new Excerpt('<foo>', 0), new State);

        $this->assertSame('<foo>', $Markup->html());
    }
}
