<?php

namespace Erusev\Parsedown\Html\Renderables;

use Erusev\Parsedown\Html\Sanitisation\Escaper;
use Erusev\Parsedown\Html\TransformableRenderable;

final class Text implements TransformableRenderable
{
    use CanonicalStateRenderable;

    /** @var string */
    private $text;

    /**
     * @param string $text
     */
    public function __construct($text = '')
    {
        $this->text = $text;
    }

    /** @return string */
    public function getStringBacking()
    {
        return $this->text;
    }

    /** @return string */
    public function getHtml()
    {
        return Escaper::htmlElementValueEscapingDoubleQuotes($this->text);
    }

    /**
     * @param \Closure(string):TransformableRenderable $Transform
     * @return TransformableRenderable
     */
    public function transformingContent(\Closure $Transform): TransformableRenderable
    {
        return $Transform($this->text);
    }

    public function replacingAll(string $search, TransformableRenderable $Replacement): TransformableRenderable
    {
        $searchLen = \strlen($search);

        if ($searchLen < 1) {
            return $this;
        }

        $result = \preg_match_all(
            '/\b'.\preg_quote($search, '/').'\b/',
            $this->text,
            $matches,
            \PREG_OFFSET_CAPTURE
        );

        if (empty($result)) {
            return $this;
        }

        $lastEndPos = 0;

        $Container = new Container;

        foreach ($matches[0] as $match) {
            $pos = $match[1];
            $endPos = $pos + $searchLen;

            if ($pos !== $lastEndPos) {
                $Container = $Container->adding(
                    new Text(\substr($this->text, $lastEndPos, $pos - $lastEndPos))
                );
            }

            $Container = $Container->adding($Replacement);
            $lastEndPos = $endPos;
        }

        if (\strlen($this->text) !== $lastEndPos) {
            $Container = $Container->adding(
                new Text(\substr($this->text, $lastEndPos))
            );
        }

        return $Container;
    }
}
