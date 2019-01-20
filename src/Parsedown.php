<?php

namespace Erusev\Parsedown;

use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\Parsing\Line;
use Erusev\Parsedown\Parsing\Lines;

#
#
# Parsedown
# http://parsedown.org
#
# (c) Emanuil Rusev
# http://erusev.com
#
# For the full license information, view the LICENSE file that was distributed
# with this source code.
#
#

class Parsedown
{
    # ~

    const version = '2.0.0-dev';

    # ~

    public function text($text)
    {
        $Elements = $this->textElements($text);

        # convert to markup
        $markup = $this->elements($Elements);

        # trim line breaks
        $markup = \trim($markup, "\n");

        return $markup;
    }

    protected function textElements($text)
    {
        # iterate through lines to identify blocks
        return $this->linesElements(Lines::fromTextLines($text, 0));
    }

    #
    # Setters
    #

    public function setBreaksEnabled($breaksEnabled)
    {
        $this->breaksEnabled = $breaksEnabled;

        return $this;
    }

    protected $breaksEnabled;

    public function setMarkupEscaped($markupEscaped)
    {
        $this->markupEscaped = $markupEscaped;

        return $this;
    }

    protected $markupEscaped;

    public function setUrlsLinked($urlsLinked)
    {
        $this->urlsLinked = $urlsLinked;

        return $this;
    }

    protected $urlsLinked = true;

    public function setSafeMode($safeMode)
    {
        $this->safeMode = (bool) $safeMode;

        return $this;
    }

    protected $safeMode;

    public function setStrictMode($strictMode)
    {
        $this->strictMode = (bool) $strictMode;

        return $this;
    }

    protected $strictMode;

    protected $safeLinksWhitelist = [
        'http://',
        'https://',
        'ftp://',
        'ftps://',
        'mailto:',
        'tel:',
        'data:image/png;base64,',
        'data:image/gif;base64,',
        'data:image/jpeg;base64,',
        'irc:',
        'ircs:',
        'git:',
        'ssh:',
        'news:',
        'steam:',
    ];

    #
    # Lines
    #

    protected $BlockTypes = [
        '#' => ['Header'],
        '*' => ['Rule', 'List'],
        '+' => ['List'],
        '-' => ['SetextHeader', 'Table', 'Rule', 'List'],
        '0' => ['List'],
        '1' => ['List'],
        '2' => ['List'],
        '3' => ['List'],
        '4' => ['List'],
        '5' => ['List'],
        '6' => ['List'],
        '7' => ['List'],
        '8' => ['List'],
        '9' => ['List'],
        ':' => ['Table'],
        '<' => ['Comment', 'Markup'],
        '=' => ['SetextHeader'],
        '>' => ['Quote'],
        '[' => ['Reference'],
        '_' => ['Rule'],
        '`' => ['FencedCode'],
        '|' => ['Table'],
        '~' => ['FencedCode'],
    ];

    # ~

    protected $unmarkedBlockTypes = [
        'Code',
    ];

    #
    # Blocks
    #

    protected function lines(Lines $Lines)
    {
        return $this->elements($this->linesElements($Lines));
    }

