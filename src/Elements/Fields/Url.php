<?php
namespace TypeRocket\Pro\Elements\Fields;

use TypeRocket\Elements\Traits\DefaultSetting;
use TypeRocket\Elements\Traits\RequiredTrait;
use TypeRocket\Html\Html;
use TypeRocket\Elements\Fields\Field;

class Url extends Field
{
    use DefaultSetting, RequiredTrait;

    protected $labelTag = 'label';

    /**
     * Run on construction
     */
    protected function init()
    {
        $this->setType( 'url' );
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

        $field = '<div class="tr-url">';
        $field .= Html::input('text', $name, $value, $this->getAttributes($this->getSearchAttributes()));
        $field .= '<ol class="tr-search-results"></ol>';
        $field .= '</div>';

        return $field;
    }

    /**
     * @return array
     */
    protected function getSearchAttributes() {
        $type = $this->getSetting('post_type', 'any');
        $taxonomy = $this->getSetting('taxonomy');
        $model = $this->getSetting('model');
        $url = $this->getSetting('url_endpoint');
        $config = $this->getSetting('search-config');

        $search_attributes = ['class' => 'tr-url-input'];

        if($url) {
            $search_attributes['data-endpoint'] = $url;
            if($map = $this->getSetting('url_map')) {
                $search_attributes['data-map'] = json_encode($map);
            }
        } if(!empty($taxonomy)) {
            $search_attributes['data-taxonomy'] = $taxonomy;
        } elseif(!empty($model)) {
            $search_attributes['data-model'] = $model;
        } else {
            $search_attributes['data-posttype'] = $type;
        }

        if(is_array($config)) {
            $search_attributes['data-search-config'] = json_encode($config);
        }

        return $search_attributes;
    }

    /**
     * Search by post type only
     *
     * @param string $type
     *
     * @return $this
     */
    public function setPostTypeOptions($type)
    {
        $this->setSetting('post_type', $type);

        return $this;
    }

    /**
     * Search by taxonomy only
     *
     * @param string $taxonomy
     *
     * @return $this
     */
    public function setTaxonomyOptions($taxonomy)
    {
        $this->setSetting('taxonomy', $taxonomy);

        return $this;
    }

    /**
     * Search URL Endpoint
     *
     * @param string $url
     * @param null|array $map
     *
     * Endpoint format must follow this pattern if map is not set:
     *
     * {
     *   "search_type":"post_type",
     *   "items": [ { "title":"<b>Hello world!</b> (post)", "id":1 } ],
     *   "count": "1 in limit of 10"
     * }
     *
     * @return $this
     */
    public function setUrlOptions($url, $map = null)
    {
        $this->setSetting('url_endpoint', $url);
        $this->setSetting('url_map', $map);

        return $this;
    }

    /**
     * Search by model only
     *
     * @param string $model class as string
     *
     * @return $this
     */
    public function setModelOptions($model)
    {
        $this->setSetting('model', $model);

        return $this;
    }

    /**
     * @param array $array
     *
     * @return $this
     */
    public function setSearchConfig(array $array)
    {
        return $this->setSetting('search-config', $array);
    }
}