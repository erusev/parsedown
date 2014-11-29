<?php

/**
 * Test Parsedown against the CommonMark spec.
 *
 * Some code based on the original JavaScript test runner by jgm.
 *
 * @link http://commonmark.org/ CommonMark
 * @link http://git.io/8WtRvQ JavaScript test runner
 */
class CommonMarkTest extends PHPUnit_Framework_TestCase
{
    const SPEC_URL = 'https://raw.githubusercontent.com/jgm/stmd/master/spec.txt';

    /**
     * @dataProvider data
     * @param $markdown
     * @param $expectedHtml
     */
    function test_($markdown, $expectedHtml)
    {
        $parsedown = new Parsedown();

        $actualHtml = $parsedown->text($markdown);

        # trim for better compatibility of the HTML output
        $actualHtml = trim($actualHtml);
        $expectedHtml = trim($expectedHtml);

        $this->assertEquals($expectedHtml, $actualHtml);
    }

    function data()
    {
        $spec = file_get_contents(self::SPEC_URL);
        $spec = strstr($spec, '<!-- END TESTS -->', true);

        $tests = array();
        $testCount = 0;
        $currentSection = '';

        preg_replace_callback(
            '/^\.\n([\s\S]*?)^\.\n([\s\S]*?)^\.$|^#{1,6} *(.*)$/m',
            function($matches) use ( & $tests, & $currentSection, & $testCount) {
                if (isset($matches[3]) and $matches[3]) {
                    $currentSection = $matches[3];
                } else {
                    $testCount++;
                    $markdown = preg_replace('/→/', "\t", $matches[1]);
                    $tests []= array(
                        $markdown, # markdown
                        $matches[2], # html
                        $currentSection, # section
                        $testCount, # number
                    );
                }
            },
            $spec
        );

        return $tests;
    }
}