    /** @param int $indentOffset */
    protected function linesElements(Lines $Lines, array $nonNestables = [], $indentOffset = 0)
    {
        $Elements = [];
        $CurrentBlock = null;

        foreach ($Lines->contexts() as $Context) {
            if (isset($CurrentBlock) && $Context->previousEmptyLines() > 0) {
                $CurrentBlock['interrupted'] = $Context->previousEmptyLines();
            }

            $Line = $Context->line();

            if (isset($CurrentBlock['continuable'])) {
                $methodName = 'block' . $CurrentBlock['type'] . 'Continue';
                $Block = $this->$methodName($Context, $CurrentBlock);

                if (isset($Block)) {
                    $CurrentBlock = $Block;

                    continue;
                } else {
                    if ($this->isBlockCompletable($CurrentBlock['type'])) {
                        $methodName = 'block' . $CurrentBlock['type'] . 'Complete';
                        $CurrentBlock = $this->$methodName($CurrentBlock);
                    }
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
                $Block = $this->{"block$blockType"}($Context, $CurrentBlock);

                if (isset($Block)) {
                    $Block['type'] = $blockType;

                    if (! isset($Block['identified'])) {
                        if (isset($CurrentBlock)) {
                            $Elements[] = $this->extractElement($CurrentBlock);
                        }

                        $Block['identified'] = true;
                    }

                    if ($this->isBlockContinuable($blockType)) {
                        $Block['continuable'] = true;
                    }

                    $CurrentBlock = $Block;

                    continue 2;
                }
            }

            # ~

            if (isset($CurrentBlock) and $CurrentBlock['type'] === 'Paragraph') {
                $Block = $this->paragraphContinue($Context, $CurrentBlock);
            }

            if (isset($Block)) {
                $CurrentBlock = $Block;
            } else {
                if (isset($CurrentBlock)) {
                    $Elements[] = $this->extractElement($CurrentBlock);
                }

                $CurrentBlock = $this->paragraph($Context);

                $CurrentBlock['identified'] = true;
            }
        }

        # ~

        if (isset($CurrentBlock['continuable']) and $this->isBlockCompletable($CurrentBlock['type'])) {
            $methodName = 'block' . $CurrentBlock['type'] . 'Complete';
            $CurrentBlock = $this->$methodName($CurrentBlock);
        }

        # ~

        if (isset($CurrentBlock)) {
            $Elements[] = $this->extractElement($CurrentBlock);
        }

        # ~

        return $Elements;
    }

    protected function extractElement(array $Component)
    {
        if (! isset($Component['element'])) {
            if (isset($Component['markup'])) {
                $Component['element'] = ['rawHtml' => $Component['markup']];
            } elseif (isset($Component['hidden'])) {
                $Component['element'] = [];
            }
        }

        return $Component['element'];
    }

    protected function isBlockContinuable($Type)
    {
        return \method_exists($this, 'block' . $Type . 'Continue');
    }

    protected function isBlockCompletable($Type)
    {
        return \method_exists($this, 'block' . $Type . 'Complete');
    }

    #
    # Code

    protected function blockCode(Context $Context, $Block = null)
    {
        if (isset($Block) and $Block['type'] === 'Paragraph' and ! $Context->previousEmptyLines() > 0) {
            return;
        }

        if ($Context->line()->indent() >= 4) {
            $Block = [
                'element' => [
                    'name' => 'pre',
                    'element' => [
                        'name' => 'code',
                        'text' => $Context->line()->ltrimBodyUpto(4),
                    ],
                ],
            ];

            return $Block;
        }
    }

    protected function blockCodeContinue(Context $Context, $Block)
    {
        if ($Context->line()->indent() >= 4) {
            if ($Context->previousEmptyLines() > 0) {
                $Block['element']['element']['text'] .= \str_repeat("\n", $Context->previousEmptyLines());

                unset($Block['interrupted']);
            }

            $Block['element']['element']['text'] .= "\n";

            $Block['element']['element']['text'] .= $Context->line()->ltrimBodyUpto(4);

            return $Block;
        }
    }

    protected function blockCodeComplete($Block)
    {
        return $Block;
    }

    #
    # Rule

    protected function blockRule(Context $Context)
    {
        $marker = $Context->line()->text()[0];

        if (\substr_count($Context->line()->text(), $marker) >= 3 and \chop($Context->line()->text(), " $marker") === '') {
            $Block = [
                'element' => [
                    'name' => 'hr',
                ],
            ];

            return $Block;
        }
    }

    #
    # Setext

    protected function blockSetextHeader(Context $Context, array $Block = null)
    {
        if (! isset($Block) or $Block['type'] !== 'Paragraph' or $Context->previousEmptyLines() > 0) {
            return;
        }

        if ($Context->line()->indent() < 4 and \chop(\chop($Context->line()->text(), " \t"), $Context->line()->text()[0]) === '') {
            $Block['element']['name'] = $Context->line()->text()[0] === '=' ? 'h1' : 'h2';

            return $Block;
        }
    }

    #
    # Markup

    protected function blockMarkup(Context $Context)
    {
        if ($this->markupEscaped or $this->safeMode) {
            return;
        }

        if (\preg_match('/^<[\/]?+(\w*)(?:[ ]*+'.$this->regexHtmlAttribute.')*+[ ]*+(\/)?>/', $Context->line()->text(), $matches)) {
            $element = \strtolower($matches[1]);

            if (\in_array($element, $this->textLevelElements, true)) {
                return;
            }

            $Block = [
                'name' => $matches[1],
                'element' => [
                    'rawHtml' => $Context->line()->text(),
                    'autobreak' => true,
                ],
            ];

            return $Block;
        }
    }

    protected function blockMarkupContinue(Context $Context, array $Block)
    {
        if (isset($Block['closed']) or $Context->previousEmptyLines() > 0) {
            return;
        }

        $Block['element']['rawHtml'] .= "\n" . $Context->line()->rawLine();

        return $Block;
    }

    #
    # Reference

    protected function blockReference(Context $Context)
    {
        if (\strpos($Context->line()->text(), ']') !== false
            and \preg_match('/^\[(.+?)\]:[ ]*+<?(\S+?)>?(?:[ ]+["\'(](.+)["\')])?[ ]*+$/', $Context->line()->text(), $matches)
        ) {
            $id = \strtolower($matches[1]);

            $Data = [
                'url' => $matches[2],
                'title' => isset($matches[3]) ? $matches[3] : null,
            ];

            $this->DefinitionData['Reference'][$id] = $Data;

            $Block = [
                'element' => [],
            ];

            return $Block;
        }
    }

    #
    # Table

    protected function blockTable(Context $Context, array $Block = null)
    {
        if (! isset($Block) or $Block['type'] !== 'Paragraph' or $Context->previousEmptyLines() > 0) {
            return;
        }

        if (
            \strpos($Block['element']['handler']['argument'], '|') === false
            and \strpos($Context->line()->text(), '|') === false
            and \strpos($Context->line()->text(), ':') === false
            or \strpos($Block['element']['handler']['argument'], "\n") !== false
        ) {
            return;
        }

        if (\chop($Context->line()->text(), ' -:|') !== '') {
            return;
        }

        $alignments = [];

        $divider = $Context->line()->text();

        $divider = \trim($divider);
        $divider = \trim($divider, '|');

        $dividerCells = \explode('|', $divider);

        foreach ($dividerCells as $dividerCell) {
            $dividerCell = \trim($dividerCell);

            if ($dividerCell === '') {
                return;
            }

            $alignment = null;

            if ($dividerCell[0] === ':') {
                $alignment = 'left';
            }

            if (\substr($dividerCell, - 1) === ':') {
                $alignment = $alignment === 'left' ? 'center' : 'right';
            }

            $alignments []= $alignment;
        }

        # ~

        $HeaderElements = [];

        $header = $Block['element']['handler']['argument'];

        $header = \trim($header);
        $header = \trim($header, '|');

        $headerCells = \explode('|', $header);

        if (\count($headerCells) !== \count($alignments)) {
            return;
        }

        foreach ($headerCells as $index => $headerCell) {
            $headerCell = \trim($headerCell);

            $HeaderElement = [
                'name' => 'th',
                'handler' => [
                    'function' => 'lineElements',
                    'argument' => $headerCell,
                    'destination' => 'elements',
                ]
            ];

            if (isset($alignments[$index])) {
                $alignment = $alignments[$index];

                $HeaderElement['attributes'] = [
                    'style' => "text-align: $alignment;",
                ];
            }

            $HeaderElements []= $HeaderElement;
        }

        # ~

        $Block = [
            'alignments' => $alignments,
            'identified' => true,
            'element' => [
                'name' => 'table',
                'elements' => [],
            ],
        ];

        $Block['element']['elements'] []= [
            'name' => 'thead',
        ];

        $Block['element']['elements'] []= [
            'name' => 'tbody',
            'elements' => [],
        ];

        $Block['element']['elements'][0]['elements'] []= [
            'name' => 'tr',
            'elements' => $HeaderElements,
        ];

        return $Block;
    }

    protected function blockTableContinue(Context $Context, array $Block)
    {
        if ($Context->previousEmptyLines() > 0) {
            return;
        }

        if (\count($Block['alignments']) === 1 or $Context->line()->text()[0] === '|' or \strpos($Context->line()->text(), '|')) {
            $Elements = [];

            $row = $Context->line()->text();

            $row = \trim($row);
            $row = \trim($row, '|');

            \preg_match_all('/(?:(\\\\[|])|[^|`]|`[^`]++`|`)++/', $row, $matches);

            $cells = \array_slice($matches[0], 0, \count($Block['alignments']));

            foreach ($cells as $index => $cell) {
                $cell = \trim($cell);

                $Element = [
                    'name' => 'td',
                    'handler' => [
                        'function' => 'lineElements',
                        'argument' => $cell,
                        'destination' => 'elements',
                    ]
                ];

                if (isset($Block['alignments'][$index])) {
                    $Element['attributes'] = [
                        'style' => 'text-align: ' . $Block['alignments'][$index] . ';',
                    ];
                }

                $Elements []= $Element;
            }

            $Element = [
                'name' => 'tr',
                'elements' => $Elements,
            ];

            $Block['element']['elements'][1]['elements'] []= $Element;

            return $Block;
        }
    }

    #
    # ~
    #

    protected function paragraph(Context $Context)
    {
        return [
            'type' => 'Paragraph',
            'element' => [
                'name' => 'p',
                'handler' => [
                    'function' => 'lineElements',
                    'argument' => $Context->line()->text(),
                    'destination' => 'elements',
                ],
            ],
        ];
    }

    protected function paragraphContinue(Context $Context, array $Block)
    {
        if (isset($Block['interrupted'])) {
            return;
        }

        $Block['element']['handler']['argument'] .= "\n".$Context->line()->text();

        return $Block;
    }

    #
    # Inline Elements
    #

    protected $InlineTypes = [
        '!' => ['Image'],
        '&' => ['SpecialCharacter'],
        '*' => ['Emphasis'],
        ':' => ['Url'],
        '<' => ['UrlTag', 'EmailTag', 'Markup'],
        '[' => ['Link'],
        '_' => ['Emphasis'],
        '`' => ['Code'],
        '~' => ['Strikethrough'],
        '\\' => ['EscapeSequence'],
    ];

    # ~

    protected $inlineMarkerList = '!*_&[:<`~\\';

    #
    # ~
    #

    public function line($text, $nonNestables = [])
    {
        return $this->elements($this->lineElements($text, $nonNestables));
    }

    protected function lineElements($text, $nonNestables = [])
    {
        # standardize line breaks
        $text = \str_replace(["\r\n", "\r"], "\n", $text);

        $Elements = [];

        $nonNestables = (
            empty($nonNestables)
            ? []
            : \array_combine($nonNestables, $nonNestables)
        );

        # $excerpt is based on the first occurrence of a marker

        while ($excerpt = \strpbrk($text, $this->inlineMarkerList)) {
            $marker = $excerpt[0];

            $markerPosition = \strlen($text) - \strlen($excerpt);

            $Excerpt = ['text' => $excerpt, 'context' => $text];

            foreach ($this->InlineTypes[$marker] as $inlineType) {
                # check to see if the current inline type is nestable in the current context

                if (isset($nonNestables[$inlineType])) {
                    continue;
                }

                $Inline = $this->{"inline$inlineType"}($Excerpt);

                if (! isset($Inline)) {
                    continue;
                }

                # makes sure that the inline belongs to "our" marker

                if (isset($Inline['position']) and $Inline['position'] > $markerPosition) {
                    continue;
                }

                # sets a default inline position

                if (! isset($Inline['position'])) {
                    $Inline['position'] = $markerPosition;
                }

                # cause the new element to 'inherit' our non nestables


                $Inline['element']['nonNestables'] = isset($Inline['element']['nonNestables'])
                    ? \array_merge($Inline['element']['nonNestables'], $nonNestables)
                    : $nonNestables
                ;

                # the text that comes before the inline
                $unmarkedText = \substr($text, 0, $Inline['position']);

                # compile the unmarked text
                $InlineText = $this->inlineText($unmarkedText);
                $Elements[] = $InlineText['element'];

                # compile the inline
                $Elements[] = $this->extractElement($Inline);

                # remove the examined text
                $text = \substr($text, $Inline['position'] + $Inline['extent']);

                continue 2;
            }

            # the marker does not belong to an inline

            $unmarkedText = \substr($text, 0, $markerPosition + 1);

            $InlineText = $this->inlineText($unmarkedText);
            $Elements[] = $InlineText['element'];

            $text = \substr($text, $markerPosition + 1);
        }

        $InlineText = $this->inlineText($text);
        $Elements[] = $InlineText['element'];

        foreach ($Elements as &$Element) {
            if (! isset($Element['autobreak'])) {
                $Element['autobreak'] = false;
            }
        }

        return $Elements;
    }

    #
    # ~
    #

    protected function inlineText($text)
    {
        $Inline = [
            'extent' => \strlen($text),
            'element' => [],
        ];

        $Inline['element']['elements'] = self::pregReplaceElements(
            $this->breaksEnabled ? '/[ ]*+\n/' : '/(?:[ ]*+\\\\|[ ]{2,}+)\n/',
            [
                ['name' => 'br'],
                ['text' => "\n"],
            ],
            $text
        );

        return $Inline;
    }

    protected function inlineCode($Excerpt)
    {
        $marker = $Excerpt['text'][0];

        if (\preg_match('/^(['.$marker.']++)[ ]*+(.+?)[ ]*+(?<!['.$marker.'])\1(?!'.$marker.')/s', $Excerpt['text'], $matches)) {
            $text = $matches[2];
            $text = \preg_replace('/[ ]*+\n/', ' ', $text);

            return [
                'extent' => \strlen($matches[0]),
                'element' => [
                    'name' => 'code',
                    'text' => $text,
                ],
            ];
        }
    }

    protected function inlineEmailTag($Excerpt)
    {
        $hostnameLabel = '[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?';

        $commonMarkEmail = '[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]++@'
            . $hostnameLabel . '(?:\.' . $hostnameLabel . ')*';

        if (\strpos($Excerpt['text'], '>') !== false
            and \preg_match("/^<((mailto:)?$commonMarkEmail)>/i", $Excerpt['text'], $matches)
        ) {
            $url = $matches[1];

            if (! isset($matches[2])) {
                $url = "mailto:$url";
            }

            return [
                'extent' => \strlen($matches[0]),
                'element' => [
                    'name' => 'a',
                    'text' => $matches[1],
                    'attributes' => [
                        'href' => $url,
                    ],
                ],
            ];
        }
    }

    protected function inlineEmphasis($Excerpt)
    {
        if (! isset($Excerpt['text'][1])) {
            return;
        }

        $marker = $Excerpt['text'][0];

        if ($Excerpt['text'][1] === $marker and \preg_match($this->StrongRegex[$marker], $Excerpt['text'], $matches)) {
            $emphasis = 'strong';
        } elseif (\preg_match($this->EmRegex[$marker], $Excerpt['text'], $matches)) {
            $emphasis = 'em';
        } else {
            return;
        }

        return [
            'extent' => \strlen($matches[0]),
            'element' => [
                'name' => $emphasis,
                'handler' => [
                    'function' => 'lineElements',
                    'argument' => $matches[1],
                    'destination' => 'elements',
                ]
            ],
        ];
    }

    protected function inlineEscapeSequence($Excerpt)
    {
        if (isset($Excerpt['text'][1]) and \in_array($Excerpt['text'][1], $this->specialCharacters, true)) {
            return [
                'element' => ['rawHtml' => $Excerpt['text'][1]],
                'extent' => 2,
            ];
        }
    }

    protected function inlineImage($Excerpt)
    {
        if (! isset($Excerpt['text'][1]) or $Excerpt['text'][1] !== '[') {
            return;
        }

        $Excerpt['text']= \substr($Excerpt['text'], 1);

        $Link = $this->inlineLink($Excerpt);

        if ($Link === null) {
            return;
        }

        $Inline = [
            'extent' => $Link['extent'] + 1,
            'element' => [
                'name' => 'img',
                'attributes' => [
                    'src' => $Link['element']['attributes']['href'],
                    'alt' => $Link['element']['handler']['argument'],
                ],
                'autobreak' => true,
            ],
        ];

        $Inline['element']['attributes'] += $Link['element']['attributes'];

        unset($Inline['element']['attributes']['href']);

        return $Inline;
    }

    protected function inlineLink($Excerpt)
    {
        $Element = [
            'name' => 'a',
            'handler' => [
                'function' => 'lineElements',
                'argument' => null,
                'destination' => 'elements',
            ],
            'nonNestables' => ['Url', 'Link'],
            'attributes' => [
                'href' => null,
                'title' => null,
            ],
        ];

        $extent = 0;

        $remainder = $Excerpt['text'];

        if (\preg_match('/\[((?:[^][]++|(?R))*+)\]/', $remainder, $matches)) {
            $Element['handler']['argument'] = $matches[1];

            $extent += \strlen($matches[0]);

            $remainder = \substr($remainder, $extent);
        } else {
            return;
        }

        if (\preg_match('/^[(]\s*+((?:[^ ()]++|[(][^ )]+[)])++)(?:[ ]+("[^"]*+"|\'[^\']*+\'))?\s*+[)]/', $remainder, $matches)) {
            $Element['attributes']['href'] = $matches[1];

            if (isset($matches[2])) {
                $Element['attributes']['title'] = \substr($matches[2], 1, - 1);
            }

            $extent += \strlen($matches[0]);
        } else {
            if (\preg_match('/^\s*\[(.*?)\]/', $remainder, $matches)) {
                $definition = \strlen($matches[1]) ? $matches[1] : $Element['handler']['argument'];
                $definition = \strtolower($definition);

                $extent += \strlen($matches[0]);
            } else {
                $definition = \strtolower($Element['handler']['argument']);
            }

            if (! isset($this->DefinitionData['Reference'][$definition])) {
                return;
            }

            $Definition = $this->DefinitionData['Reference'][$definition];

            $Element['attributes']['href'] = $Definition['url'];
            $Element['attributes']['title'] = $Definition['title'];
        }

        return [
            'extent' => $extent,
            'element' => $Element,
        ];
    }

    protected function inlineMarkup($Excerpt)
    {
        if ($this->markupEscaped or $this->safeMode or \strpos($Excerpt['text'], '>') === false) {
            return;
        }

        if ($Excerpt['text'][1] === '/' and \preg_match('/^<\/\w[\w-]*+[ ]*+>/s', $Excerpt['text'], $matches)) {
            return [
                'element' => ['rawHtml' => $matches[0]],
                'extent' => \strlen($matches[0]),
            ];
        }

        if ($Excerpt['text'][1] === '!' and \preg_match('/^<!---?[^>-](?:-?+[^-])*-->/s', $Excerpt['text'], $matches)) {
            return [
                'element' => ['rawHtml' => $matches[0]],
                'extent' => \strlen($matches[0]),
            ];
        }

        if ($Excerpt['text'][1] !== ' ' and \preg_match('/^<\w[\w-]*+(?:[ ]*+'.$this->regexHtmlAttribute.')*+[ ]*+\/?>/s', $Excerpt['text'], $matches)) {
            return [
                'element' => ['rawHtml' => $matches[0]],
                'extent' => \strlen($matches[0]),
            ];
        }
    }

    protected function inlineSpecialCharacter($Excerpt)
    {
        if (\substr($Excerpt['text'], 1, 1) !== ' ' and \strpos($Excerpt['text'], ';') !== false
            and \preg_match('/^&(#?+[0-9a-zA-Z]++);/', $Excerpt['text'], $matches)
        ) {
            return [
                'element' => ['rawHtml' => '&' . $matches[1] . ';'],
                'extent' => \strlen($matches[0]),
            ];
        }

        return;
    }

    protected function inlineStrikethrough($Excerpt)
    {
        if (! isset($Excerpt['text'][1])) {
            return;
        }

        if ($Excerpt['text'][1] === '~' and \preg_match('/^~~(?=\S)(.+?)(?<=\S)~~/', $Excerpt['text'], $matches)) {
            return [
                'extent' => \strlen($matches[0]),
                'element' => [
                    'name' => 'del',
                    'handler' => [
                        'function' => 'lineElements',
                        'argument' => $matches[1],
                        'destination' => 'elements',
                    ]
                ],
            ];
        }
    }

    protected function inlineUrl($Excerpt)
    {
        if ($this->urlsLinked !== true or ! isset($Excerpt['text'][2]) or $Excerpt['text'][2] !== '/') {
            return;
        }

        if (\strpos($Excerpt['context'], 'http') !== false
            and \preg_match('/\bhttps?+:[\/]{2}[^\s<]+\b\/*+/ui', $Excerpt['context'], $matches, \PREG_OFFSET_CAPTURE)
        ) {
            $url = $matches[0][0];

            $Inline = [
                'extent' => \strlen($matches[0][0]),
                'position' => $matches[0][1],
                'element' => [
                    'name' => 'a',
                    'text' => $url,
                    'attributes' => [
                        'href' => $url,
                    ],
                ],
            ];

            return $Inline;
        }
    }

    protected function inlineUrlTag($Excerpt)
    {
        if (\strpos($Excerpt['text'], '>') !== false and \preg_match('/^<(\w++:\/{2}[^ >]++)>/i', $Excerpt['text'], $matches)) {
            $url = $matches[1];

            return [
                'extent' => \strlen($matches[0]),
                'element' => [
                    'name' => 'a',
                    'text' => $url,
                    'attributes' => [
                        'href' => $url,
                    ],
                ],
            ];
        }
    }

    # ~

    protected function unmarkedText($text)
    {
        $Inline = $this->inlineText($text);
        return $this->element($Inline['element']);
    }

    #
    # Handlers
    #

    protected function handle(array $Element)
    {
        if (isset($Element['handler'])) {
            if (!isset($Element['nonNestables'])) {
                $Element['nonNestables'] = [];
            }

            if (\is_string($Element['handler'])) {
                $function = $Element['handler'];
                $argument = $Element['text'];
                unset($Element['text']);
                $destination = 'rawHtml';
            } else {
                $function = $Element['handler']['function'];
                $argument = $Element['handler']['argument'];
                $destination = $Element['handler']['destination'];
            }

            $Element[$destination] = $this->{$function}($argument, $Element['nonNestables']);

            if ($destination === 'handler') {
                $Element = $this->handle($Element);
            }

            unset($Element['handler']);
        }

        return $Element;
    }

    protected function handleElementRecursive(array $Element)
    {
        return $this->elementApplyRecursive([$this, 'handle'], $Element);
    }

    protected function handleElementsRecursive(array $Elements)
    {
        return $this->elementsApplyRecursive([$this, 'handle'], $Elements);
    }

    protected function elementApplyRecursive($closure, array $Element)
    {
        $Element = \call_user_func($closure, $Element);

        if (isset($Element['elements'])) {
            $Element['elements'] = $this->elementsApplyRecursive($closure, $Element['elements']);
        } elseif (isset($Element['element'])) {
            $Element['element'] = $this->elementApplyRecursive($closure, $Element['element']);
        }

        return $Element;
    }

    protected function elementApplyRecursiveDepthFirst($closure, array $Element)
    {
        if (isset($Element['elements'])) {
            $Element['elements'] = $this->elementsApplyRecursiveDepthFirst($closure, $Element['elements']);
        } elseif (isset($Element['element'])) {
            $Element['element'] = $this->elementsApplyRecursiveDepthFirst($closure, $Element['element']);
        }

        $Element = \call_user_func($closure, $Element);

        return $Element;
    }

    protected function elementsApplyRecursive($closure, array $Elements)
    {
        foreach ($Elements as &$Element) {
            $Element = $this->elementApplyRecursive($closure, $Element);
        }

        return $Elements;
    }

    protected function elementsApplyRecursiveDepthFirst($closure, array $Elements)
    {
        foreach ($Elements as &$Element) {
            $Element = $this->elementApplyRecursiveDepthFirst($closure, $Element);
        }

        return $Elements;
    }

    protected function element(array $Element)
    {
        if ($this->safeMode) {
            $Element = $this->sanitiseElement($Element);
        }

        # identity map if element has no handler
        $Element = $this->handle($Element);

        $hasName = isset($Element['name']);

        $markup = '';

        if ($hasName) {
            $markup .= '<' . $Element['name'];

            if (isset($Element['attributes'])) {
                foreach ($Element['attributes'] as $name => $value) {
                    if ($value === null) {
                        continue;
                    }

                    $markup .= " $name=\"".self::escape($value).'"';
                }
            }
        }

        $permitRawHtml = false;

        if (isset($Element['text'])) {
            $text = $Element['text'];
        }
        // very strongly consider an alternative if you're writing an
        // extension
        elseif (isset($Element['rawHtml'])) {
            $text = $Element['rawHtml'];

            $allowRawHtmlInSafeMode = isset($Element['allowRawHtmlInSafeMode']) && $Element['allowRawHtmlInSafeMode'];
            $permitRawHtml = !$this->safeMode || $allowRawHtmlInSafeMode;
        }

        $hasContent = isset($text) || isset($Element['element']) || isset($Element['elements']);

        if ($hasContent) {
            $markup .= $hasName ? '>' : '';

            if (isset($Element['elements'])) {
                $markup .= $this->elements($Element['elements']);
            } elseif (isset($Element['element'])) {
                $markup .= $this->element($Element['element']);
            } else {
                if (!$permitRawHtml) {
                    $markup .= self::escape($text, true);
                } else {
                    $markup .= $text;
                }
            }

            $markup .= $hasName ? '</' . $Element['name'] . '>' : '';
        } elseif ($hasName) {
            $markup .= ' />';
        }

        return $markup;
    }

    protected function elements(array $Elements)
    {
        $markup = '';

        $autoBreak = true;

        foreach ($Elements as $Element) {
            if (empty($Element)) {
                continue;
            }

            $autoBreakNext = (
                isset($Element['autobreak'])
                ? $Element['autobreak'] : isset($Element['name'])
            );
            // (autobreak === false) covers both sides of an element
            $autoBreak = !$autoBreak ? $autoBreak : $autoBreakNext;

            $markup .= ($autoBreak ? "\n" : '') . $this->element($Element);
            $autoBreak = $autoBreakNext;
        }

        $markup .= $autoBreak ? "\n" : '';

        return $markup;
    }

    # ~

    protected function li(Lines $Lines)
    {
        $Elements = $this->linesElements($Lines);

        if (! $Lines->containsBlankLines()
            and isset($Elements[0]) and isset($Elements[0]['name'])
            and $Elements[0]['name'] === 'p'
        ) {
            unset($Elements[0]['name']);
        }

        return $Elements;
    }

    #
    # AST Convenience
    #

    /**
     * Replace occurrences $regexp with $Elements in $text. Return an array of
     * elements representing the replacement.
     */
    protected static function pregReplaceElements($regexp, $Elements, $text)
    {
        $newElements = [];

        while (\preg_match($regexp, $text, $matches, \PREG_OFFSET_CAPTURE)) {
            $offset = $matches[0][1];
            $before = \substr($text, 0, $offset);
            $after = \substr($text, $offset + \strlen($matches[0][0]));

            $newElements[] = ['text' => $before];

            foreach ($Elements as $Element) {
                $newElements[] = $Element;
            }

            $text = $after;
        }

        $newElements[] = ['text' => $text];

        return $newElements;
    }

    #
    # Deprecated Methods
    #

    public function parse($text)
    {
        $markup = $this->text($text);

        return $markup;
    }

    protected function sanitiseElement(array $Element)
    {
        static $goodAttribute = '/^[a-zA-Z0-9][a-zA-Z0-9-_]*+$/';
        static $safeUrlNameToAtt  = [
            'a'   => 'href',
            'img' => 'src',
        ];

        if (! isset($Element['name'])) {
            unset($Element['attributes']);
            return $Element;
        }

        if (isset($safeUrlNameToAtt[$Element['name']])) {
            $Element = $this->filterUnsafeUrlInAttribute($Element, $safeUrlNameToAtt[$Element['name']]);
        }

        if (! empty($Element['attributes'])) {
            foreach ($Element['attributes'] as $att => $val) {
                # filter out badly parsed attribute
                if (! \preg_match($goodAttribute, $att)) {
                    unset($Element['attributes'][$att]);
                }
                # dump onevent attribute
                elseif (self::striAtStart($att, 'on')) {
                    unset($Element['attributes'][$att]);
                }
            }
        }

        return $Element;
    }

    protected function filterUnsafeUrlInAttribute(array $Element, $attribute)
    {
        foreach ($this->safeLinksWhitelist as $scheme) {
            if (self::striAtStart($Element['attributes'][$attribute], $scheme)) {
                return $Element;
            }
        }

        $Element['attributes'][$attribute] = \str_replace(':', '%3A', $Element['attributes'][$attribute]);

        return $Element;
    }

    #
    # Static Methods
    #

    protected static function escape($text, $allowQuotes = false)
    {
        return \htmlspecialchars($text, $allowQuotes ? \ENT_NOQUOTES : \ENT_QUOTES, 'UTF-8');
    }

    protected static function striAtStart($string, $needle)
    {
        $len = \strlen($needle);

        if ($len > \strlen($string)) {
            return false;
        } else {
            return \strtolower(\substr($string, 0, $len)) === \strtolower($needle);
        }
    }

    public static function instance($name = 'default')
    {
        if (isset(self::$instances[$name])) {
            return self::$instances[$name];
        }

        $instance = new static();

        self::$instances[$name] = $instance;

        return $instance;
    }

    private static $instances = [];

    #
    # Read-Only

    protected $specialCharacters = [
        '\\', '`', '*', '_', '{', '}', '[', ']', '(', ')', '>', '#', '+', '-', '.', '!', '|', '~'
    ];

    protected $StrongRegex = [
        '*' => '/^[*]{2}((?:\\\\\*|[^*]|[*][^*]*+[*])+?)[*]{2}(?![*])/s',
        '_' => '/^__((?:\\\\_|[^_]|_[^_]*+_)+?)__(?!_)/us',
    ];

    protected $EmRegex = [
        '*' => '/^[*]((?:\\\\\*|[^*]|[*][*][^*]+?[*][*])+?)[*](?![*])/s',
        '_' => '/^_((?:\\\\_|[^_]|__[^_]*__)+?)_(?!_)\b/us',
    ];

    protected $regexHtmlAttribute = '[a-zA-Z_:][\w:.-]*+(?:\s*+=\s*+(?:[^"\'=<>`\s]+|"[^"]*+"|\'[^\']*+\'))?+';

    protected $voidElements = [
        'area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source',
    ];

    protected $textLevelElements = [
        'a', 'br', 'bdo', 'abbr', 'blink', 'nextid', 'acronym', 'basefont',
        'b', 'em', 'big', 'cite', 'small', 'spacer', 'listing',
        'i', 'rp', 'del', 'code',          'strike', 'marquee',
        'q', 'rt', 'ins', 'font',          'strong',
        's', 'tt', 'kbd', 'mark',
        'u', 'xm', 'sub', 'nobr',
                   'sup', 'ruby',
                   'var', 'span',
                   'wbr', 'time',
    ];
}
