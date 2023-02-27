<?php
namespace TypeRocket\Pro\Elements\Fields;

use TypeRocket\Html\Html;
use TypeRocket\Elements\Fields\Field;

class Checkboxes extends Field
{
    protected $options = [];

    /**
     * Run on construction
     */
    protected function init()
    {
        $this->setType( 'checkbox' );
    }

    /**
     * Covert Checkbox to HTML string
     */
    public function getString()
    {
        if(!$this->canDisplay()) { return ''; }

        $name = $this->getNameAttributeString();
        $this->removeAttribute( 'name' );
        $values = $this->setCast('array')->getValue();
        $this->attrClass('tr-checkboxes');

        $ul = Html::ul($this->getAttributes());

        foreach ($this->options as $fieldSub => $settings) {
            $fieldSub = sanitize_key($fieldSub);
            if(is_string($settings)) { $settings = [ 'text' => $settings ]; }
            $settings = array_merge(['default' => false], $settings);

            $value = $values[$fieldSub] ?? null;
            $fieldName = $name . "[{$fieldSub}]";

            $attr = [];
            $attr['value'] = '1';
            $attr['data-tr-field'] = $this->getContextId() . '.' . $fieldSub;

            if ($value == '1') {
                $attr['checked'] = 'checked';
            }
            elseif($settings['default'] === true && is_null($value)) {
                $attr['checked'] = 'checked';
            }

            $label = Html::label()->nest([
                Html::input('hidden', $fieldName, '0'),
                Html::input( 'checkbox', $fieldName, '1', $attr),
                Html::span($settings['text'])
            ]);

            $ul->nest(Html::li($label));
        }

        return $ul->getString();
    }

    /**
     * Set option
     *
     * @param string $key
     * @param string|array $value
     *
     * @return $this
     */
    public function setOption( $key, $value )
    {
        $this->options[ $key ] = $value;

        return $this;
    }

    /**
     * Set all options
     *
     * @param array $options
     *
     * @return $this
     */
    public function setOptions( $options )
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Get option by key
     *
     * @param string $key
     * @param null|mixed $default
     *
     * @return null
     */
    public function getOption( $key, $default = null )
    {
        return $this->options[ $key ] ?? $default;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Remove option by key
     *
     * @param string $key
     *
     * @return $this
     */
    public function removeOption( $key )
    {
        if ( array_key_exists( $key, $this->options ) ) {
            unset( $this->options[ $key ] );
        }

        return $this;
    }

}