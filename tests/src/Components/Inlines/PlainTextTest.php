<?php

namespace Erusev\Parsedown\Tests\Components\Inlines;

use Erusev\Parsedown\Components\Inlines\PlainText;
use Erusev\Parsedown\Parsing\Excerpt;
use PHPUnit\Framework\TestCase;

final class PlainTextTest extends TestCase
{
    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testPlainTextText()
    {
        $Plaintext = Plaintext::build(new Excerpt('foo', 0));

        $this->assertSame('foo', $Plaintext->text());
    }
}
