<?php
use \TypeRocket\Pro\Elements\Fields\WordPressTimezone;

$date_format = _x('Y/m/d \a\t g:i a', 'typerocket-ext-view-blocks-index', 'typerocket-core');

$table = tr_table($blockModel)->setOrder('updated_at', 'DESC')->setColumns([
    'title' => [
        'sort' => true,
        'label' => 'Title',
        'actions' => ['edit', 'delete'],
        'callback' => function( $text, $result ) {
            $id = null;
            $alt = 'unknown';

            if($user = $result->lastUser) {
                $id = $user->getID();
                $alt = $user->display_name;
            }

            $alt = esc_attr($alt);
            $title = sprintf(esc_attr__('Last edited by %s', 'typerocket-core'), $alt);

            return get_avatar($id, 32, '', $alt, ['class' => 'tr-pr-10 tr-fl', 'extra_attr' => "title=\"{$title}\""]) . ' ' . $text;
        },
    ],
    'id' => [
        'sort' => true,
        'label' => 'ID'
    ],
    'created_at' => [
        'sort' => true,
        'label' => 'Created At',
        'exclude_search_select' => true,
        'callback' => function( $text, $result ) use ($date_format) {
            if(!$text) {
                return 'NA';
            }

            return  WordPressTimezone::switchDatesTimezoneToSiteTimezone($text,'UTC')->format($date_format);
        },
    ],
    'updated_at' => [
        'sort' => true,
        'label' => 'Updated At',
        'exclude_search_select' => true,
        'callback' => function( $text, $result ) use ($date_format) {
            if(!$text) {
                return 'NA';
            }

            return WordPressTimezone::switchDatesTimezoneToSiteTimezone($text,'UTC')->format($date_format);
        },
    ],
], 'title');

do_action('typerocket_ext_page_builder_plus_blocks_table', $table, $blockModel);

echo $table;