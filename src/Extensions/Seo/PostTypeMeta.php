<?php
namespace TypeRocket\Pro\Extensions\Seo;

use TypeRocket\Elements\BaseForm;
use TypeRocket\Elements\Tabs;
use TypeRocket\Interfaces\Formable;
use TypeRocket\Register\MetaBox;

class PostTypeMeta
{
    public $postTypes = null;

    public function setup($post_types)
    {
        $this->postTypes = $post_types;
        $pt = apply_filters('typerocket_seo_post_types', $this->postTypes);
        $this->postTypes = $pt ?? get_post_types( ['public' => true] );
        add_action('typerocket_model', [$this, 'tr_model'], 9999999999, 2 );

        if ( is_admin() ) {
            add_action( 'add_meta_boxes', [$this, 'add_meta_boxes']);
        }

        return $this;
    }

    public function tr_model( $model )
    {
        global $post;

        if(!empty($post) && $model instanceof \TypeRocket\Models\WPPost) {
            $fillable = $model->getFillableFields();
            /** @var \WP_Post $data */
            $types = get_post_types(['public' => true]);
            if(!empty($fillable) && !empty($types[$post->post_type]) ) {
                $model->appendFillableField('seo');
            }
        }
    }

    public function add_meta_boxes()
    {
        // SEO Meta Box
        $seo_args = [
            'label'    => __('Search Engine Optimization', 'typerocket-ext-seo'),
            'priority' => 'low',
            'callback' => [$this, 'fields']
        ];

        $seo = new \TypeRocket\Register\MetaBox('typerocket_seo', null, $seo_args );
        $seo->addPostType( $this->postTypes )->register();
    }

