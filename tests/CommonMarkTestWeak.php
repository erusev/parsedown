<?php

namespace Erusev\Parsedown\Tests;

use Erusev\Parsedown\Html\Renderables\Element;

/**
 * Test Parsedown against the CommonMark spec, but less aggressive
 *
 * The resulting HTML markup is cleaned up before comparison, so examples
 * which would normally fail due to actually invisible differences (e.g.
 * superfluous whitespaces), don't fail. However, cleanup relies on block
 * element detection. The detection doesn't work correctly when a element's
 * `display` CSS property is manipulated. According to that this test is only
 * a interim solution on Parsedown's way to full CommonMark compatibility.
 *
 * @link http://commonmark.org/ CommonMark
 */
class CommonMarkTestWeak extends CommonMarkTestStrict
{
    /** @var string */
    protected $textLevelElementRegex;

    /**
     * @param string|null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        $textLevelElements = \array_keys(Element::$TEXT_LEVEL_ELEMENTS);

        \array_walk(
            $textLevelElements,
            /**
             * @param string &$element
             * @return void
             */
            function (&$element) {
                $element = \preg_quote($element, '/');
            }
        );
        $this->textLevelElementRegex = '\b(?:' . \implode('|', $textLevelElements) . ')\b';

        parent::__construct($name, $data, $dataName);
    }

    /**
     * @dataProvider data
     * @param int $_
     * @param string $__
     * @param string $markdown
     * @param string $expectedHtml
     * @return void
     * @throws \PHPUnit\Framework\AssertionFailedError
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testExample($_, $__, $markdown, $expectedHtml)
    {
        $expectedHtml = $this->cleanupHtml($expectedHtml);

        $actualHtml = $this->Parsedown->text($markdown);
        $actualHtml = $this->cleanupHtml($actualHtml);

        $this->assertEquals($expectedHtml, $actualHtml);
    }

    /**
     * @param string $markup
     * @return string
     */
    protected function cleanupHtml($markup)
    {
        // invisible whitespaces at the beginning and end of block elements
        // however, whitespaces at the beginning of <pre> elements do matter
        $markup = \preg_replace(
            [
                '/(<(?!(?:' . $this->textLevelElementRegex . '|\bpre\b))\w+\b[^>]*>(?:<' . $this->textLevelElementRegex . '[^>]*>)*)\s+/s',
                '/\s+((?:<\/' . $this->textLevelElementRegex . '>)*<\/(?!' . $this->textLevelElementRegex . ')\w+\b>)/s'
            ],
            '$1',
            $markup
        );

        return $markup;
    }
}
