<?php
require_once(__DIR__ . '/CommonMarkTestStrict.php');

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
    protected $textLevelElementRegex;

    protected function setUp()
    {
        parent::setUp();

        $textLevelElements = $this->parsedown->getTextLevelElements();
        array_walk($textLevelElements, function (&$element) {
            $element = preg_quote($element, '/');
        });
        $this->textLevelElementRegex = '\b(?:' . implode('|', $textLevelElements) . ')\b';
    }

    /**
     * @dataProvider data
     * @param $id
     * @param $section
     * @param $markdown
     * @param $expectedHtml
     */
    public function testExample($id, $section, $markdown, $expectedHtml)
    {
        $expectedHtml = $this->cleanupHtml($expectedHtml);

        $actualHtml = $this->parsedown->text($markdown);
        $actualHtml = $this->cleanupHtml($actualHtml);

        $this->assertEquals($expectedHtml, $actualHtml);
    }

    protected function cleanupHtml($markup)
    {
        // invisible whitespaces at the beginning and end of block elements
        // however, whitespaces at the beginning of <pre> elements do matter
        $markup = preg_replace(
            array(
                '/(<(?!(?:' . $this->textLevelElementRegex . '|\bpre\b))\w+\b[^>]*>(?:<' . $this->textLevelElementRegex . '[^>]*>)*)\s+/s',
                '/\s+((?:<\/' . $this->textLevelElementRegex . '>)*<\/(?!' . $this->textLevelElementRegex . ')\w+\b>)/s'
            ),
            '$1',
            $markup
        );

        return $markup;
    }
}
