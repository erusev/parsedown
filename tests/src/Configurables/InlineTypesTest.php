<?php

namespace Erusev\Parsedown\Tests\Configurables;

use Erusev\Parsedown\Components\Inlines\Code;
use Erusev\Parsedown\Components\Inlines\Emphasis;
use Erusev\Parsedown\Components\Inlines\Link;
use Erusev\Parsedown\Configurables\InlineTypes;
use PHPUnit\Framework\TestCase;

final class InlineTypesTest extends TestCase
{
    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testAddingTypes()
    {
        $InlineTypes = new InlineTypes([]);
        $this->assertSame([], $InlineTypes->markedBy('@'));

        $InlineTypes = $InlineTypes->addingHighPrecedence('@', [Emphasis::class]);
        $this->assertSame([Emphasis::class], $InlineTypes->markedBy('@'));

        $InlineTypes = $InlineTypes->addingHighPrecedence('@', [Code::class]);
        $this->assertSame([Code::class, Emphasis::class], $InlineTypes->markedBy('@'));

        $InlineTypes = $InlineTypes->addingLowPrecedence('@', [Link::class]);
        $this->assertSame([Code::class, Emphasis::class, Link::class], $InlineTypes->markedBy('@'));
    }
}
