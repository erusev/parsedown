<?php

namespace Erusev\Parsedown\Configurables;

use Erusev\Parsedown\Configurable;

/**
 * @psalm-type _Data=array{url: string, title: string|null}
 */
final class DefinitionBook implements Configurable
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
     * @return self
     */
    public function setting($id, array $data)
    {
        $book = $this->book;
        $book[$id] = $data;

        return new self($book);
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
}
