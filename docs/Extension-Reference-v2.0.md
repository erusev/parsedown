# Parsedown v2 Extension Reference

This document provides an overview of several core concepts that appear when implementing custom extensions.
It is intended to complement the extension tutorial and the migration guide.

## Configurables

A [`Configurable`](../src/Configurable.php) represents a single configuration value within a [`State`](../src/State.php).
Each configurable type may appear at most once in a state. You retrieve an instance with `$State->get(SomeConfigurable::class)`
and insert a replacement using `$State->setting($Configurable)`.

Configurables are immutable by default. If a configurable needs to be written to during parsing it should instead implement
[`MutableConfigurable`](../src/MutableConfigurable.php). The `State` will automatically provide an isolated clone of such
objects when `->get()` is called, allowing safe mutation within a single parse.

## Books

"Books" are mutable configurables used to store information gathered while parsing. The built in
[`DefinitionBook`](../src/Configurables/DefinitionBook.php) collects link reference definitions and can be queried by
inlines. Other extensions may implement their own books in the same fashion.

```php
use Erusev\Parsedown\Configurables\DefinitionBook;

$State->get(DefinitionBook::class)->mutatingSet('id', ['url' => 'https://example.com', 'title' => null]);
```

The `mutatingSet()` method updates the stored data and affects the rest of the current parse.

## AST

Parsedown creates an abstract syntax tree (AST) while parsing. Each block or inline returns an object that exposes a
`stateRenderable()` method. This method yields an [`AST\StateRenderable`](../src/AST/StateRenderable.php) which can later be
converted into a [`Renderable`](../src/Html/Renderable.php) by providing a `State`.

This two step approach lets extensions alter the rendering process without changing the tree structure itself.

## Renderables

Renderables are small objects that model the resulting HTML. Core renderables include [`Element`](../src/Html/Renderables/Element.php),
[`Text`](../src/Html/Renderables/Text.php) and [`Container`](../src/Html/Renderables/Container.php). After parsing, the state
provides a `RenderStack` that transforms these objects into the final HTML string.

### Element

Represents a single HTML tag. The constructor accepts the tag name, attributes and optional child renderables. A convenience method `Element::selfClosing()` creates elements without children. When rendered, names and attribute values are escaped for safety.

### Text

A plain text node that automatically escapes HTML entities. Use it for raw strings that should not be treated as markup.

### Container

A lightweight wrapper that stores an ordered list of renderables. The `adding()` method returns a new instance with an extra item appended. Containers are commonly produced when transforming renderables or building fragments.

## WidthTrait

Inline components commonly use the [`WidthTrait`](../src/Components/Inlines/WidthTrait.php) to track how many characters were
consumed while parsing. The `width()` method is later consulted when advancing through the source text.

## Parsing

Parsing is performed line by line. `BlockTypes` holds the list of block parsers that may start on a given marker while
`InlineTypes` performs a similar role for inlines. The [`Parsing\Context`](../src/Parsing/Context.php) object tracks the
current line and facilitates continuations. Recursion is limited via the `RecursionLimiter` configurable to avoid runaway
processing.

For a practical walkthrough of extending Parsedown refer to
[Creating-Extensions-v2.0.md](Creating-Extensions-v2.0.md).