    /**
     * Search Engine Optimization - Meta Box Fields
     *
     * @param null|BaseForm|MetaBox $ex
     * @param null|Tabs $tabs
     *
     * @throws \Exception
     */
    public function fields($ex = null, $tabs = null)
    {
        // build form
        $form = $ex instanceof MetaBox ? new BaseForm() : $ex;
        $form = $form->extend( 'seo.meta' )->setDebugStatus( false );
        $seo_plugin = $this;

        // General
        $general = function() use ($form, $seo_plugin){

            $title = [
                'label' => __('Page Title', 'typerocket-ext-seo')
            ];

            $desc = [
                'label' => __('Search Result Description', 'typerocket-ext-seo')
            ];

            echo $form->text( 'basic.title', [], $title )->attrClass('tr-js-seo-title-field');
            echo $form->textarea( 'basic.description', [], $desc )->attrClass('tr-js-seo-desc-field');

            do_action('typerocket_seo_fields', $form);

            $seo_plugin->general($form->getModel(), $form->getGroup());
        };

        // Social
        $social = function() use ($form){

            $og_title = [
                'label' => __('Title', 'typerocket-ext-seo'),
                'help'  => __('The open graph protocol is used by social networks like FB, Google+, LinkedIn, and Pinterest. Set the title used when sharing.', 'typerocket-ext-seo')
            ];

            $og_desc = [
                'label' => __('Description', 'typerocket-ext-seo'),
                'help'  => __('Set the open graph description to override "Search Result Description". Will be used by FB, Google+ and Pinterest.', 'typerocket-ext-seo')
            ];

            $og_type = [
                'label' => __('Page Type', 'typerocket-ext-seo'),
                'help'  => __('Set the open graph page type. You can never go wrong with "Article".', 'typerocket-ext-seo')
            ];

            $img = [
                'label' => __('Image', 'typerocket-ext-seo'),
                'help'  => __("The image is shown when sharing socially using the open graph protocol. Will be used by FB, Google+ and Pinterest. Need help? Try the Facebook <a href=\"https://developers.facebook.com/tools/debug/og/object/\" target=\"_blank\">open graph object debugger</a> and <a href=\"https://developers.facebook.com/docs/sharing/best-practices\" target=\"_blank\">best practices</a>.", 'typerocket-ext-seo')
            ];

            echo $form->text( 'og.title', [], $og_title );
            echo $form->textarea( 'og.desc', [], $og_desc );
            echo $form->select( 'og.type', [], $og_type )->setOptions(['Article' => 'article', 'Profile' => 'profile']);
            echo $form->image( 'og.img', [], $img );
        };

        // Twitter
        $twitter = function() use ($form){

            $tw_img = [
                'label' => __('Image', 'typerocket-ext-seo'),
                'help'  => __("Images for a 'summary_large_image' card should be at least 280px in width, and at least 150px in height. Image must be less than 1MB in size. Do not use a generic image such as your website logo, author photo, or other image that spans multiple pages.", 'typerocket-ext-seo')
            ];

            $tw_help = __("Need help? Try the Twitter <a href=\"https://cards-dev.twitter.com/validator/\" target=\"_blank\">card validator</a>, <a href=\"https://dev.twitter.com/cards/getting-started\" target=\"_blank\">getting started guide</a>, and <a href=\"https://business.twitter.com/en/help/campaign-setup/advertiser-card-specifications.html\" target=\"_blank\">advertiser creative specifications</a>.", 'typerocket-ext-seo');

            $card_opts = [
                __('Summary', 'typerocket-ext-seo')             => 'summary',
                __('Summary large image', 'typerocket-ext-seo') => 'summary_large_image',
            ];

            echo $form->text('tw.site')->setBefore('@')->setLabel(__('Site Twitter Account', 'typerocket-ext-seo'));
            echo $form->text('tw.creator')->setBefore('@')->setLabel(__('Page Author\'s Twitter Account', 'typerocket-ext-seo'));
            echo $form->select('tw.card')->setOptions($card_opts)->setLabel(__('Card Type', 'typerocket-ext-seo'))->setSetting('help', $tw_help);
            echo $form->text('tw.title')->setLabel(__('Title', 'typerocket-ext-seo'))->setAttribute('maxlength', 70 );
            echo $form->textarea('tw.desc')->setLabel(__('Description', 'typerocket-ext-seo'))->setHelp( __('Description length is dependent on card type.') );
            echo $form->image('tw.img', [], $tw_img );
        };

        // Advanced
        $advanced = function() use ($form){

            $redirect = [
                'label'    => __('301 Redirect', 'typerocket-ext-seo'),
                'help'     => __('Move this page permanently to a new URL.', 'typerocket-ext-seo') . ' <a href="#tr_field_seo_meta_advanced_redirect" id="tr_seo_redirect_unlock">' . __('Unlock 301 Redirect', 'typerocket-ext-seo') .'</a>',
                'readonly' => true
            ];

            $follow = [
                'label' => __('Robots Follow?', 'typerocket-ext-seo'),
                'desc'  => __("Don't Follow", 'typerocket-ext-seo'),
                'help'  => __('This instructs search engines not to follow links on this page. This only applies to links on this page. It\'s entirely likely that a robot might find the same links on some other page and still arrive at your undesired page.', 'typerocket-ext-seo')
            ];

            $follow_opts = [
                __('Not Set', 'typerocket-ext-seo')      => 'none',
                __('Follow', 'typerocket-ext-seo')       => 'follow',
                __("Don't Follow", 'typerocket-ext-seo') => 'nofollow'
            ];

            $index_opts = [
                __('Not Set', 'typerocket-ext-seo')     => 'none',
                __('Index', 'typerocket-ext-seo')       => 'index',
                __("Don't Index", 'typerocket-ext-seo') => 'noindex'
            ];

            $canon = [
                'label' => __('Canonical URL', 'typerocket-ext-seo'),
                'help'  => __('The canonical URL that this page should point to, leave empty to default to permalink.', 'typerocket-ext-seo')
            ];

            $help = [
                'label' => __('Robots Index?', 'typerocket-ext-seo'),
                'desc'  => __("Don't Index", 'typerocket-ext-seo'),
                'help'  => __('This instructs search engines not to show this page in its web search results.', 'typerocket-ext-seo')
            ];

            echo $form->text( 'advanced.canonical', [], $canon )->setLabel(__('Canonical', 'typerocket-ext-seo'));
            echo $form->text( 'advanced.redirect', ['readonly' => 'readonly'], $redirect )->setLabel(__('Redirect', 'typerocket-ext-seo'))->attrClass('tr-js-seo-redirect-field');
            echo $form->row([
                $form->select( 'robots.follow', [], $follow )->setLabel(__('Follow', 'typerocket-ext-seo'))->setOptions($follow_opts),
                $form->select( 'robots.index', [], $help )->setLabel(__('Index', 'typerocket-ext-seo'))->setOptions($index_opts)
            ]);
        };

        // Tools
        $tools = function() use ($form) {
            global $post;
            echo '<div class="tr-control-section tr-divide">';
            $link = esc_url_raw(get_permalink($post));
            $speed = "<a class=\"button\" href=\"https://developers.google.com/speed/pagespeed/insights/?url=$link\" target=\"_blank\">Analyze Page Speed</a>";
            $rich = "<a class=\"button\" href=\"https://search.google.com/test/rich-results?url=$link\" target=\"_blank\">Analyze Rich Results</a>";
            echo "<div><div class='button-group'>{$speed}{$rich}</div></div>";
            echo '</div>';
        };

        if(!$tabs instanceof Tabs) {
            $tabs = \TypeRocket\Elements\Tabs::new()->layoutLeft();
        }

        $tabs->tab("Google", 'dashicons-google')->setCallback($general)->setDescription(__('Classic SEO', 'typerocket-ext-seo'));
        $tabs->tab("Social", 'dashicons-facebook-alt')->setCallback($social)->setDescription(__('Open graph tags', 'typerocket-ext-seo'));
        $tabs->tab("Twitter", 'dashicons-twitter')->setCallback($twitter)->setDescription(__('Tweet cards', 'typerocket-ext-seo'));
        $tabs->tab("Advanced", 'dashicons-admin-site-alt3')->setCallback($advanced)->setDescription(__('Redirects & robots', 'typerocket-ext-seo'));
        $tabs->tab("Analyze", 'dashicons-code-standards')->setCallback($tools)->setDescription(__('Speed & schema', 'typerocket-ext-seo'));
        $tabs->render();
    }

