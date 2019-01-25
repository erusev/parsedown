<?php

namespace Erusev\Parsedown\Configurables;

use Erusev\Parsedown\Components\Block;
use Erusev\Parsedown\Components\Blocks\BlockQuote;
use Erusev\Parsedown\Components\Blocks\Comment;
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

/**
 * @psalm-type _Data=array{url: string, title: string|null}
 */
final class BlockTypes implements Configurable
{
    /** @var array<array-key, class-string<Block>[]> */
    private static $defaultBlockTypes = [
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
        '<' => [Comment::class, BlockMarkup::class],
        '=' => [SetextHeader::class],
        '>' => [BlockQuote::class],
        '[' => [Reference::class],
        '_' => [Rule::class],
        '`' => [FencedCode::class],
        '|' => [Table::class],
        '~' => [FencedCode::class],
    ];

    /** @var class-string<Block>[] */
    private static $defaultUnmarkedBlockTypes = [
        IndentedCode::class,
    ];

    /** @var array<array-key, class-string<Block>[]> */
    private $blockTypes;

    /** @var class-string<Block>[] */
    private $unmarkedBlockTypes;

    /**
     * @param array<array-key, class-string<Block>[]> $blockTypes
     * @param class-string<Block>[] $unmarkedBlockTypes
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
            self::$defaultBlockTypes,
            self::$defaultUnmarkedBlockTypes
        );
    }

    /**
     * @param string $marker
     * @param class-string<Block>[] $newBlockTypes
     * @return self
     */
    public function settingMarked($marker, array $newBlockTypes)
    {
        $blockTypes = $this->blockTypes;
        $blockTypes[$marker] = $newBlockTypes;

        return new self($blockTypes, $this->unmarkedBlockTypes);
    }

    /**
     * @param class-string<Block>[] $newUnmarkedBlockTypes
     * @return self
     */
    public function settingUnmarked(array $newUnmarkedBlockTypes)
    {
        return new self($this->blockTypes, $newUnmarkedBlockTypes);
    }

    /**
     * @param string $marker
     * @return class-string<Block>[]
     */
    public function get($marker)
    {
        if (isset($this->blockTypes[$marker])) {
            return $this->blockTypes[$marker];
        }

        return [];
    }

    /**
     * @return class-string<Block>[]
     */
    public function unmarked()
    {
        return $this->unmarkedBlockTypes;
    }
}
