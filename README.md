## Parsedown PHP

Parsedown is a parser for Markdown. It parses Markdown text the way people do. First, it divides texts into blocks. Then it looks at how these blocks start and how they relate to each other. Finally, it looks for special characters to identify inline elements. As a result, Parsedown is (super) fast, consistent and clean.

[Demo](http://parsedown.org/explorer/) &middot; [Tests](http://parsedown.org/tests/) &middot; [Homepage](http://parsedown.org)

### Installation

Include `Parsedown.php` or install [the composer package](https://packagist.org/packages/erusev/parsedown).

### Example

```php
$text = 'Hello **Parsedown**!';

$result = Parsedown::instance()->parse($text);

echo $result; # prints: <p>Hello <strong>Parsedown</strong>!</p>
```
