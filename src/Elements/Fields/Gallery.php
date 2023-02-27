<?php
namespace TypeRocket\Pro\Elements\Fields;

use TypeRocket\Elements\Traits\ImageFeaturesTrait;
use TypeRocket\Html\Html;
use TypeRocket\Elements\Fields\Field;
use TypeRocket\Elements\Fields\ScriptField;
use TypeRocket\Utility\Str;

class Gallery extends Field implements ScriptField
{
    use ImageFeaturesTrait;

    /**
     * Run on construction
     */
    protected function init()
    {
        $this->setType( 'gallery' );
    }

    /**
     * Get the scripts
     */
    public function enqueueScripts() {
        wp_enqueue_media();
        wp_enqueue_script('jquery-ui-sortable', ['jquery'], false, true );
    }

    /**
     * Covert Gallery to HTML string
     */
    public function getString()
    {
        if(!$this->canDisplay()) { return ''; }

        $name = $this->getNameAttributeString();
        $images = $this->setCast('array')->getValue();

        $this->attrClass( 'image-picker');
        $this->maybeSetSetting( 'button',  __('Insert Images', 'typerocket-domain') );

        $list = [];

        if (is_array( $images )) {
            foreach ($images as $id) {
                $id = (int) $id;
                $img = wp_get_attachment_image( $id, $this->getSetting('size', 'thumbnail') );
                $edit = Html::a( '', admin_url("post.php?post={$id}&action=edit"), [
                    'class' => 'dashicons dashicons-edit tr-image-edit',
                    'target' => '_blank',
                    'title' => __('Edit', 'typerocket-domain'),
                    'tabindex' => '0',
                ]);

                if (!empty( $img )) {
                    $input = Html::input( 'hidden', $name . '[]', $id );
                    $a = Html::el('a', ['class' => 'dashicons dashicons-no-alt tr-gallery-remove', 'title' => __('Remove Image', 'typerocket-domain'), 'tabindex' => '0']);
                    $list[] = Html::li(['class' => 'tr-gallery-item tr-image-picker-placeholder', 'tabindex' => '0'], [$a, $edit, $img, $input]);
                }
            }
        }

        $this->removeAttribute('name');
        $this->removeAttribute('id');

        $html = (string) Html::input( 'hidden', $name, '0', $this->getAttributes() );

        $button = Html::input( 'button', null, $this->getSetting( 'button' ), [
            'class' => 'tr-gallery-picker-button button',
            'data-size' => $this->getSetting('size', 'thumbnail')
        ]);

        $clear = Html::input( 'button', null, __('Clear', 'typerocket-domain'), [
            'class' => 'tr-gallery-picker-clear button',
        ]);

        $html .= Html::div(['class' => 'button-group'])->nest( [$button, $clear] );

        $classes = Str::classNames('tr-gallery-list cf', [
            'tr-dark-image-background' => $this->getSetting('background', 'light') == 'dark'
        ]);

        $html .= Html::ul([
            'class' => $classes
        ], $list );

        return $html;
    }

}