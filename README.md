## Parsedown

Better [Markdown](http://en.wikipedia.org/wiki/Markdown) parser for PHP.

***

[ [demo](http://parsedown.org/demo) ] [ [tests](http://parsedown.org/tests/) ]

***

### Features

* [fast](http://parsedown.org/speed)
* [consistent](http://parsedown.org/consistency)
* [GitHub Flavored](https://help.github.com/articles/github-flavored-markdown)
* [tested](https://travis-ci.org/erusev/parsedown) in PHP 5.2, 5.3, 5.4, 5.5, 5.6 and [hhvm](http://www.hhvm.com/)
* friendly to international input

### Installation

Include `Parsedown.php` or install [the composer package](https://packagist.org/packages/erusev/parsedown).

### Example

``` php
$parsedown = new Parsedown();

echo $parsedown->parse('Hello _Parsedown_!'); # prints: <p>Hello <em>Parsedown</em>!</p>
```
