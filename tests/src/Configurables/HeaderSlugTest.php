<?php

namespace Erusev\Parsedown\Tests\Configurables;

use Erusev\Parsedown\Configurables\HeaderSlug;
use Erusev\Parsedown\Configurables\SlugRegister;
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
            $HeaderSlug->transform(SlugRegister::initial(), 'foo bar')
        );
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testCustomDuplicationCallback()
    {
        $HeaderSlug = HeaderSlug::withDuplicationCallback(function (string $t, int $n): string {
            return $t . '_' . \strval($n-1);
        });

        $SlugRegister = new SlugRegister;
        $HeaderSlug->transform($SlugRegister, 'foo bar');

        $this->assertSame(
            'foo-bar_1',
            $HeaderSlug->transform($SlugRegister, 'foo bar')
        );
    }
}
