## Parsedown

Better Markdown Parser in PHP

[[ demo ]](http://parsedown.org/demo)

### Features

* [Fast](http://parsedown.org/speed)
* [Consistent](http://parsedown.org/consistency)
* [GitHub flavored](https://help.github.com/articles/github-flavored-markdown)
* [Tested](http://parsedown.org/tests/) in PHP 5.2, 5.3, 5.4, 5.5, 5.6 and [hhvm](http://www.hhvm.com/)
* Extensible
* [Markdown Extra extension](https://github.com/erusev/parsedown-extra) <sup>new</sup>
* [JavaScript port](https://github.com/hkdobrev/parsedown.js) under development <sup>new</sup>

### Installation

Include `Parsedown.php` or install [the composer package](https://packagist.org/packages/erusev/parsedown).

### Example

``` php
$Parsedown = new Parsedown();

echo $Parsedown->text('Hello _Parsedown_!'); # prints: <p>Hello <em>Parsedown</em>!</p>
```

More examples in [the wiki](https://github.com/erusev/parsedown/wiki/Usage) and in [this video tutorial](http://youtu.be/wYZBY8DEikI).

### Questions

**How does Parsedown work?**<br/>
Parsedown recognises that Markdown texts are optimised to be read by humans so it tries to read like one. It goes through texts line by line. It looks at how lines start to identify blocks. It looks for special characters to identify inline elements.

**Why doesn’t Parsedown use namespaces?**<br/>
Using namespaces would mean dropping support for PHP 5.2. We believe that since Parsedown is a single class with an uncommon name, making this trade wouldn't be worth it.

**Is Parsedown compliant with CommonMark?**<br/>
We are [working on it](https://github.com/erusev/parsedown/tree/commonmark).

**Who uses Parsedown?**<br/>
[phpDocumentor](http://www.phpdoc.org/), [October CMS](http://octobercms.com/), [Bolt CMS](http://bolt.cm/), [Kirby CMS](http://getkirby.com/),  [RaspberryPi.org](http://www.raspberrypi.org/) and [more](https://www.versioneye.com/php/erusev:parsedown/references).

**How can I help?**<br/>
Use the project, tell friends about it and if you feel generous, [donate some money](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=528P3NZQMP8N2).
