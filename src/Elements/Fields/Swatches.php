<?php
namespace TypeRocket\Pro\Elements\Fields;

use TypeRocket\Elements\Traits\DefaultSetting;
use TypeRocket\Elements\Traits\OptionsTrait;
use TypeRocket\Html\Html;
use TypeRocket\Utility\Sanitize;
use TypeRocket\Elements\Fields\Field;

class Swatches extends Field
{

    use OptionsTrait, DefaultSetting;

    /**
     * Run on construction
     */
    protected function init()
    {
        $this->setType('swatches');
    }

    /**
     * Covert Radio to HTML string
     */
    public function getString()
    {
        if(!$this->canDisplay()) { return ''; }

        $name    = $this->getNameAttributeString();
        $default = $this->getSetting('default');
        $selected  = $this->getValue();
        $selected  = ! is_null($selected) ? $selected : $default;
        $this->removeAttribute('name');
        $id = $this->getAttribute('id', '');
        $this->removeAttribute('id');
        $this->setAttribute('data-tr-field', $this->getContextId());

        if($id) { $id = "id=\"{$id}\""; }

        $field = "<ul class=\"tr-radio-options tr-swatches\" {$id}>";

        foreach ($this->options as $key => $hex) {
            $value = Sanitize::underscore($key);
            $key = esc_attr($key);
            $label = esc_html($key);

            $hex = array_map(function($h) {
                return Sanitize::hex($h);
            }, $hex);

            if ( ( $selected == $value && isset($selected) ) || ( !isset($selected) && $value == $default) ) {
                $this->setAttribute('checked', 'checked');
            } else {
                $this->removeAttribute('checked');
            }

            $field .= "<li title='{$key}' style='--tr-swatch-a: {$hex[0]}; --tr-swatch-b: {$hex[1]};'><label aria-label='{$key}' class='tr-radio-options-label' tabindex='0'>";
            $field .= Html::input( 'radio', $name, $value, $this->getAttributes(['tabindex' => '-1']) );
            $field .= "<span>{$label}</span><div class='tr-swatch-box'></div></label></li>";
        }

        $field .= '</ul>';

        return $field;
    }

    /**
     * Set option
     *
     * @param string $key
     * @param array $value
     *
     * @return $this
     */
    public function setOption( $key, $value )
    {
        $this->options[ $key ] = $value;

        return $this;
    }
}