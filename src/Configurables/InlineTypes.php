<?php

namespace Erusev\Parsedown\Configurables;

use Erusev\Parsedown\Components\Inline;
use Erusev\Parsedown\Components\Inlines\Code;
use Erusev\Parsedown\Components\Inlines\Email;
use Erusev\Parsedown\Components\Inlines\Emphasis;
use Erusev\Parsedown\Components\Inlines\EscapeSequence;
use Erusev\Parsedown\Components\Inlines\HardBreak;
use Erusev\Parsedown\Components\Inlines\Image;
use Erusev\Parsedown\Components\Inlines\Link;
use Erusev\Parsedown\Components\Inlines\Markup as InlineMarkup;
use Erusev\Parsedown\Components\Inlines\SoftBreak;
use Erusev\Parsedown\Components\Inlines\SpecialCharacter;
use Erusev\Parsedown\Components\Inlines\Strikethrough;
use Erusev\Parsedown\Components\Inlines\Url;
use Erusev\Parsedown\Components\Inlines\UrlTag;
use Erusev\Parsedown\Configurable;

final class InlineTypes implements Configurable
{
    /** @var array<array-key, array<int, class-string<Inline>>> */
    private static $defaultInlineTypes = [
        '!' => [Image::class],
        '*' => [Emphasis::class],
        '_' => [Emphasis::class],
        '&' => [SpecialCharacter::class],
        '[' => [Link::class],
        ':' => [Url::class],
        '<' => [UrlTag::class, Email::class, InlineMarkup::class],
        '`' => [Code::class],
        '~' => [Strikethrough::class],
        '\\' => [EscapeSequence::class],
        "\n" => [HardBreak::class, SoftBreak::class],
    ];

    /** @var array<array-key, array<int, class-string<Inline>>> */
    private $inlineTypes;

    /** @var string */
    private $inlineMarkers;

    /**
     * @param array<array-key, array<int, class-string<Inline>>> $inlineTypes
     */
    public function __construct(array $inlineTypes)
    {
        $this->inlineTypes = $inlineTypes;
        $this->inlineMarkers = \implode('', \array_keys($inlineTypes));
    }

    /** @return self */
    public static function initial()
    {
        return new self(self::$defaultInlineTypes);
    }

    /**
     * @param string $marker
     * @param array<int, class-string<Inline>> $newInlineTypes
     * @return self
     */
    public function setting($marker, array $newInlineTypes)
    {
        $inlineTypes = $this->inlineTypes;
        $inlineTypes[$marker] = $newInlineTypes;

        return new self($inlineTypes);
    }

    /**
     * @param string $marker
     * @param array<int, class-string<Inline>> $newInlineTypes
     * @return self
     */
    public function addingHighPrecedence($marker, array $newInlineTypes)
    {
        return $this->setting(
            $marker,
            \array_merge(
                $newInlineTypes,
                isset($this->inlineTypes[$marker]) ? $this->inlineTypes[$marker] : []
            )
        );
    }

    /**
     * @param string $marker
     * @param array<int, class-string<Inline>> $newInlineTypes
     * @return self
     */
    public function addingLowPrecedence($marker, array $newInlineTypes)
    {
        return $this->setting(
            $marker,
            \array_merge(
                isset($this->inlineTypes[$marker]) ? $this->inlineTypes[$marker] : [],
                $newInlineTypes
            )
        );
    }

    /**
     * @param array<int, class-string<Inline>> $removeInlineTypes
     * @return self
     */
    public function removing(array $removeInlineTypes)
    {
        return new self(\array_map(
            /**
             * @param array<int, class-string<Inline>> $inlineTypes
             * @return array<int, class-string<Inline>>
             */
            function ($inlineTypes) use ($removeInlineTypes) {
                return \array_diff($inlineTypes, $removeInlineTypes);
            },
            $this->inlineTypes
        ));
    }

    /**
     * @param string $marker
     * @return array<int, class-string<Inline>>
     */
    public function markedBy($marker)
    {
        if (isset($this->inlineTypes[$marker])) {
            return $this->inlineTypes[$marker];
        }

        return [];
    }

    /** @return string */
    public function markers()
    {
        return $this->inlineMarkers;
    }
}
