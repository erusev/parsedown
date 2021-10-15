<?php

namespace Erusev\Parsedown\Components\Blocks;

use Erusev\Parsedown\AST\Handler;
use Erusev\Parsedown\Components\Block;
use Erusev\Parsedown\Configurables\HeaderSlug;
use Erusev\Parsedown\Configurables\SlugRegister;
use Erusev\Parsedown\Configurables\StrictMode;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Parsedown;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\State;

final class Header implements Block
{
    /** @var string */
    private $text;

    /** @var 1|2|3|4|5|6 */
    private $level;

    /**
     * @param string $text
     * @param 1|2|3|4|5|6 $level
     */
    private function __construct($text, $level)
    {
        $this->text = $text;
        $this->level = $level;
    }

    /**
     * @param Context $Context
     * @param State $State
     * @param Block|null $Block
     * @return static|null
     */
    public static function build(
        Context $Context,
        State $State,
        Block $Block = null
    ) {
        if ($Context->line()->indent() > 3) {
            return null;
        }

        $level = \strspn($Context->line()->text(), '#');

        if ($level > 6 || $level < 1) {
            return null;
        }

        /** @var 1|2|3|4|5|6 $level */

        $text = \ltrim($Context->line()->text(), '#');

        $firstChar = \substr($text, 0, 1);

        if (
            $State->get(StrictMode::class)->isEnabled()
            && \trim($firstChar, " \t") !== ''
        ) {
            return null;
        }

        $text = \trim($text, " \t");

        # remove closing sequence
        $removedClosing = \rtrim($text, '#');
        $lastChar = \substr($removedClosing, -1);

        if (\trim($lastChar, " \t") === '') {
            $text = \rtrim($removedClosing, " \t");
        }

        return new self($text, $level);
    }

    /** @return string */
    public function text()
    {
        return $this->text;
    }

    /** @return 1|2|3|4|5|6 */
    public function level()
    {
        return $this->level;
    }

    /**
     * @return Handler<Element>
     */
    public function stateRenderable()
    {
        return new Handler(
            /** @return Element */
            function (State $State) {
                $HeaderSlug = $State->get(HeaderSlug::class);
                $Register = $State->get(SlugRegister::class);
                $attributes = (
                    $HeaderSlug->isEnabled()
                    ? ['id' => $HeaderSlug->transform($Register, $this->text())]
                    : []
                );

                return new Element(
                    'h' . \strval($this->level()),
                    $attributes,
                    $State->applyTo(Parsedown::line($this->text(), $State))
                );
            }
        );
    }
}
