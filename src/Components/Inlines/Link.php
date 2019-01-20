<?php

namespace Erusev\Parsedown\Components\Inlines;

use Erusev\Parsedown\AST\Handler;
use Erusev\Parsedown\AST\StateRenderable;
use Erusev\Parsedown\Components\Inline;
use Erusev\Parsedown\Configurables\DefinitionBook;
use Erusev\Parsedown\Configurables\SafeMode;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Html\Renderables\Text;
use Erusev\Parsedown\Parsedown;
use Erusev\Parsedown\Parsing\Excerpt;
use Erusev\Parsedown\State;

/** @psalm-type _Metadata=array{href: string, title?: string} */
final class Link implements Inline
{
    use WidthTrait, DefaultBeginPosition;

    /** @var string */
    private $label;

    /** @var _Metadata */
    private $attributes;

    /**
     * @param string $label
     * @param _Metadata $attributes
     * @param int $width
     */
    public function __construct($label, $attributes, $width)
    {
        $this->label = $label;
        $this->attributes = $attributes;
        $this->width = $width;
    }

    /**
     * @param Excerpt $Excerpt
     * @param State $State
     * @return static|null
     */
    public static function build(Excerpt $Excerpt, State $State)
    {
        $remainder = $Excerpt->text();

        if (! \preg_match('/\[((?:[^][]++|(?R))*+)\]/', $remainder, $matches)) {
            return null;
        }
        $rawLabelPart = $matches[0];
        $label = $matches[1];
        /** @var _Metadata|null */
        $attributes = null;

        $extent = \strlen($matches[0]);

        $remainder = \substr($remainder, $extent);

        if (\preg_match('/^[(]\s*+((?:[^ ()]++|[(][^ )]+[)])++)(?:[ ]+("[^"]*+"|\'[^\']*+\'))?\s*+[)]/', $remainder, $matches)) {
            $attributes = ['href' => $matches[1]];

            if (isset($matches[2])) {
                $attributes['title'] = \substr($matches[2], 1, - 1);
            }

            $extent += \strlen($matches[0]);
        } else {
            if (\preg_match('/^\s*\[(.*?)\]/', $remainder, $matches)) {
                $definition = \strlen($matches[1]) ? $matches[1] : $label;
                $definition = \strtolower($definition);

                $extent += \strlen($matches[0]);
            } else {
                $definition = \strtolower($label);
            }

            $data = $State->getOrDefault(DefinitionBook::class)->lookup($definition);

            if (! isset($data)) {
                return null;
            }

            $attributes = ['href' => $data['url']];

            if (isset($data['title'])) {
                $attributes['title'] = $data['title'];
            }
        }

        return new self($label, $attributes, $extent);
    }

    /** @return string */
    public function label()
    {
        return $this->label;
    }

    /** @return _Metadata */
    public function attributes()
    {
        return $this->attributes;
    }

    /**
     * @return Handler<Element|Text>
     */
    public function stateRenderable(Parsedown $Parsedown)
    {
        return new Handler(
            /** @return Element|Text */
            function (State $State) use ($Parsedown) {
                $attributes = $this->attributes;

                if ($State->getOrDefault(SafeMode::class)->enabled()) {
                    $attributes['href'] = Element::filterUnsafeUrl($attributes['href']);
                }

                return new Element(
                    'a',
                    $attributes,
                    $State->applyTo($Parsedown->lineElements($this->label))
                );
            }
        );
    }
}
