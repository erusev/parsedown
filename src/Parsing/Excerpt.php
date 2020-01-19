<?php

namespace Erusev\Parsedown\Parsing;

final class Excerpt
{
    /** @var string */
    private $context;

    /** @var int */
    private $offset;

    /** @var string */
    private $text;

    /**
     * @param string $context
     * @param int $offset
     */
    public function __construct($context, $offset)
    {
        $this->context = $context;
        $this->offset = $offset;
        $this->text = \substr($context, $offset);

        // only necessary pre-php7
        if ($this->text === false) {
            $this->text = '';
        }
    }

    /**
     * @param string $mask
     * @return self
     */
    public function pushingOffsetTo($mask)
    {
        return $this->addingToOffset(\strcspn($this->text, $mask));
    }

    /**
     * @param int $offset
     * @return self
     */
    public function choppingFromOffset($offset)
    {
        return new self(\substr($this->context, $offset), 0);
    }

    /**
     * @param int $offset
     * @return self
     */
    public function choppingUpToOffset($offset)
    {
        return new self(\substr($this->context, 0, $offset), 0);
    }

    /**
     * @param int $offsetIncrement
     * @return self
     */
    public function addingToOffset($offsetIncrement)
    {
        return new self($this->context, $this->offset + $offsetIncrement);
    }

    /** @return string */
    public function context()
    {
        return $this->context;
    }

    /** @return int */
    public function offset()
    {
        return $this->offset;
    }

    /** @return string */
    public function text()
    {
        return $this->text;
    }
}
