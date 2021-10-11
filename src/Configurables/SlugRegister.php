<?php

namespace Erusev\Parsedown\Configurables;

use Erusev\Parsedown\MutableConfigurable;

final class SlugRegister implements MutableConfigurable
{
    /** @var array<string, int> */
    private $register;

    /**
     * @param array<string, int> $register
     */
    public function __construct(array $register = [])
    {
        $this->register = $register;
    }

    /** @return self */
    public static function initial()
    {
        return new self;
    }

    public function mutatingIncrement(string $slug): int
    {
        if (! isset($this->register[$slug])) {
            $this->register[$slug] = 0;
        }

        return ++$this->register[$slug];
    }

    public function slugCount(string $slug): int
    {
        return $this->register[$slug] ?? 0;
    }

    /** @return static */
    public function isolatedCopy(): self
    {
        return new self($this->register);
    }
}
