<?php

namespace Erusev\Parsedown\Components\Blocks;

use Erusev\Parsedown\AST\Handler;
use Erusev\Parsedown\AST\StateRenderable;
use Erusev\Parsedown\Components\Block;
use Erusev\Parsedown\Components\ContinuableBlock;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Parsedown;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\State;

/**
 * @psalm-type _Alignment='left'|'center'|'right'
 */
final class Table implements ContinuableBlock
{
    use ContinuableBlockDefaultInterrupt, BlockAcquisition;

    /** @var array<int, _Alignment|null> */
    private $alignments;

    /** @var array<int, string> */
    private $headerCells;

    /** @var array<int, array<int, string>> */
    private $rows;

    /**
     * @param array<int, _Alignment|null> $alignments
     * @param array<int, string> $headerCells
     * @param array<int, array<int, string>> $rows
     * @param bool $acquired
     */
    public function __construct($alignments, $headerCells, $rows, $acquired = false)
    {
        $this->alignments = $alignments;
        $this->headerCells = $headerCells;
        $this->rows = $rows;
        $this->acquired = $acquired;
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
        if (! isset($Block) or ! $Block instanceof Paragraph or $Context->previousEmptyLines() > 0) {
            return null;
        }

        if (
            \strpos($Block->text(), '|') === false
            and \strpos($Context->line()->text(), '|') === false
            and \strpos($Context->line()->text(), ':') === false
            or \strpos($Block->text(), "\n") !== false
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

        return new self($alignments, $headerCells, [], true);
    }

    /**
     * @param Context $Context
     * @return self|null
     */
    public function continue(Context $Context)
    {
        if ($Context->previousEmptyLines() > 0) {
            return null;
        }

        if (\count($this->alignments) !== 1 and $Context->line()->text()[0] !== '|' and !\strpos($Context->line()->text(), '|')) {
            return null;
        }

        $Elements = [];

        $row = \trim(\trim($Context->line()->text()), '|');

        if (
            ! \preg_match_all('/(?:(\\\\[|])|[^|`]|`[^`]++`|`)++/', $row, $matches)
            or ! isset($matches[0])
            or ! \is_array($matches[0])
        ) {
            return null;
        }

        $cells = \array_map('trim', \array_slice($matches[0], 0, \count($this->alignments)));

        return new self(
            $this->alignments,
            $this->headerCells,
            \array_merge($this->rows, [$cells])
        );
    }

    /**
     * @param string $dividerRow
     * @return array<int, _Alignment|null>|null
     */
    private static function parseAlignments($dividerRow)
    {
        $dividerRow = \trim($dividerRow);
        $dividerRow = \trim($dividerRow, '|');

        $dividerCells = \explode('|', $dividerRow);

        /** @var array<int, _Alignment|null> */
        $alignments = [];

        foreach ($dividerCells as $dividerCell) {
            $dividerCell = \trim($dividerCell);

            if ($dividerCell === '') {
                return null;
            }

            /** @var _Alignment|null */
            $alignment = null;

            if ($dividerCell[0] === ':') {
                $alignment = 'left';
            }

            if (\substr($dividerCell, - 1) === ':') {
                $alignment = $alignment === 'left' ? 'center' : 'right';
            }

            $alignments []= $alignment;
        }

        return $alignments;
    }

    /**
     * @return Handler<Element>
     */
    public function stateRenderable(Parsedown $Parsedown)
    {
        return new Handler(
            /** @return Element */
            function (State $State) use ($Parsedown) {
                return new Element('table', [], [
                    new Element('thead', [], [new Element('tr', [], \array_map(
                        /**
                         * @param string $cell
                         * @param _Alignment|null $alignment
                         * @return Element
                         */
                        function ($cell, $alignment) use ($Parsedown, $State) {
                            return new Element(
                                'th',
                                isset($alignment) ? ['style' => "text-align: $alignment;"] : [],
                                $State->applyTo($Parsedown->lineElements($cell))
                            );
                        },
                        $this->headerCells,
                        $this->alignments
                    ))]),
                    new Element('tbody', [], \array_map(
                        /**
                         * @param array<int, string> $cells
                         * @return Element
                         */
                        function ($cells) use ($Parsedown, $State) {
                            return new Element('tr', [], \array_map(
                                /**
                                 * @param string $cell
                                 * @param _Alignment|null $alignment
                                 * @return Element
                                 */
                                function ($cell, $alignment) use ($Parsedown, $State) {
                                    return new Element(
                                        'td',
                                        isset($alignment) ? ['style' => "text-align: $alignment;"] : [],
                                        $State->applyTo($Parsedown->lineElements($cell))
                                    );
                                },
                                $cells,
                                \array_slice($this->alignments, 0, \count($cells))
                            ));
                        },
                        $this->rows
                    ))
                ]);
            }
        );
    }
}
