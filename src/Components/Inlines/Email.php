<?php

namespace Erusev\Parsedown\Components\Inlines;

use Erusev\Parsedown\Components\Inline;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Html\Renderables\Text;
use Erusev\Parsedown\Parsing\Excerpt;
use Erusev\Parsedown\State;

final class Email implements Inline
{
    use WidthTrait;

    /** @var string */
    private $text;

    /** @var string */
    private $url;

    /**
     * @param string $text
     * @param string $url
     * @param int $width
     */
    private function __construct($text, $url, $width)
    {
        $this->text = $text;
        $this->url = $url;
        $this->width = $width;
    }

    /**
     * @param Excerpt $Excerpt
     * @param State $State
     * @return static|null
     */
    public static function build(Excerpt $Excerpt, State $State)
    {
        $hostnameLabel = '[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?';

        $commonMarkEmail = '[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]++@'
            . $hostnameLabel . '(?:\.' . $hostnameLabel . ')*';

        if (\preg_match("/^<((mailto:)?$commonMarkEmail)>/i", $Excerpt->text(), $matches)) {
            $url = $matches[1];

            if (! isset($matches[2])) {
                $url = "mailto:$url";
            }

            return new self($matches[1], $url, \strlen($matches[0]));
        }
    }

    /** @return string */
    public function text()
    {
        return $this->text;
    }

    /** @return string */
    public function url()
    {
        return $this->url;
    }

    /**
     * @return Element
     */
    public function stateRenderable()
    {
        return new Element('a', ['href' => $this->url()], [new Text($this->text())]);
    }

    /**
     * @return Text
     */
    public function bestPlaintext()
    {
        return new Text($this->text());
    }
}
