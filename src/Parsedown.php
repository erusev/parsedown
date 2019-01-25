<?php

namespace Erusev\Parsedown;

use Erusev\Parsedown\AST\StateRenderable;
use Erusev\Parsedown\Components\Block;
use Erusev\Parsedown\Components\Blocks\Paragraph;
use Erusev\Parsedown\Components\ContinuableBlock;
use Erusev\Parsedown\Components\Inline;
use Erusev\Parsedown\Components\Inlines\PlainText;
use Erusev\Parsedown\Components\StateUpdatingBlock;
use Erusev\Parsedown\Configurables\BlockTypes;
use Erusev\Parsedown\Configurables\InlineTypes;
use Erusev\Parsedown\Html\Renderable;
use Erusev\Parsedown\Html\Renderables\Invisible;
use Erusev\Parsedown\Html\Renderables\Text;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\Parsing\Excerpt;
use Erusev\Parsedown\Parsing\Line;
use Erusev\Parsedown\Parsing\Lines;

final class Parsedown
{
    const version = '2.0.0-dev';

    /** @var State */
    private $State;

    public function __construct(State $State = null)
    {
        $this->State = $State ?: new State;

        // ensure we cache the initial value if these weren't explicitly set
        $this->State = $this->State->mergingWith(new State([
            $this->State->get(BlockTypes::class),
            $this->State->get(InlineTypes::class),
        ]));
    }

    /**
     * @param string $text
     * @return string
     */
    public function text($text)
    {
        $InitialState = $this->State;

        $StateRenderables = $this->lines(Lines::fromTextLines($text, 0));

        $Renderables = $this->State->applyTo($StateRenderables);

        $this->State = $InitialState;

        $html = self::render($Renderables);

        # trim line breaks
        $html = \trim($html, "\n");

        return $html;
    }

    /**
     * @return StateRenderable[]
     */
    public function lines(Lines $Lines)
    {
        return \array_map(
            /** @return StateRenderable */
            function (Block $Block) { return $Block->stateRenderable($this); },
            $this->blocks($Lines)
        );
    }

    /**
     * @return Block[]
     */
    public function blocks(Lines $Lines)
    {
        /** @var Block[] */
        $Blocks = [];
        /** @var Block|null */
        $Block = null;
        /** @var Block|null */
        $CurrentBlock = null;

        foreach ($Lines->contexts() as $Context) {
            $Line = $Context->line();

            if (
                isset($CurrentBlock)
                && $CurrentBlock instanceof ContinuableBlock
                && ! $CurrentBlock instanceof Paragraph
            ) {
                $Block = $CurrentBlock->advance($Context);

                if (isset($Block)) {
                    $CurrentBlock = $Block;

                    continue;
                }
            }

            $marker = $Line->text()[0];

            $potentialBlockTypes = \array_merge(
                $this->State->get(BlockTypes::class)->unmarked(),
                $this->State->get(BlockTypes::class)->markedBy($marker)
            );

            foreach ($potentialBlockTypes as $blockType) {
                $Block = $blockType::build($Context, $CurrentBlock, $this->State);

                if (isset($Block)) {
                    if ($Block instanceof StateUpdatingBlock) {
                        $this->State = $Block->latestState();
                    }

                    if (isset($CurrentBlock) && ! $Block->acquiredPrevious()) {
                        $Blocks[] = $CurrentBlock;
                    }

                    $CurrentBlock = $Block;

                    continue 2;
                }
            }

            if (isset($CurrentBlock) and $CurrentBlock instanceof Paragraph) {
                $Block = $CurrentBlock->advance($Context);
            }

            if (isset($Block)) {
                $CurrentBlock = $Block;
            } else {
                if (isset($CurrentBlock)) {
                    $Blocks[] = $CurrentBlock;
                }

                $CurrentBlock = Paragraph::build($Context);
            }
        }

        if (isset($CurrentBlock)) {
            $Blocks[] = $CurrentBlock;
        }

        return $Blocks;
    }

    /**
     * @param string $text
     * @return StateRenderable[]
     */
    public function line($text)
    {
        return \array_map(
            /** @return StateRenderable */
            function (Inline $Inline) { return $Inline->stateRenderable($this); },
            $this->inlines($text)
        );
    }

    /**
     * @param string $text
     * @return Inline[]
     */
    public function inlines($text)
    {
        # standardize line breaks
        $text = \str_replace(["\r\n", "\r"], "\n", $text);

        /** @var Inline[] */
        $Inlines = [];

        # $excerpt is based on the first occurrence of a marker

        $InlineTypes = $this->State->get(InlineTypes::class);
        $markerMask = $InlineTypes->markers();

        for (
            $Excerpt = (new Excerpt($text, 0))->pushingOffsetTo($markerMask);
            $Excerpt->text() !== '';
            $Excerpt = $Excerpt->pushingOffsetTo($markerMask)
        ) {
            $marker = \substr($Excerpt->text(), 0, 1);

            foreach ($InlineTypes->markedBy($marker) as $inlineType) {
                # check to see if the current inline type is nestable in the current context

                $Inline = $inlineType::build($Excerpt, $this->State);

                if (! isset($Inline)) {
                    continue;
                }

                $startPosition = $Inline->modifyStartPositionTo();

                if (! isset($startPosition)) {
                    $startPosition = $Excerpt->offset();
                }

                # makes sure that the inline belongs to "our" marker

                if ($startPosition > $Excerpt->offset()) {
                    continue;
                }

                # the text that comes before the inline
                # compile the unmarked text
                $Inlines[] = Plaintext::build($Excerpt->choppingUpToOffset($startPosition));

                # compile the inline
                $Inlines[] = $Inline;

                # remove the examined text
                /** @psalm-suppress LoopInvalidation */
                $Excerpt = $Excerpt->choppingFromOffset($startPosition + $Inline->width());

                continue 2;
            }

            /** @psalm-suppress LoopInvalidation */
            $Excerpt = $Excerpt->addingToOffset(1);
        }

        $Inlines[] = Plaintext::build($Excerpt->choppingFromOffset(0));

        return $Inlines;
    }

    /**
     * @param Renderable[] $Renderables
     * @return string
     */
    public static function render(array $Renderables)
    {
        return \array_reduce(
            $Renderables,
            /**
             * @param string $html
             * @return string
             */
            function ($html, Renderable $Renderable) {
                return (
                    $html
                    . ($Renderable instanceof Invisible ? '' : "\n")
                    . $Renderable->getHtml()
                );
            },
            ''
        );
    }
}
