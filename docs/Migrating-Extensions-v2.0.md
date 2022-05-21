# Implementing "Extensions" in v2.0

Parsedown v1.x allowed extensability through class extensions, where an developer
could extend the core Parsedown class, and access or override any of the `protected`
level methods and variables.

Whilst this approach allows huge breadth to the type of functionality that can
be added by an extension, it has some downsides too:

* ### Composability: extensions cannot be combined easily
  An extension must extend another extension for two extensions to work together.
  This limits the usefulness of small extensions, because they cannot be combined with another small or popular extension.
  If an extension author wishes the extension to be compatible with another extension, they can only pick one.

* ### API stability
  Because extensions have access to functions and variables at the `protected` API layer, it is hard to determine impacts of
  internal changes. Yet, without being able to make a certain amount of internal change it is impractical to fix bugs or develop
  new features. In the `1.x` branch, `1.8` was never released outside of a "beta" version for this reason: changes in the
  `protected` API layer would break extensions.

In order to address these concerns, "extensions" in Parsedown v2.0 will become more like "plugins", and with that comes a lot of
flexability.

ParsedownExtra is a popular extension for Parsedown, and this has been completely re-implemented for 2.0. In order to use
ParsedownExtra with Parsedown, a user simply needs to write the following:

```php
$Parsedown = new Parsedown(new ParsedownExtra);
$actualMarkup = $Parsedown->toHtml($markdown);
```

Here, ParsedownExtra is *composed* with Parsedown, but does not extend it.

A key feature of *composability* is the ability to compose *multiple* extensions together, for example another
extension, say, `ParsedownMath` could be composed with `ParsedownExtra` in a user-defined order.

This time using the `::from` method, rather than the convinence constructor provided by `ParsedownExtra`.

```php
$Parsedown = new Parsedown(ParsedownExtra::from(ParsedownMath::from(new State)));
```

```php
$Parsedown = new Parsedown(ParsedownMath::from(ParsedownExtra::from(new State)));
```

In the above, the first object that we initialise the chain of composed extensions is the `State` object. This `State`
object is passed from `ParsedownExtra` to `ParsedownMath`, and then finally, to `Parsedown`. At each stage new
information is added to the `State`: adding or removing parsing instructions, and to enabling or disabling features.

The `State` object both contains instructions for how to parse a document (e.g. new blocks and inlines), as well as
information used throughout parsing (such as link reference definitions, or recursion depth). By writing `new State`,
we create a `State` object that is setup with Parsedown's default behaviours, and by passing that object through
different extensions (using the `::from` method), these extensions are free to alter, add to, or remove from that
default behaviour.

## Introduction to the `State` Object
Key to Parsedown's new composability for extensions is the `State` object.

This name is a little obtuse, but is importantly accurate.

A `State` object incorporates `Block`s, `Inline`s, some additional render steps, and any custom configuration options that
the user might want to set. This can **fully** control how a document is parsed and rendered.

In the above code, `ParsedownExtra` and `ParsedownMath` would both be implementing the `StateBearer` interface, which
essentially means "this class holds onto a particular Parsedown State". A `StateBearer` should be constructable from
an existing `State` via `::from(StateBearer $StateBearer)`, and reveals the `State` it is holding onto via `->state(): State`.

Implementing the `StateBearer` interface is **strongly encouraged** if implementing an extension, but not necessarily required.
In the end, you can modify Parsedown's behaviour by producing an appropriate `State` object (which itself is trivially a
`StateBearer`).

In general, extensions are encouraged to go further still, and split each self-contained piece of functionality out into its own
`StateBearer`. This will allow your users to cherry-pick specific pieces of functionality and combine it with other
functionality from different authors as they like. For example, a feature of ParsedownExtra is the ability to define and expand
"abbreviations". This feature is self-contained, and does not depend on other features (e.g. "footnotes").

A user could import *only* the abbreviations feature from ParsedownExtra by using the following:

```php
use Erusev\Parsedown\State;
use Erusev\Parsedown\Parsedown;
use Erusev\ParsedownExtra\Features\Abbreviations;

$State = Abbreviations::from(new State);

$Parsedown = new Parsedown($State);
$actualMarkup = $Parsedown->toHtml($markdown);
```

This allows a user to have fine-grained control over which features they import, and will allow them much more control over
combining features from multiple sources. E.g. a user may not like the way ParsedownExtra has implemented the "footnotes" feature,
and so may wish to utilise an implementation from another source. By implementing each feature as its own `StateBearer`, we give
users the freedom to compose features in a way that works for them.

## Anatomy of the `State` Object

The `State` object, generically, consists of a set of `Configurable`s. The word "set" is important here: only one instance of each
`Configurable` may exist in a `State`. If you need to store related data in a `Configurable`, your `Configurable` needs to handle
this containerisation itself.

