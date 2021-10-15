<?php

namespace Erusev\Parsedown\Components\Blocks;

use Erusev\Parsedown\Components\Block;
use Erusev\Parsedown\Components\StateUpdatingBlock;
use Erusev\Parsedown\Configurables\DefinitionBook;
use Erusev\Parsedown\Html\Renderables\Invisible;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\State;

final class Reference implements StateUpdatingBlock
{
    /** @var State */
    private $State;

    private function __construct(State $State)
    {
        $this->State = $State;
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
        if (\preg_match(
            '/^\[(.+?)\]:[ ]*+<?(\S+?)>?(?:[ ]+["\'(](.+)["\')])?[ ]*+$/',
            $Context->line()->text(),
            $matches
        )) {
            $id = \strtolower($matches[1]);

            $Data = [
                'url' => $matches[2],
                'title' => isset($matches[3]) ? $matches[3] : null,
            ];

            $State->get(DefinitionBook::class)->mutatingSet($id, $Data);

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
