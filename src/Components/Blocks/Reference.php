<?php

namespace Erusev\Parsedown\Components\Blocks;

use Erusev\Parsedown\AST\StateRenderable;
use Erusev\Parsedown\Components\Block;
use Erusev\Parsedown\Components\StateUpdatingBlock;
use Erusev\Parsedown\Configurables\DefinitionBook;
use Erusev\Parsedown\Html\Renderables\Invisible;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\State;

final class Reference implements StateUpdatingBlock
{
    use BlockAcquisition;

    /** @var State */
    private $State;

    public function __construct(State $State)
    {
        $this->State = $State;
    }

    /**
     * @param Context $Context
     * @param Block|null $Block
     * @param State|null $State
     * @return static|null
     */
    public static function build(
        Context $Context,
        Block $Block = null,
        State $State = null
    ) {
        $State = $State ?: new State;

        if (\strpos($Context->line()->text(), ']') !== false
            and \preg_match(
                '/^\[(.+?)\]:[ ]*+<?(\S+?)>?(?:[ ]+["\'(](.+)["\')])?[ ]*+$/',
                $Context->line()->text(),
                $matches
            )
        ) {
            $id = \strtolower($matches[1]);

            $Data = [
                'url' => $matches[2],
                'title' => isset($matches[3]) ? $matches[3] : null,
            ];

            $State = $State->setting(
                $State->get(DefinitionBook::class)->setting($id, $Data)
            );

            return new self($State);
        }

        return null;
    }

    /** @return State */
    public function latestState()
    {
        return $this->State;
    }

    /**
     * @return Invisible
     */
    public function stateRenderable()
    {
        return new Invisible;
    }
}
