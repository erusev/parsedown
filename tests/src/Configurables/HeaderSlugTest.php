<?php

namespace Erusev\Parsedown\Tests\Configurables;

use Erusev\Parsedown\Configurables\HeaderSlug;
use Erusev\Parsedown\State;
use PHPUnit\Framework\TestCase;

final class HeaderSlugTest extends TestCase
{
    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testNamedConstructor()
    {
        $State = new State([HeaderSlug::enabled()]);

        $this->assertSame(true, $State->get(HeaderSlug::class)->isEnabled());
    }
    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testCustomCallback()
    {
        $HeaderSlug = HeaderSlug::withCallback(function (string $t): string {
            return \preg_replace('/[^A-Za-z0-9]++/', '_', $t);
        });

        $this->assertSame(
            'foo_bar',
            $HeaderSlug->transform('foo bar')
        );
    }
}
