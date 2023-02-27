<?php
namespace TypeRocket\Pro\Elements\Fields;

use TypeRocket\Elements\Traits\BeforeAfterSetting;
use TypeRocket\Elements\Traits\DefaultSetting;
use TypeRocket\Elements\Traits\RequiredTrait;
use TypeRocket\Html\Html;
use TypeRocket\Elements\Fields\Field;

class Range extends Field
{
    use DefaultSetting, RequiredTrait, BeforeAfterSetting;

    protected $labelTag = 'label';

    /**
     * Run on construction
     */
    protected function init()
    {
        $this->setType( 'range' );
    }

    /**
     * Covert Test to HTML string
     */
    public function getString()
    {
        if(!$this->canDisplay()) { return ''; }

        $this->maybeSetDefault(0);
        $this->setupInputId();
        $this->setAttribute('data-tr-field', $this->getContextId());
        $name = $this->getNameAttributeString();
        $value = $this->setCast('string')->getValue();

        $default = $this->getDefault();
        $this->setupInputId();

        $this->maybeSetAttribute('min', 0);
        $this->maybeSetAttribute('step', 1);
        $this->maybeSetAttribute('max', 10);
        $min = $this->getMin();
        $max = $this->getMax();

        $value = !empty($value) || $value == '0' ? $value : $default;
        $value = $this->sanitize($value, 'raw');

        $field = Html::div(['class' => 'tr-range'], [
            Html::div(['class' => 'tr-range-selected'], $this->getBefore() . "<span>{$value}</span>" . $this->getAfter()),
            Html::input('range', $name, $value, $this->getAttributes(['class' => 'tr-range-input'])),
            Html::div(['class' => 'tr-range-labels'], [
                '<div>' . $this->getBefore() . $min . $this->getAfter() . '</div>',
                '<div>' .$this->getBefore() . $max . $this->getAfter() . '</div>',
            ]),
        ]);

        return (string) $field;
    }

    /**
     * @param int|float $max
     *
     * @return Range
     */
    public function setMax($max)
    {
        return $this->setAttribute('max', $max);
    }

    /**
     * @return int|float|null
     */
    public function getMax()
    {
        return $this->getAttribute('max');
    }

    /**
     * @param int|float $min
     *
     * @return Range
     */
    public function setMin($min)
    {
        return $this->setAttribute('min', $min);
    }

    /**
     * @return int|float|null
     */
    public function getMin()
    {
        return $this->getAttribute('min');
    }

    /**
     * @param int|float $step
     *
     * @return Range
     */
    public function setStep($step)
    {
        return $this->setAttribute('step', $step);
    }

    /**
     * @return int|float|null
     */
    public function getStep()
    {
        return $this->getAttribute('step');
    }
}