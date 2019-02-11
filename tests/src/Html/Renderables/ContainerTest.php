<?php

namespace Erusev\Parsedown\Tests\Html\Renderables;

use Erusev\Parsedown\Html\Renderables\Container;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Html\Renderables\Text;
use PHPUnit\Framework\TestCase;

final class ContainerTest extends TestCase
{
    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testContainerContents()
    {
        $Container = new Container([
            new Element('foo', [], null),
            new Text('bar'),
        ]);

        $Contents = $Container->contents();

        $this->assertTrue($Contents[0] instanceof Element);
        $this->assertSame($Contents[0]->name(), 'foo');
        $this->assertTrue($Contents[1] instanceof Text);
        $this->assertSame($Contents[1]->getHtml(), 'bar');
    }
}
