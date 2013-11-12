## Parsedown PHP

Parsedown is a parser for Markdown. It reads Markdown similarly to how people do. First, it breaks texts into lines. Then, it looks at how these lines start and relate to each other. Finally, it looks for special characters to identify inline elements. As a result, Parsedown is (very) fast and consistent.

[Demo](http://parsedown.org/explorer/) &middot; [Tests](http://parsedown.org/tests/) &middot; [Home](http://parsedown.org)

### Installation

Include `Parsedown.php` or install [the composer package](https://packagist.org/packages/erusev/parsedown).

### Example

```php
$text = 'Hello **Parsedown**!';

$result = Parsedown::instance()->parse($text);

echo $result; # prints: <p>Hello <strong>Parsedown</strong>!</p>
```
