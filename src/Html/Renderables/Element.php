<?php

namespace Erusev\Parsedown\Html\Renderables;

use Erusev\Parsedown\Html\Renderable;
use Erusev\Parsedown\Html\Sanitisation\CharacterFilter;
use Erusev\Parsedown\Html\Sanitisation\Escaper;
use Erusev\Parsedown\Html\TransformableRenderable;

final class Element implements TransformableRenderable
{
    use CanonicalStateRenderable;

    const TEXT_LEVEL_ELEMENTS = [
        'a' => true,
        'b' => true,
        'i' => true,
        'q' => true,
        's' => true,
        'u' => true,

        'br' => true,
        'em' => true,
        'rp' => true,
        'rt' => true,
        'tt' => true,
        'xm' => true,

        'bdo' => true,
        'big' => true,
        'del' => true,
        'img' => true,
        'ins' => true,
        'kbd' => true,
        'sub' => true,
        'sup' => true,
        'var' => true,
        'wbr' => true,

        'abbr' => true,
        'cite' => true,
        'code' => true,
        'font' => true,
        'mark' => true,
        'nobr' => true,
        'ruby' => true,
        'span' => true,
        'time' => true,

        'blink' => true,
        'small' => true,

        'nextid' => true,
        'spacer' => true,
        'strike' => true,
        'strong' => true,

        'acronym' => true,
        'listing' => true,
        'marquee' => true,

        'basefont' => true,
    ];

    /** @var string */
    private $name;

    /** @var array<string, string>*/
    private $attributes;

    /** @var Renderable[]|null */
    private $Contents;

    /**
     * @param string $name
     * @param array<string, string> $attributes
     * @param Renderable[]|null $Contents
     */
    public function __construct($name, $attributes, $Contents)
    {
        $this->name = $name;
        $this->attributes = $attributes;
        $this->Contents = $Contents;
    }

    /**
     * @param string $name
     * @param array<string, string> $attributes
     * @return self
     */
    public static function selfClosing($name, array $attributes)
    {
        return new self($name, $attributes, null);
    }

    /** @return string */
    public function name()
    {
        return $this->name;
    }

    /**
     * @return array<string, string>
     */
    public function attributes()
    {
        return $this->attributes;
    }

    /**
     * @return Renderable[]|null
     */
    public function contents()
    {
        return $this->Contents;
    }

    /**
     * @param string $name
     * @return self
     */
    public function settingName($name)
    {
        return new self($name, $this->attributes, $this->Contents);
    }

    /**
     * @param array<string, string> $attributes
     * @return self
     */
    public function settingAttributes(array $attributes)
    {
        return new self($this->name, $attributes, $this->Contents);
    }

    /**
     * @param Renderable[]|null $Contents
     * @return self
     */
    public function settingContents($Contents)
    {
        return new self($this->name, $this->attributes, $Contents);
    }

    /** @return string */
    public function getHtml()
    {
        $elementName = CharacterFilter::htmlElementName($this->name);

        $html = '<' . $elementName;

        if (! empty($this->attributes)) {
            foreach ($this->attributes as $name => $value) {
                $html .= ' '
                    . CharacterFilter::htmlAttributeName($name)
                    . '="'
                    . Escaper::htmlAttributeValue($value)
                    . '"'
                ;
            }
        }

        if ($this->Contents !== null) {
            $html .= '>';

            if (! empty($this->Contents)) {
                foreach ($this->Contents as $C) {
                    if (
                        $C instanceof Element
                        && ! \array_key_exists(\strtolower($C->name()), self::TEXT_LEVEL_ELEMENTS)
                    ) {
                        $html .= "\n";
                    }

                    $html .= $C->getHtml();
                }

                $Last = \end($this->Contents);

                if (
                    $Last instanceof Element
                    && ! \array_key_exists(\strtolower($Last->name()), self::TEXT_LEVEL_ELEMENTS)
                ) {
                    $html .= "\n";
                }
            }

            $html .= "</" . $elementName . ">";
        } else {
            $html .= ' />';
        }

        return $html;
    }

    /**
     * @param \Closure(string):TransformableRenderable $Transform
     * @return TransformableRenderable
     */
    public function transformingContent(\Closure $Transform): TransformableRenderable
    {
        if (! isset($this->Contents)) {
            return $this;
        }

        return new self($this->name, $this->attributes, \array_map(
            function (Renderable $R) use ($Transform): Renderable {
                if (! $R instanceof TransformableRenderable) {
                    return $R;
                }

                return $R->transformingContent($Transform);
            },
            $this->Contents
        ));
    }

    public function replacingAll(string $search, TransformableRenderable $Replacement): TransformableRenderable
    {
        if (! isset($this->Contents)) {
            return $this;
        }

        return new self($this->name, $this->attributes, \array_map(
            function (Renderable $R) use ($search, $Replacement): Renderable {
                if (! $R instanceof TransformableRenderable) {
                    return $R;
                }

                return $R->replacingAll($search, $Replacement);
            },
            $this->Contents
        ));
    }
}
