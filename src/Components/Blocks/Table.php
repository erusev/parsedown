<?php

namespace Erusev\Parsedown\Components\Blocks;

use Erusev\Parsedown\AST\Handler;
use Erusev\Parsedown\Components\AcquisitioningBlock;
use Erusev\Parsedown\Components\Block;
use Erusev\Parsedown\Components\ContinuableBlock;
use Erusev\Parsedown\Components\Inline;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Parsedown;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\State;

/**
 * @psalm-type _Alignment='left'|'center'|'right'
 */
final class Table implements AcquisitioningBlock, ContinuableBlock
{
    /** @var list<_Alignment|null> */
    private $alignments;

    /** @var list<string> */
    private $headerCells;

    /** @var list<list<string>> */
    private $rows;

    /**
     * @param list<_Alignment|null> $alignments
     * @param list<string> $headerCells
     * @param list<list<string>> $rows
     */
    private function __construct($alignments, $headerCells, $rows)
    {
        $this->alignments = $alignments;
        $this->headerCells = $headerCells;
        $this->rows = $rows;
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
        if (! isset($Block) || ! $Block instanceof Paragraph) {
            return null;
        }

        if (
            \strpos($Block->text(), '|') === false
            && \strpos($Context->line()->text(), '|') === false
            && \strpos($Context->line()->text(), ':') === false
            || \strpos($Block->text(), "\n") !== false
        ) {
            return null;
        }

        if (\chop($Context->line()->text(), ' -:|') !== '') {
            return null;
        }


        $alignments = self::parseAlignments($Context->line()->text());

        if (! isset($alignments)) {
            return null;
        }

        # ~

        $headerRow = \trim(\trim($Block->text()), '|');

        $headerCells = \array_map('trim', \explode('|', $headerRow));

        if (\count($headerCells) !== \count($alignments)) {
            return null;
        }

        # ~

        return new self($alignments, $headerCells, []);
    }

    /**
     * @param Context $Context
     * @param State $State
     * @return self|null
     */
    public function advance(Context $Context, State $State)
    {
        if ($Context->precedingEmptyLines() > 0) {
            return null;
        }

        if (
            \count($this->alignments) !== 1
            && \strpos($Context->line()->text(), '|') === false
        ) {
            return null;
        }

        $row = \trim(\trim($Context->line()->text()), '|');

        if (
            ! \preg_match_all('/(?:(\\\\[|])|[^|`]|`[^`]++`|`)++/', $row, $matches)
            || ! isset($matches[0])
        ) {
            return null;
        }

        $cells = \array_map(
            'trim',
            \array_slice($matches[0], 0, \count($this->alignments))
        );

        return new self(
            $this->alignments,
            $this->headerCells,
            \array_merge($this->rows, [$cells])
        );
    }

    /**
     * @param string $dividerRow
     * @return list<_Alignment|null>|null
     */
    private static function parseAlignments($dividerRow)
    {
        $dividerRow = \trim($dividerRow);
        $dividerRow = \trim($dividerRow, '|');

        $dividerCells = \explode('|', $dividerRow);

        /** @var list<_Alignment|null> */
        $alignments = [];

        foreach ($dividerCells as $dividerCell) {
            $dividerCell = \trim($dividerCell);

            if ($dividerCell === '') {
                return null;
            }

            /** @var _Alignment|null */
            $alignment = null;

            if (\substr($dividerCell, 0, 1) === ':') {
                $alignment = 'left';
            }

            if (\substr($dividerCell, - 1) === ':') {
                $alignment = $alignment === 'left' ? 'center' : 'right';
            }

            $alignments []= $alignment;
        }

        return $alignments;
    }

    /** @return bool */
    public function acquiredPrevious()
    {
        return true;
    }

    /** @return list<Inline[]> */
    public function headerRow(State $State)
    {
        return \array_map(
            /**
             * @param string $cell
             * @return Inline[]
             */
            function ($cell) use ($State) {
                return Parsedown::inlines($cell, $State);
            },
            $this->headerCells
        );
    }

    /** @return list<Inline[]>[] */
    public function rows(State $State)
    {
        return \array_map(
            /**
             * @param list<string> $cells
             * @return list<Inline[]>
             */
            function ($cells) use ($State) {
                return \array_map(
                    /**
                     * @param string $cell
                     * @return Inline[]
                     */
                    function ($cell) use ($State) {
                        return Parsedown::inlines($cell, $State);
                    },
                    $cells
                );
            },
            $this->rows
        );
    }

    /** @return list<_Alignment|null> */
    public function alignments()
    {
        return $this->alignments;
    }

    /**
     * @return Handler<Element>
     */
    public function stateRenderable()
    {
        return new Handler(
            /** @return Element */
            function (State $State) {
                return new Element('table', [], [
                    new Element('thead', [], [new Element('tr', [], \array_map(
                        /**
                         * @param Inline[] $Cell
                         * @param _Alignment|null $alignment
                         * @return Element
                         */
                        function ($Cell, $alignment) use ($State) {
                            return new Element(
                                'th',
                                isset($alignment) ? ['style' => "text-align: $alignment;"] : [],
                                $State->applyTo(Parsedown::stateRenderablesFrom($Cell))
                            );
                        },
                        $this->headerRow($State),
                        $this->alignments()
                    ))]),
                    new Element('tbody', [], \array_map(
                        /**
                         * @param Inline[][] $Cells
                         * @return Element
                         */
                        function ($Cells) use ($State) {
                            return new Element('tr', [], \array_map(
                                /**
                                 * @param Inline[] $Cell
                                 * @param _Alignment|null $alignment
                                 * @return Element
                                 */
                                function ($Cell, $alignment) use ($State) {
                                    return new Element(
                                        'td',
                                        isset($alignment) ? ['style' => "text-align: $alignment;"] : [],
                                        $State->applyTo(Parsedown::stateRenderablesFrom($Cell))
                                    );
                                },
                                $Cells,
                                \array_slice($this->alignments(), 0, \count($Cells))
                            ));
                        },
                        $this->rows($State)
                    ))
                ]);
            }
        );
    }
}
