## Parsedown PHP

Parsedown PHP is a parser for Markdown. It reads Markdown the way people do. First, it breaks texts into lines. Then, it looks at how these lines start and relate to each other. Finally, it looks for special characters to identify inline elements. As a result, Parsedown PHP is (very) fast and consistent.

[Home](http://parsedown.org) &middot; [Demo](http://parsedown.org/explorer/) &middot; [Tests](http://parsedown.org/tests/)

### Installation

Include `Parsedown.php` or install [the composer package](https://packagist.org/packages/erusev/parsedown).

### Example

```php
$text = 'Hello **Parsedown**!';

$result = Parsedown::instance()->parse($text);

echo $result; # prints: <p>Hello <strong>Parsedown</strong>!</p>
```
