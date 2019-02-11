<?php

namespace Erusev\Parsedown\Tests\Configurables;

use Erusev\Parsedown\Components\Blocks\IndentedCode;
use Erusev\Parsedown\Components\Blocks\Markup;
use Erusev\Parsedown\Components\Blocks\Rule;
use Erusev\Parsedown\Configurables\BlockTypes;
use PHPUnit\Framework\TestCase;

final class BlockTypesTest extends TestCase
{
    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testAddingTypes()
    {
        $BlockTypes = new BlockTypes([], []);
        $this->assertSame([], $BlockTypes->markedBy('@'));
        $this->assertSame([], $BlockTypes->unmarked());

        $BlockTypes = $BlockTypes->addingMarkedHighPrecedence('@', [IndentedCode::class]);
        $this->assertSame([IndentedCode::class], $BlockTypes->markedBy('@'));

        $BlockTypes = $BlockTypes->addingUnmarkedHighPrecedence([Markup::class]);
        $this->assertSame([IndentedCode::class], $BlockTypes->markedBy('@'));
        $this->assertSame([Markup::class], $BlockTypes->unmarked());

        $BlockTypes = $BlockTypes->addingMarkedHighPrecedence('@', [Markup::class]);
        $this->assertSame([Markup::class, IndentedCode::class], $BlockTypes->markedBy('@'));

        $BlockTypes = $BlockTypes->addingUnmarkedHighPrecedence([Rule::class]);
        $this->assertSame([Markup::class, IndentedCode::class], $BlockTypes->markedBy('@'));
        $this->assertSame([Rule::class, Markup::class], $BlockTypes->unmarked());

        $BlockTypes = $BlockTypes->addingMarkedLowPrecedence('@', [Rule::class]);
        $this->assertSame([Markup::class, IndentedCode::class, Rule::class], $BlockTypes->markedBy('@'));

        $BlockTypes = $BlockTypes->addingUnmarkedLowPrecedence([IndentedCode::class]);
        $this->assertSame([Markup::class, IndentedCode::class, Rule::class], $BlockTypes->markedBy('@'));
        $this->assertSame([Rule::class, Markup::class, IndentedCode::class], $BlockTypes->unmarked());
    }
}
