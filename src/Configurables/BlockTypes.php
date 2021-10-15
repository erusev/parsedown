<?php

namespace Erusev\Parsedown\Configurables;

use Erusev\Parsedown\Components\Block;
use Erusev\Parsedown\Components\Blocks\BlockQuote;
use Erusev\Parsedown\Components\Blocks\FencedCode;
use Erusev\Parsedown\Components\Blocks\Header;
use Erusev\Parsedown\Components\Blocks\IndentedCode;
use Erusev\Parsedown\Components\Blocks\Markup as BlockMarkup;
use Erusev\Parsedown\Components\Blocks\Reference;
use Erusev\Parsedown\Components\Blocks\Rule;
use Erusev\Parsedown\Components\Blocks\SetextHeader;
use Erusev\Parsedown\Components\Blocks\Table;
use Erusev\Parsedown\Components\Blocks\TList;
use Erusev\Parsedown\Configurable;

final class BlockTypes implements Configurable
{
    private const DEFAULT_BLOCK_TYPES = [
        '#' => [Header::class],
        '*' => [Rule::class, TList::class],
        '+' => [TList::class],
        '-' => [SetextHeader::class, Table::class, Rule::class, TList::class],
        '0' => [TList::class],
        '1' => [TList::class],
        '2' => [TList::class],
        '3' => [TList::class],
        '4' => [TList::class],
        '5' => [TList::class],
        '6' => [TList::class],
        '7' => [TList::class],
        '8' => [TList::class],
        '9' => [TList::class],
        ':' => [Table::class],
        '<' => [BlockMarkup::class],
        '=' => [SetextHeader::class],
        '>' => [BlockQuote::class],
        '[' => [Reference::class],
        '_' => [Rule::class],
        '`' => [FencedCode::class],
        '|' => [Table::class],
        '~' => [FencedCode::class],
    ];

    private const DEFAULT_UNMARKED_BLOCK_TYPES = [
        IndentedCode::class,
    ];

    /** @var array<array-key, list<class-string<Block>>> */
    private $blockTypes;

    /** @var list<class-string<Block>> */
    private $unmarkedBlockTypes;

    /**
     * @param array<array-key, list<class-string<Block>>> $blockTypes
     * @param list<class-string<Block>> $unmarkedBlockTypes
     */
    public function __construct(array $blockTypes, array $unmarkedBlockTypes)
    {
        $this->blockTypes = $blockTypes;
        $this->unmarkedBlockTypes = $unmarkedBlockTypes;
    }

    /** @return self */
    public static function initial()
    {
        return new self(
            self::DEFAULT_BLOCK_TYPES,
            self::DEFAULT_UNMARKED_BLOCK_TYPES
        );
    }

    /**
     * @param string $marker
     * @param list<class-string<Block>> $newBlockTypes
     * @return self
     */
    public function settingMarked($marker, array $newBlockTypes)
    {
        $blockTypes = $this->blockTypes;
        $blockTypes[$marker] = $newBlockTypes;

        return new self($blockTypes, $this->unmarkedBlockTypes);
    }

    /**
     * @param string $marker
     * @param list<class-string<Block>> $newBlockTypes
     * @return self
     */
    public function addingMarkedHighPrecedence($marker, array $newBlockTypes)
    {
        return $this->settingMarked(
            $marker,
            \array_merge(
                $newBlockTypes,
                isset($this->blockTypes[$marker]) ? $this->blockTypes[$marker] : []
            )
        );
    }

    /**
     * @param class-string<Block> $searchBlockType
     * @param class-string<Block> $replacementBlockType
     */
    public function replacing($searchBlockType, $replacementBlockType): self
    {
        $replacer = self::makeReplacer($searchBlockType, $replacementBlockType);

        return new self(
            \array_map($replacer, $this->blockTypes),
            $replacer($this->unmarkedBlockTypes)
        );
    }

    /**
     * @param class-string<Block> $searchBlockType
     * @param class-string<Block> $replacementBlockType
     * @return \Closure(list<class-string<Block>>):list<class-string<Block>>
     */
    private static function makeReplacer($searchBlockType, $replacementBlockType)
    {
        /**
         * @param list<class-string<Block>> $blockTypes
         * @return list<class-string<Block>>
         */
        return function ($blockTypes) use ($searchBlockType, $replacementBlockType) {
            return \array_map(
                /**
                 * @param class-string<Block> $blockType
                 * @return class-string<Block>
                 */
                function ($blockType) use ($searchBlockType, $replacementBlockType) {
                    return $blockType === $searchBlockType ? $replacementBlockType : $blockType;
                },
                $blockTypes
            );
        };
    }

    /**
     * @param string $marker
     * @param list<class-string<Block>> $newBlockTypes
     * @return self
     */
    public function addingMarkedLowPrecedence($marker, array $newBlockTypes)
    {
        return $this->settingMarked(
            $marker,
            \array_merge(
                isset($this->blockTypes[$marker]) ? $this->blockTypes[$marker] : [],
                $newBlockTypes
            )
        );
    }

    /**
     * @param list<class-string<Block>> $newUnmarkedBlockTypes
     * @return self
     */
    public function settingUnmarked(array $newUnmarkedBlockTypes)
    {
        return new self($this->blockTypes, $newUnmarkedBlockTypes);
    }

    /**
     * @param list<class-string<Block>> $newBlockTypes
     * @return self
     */
    public function addingUnmarkedHighPrecedence(array $newBlockTypes)
    {
        return $this->settingUnmarked(
            \array_merge($newBlockTypes, $this->unmarkedBlockTypes)
        );
    }

    /**
     * @param list<class-string<Block>> $newBlockTypes
     * @return self
     */
    public function addingUnmarkedLowPrecedence(array $newBlockTypes)
    {
        return $this->settingUnmarked(
            \array_merge($this->unmarkedBlockTypes, $newBlockTypes)
        );
    }

    /**
     * @param list<class-string<Block>> $removeBlockTypes
     * @return self
     */
    public function removing(array $removeBlockTypes)
    {
        return new self(
            \array_map(
                /**
                 * @param list<class-string<Block>> $blockTypes
                 * @return list<class-string<Block>>
                 */
                function ($blockTypes) use ($removeBlockTypes) {
                    return \array_values(\array_diff($blockTypes, $removeBlockTypes));
                },
                $this->blockTypes
            ),
            \array_values(\array_diff($this->unmarkedBlockTypes, $removeBlockTypes))
        );
    }

    /**
     * @param string $marker
     * @return list<class-string<Block>>
     */
    public function markedBy($marker)
    {
        if (isset($this->blockTypes[$marker])) {
            return $this->blockTypes[$marker];
        }

        return [];
    }

    /**
     * @return list<class-string<Block>>
     */
    public function unmarked()
    {
        return $this->unmarkedBlockTypes;
    }
}
