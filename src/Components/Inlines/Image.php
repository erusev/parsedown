<?php

namespace Erusev\Parsedown\Components\Inlines;

use Erusev\Parsedown\AST\Handler;
use Erusev\Parsedown\AST\StateRenderable;
use Erusev\Parsedown\Components\Inline;
use Erusev\Parsedown\Configurables\SafeMode;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Html\Renderables\Text;
use Erusev\Parsedown\Html\Sanitisation\UrlSanitiser;
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
    public function stateRenderable()
    {
        return new Handler(
            /** @return Element|Text */
            function (State $State) {
                $attributes = [
                    'src' => $this->Link->url(),
                    'alt' => \array_reduce(
                        Parsedown::inlines($this->Link->label(), $State),
                        /**
                         * @param string $text
                         * @return string
                         */
                        function ($text, Inline $Inline) {
                            return (
                                $text
                                . $Inline->bestPlaintext()->getStringBacking()
                            );
                        },
                        ''
                    ),
                ];

                $title = $this->Link->title();

                if (isset($title)) {
                    $attributes['title'] = $title;
                }

                if ($State->get(SafeMode::class)->isEnabled()) {
                    $attributes['src'] = UrlSanitiser::filter($attributes['src']);
                }

                return Element::selfClosing('img', $attributes);
            }
        );
    }

    /**
     * @return Text
     */
    public function bestPlaintext()
    {
        return new Text($this->Link->label());
    }
}
