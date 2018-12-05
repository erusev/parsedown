<?php

namespace Erusev\Parsedown\Html\Renderables;

use Erusev\Parsedown\Html\Renderable;
use Erusev\Parsedown\Html\Sanitisation\CharacterFilter;
use Erusev\Parsedown\Html\Sanitisation\Escaper;

final class Element implements Renderable
{
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
     * @param Renderable[] $Contents
     * @return self
     */
    public static function create($name, array $attributes, array $Contents)
    {
        return new self($name, $attributes, $Contents);
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
        $html = '';

        $elementName = CharacterFilter::htmlElementName($this->name);

        $html .= '<' . $elementName;

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
                $html .= "\n";

                foreach ($this->Contents as $C) {
                    $html .= $C->getHtml();
                }
            }

            $html .= "</" . $elementName . ">\n";
        } else {
            $html .= ' />';
        }

        return $html;
    }
}
