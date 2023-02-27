<?php
namespace TypeRocket\Pro\Elements\Fields;

use TypeRocket\Elements\Fields\Field;
use TypeRocket\Elements\Traits\DefaultSetting;
use TypeRocket\Elements\Traits\RequiredTrait;
use TypeRocket\Html\Html;
use TypeRocket\Utility\DateTime;

class WordPressTimezone extends Field
{
    use DefaultSetting, RequiredTrait;

    protected $labelTag = 'label';

    /**
     * Run on construction
     */
    protected function init()
    {
        $this->setType('timezone');
    }

    /**
     * Covert Timezone to HTML string
     */
    public function getString()
    {
        if(!$this->canDisplay()) { return ''; }

        $this->setupInputId();
        $default = $this->getSetting('default', 'UTC');
        $name = $this->getNameAttributeString();
        $this->setAttribute('data-tr-field', $this->getContextId());
        $this->setCast('string');
        $this->setAttribute('name', $name);
        $locale = $this->getSetting('locale');
        $option = $this->getValue(true);
        $option = ! is_null($option) ? $option : $default;

        return Html::select( $this->getAttributes(), wp_timezone_choice($option, $locale) );
    }

    /**
     * @param string|null $locale
     *
     * @return $this
     */
    public function setTimezoneLocale(?string $locale)
    {
        return $this->setSetting('locale', $locale);
    }

    /**
     * @return mixed
     */
    public function getTimezoneLocale()
    {
        return $this->getSetting('locale');
    }

    /**
     * @return $this
     */
    public function setTimezoneSiteDefault()
    {
        if(!$tz = get_option('timezone_string')) {
            $tz = get_option( 'gmt_offset' );
        }

        return $this->setDefault($tz);
    }

    /**
     * Get \DateTimeZone Object
     *
     * @param string $tz WordPress style timezone
     *
     * @return \DateTimeZone
     */
    public static function newDateTimezone(string $tz) : \DateTimeZone
    {
        return DateTime::newDateTimezone(...func_get_args());
    }

    /**
     * Get \DateTime Object
     *
     * @param string $dt HTML5 datetime input field default format
     * @param string $tz WordPress style timezone
     *
     * @return null|\DateTime
     * @throws \Exception
     */
    public static function newDateTime(string $dt, string $tz = 'UTC') : ?\DateTime
    {
        return DateTime::newDateTime(...func_get_args());
    }

    /**
     * Get \DateTime Object and Switch Timezones to UTC
     *
     * @param string $dt HTML5 datetime input field default format
     * @param string $from_tz from this WordPress style timezone to UTC
     *
     * @return null|\DateTime
     * @throws \Exception
     */
    public static function switchDatesTimezoneToUTC(string $dt, string $from_tz) : ?\DateTime
    {
        return DateTime::switchDatesTimezoneToUTC(...func_get_args());
    }

    /**
     * Switch \DateTime Object's Timezone from UTC
     *
     * @param string $dt HTML5 datetime input field default format
     * @param string $to_tz to this WordPress style timezone from UTC
     *
     * @return null|\DateTime
     * @throws \Exception
     */
    public static function switchDatesTimezoneFromUTC(string $dt, string $to_tz) : ?\DateTime
    {
        return DateTime::switchDatesTimezoneFromUTC(...func_get_args());
    }

    /**
     * Get \DateTime Object and Switch Timezones to Site Timezone
     *
     * @param string $dt HTML5 datetime input field default format
     * @param string $from_tz from this WordPress style timezone to site timezone
     *
     * @return null|\DateTime
     * @throws \Exception
     */
    public static function switchDatesTimezoneToSiteTimezone(string $dt, string $from_tz) : ?\DateTime
    {
        return DateTime::switchDatesTimezoneToSiteTimezone(...func_get_args());
    }

    /**
     * Get \DateTime Object and Switch Timezones from Site Timezone
     *
     * @param string $dt HTML5 datetime input field default format
     * @param string $to_tz to this WordPress style timezone from site timezone
     *
     * @return null|\DateTime
     * @throws \Exception
     */
    public static function switchDatesTimezoneFromSiteTimezone(string $dt, string $to_tz) : ?\DateTime
    {
        return DateTime::switchDatesTimezoneFromSiteTimezone(...func_get_args());
    }
}