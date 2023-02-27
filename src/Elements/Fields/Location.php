<?php
namespace TypeRocket\Pro\Elements\Fields;

use TypeRocket\Core\Config;
use TypeRocket\Html\Html;
use TypeRocket\Elements\Fields\Field;
use TypeRocket\Elements\Fields\ScriptField;
use TypeRocket\Utility\Manifest;
use TypeRocket\Utility\ModelField;

class Location extends Field implements ScriptField
{
    static $locationScriptsLoaded = false;

    protected $useGoogle = false;
    protected $useCountry = false;
    protected $locationLabels = [];

    /**
     * @return void
     */
    public function enqueueScripts()
    {
        if(self::$locationScriptsLoaded) {
            return;
        }

        $maps = Config::get('external.google_maps');
        $maps['api_key'] = $maps['api_key'] ?? ModelField::option('typerocket_theme_options.google_maps_api_key');

        if($maps['api_key'] && $this->getSetting('google', true)) {
            self::$locationScriptsLoaded = true;
            $this->useGoogle = true;
            $url = Config::get('urls.typerocket');
            $manifest = Manifest::typerocket();

            wp_enqueue_script('tr-field-location-google', 'https://maps.googleapis.com/maps/api/js?libraries=geometry&key=' . $maps['api_key'],
                [], '', true);
            wp_enqueue_script('tr-field-location', $url . $manifest['/js/location.field.js'],
                ['jquery', 'tr-field-location-google'], false, true);
            wp_localize_script('tr-field-location', 'TR_GOOGLE_MAPS_API', $maps);
        }
    }

    /**
     * Init is normally used to setup initial configuration like a
     * constructor does.
     *
     * @return void
     */
    protected function init()
    {
        $this->setType( 'location' );
    }

    /**
     * Configure in all concrete Field classes
     *
     * @return string
     */
    public function getString()
    {
        if(!$this->canDisplay()) { return ''; }

        $values = $this->setCast('array')->getValue();
        $name = $this->getNameAttributeString();
        $contextId = $this->getContextId();
        $html = '<div class="tr_field_location_fields">';

        $labels = $this->locationLabels = array_merge([
            'city' => __('City', 'typerocket-domain'),
            'state' => __('State', 'typerocket-domain'),
            'zip' => __('Zip Code', 'typerocket-domain'),
            'country' => __('Country', 'typerocket-domain'),
            'address1' => __('Address', 'typerocket-domain'),
            'address2' => __('Address Line 2', 'typerocket-domain'),
            'lat' => __('Lat', 'typerocket-domain'),
            'lng' => __('Lng', 'typerocket-domain'),
            'generate' => __('Get Address Lat/Lng', 'typerocket-domain'),
            'clear' => __('Clear Address Lat/Lng', 'typerocket-domain'),
        ], $this->locationLabels);

        $cszc = ['city' => $labels['city'], 'state' => $labels['state'], 'zip' => $labels['zip']];

        if($this->useCountry) {
            $cszc['country'] = $labels['country'];
        }

        $field_groups = [
            ['address1' => $labels['address1']],
            ['address2' => $labels['address2']],
            $cszc
        ];

        if($this->useGoogle) {
            $field_groups[] = ['lat' => $labels['lat'], 'lng' => $labels['lng']];
        }

        foreach ($field_groups as $group) {
            $html .= '<div class="tr-flex-list tr-mt-10">';
            foreach($group as $field => $title ) {
                $attrs = [
                    'type' => 'text',
                    'value' => esc_attr( $values[$field] ?? '' ),
                    'name' => $name . '['. $field .']',
                    'data-tr-field' => $contextId . '.' . $field,
                    'class' => 'tr_field_location_' . $field
                ];
                $html .= Html::label(['class' => 'tr-label-thin', 'tabindex' => '-1'], "<span>{$title}</span>")->nestAtTop(Html::el('input', $attrs));
            }
            $html .= '</div>';
        }

        if($this->useGoogle) {
            $html .= '<div class="tr_field_location_load_lat_lng_section button-group">
                <a class="button tr_field_location_load_lat_lng" type="button">'.$labels['generate'].'</a>
                <a class="button tr_field_location_clear_lat_lng" type="button">'.$labels['clear'].'</a>
                </div>
                <div class="tr_field_location_google_map"></div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Set labels
     *
     * @param array $labels
     *
     * @return $this
     */
    public function setLocationLabels(array $labels)
    {
        $this->locationLabels = $labels;
        return $this;
    }

    /**
     * Get labels
     *
     * @return array
     */
    public function getLocationLabels()
    {
        return $this->locationLabels;
    }

    /**
     * Disable Country
     *
     * @return $this
     */
    public function enableCountry()
    {
        $this->useCountry = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function disableGoogle()
    {
        $this->useGoogle = false;
        return $this->setSetting('google', false);
    }
}