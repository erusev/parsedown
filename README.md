## Parsedown

Better [Markdown](http://en.wikipedia.org/wiki/Markdown) parser for PHP.

- [Demo](http://parsedown.org/demo)
- [Tests](http://parsedown.org/tests/)

### Features

* [Fast](http://parsedown.org/speed)
* [Consistent](http://parsedown.org/consistency)
* [GitHub Flavored](https://help.github.com/articles/github-flavored-markdown)
* [Tested](https://travis-ci.org/erusev/parsedown) in PHP 5.2, 5.3, 5.4, 5.5, 5.6 and [hhvm](http://www.hhvm.com/)
* Extensible

### Installation

Include `Parsedown.php` or install [the composer package](https://packagist.org/packages/erusev/parsedown).

### Example

``` php
$Parsedown = new Parsedown();

echo $Parsedown->text('Hello _Parsedown_!'); # prints: <p>Hello <em>Parsedown</em>!</p>
```
