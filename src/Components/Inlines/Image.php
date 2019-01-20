<?php

namespace Erusev\Parsedown\Components\Inlines;

use Erusev\Parsedown\AST\Handler;
use Erusev\Parsedown\AST\StateRenderable;
use Erusev\Parsedown\Components\Inline;
use Erusev\Parsedown\Configurables\SafeMode;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Html\Renderables\Text;
use Erusev\Parsedown\Parsedown;
use Erusev\Parsedown\Parsing\Excerpt;
use Erusev\Parsedown\State;

/** @psalm-type _Metadata=array{href: string, title?: string} */
final class Image implements Inline
{
    use WidthTrait, DefaultBeginPosition;

    /** @var Link */
    private $Link;

    /**
     * @param Link $Link
     */
    public function __construct(Link $Link)
    {
        $this->Link = $Link;
        $this->width = $Link->width() + 1;
    }

    /**
     * @param Excerpt $Excerpt
     * @param State $State
     * @return static|null
     */
    public static function build(Excerpt $Excerpt, State $State)
    {
        if (\substr($Excerpt->text(), 0, 1) !== '!') {
            return null;
        }

        $Excerpt = $Excerpt->addingToOffset(1);

        $Link = Link::build($Excerpt, $State);

        if (! isset($Link)) {
            return null;
        }

        return new self($Link);
    }

    /**
     * @return Handler<Element|Text>
     */
    public function stateRenderable(Parsedown $Parsedown)
    {
        return new Handler(
            /** @return Element|Text */
            function (State $State) use ($Parsedown) {
                $linkAttributes = $this->Link->attributes();

                $attributes = [
                    'src' => $linkAttributes['href'],
                    'alt' => $this->Link->label(),
                ];

                if (isset($linkAttributes['title'])) {
                    $attributes['title'] = $linkAttributes['title'];
                }

                if ($State->getOrDefault(SafeMode::class)->enabled()) {
                    $attributes['src'] = Element::filterUnsafeUrl($attributes['src']);
                }

                return Element::selfClosing('img', $attributes);
            }
        );
    }
}