`State` has a special property: all `Configurable`s "exist" in any `State` object when retrieving that `Configurable` with `->get`.

This means that retrieval cannot fail when using this method, though does mean that all `Configurable`s need to be "default constructable" (i.e. can be constructed into a "default" state). All `Configurable`s must therefore implement the static method
`initial`, which must return an instance of the given `Configurable`. No initial data will be provided, but the `Configurable` **must** arrive at some sane default instance.

`Configurable`s must also be immutable, unless they declare themeslves otherwise by implementing the `MutableConfigurable` interface.

### Blocks
One of the "core" `Configurable`s in Parsedown is `BlockTypes`. This contains a mapping of "markers" (a character that Parsedown
looks for, before handing off to the block-specific parser), and a list of `Block`s that can begin parsing from a specific marker.
Also contained, is a list of "unmarked" blocks, which Parsedown will hand off to prior to trying any marked blocks. Within marked
blocks there is also a precedence order, where the first block type to successfully parse in this list will be the one chosen.

The default value given by `BlockTypes::initial()` consists of Parsedown's default blocks. The following is a snapshot of this list:

```php
const DEFAULT_BLOCK_TYPES = [
    '#' => [Header::class],
    '*' => [Rule::class, TList::class],
    '+' => [TList::class],
    '-' => [SetextHeader::class, Table::class, Rule::class, TList::class],
...
```

This means that if a `-` marker is found, Parsedown will first try to parse a `SetextHeader`, then try to parse a `Table`, and
so on...

A new block can be added to this list in several ways. ParsedownExtra, for example, adds a new `Abbreviation` block as follows:

```php
$BlockTypes = $State->get(BlockTypes::class)
    ->addingMarkedLowPrecedence('*', [Abbreviation::class])
;

$State = $State->setting($BlockTypes);
```

This first retrieves the current value of the `BlockTypes` configurable, adds `Abbreviation` with low precedence (i.e. the
back of the list) to the `*` marker, and then updates the `$State` object by using the `->setting` method.

### Immutability

Note that the `->setting` method must be used to create a new instance of the `State` object because `BlockTypes` is immutable,
the same will be true of most configurables. This approach is preferred because mutations to `State` are localised by default: i.e.
only affect copies of `$State` which we provide to other methods, but does not affect copies of `$State` which were provided to our
code by a parent caller.

Localised mutability allows for more sensible reasoning by default, for example (this time talking about `Inline`s), the `Link` inline
can enforce that no inline `Url`s are parsed (which would cause double links in output when parsing something like:
`[https://example.com](https://example.com)`). This can be done by updating the copy of `$State` which is passed down to lower level
parsers to simply no longer include parsing of `Url`s:

```php
$State = $State->setting(
    $State->get(InlineTypes::class)->removing([Url::class])
);
```

If `InlineTypes` were mutable, this change would not only affect decendent parsing, but would also affect all parsing which occured after our link was parsed (i.e. would stop URL parsing from that point on in the document).

Another use case for this is implementing a recursion limiter (which *is* implemented as a configurable). After a user-specifiable
max-depth is exceeded: further parsing will halt. The implementaion for this is extremely simple, only because of immutability.

### Mutability
The preference toward immutability by default is not an assertion that "mutability is bad", rather that "unexpected mutability
is bad". By opting-in to mutability, we can treat mutability with the care it deserves.

While immutabiltiy can do a lot to simplify reasoning in the majority of cases, there are some cirumstances where mutability is
required to implement a specific feature. An exmaple of this is found in ParsedownExtra's "abbreviations" feature, which implements
the following:

```php
final class AbbreviationBook implements MutableConfigurable
{
    /** @var array<string, string> */
    private $book;

    /**
     * @param array<string, string> $book
     */
    public function __construct(array $book = [])
    {
        $this->book = $book;
    }

    /** @return self */
    public static function initial()
    {
        return new self;
    }

    public function mutatingSet(string $abbreviation, string $definition): void
    {
        $this->book[$abbreviation] = $definition;
    }

    public function lookup(string $abbreviation): ?string
    {
        return $this->book[$abbreviation] ?? null;
    }

    /** @return array<string, string> */
    public function all()
    {
        return $this->book;
    }

    /** @return self */
    public function isolatedCopy(): self
    {
        return new self($this->book);
    }
}
```

Under the hood, `AbbreviationBook` is nothing more than a string-to-string mapping between an abbreviation, and its definition.

The powerful feature here is that when an abbreviation is identified during parsing, that definition can be updated immediately
everywhere, without needing to worry about the current parsing depth, or organise an alternate method to sharing this data. Footnotes
also make use of this with a `FootnoteBook`, with slightly more complexity in what is stored (so that inline references can be
individually numbered).