    public function general(Formable $model = null, $group = '')
    {
        global $post; ?>
        <div id="tr-seo-preview" class="tr-control-section tr-divide">
            <h4><?php _e('Example Preview', 'typerocket-ext-seo'); ?></h4>

            <p><?php _e('Google has <b>no definitive character limits</b> for page "Titles" and "Descriptions". However, your Google search result may look something like:', 'typerocket-ext-seo'); ?>

            <div class="tr-seo-preview-google">
        <span id="tr-seo-preview-google-title-orig">
          <?php echo mb_substr( strip_tags( $post->post_title ?? null ), 0, 60 ); ?>
        </span>
                <span id="tr-seo-preview-google-title">
          <?php
          $title = $model->getFieldValue(trim($group . '.basic.title', '.'));
          if ( ! empty( $title ) ) {
              $s  = strip_tags( $title );
              $tl = mb_strlen( $s );
              echo mb_substr( $s, 0, 60 );
          } else {
              $s  = strip_tags( $post->post_title ?? null );
              $tl = mb_strlen( $s );
              echo mb_substr( $s, 0, 60 );
          }

          if ( $tl > 60 ) {
              echo '...';
          }
          ?>
        </span>

        <div id="tr-seo-preview-google-url">
            <?php
            $url = get_permalink( $post->ID ?? null );
            echo $url ?: site_url(); ?>
        </div>
        <span id="tr-seo-preview-google-desc-orig">
          <?php echo mb_substr( strip_tags( $post->post_content ?? null ), 0, 300 ); ?>
        </span>
        <span id="tr-seo-preview-google-desc">
          <?php
          $desc = $model->getFieldValue(trim($group . '.basic.description', '') );
          if ( ! empty( $desc ) ) {
              $s  = strip_tags( $desc );
              $dl = mb_strlen( $s );
              echo mb_substr( $s, 0, 300 );
          } else {
              $s  = strip_tags( $post->post_content ?? null );
              $dl = mb_strlen( $s );
              echo mb_substr( $s, 0, 300 );
          }

          if ( $dl > 300 ) {
              echo ' ...';
          }
          ?>
        </span>
        </div>
        </div>
    <?php }
}