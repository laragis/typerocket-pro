<?php
namespace TypeRocket\Pro\Elements\Fields;

use TypeRocket\Elements\Traits\DefaultSetting;
use TypeRocket\Elements\Traits\RequiredTrait;
use TypeRocket\Html\Html;
use TypeRocket\Elements\Fields\Field;

class Textexpand extends Field
{
    use DefaultSetting, RequiredTrait;

    protected $labelTag = 'span';

    /**
     * Run on construction
     */
    protected function init()
    {
        $this->setType( 'textexpand' );
    }

    /**
     * Covert Test to HTML string
     */
    public function getString()
    {
        if(!$this->canDisplay()) { return ''; }

        $this->setupInputId();
        $this->setCast('string');
        $this->setAttribute('data-tr-field', $this->getContextId());
        $name = $this->getNameAttributeString();
        $value = $this->getValue();
        $default = $this->getDefault();
        $value = !empty($value) || $value == '0' ? $value : $default;
        $value = $this->sanitize($value, 'raw');

        $expand = (string) Html::div(['data-tr-hidden-name' => $name, 'contenteditable' => 'true', 'class' => 'tr-input-textexpand'], esc_html($value));
        $hidden = (string) Html::input('hidden', $name, $value, $this->getAttributes());

        return (string) Html::div(['class' => 'tr-textexpand'], $expand . $hidden);
    }

}