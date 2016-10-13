<?php

/**
 * Test Parsedown against the CommonMark spec
 *
 * @link http://commonmark.org/ CommonMark
 */
class CommonMarkTestStrict extends PHPUnit_Framework_TestCase
{
    const SPEC_URL = 'https://raw.githubusercontent.com/jgm/CommonMark/master/spec.txt';

    protected $parsedown;

    protected function setUp()
    {
        $this->parsedown = new TestParsedown();
        $this->parsedown->setUrlsLinked(false);
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
        $actualHtml = $this->parsedown->text($markdown);
        $this->assertEquals($expectedHtml, $actualHtml);
    }

    /**
     * @return array
     */
    public function data()
    {
        $spec = file_get_contents(self::SPEC_URL);
        if ($spec === false) {
            $this->fail('Unable to load CommonMark spec from ' . self::SPEC_URL);
        }

        $spec = str_replace("\r\n", "\n", $spec);
        $spec = strstr($spec, '<!-- END TESTS -->', true);

        $matches = array();
        preg_match_all('/^`{32} example\n((?s).*?)\n\.\n(?:|((?s).*?)\n)`{32}$|^#{1,6} *(.*?)$/m', $spec, $matches, PREG_SET_ORDER);

        $data = array();
        $currentId = 0;
        $currentSection = '';
        foreach ($matches as $match) {
            if (isset($match[3])) {
                $currentSection = $match[3];
            } else {
                $currentId++;
                $markdown = str_replace('→', "\t", $match[1]);
                $expectedHtml = isset($match[2]) ? str_replace('→', "\t", $match[2]) : '';

                $data[$currentId] = array(
                    'id' => $currentId,
                    'section' => $currentSection,
                    'markdown' => $markdown,
                    'expectedHtml' => $expectedHtml
                );
            }
        }

        return $data;
    }
}
