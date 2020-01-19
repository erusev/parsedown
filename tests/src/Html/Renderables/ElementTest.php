<?php

namespace Erusev\Parsedown\Tests\Html\Renderables;

use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Html\Renderables\Text;
use PHPUnit\Framework\TestCase;

final class ElementTest extends TestCase
{
    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testElementProperties()
    {
        $Element = new Element(
            'foo',
            [
                'bar' => 'baz',
                'boo' => 'bim',
            ],
            [new Text('zoo')]
        );

        $this->assertSame($Element->name(), 'foo');
        $this->assertSame(
            $Element->attributes(),
            [
                'bar' => 'baz',
                'boo' => 'bim',
            ]
        );
        $this->assertTrue($Element->contents()[0] instanceof Text);
        $this->assertSame($Element->contents()[0]->getHtml(), 'zoo');
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testSettingElementProperties()
    {
        $Element = new Element(
            'foo',
            [
                'bar' => 'baz',
                'boo' => 'bim',
            ],
            [new Text('zoo')]
        );

        $Element = $Element
            ->settingAttributes(['bar' => 'bim'])
            ->settingContents(null)
        ;

        $this->assertSame($Element->name(), 'foo');
        $this->assertSame($Element->attributes(), ['bar' => 'bim']);
        $this->assertSame($Element->contents(), null);

        $Element = $Element->settingName('foo1');
        $this->assertSame($Element->name(), 'foo1');
    }
}
