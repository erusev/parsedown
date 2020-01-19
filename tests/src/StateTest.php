<?php

namespace Erusev\Parsedown\Tests;

use Erusev\Parsedown\Configurables\Breaks;
use Erusev\Parsedown\Configurables\SafeMode;
use Erusev\Parsedown\Configurables\StrictMode;
use Erusev\Parsedown\State;
use PHPUnit\Framework\TestCase;

final class StateTest extends TestCase
{
    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testStateMerge()
    {
        $State = new State;

        $this->assertFalse($State->get(SafeMode::class)->isEnabled());
        $this->assertFalse($State->get(StrictMode::class)->isEnabled());
        $this->assertFalse($State->get(Breaks::class)->isEnabled());

        $UpdatedState = $State->mergingWith(new State([SafeMode::enabled()]));

        $this->assertTrue($UpdatedState->get(SafeMode::class)->isEnabled());
        $this->assertFalse($UpdatedState->get(StrictMode::class)->isEnabled());
        $this->assertFalse($UpdatedState->get(Breaks::class)->isEnabled());
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testStateCloneVisibility()
    {
        $this->assertInstanceOf(State::class, clone(new State));
    }
}
