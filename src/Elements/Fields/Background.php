<?php
namespace TypeRocket\Pro\Elements\Fields;

use TypeRocket\Elements\Traits\ImageFeaturesTrait;
use TypeRocket\Html\Html;
use TypeRocket\Elements\Fields\ScriptField;
use TypeRocket\Elements\Fields\Field;
use TypeRocket\Utility\Data;
use TypeRocket\Utility\Str;


class Background extends Field implements ScriptField
{
    use ImageFeaturesTrait;

    /**
     * Init is normally used to setup initial configuration like a
     * constructor does.
     */
    protected function init()
    {
        $this->setType('background');
    }

    /**
     * Define debug function
     *
     * @return string
     */
    public function getDebugHelperFunctionModifier()
    {
        return ":background:full:";
    }

    /**
     * Configure in all concrete Field classes
     *
     * @return string
     */
    public function getString()
    {
        $name = $this->getNameAttributeString();
        $this->attrClass( 'image-picker' );
        $value = $this->getValue();

        if(is_numeric($value)) {
            $value = ['id' => (int) $value];
        }

        $value = Data::cast($value, 'array');

        $values = array_merge([
            'id' => null,
            'x'  => null,
            'y'  => null,
        ], is_array($value) ? $value : [] );

        $x = $values['x'] ?: 50;
        $y = $values['y'] ?: 50;

        $this->removeAttribute( 'name' );

        if ( ! $this->getSetting( 'button' )) {
            $this->setSetting( 'button', __('Insert Image', 'typerocket-domain') );
        }

        if ( ! $this->getSetting( 'clear' )) {
            $this->setSetting( 'clear', __('Clear', 'typerocket-domain') );
        }

        $image_src = $img = '';

        if ($values['id']) {
            $image_src = wp_get_attachment_image_src( (int) $values['id'], $this->getSetting('size', 'full') );
            $image_src = $image_src ? $image_src[0] : '';
            $img = "<img src='{$image_src}' />";
        }

        $classes = Str::classNames('tr-image-background-placeholder', [
            'tr-dark-image-background' => $this->getSetting('background', 'light') == 'dark'
        ]);

        $container = Html::div($this->getAttributes([
            'class' => 'tr-image-field-background',
            'style' => "--tr-image-field-bg-src: url({$image_src});"
        ]));

        $help = __('Insert an image. Then, click a location on the image preview to set the background focal point or adjust the X & Y coordinates manually.', 'typerocket-domain');
        $help_x = __('Focal point X axis percentage', 'typerocket-domain');
        $help_y = __('Focal point Y axis percentage', 'typerocket-domain');

        $html = "<p class='tr-field-help-top'>{$help}</p>";
        $html .= Html::input( 'hidden', $name.'[id]', $values['id'], ['data-tr-field' => $this->getContextId() ] );
        $html .= '<div class="button-group">';
        $html .= Html::el( 'input', [
            'type'  => 'button',
            'class' => 'tr-image-bg-picker-button button',
            'data-size' => $this->getSetting('size', 'full'),
            'data-type' => $this->getSetting('type', 'background'),
            'value' => $this->getSetting( 'button' )
        ]);
        $html .= Html::el( 'input', [
            'type'  => 'button',
            'class' => 'tr-image-bg-picker-clear button',
            'value' => $this->getSetting( 'clear' )
        ]);
        $html .= '</div>';
        $html .= "<div class='tr-position-image' style='--tr-image-field-bg-x: {$x}%; --tr-image-field-bg-y: {$y}%;'>";
        $html .= Html::div([
            'class' => $classes,
        ], $img );
        $html .= '</div>';
        $html .= '<div class="tr-position-inputs">';
        $html .= "<label aria-label='{$help_x}'>X:";
        $html .= (string) Html::input('number', $name.'[x]', $x, ['placeholder' => '50', 'class' => 'tr-pos-x', 'min' => 0, 'max' => 100] );
        $html .= '</label>';
        $html .= "<label aria-label='{$help_y}'>Y:";
        $html .= (string) Html::input('number', $name.'[y]', $y, ['placeholder' => '50', 'class' => 'tr-pos-y', 'min' => 0, 'max' => 100] );
        $html .= '</label>';
        $html .= '</div>';

        return $container->nest($html);
    }

    public function enqueueScripts()
    {
        wp_enqueue_media();
    }
}