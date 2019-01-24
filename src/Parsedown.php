<?php

namespace Erusev\Parsedown;

use Erusev\Parsedown\AST\StateRenderable;
use Erusev\Parsedown\Components\Block;
use Erusev\Parsedown\Components\Blocks\BlockQuote;
use Erusev\Parsedown\Components\Blocks\Comment;
use Erusev\Parsedown\Components\Blocks\FencedCode;
use Erusev\Parsedown\Components\Blocks\Header;
use Erusev\Parsedown\Components\Blocks\IndentedCode;
use Erusev\Parsedown\Components\Blocks\Markup as BlockMarkup;
use Erusev\Parsedown\Components\Blocks\Paragraph;
use Erusev\Parsedown\Components\Blocks\Reference;
use Erusev\Parsedown\Components\Blocks\Rule;
use Erusev\Parsedown\Components\Blocks\SetextHeader;
use Erusev\Parsedown\Components\Blocks\Table;
use Erusev\Parsedown\Components\Blocks\TList;
use Erusev\Parsedown\Components\ContinuableBlock;
use Erusev\Parsedown\Components\Inline;
use Erusev\Parsedown\Components\Inlines\Code;
use Erusev\Parsedown\Components\Inlines\Email;
use Erusev\Parsedown\Components\Inlines\Emphasis;
use Erusev\Parsedown\Components\Inlines\EscapeSequence;
use Erusev\Parsedown\Components\Inlines\Image;
use Erusev\Parsedown\Components\Inlines\Link;
use Erusev\Parsedown\Components\Inlines\Markup as InlineMarkup;
use Erusev\Parsedown\Components\Inlines\PlainText;
use Erusev\Parsedown\Components\Inlines\SpecialCharacter;
use Erusev\Parsedown\Components\Inlines\Strikethrough;
use Erusev\Parsedown\Components\Inlines\Url;
use Erusev\Parsedown\Components\Inlines\UrlTag;
use Erusev\Parsedown\Components\StateUpdatingBlock;
use Erusev\Parsedown\Html\Renderable;
use Erusev\Parsedown\Html\Renderables\Invisible;
use Erusev\Parsedown\Html\Renderables\Text;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\Parsing\Excerpt;
use Erusev\Parsedown\Parsing\Line;
use Erusev\Parsedown\Parsing\Lines;

final class Parsedown
{
    # ~

    const version = '2.0.0-dev';

    # ~

    /** @var State */
    private $State;

    public function __construct(State $State = null)
    {
        $this->State = $State ?: new State;
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

    #
    # Lines
    #

    /** @var array<array-key, class-string<Block>[]> */
    protected $BlockTypes = [
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

    # ~

    /** @var class-string<Block>[] */
    protected $unmarkedBlockTypes = [
        IndentedCode::class,
    ];

    #
    # Blocks
    #

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

            # ~

            $marker = $Line->text()[0];

            # ~

            $blockTypes = $this->unmarkedBlockTypes;

            if (isset($this->BlockTypes[$marker])) {
                foreach ($this->BlockTypes[$marker] as $blockType) {
                    $blockTypes []= $blockType;
                }
            }

            #
            # ~

            foreach ($blockTypes as $blockType) {
                $Block = $blockType::build($Context, $CurrentBlock, $this->State);

                if (isset($Block)) {
                    if ($Block instanceof StateUpdatingBlock) {
                        $this->State = $this->State->mergingWith(
                            $Block->latestState()
                        );
                    }

                    if (isset($CurrentBlock) && ! $Block->acquiredPrevious()) {
                        $Blocks[] = $CurrentBlock;
                    }

                    $CurrentBlock = $Block;

                    continue 2;
                }
            }

            # ~

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

        # ~

        if (isset($CurrentBlock)) {
            $Blocks[] = $CurrentBlock;
        }

        # ~

        return $Blocks;
    }

    #
    # Inline Elements
    #

    /** @var array<array-key, class-string<Inline>[]> */
    protected $InlineTypes = [
        '!' => [Image::class],
        '&' => [SpecialCharacter::class],
        '*' => [Emphasis::class],
        ':' => [Url::class],
        '<' => [UrlTag::class, Email::class, InlineMarkup::class],
        '[' => [Link::class],
        '_' => [Emphasis::class],
        '`' => [Code::class],
        '~' => [Strikethrough::class],
        '\\' => [EscapeSequence::class],
    ];

    # ~

    /** @var string */
    protected $inlineMarkerList = '!*_&[:<`~\\';

    #
    # ~
    #

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

        for (
            $Excerpt = (new Excerpt($text, 0))->pushingOffsetTo($this->inlineMarkerList);
            $Excerpt->text() !== '';
            $Excerpt = $Excerpt->pushingOffsetTo($this->inlineMarkerList)
        ) {
            $text = $Excerpt->text();
            $marker = $text[0];

            foreach ($this->InlineTypes[$marker] as $inlineType) {
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

            if (! isset($startPosition)) {
                $startPosition = $Excerpt->offset();
            }

            # the marker does not belong to an inline

            $Inlines[] = Plaintext::build($Excerpt->choppingUpToOffset($startPosition + 1));

            $text = \substr($Excerpt->text(), $startPosition + 1);
            /** @psalm-suppress LoopInvalidation */
            $Excerpt = $Excerpt->choppingFromOffset($startPosition + 1);
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
