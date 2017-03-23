<?php

/**
 * Test Parsedown against the CommonMark spec.
 *
 * Some code based on the original JavaScript test runner by jgm.
 *
 * @link http://commonmark.org/ CommonMark
 * @link http://git.io/8WtRvQ JavaScript test runner
 */
class CommonMarkTest extends \PHPUnit\Framework\TestCase
{
    const SPEC_URL = 'https://raw.githubusercontent.com/jgm/stmd/master/spec.txt';

    /**
     * @dataProvider data
     * @param $section
     * @param $markdown
     * @param $expectedHtml
     */
    function test_($section, $markdown, $expectedHtml)
    {
        $Parsedown = new Parsedown();
        $Parsedown->setUrlsLinked(false);

        $actualHtml = $Parsedown->text($markdown);
        $actualHtml = $this->normalizeMarkup($actualHtml);

        $this->assertEquals($expectedHtml, $actualHtml);
    }

    function data()
    {
        $spec = file_get_contents(self::SPEC_URL);
        $spec = strstr($spec, '<!-- END TESTS -->', true);

        $tests = array();
        $currentSection = '';

        preg_replace_callback(
            '/^\.\n([\s\S]*?)^\.\n([\s\S]*?)^\.$|^#{1,6} *(.*)$/m',
            function($matches) use ( & $tests, & $currentSection, & $testCount) {
                if (isset($matches[3]) and $matches[3]) {
                    $currentSection = $matches[3];
                } else {
                    $testCount++;
                    $markdown = $matches[1];
                    $markdown = preg_replace('/→/', "\t", $markdown);
                    $expectedHtml = $matches[2];
                    $expectedHtml = $this->normalizeMarkup($expectedHtml);
                    $tests []= array(
                        $currentSection, # section
                        $markdown, # markdown
                        $expectedHtml, # html
                    );
                }
            },
            $spec
        );

        return $tests;
    }

    private function normalizeMarkup($markup)
    {
        $markup = preg_replace("/\n+/", "\n", $markup);
        $markup = preg_replace('/^\s+/m', '', $markup);
        $markup = preg_replace('/^((?:<[\w]+>)+)\n/m', '$1', $markup);
        $markup = preg_replace('/\n((?:<\/[\w]+>)+)$/m', '$1', $markup);
        $markup = trim($markup);

        return $markup;
    }
}
