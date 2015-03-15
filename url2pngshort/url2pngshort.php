<?php
/* Plugin Name: URL2PNG shortcode
 * Plugin URI: https://github.com/mpcm/wp_url2png
 * Description: Insert URL2PNG screenshots via shortcode
 * Author: Matthew Morley
 * Author URI: http://www.mpcm.com
 * Stable tag: 0.3
 * Version: 0.3
 */

function url2png_imgtag($attrs, $content = null) {
  extract(shortcode_atts(array(
    "width"=>'width',
    "link"=>'link'
  ), $attrs));

  $options = array();
  if( isset( $width ) ) {
    $options['thumbnail_max_width'] = 500;
  }

  $src = url2png_v6($content, $options);
  $output = "<img class='wp_url2png' src=\"${src}\" />";
  if( isset( $link ) ) {
    $output = "<A href=\"$content\">$output</A>";
  }
  return $output;
}

add_shortcode("screenshot", "url2png_imgtag");
if( is_admin() ) {
  new URL2PNGSettings();
}

function url2png_v6($url, $args) {
  # Get your apikey from http://url2png.com/plans
  $options = get_option( 'url2png_option_name', Array() );
  if( isset($options['apikey']) ){
    $URL2PNG_APIKEY = $options['apikey'];
  }
  if( isset($options['secret']) ){
    $URL2PNG_SECRET = $options['secret'];
  }

  # urlencode request target
  $options['url'] = urlencode($url);
  $options += $args;

  # create the query string based on the options
  foreach($options as $key => $value) { $_parts[] = "$key=$value"; }

  # create a token from the ENTIRE query string
  $query_string = implode("&", $_parts);
  $TOKEN = md5($query_string . $URL2PNG_SECRET);

  return "http://beta.url2png.com/v6/$URL2PNG_APIKEY/$TOKEN/png/?$query_string";
}


class URL2PNGSettings
{
    private $options;

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    public function add_plugin_page() {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin',
            'URL2PNG Shortcode',
            'manage_options',
            'url2png-shortcode-settings',
            array( $this, 'create_admin_page' )
        );
    }

    public function create_admin_page() {
        // Set class property
        $this->options = get_option( 'url2png_option_name' );
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2>URL2PNG Shortcode settings</h2>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'url2png_option_group' );
                do_settings_sections( 'url2png-shortcode-settings' );
                submit_button();
            ?>
            </form>
            <h3><A href="https://www.url2png.com/dashboard/#account">Find your apikey and secret</A></h3>
            <h3><a href="https://www.url2png.com/plans">Not yet a customer</A></h3>
        </div>
        <?php
    }

    public function page_init()
    {
        register_setting('url2png_option_group', 'url2png_option_name', array( $this, 'sanitize' ) );

        add_settings_section(
            'setting_section_id',
            'URL2PNG API Key',
            array( $this, 'print_section_info' ),
            'url2png-shortcode-settings'
        );

        add_settings_field(
            'apikey',
            'API Key',
            array( $this, 'apikey_callback' ),
            'url2png-shortcode-settings',
            'setting_section_id'
        );

        add_settings_field(
            'secret',
            'Secret',
            array( $this, 'secret_callback' ),
            'url2png-shortcode-settings',
            'setting_section_id'
        );
    }

    public function sanitize( $input ) {
        $new_input = array();
        if( isset( $input['apikey'] ) ) {
            $new_input['apikey'] = sanitize_text_field( $input['apikey'] );
        }
        if( isset( $input['secret'] ) ) {
            $new_input['secret'] = sanitize_text_field( $input['secret'] );
        }
        return $new_input;
    }

    public function print_section_info() {
        print 'Enter your settings below:';
    }

    public function apikey_callback() {
        printf(
            '<input type="text" id="apikey" name="url2png_option_name[apikey]" value="%s" />',
            isset( $this->options['apikey'] ) ? esc_attr( $this->options['apikey']) : ''
        );
    }

    public function secret_callback() {
        printf(
            '<input type="text" id="secret" name="url2png_option_name[secret]" value="%s" />',
            isset( $this->options['secret'] ) ? esc_attr( $this->options['secret']) : ''
        );
    }
}
