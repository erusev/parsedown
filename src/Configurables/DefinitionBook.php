<?php

namespace Erusev\Parsedown\Configurables;

use Erusev\Parsedown\MutableConfigurable;

/**
 * @psalm-type _Data=array{url: string, title: string|null}
 */
final class DefinitionBook implements MutableConfigurable
{
    /** @var array<string, _Data> */
    private $book;

    /**
     * @param array<string, _Data> $book
     */
    public function __construct(array $book = [])
    {
        $this->book = $book;
    }

    /** @return self */
    public static function initial()
    {
        return new self;
    }

    /**
     * @param string $id
     * @param _Data $data
     */
    public function mutatingSet($id, array $data): void
    {
        $this->book[$id] = $data;
    }

    /**
     * @param string $id
     * @return _Data|null
     */
    public function lookup($id)
    {
        if (isset($this->book[$id])) {
            return $this->book[$id];
        }

        return null;
    }

    /** @return static */
    public function isolatedCopy(): self
    {
        return new self($this->book);
    }
}
