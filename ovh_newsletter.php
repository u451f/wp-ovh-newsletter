<?php
/*
Plugin Name: OVH newsletter
Description: Subscriptioni OVH newsletters
Version: 0.1
Author: u
Author URI: http://curlybracket.net
License: GPL2
*/
/*
    Copyright 2016  Ulrike Uhlig (email : u@curlybracket.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
?>
<?php
/* Plugin l10n */
function ovh_newsletter_init() {
	 $plugin_dir = basename(dirname(__FILE__));
	 load_plugin_textdomain( 'ovh-newsletter', false, "$plugin_dir/languages" );
}
add_action('plugins_loaded', 'ovh_newsletter_init');

/************************************************
Abonnement à la newsletter OVH
*************************************************/

function sendAdminMail($email, $returnValue) {
	$options = get_option('ovh_newsletter_option_name');
    $to = $options['ml-admin'];
	$headers = 'From: '.$options['ml-name'].' <no-reply@'.$options['ml-domain'].'>' . "\r\n" .
			   'X-Mailer: PHP/' . phpversion();
	$originatingIP = $_SERVER['REMOTE_ADDR'];
	$subject  = __("New subscriber on ", 'ovh-newsletter'). $options['ml-domain'];
	$message  = __('Newsletter subscription from ', 'ovh-newsletter');
	$message .= "$email\n\n";
	$message .= __("Success or Error: ", 'ovh-newsletter')."$returnValue\n\n";
	$message .= __("IP: ") . $originatingIP;
	mail($to, $subject, $message, $headers);
}

/* Create a hash using salt. */
function create_hash($email) {
	$options = get_option('ovh_newsletter_option_name');
    $salt = $options['ml-salt'];
	$hash = md5($email.$salt);
	return $hash;
}

// create a shortcode which will insert a form [ovh_newsletter]
function ovh_newsletter_shortcode() {
	$process = plugins_url('', __FILE__).'/process.php';
	$form = '<form name="ovh_newsletter_form" class="form ovh-newsletter-form" method="post" autocomplete="on" action="'.$process.'">';
	$form .= '<input class="form mail" name="mail" value="" type="email" placeholder="E-Mail" validate /><input type="submit" class="submit" name="subscribe" value="'.__("Subscribe", "ovh-newsletter").'" />';
	$form .= '</form>';
	return $form;
}
add_shortcode( 'ovh_newsletter', 'ovh_newsletter_shortcode' );

// call javascript
function ovh_newsletter_scripts() {
	wp_register_script( 'process', plugins_url('', __FILE__).'/process.js', 0, 0, true );
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'process' );
}
add_action( 'wp_enqueue_scripts', 'ovh_newsletter_scripts' );

// Create configuration page for wp-admin. Each domain shall configure their ml-name, ml-domain, HOST and results page.
class ovhNewsletterSettingsPage {
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page() {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin',
            'OVH Newsletter Settings',
            'manage_options',
            'ovh-newsletter-settings',
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page() {
        // Set class property
        $this->options = get_option( 'ovh_newsletter_option_name' );
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2><?php _e('Settings OVH Newsletter'); ?></h2>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'ovh_newsletter_option_group' );
                do_settings_sections( 'ovh-newsletter-settings' );
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init() {
        register_setting(
            'ovh_newsletter_option_group', // Option group
            'ovh_newsletter_option_name', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'ovh_newsletter_section_general', // ID
            'OVH Newsletter Settings', // Title
            array( $this, 'print_section_info' ), // Callback
            'ovh-newsletter-settings' // Page
        );
        add_settings_field(
            'ml-name',
            'ML name',
            array( $this, 'ml_name_callback' ),
            'ovh-newsletter-settings',
            'ovh_newsletter_section_general'
        );
        add_settings_field(
            'ml-domain',
            'ML Domain',
            array( $this, 'ml_domain_callback' ),
            'ovh-newsletter-settings',
            'ovh_newsletter_section_general'
        );
        add_settings_field(
            'ml-salt',
            'Salt',
            array( $this, 'ml_salt_callback' ),
            'ovh-newsletter-settings',
            'ovh_newsletter_section_general'
        );
        add_settings_field(
            'ml-ovh-login',
            'Login OVH',
            array( $this, 'ml_ovh_login_callback' ),
            'ovh-newsletter-settings',
            'ovh_newsletter_section_general'
        );
        add_settings_field(
            'ml-ovh-password',
            'Password OVH',
            array( $this, 'ml_ovh_password_callback' ),
            'ovh-newsletter-settings',
            'ovh_newsletter_section_general'
        );
        add_settings_field(
            'ml-admin',
            'Admin email for moderation',
            array( $this, 'ml_admin_callback' ),
            'ovh-newsletter-settings',
            'ovh_newsletter_section_general'
        );

    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input ) {

    if( !empty( $input['ml-admin'] ) )
        $input['ml-admin'] = sanitize_email( $input['ml-admin'] );
    if( !empty( $input['ml-name'] ) )
        $input['ml-name'] = sanitize_text_field( $input['ml-name'] );
    if( !empty( $input['ml-domain'] ) )
        $input['ml-domain'] = sanitize_text_field( $input['ml-domain'] );
    if( !empty( $input['ml-salt'] ) )
        $input['ml-salt'] = sanitize_text_field( $input['ml-salt'] );
    if( !empty( $input['ml-ovh-login'] ) )
        $input['ml-ovh-login'] = sanitize_text_field( $input['ml-ovh-login'] );
    if( !empty( $input['ml-ovh-password'] ) )
        $input['ml-ovh-password'] = sanitize_text_field( $input['ml-ovh-password'] );
        return $input;
    }

    /**
     * Print the Section text
     */

    public function print_section_info() {
        print _e('Please fill in the corresponding fields.');
    }

    /**
     * Get the settings option array and print one of its values
     */

    public function ml_domain_callback() {
        printf(
            '<input type="text" id="ml-domain" name="ovh_newsletter_option_name[ml-domain]" value="%s" class="regular-text ltr" required />',
            esc_attr( $this->options['ml-domain'])
        );
    }

    public function ml_name_callback() {
        printf(
            '<input type="text" id="ml-name" name="ovh_newsletter_option_name[ml-name]" value="%s" class="regular-text ltr" required />',
            esc_attr( $this->options['ml-name'])
        );
    }

    public function ml_salt_callback() {
        printf(
            '<input type="text" id="ml-salt" name="ovh_newsletter_option_name[ml-salt]" value="%s" class="regular-text ltr" required />',
            esc_attr( $this->options['ml-salt'])
        );
    }

    public function ml_admin_callback() {
        printf(
            '<input type="text" id="ml-admin" name="ovh_newsletter_option_name[ml-admin]" value="%s" class="regular-text ltr" required />',
            esc_attr( $this->options['ml-admin'])
        );
    }

    public function ml_ovh_login_callback() {
        printf(
            '<input type="text" id="ml-ovh-login" name="ovh_newsletter_option_name[ml-ovh-login]" value="%s" class="regular-text ltr" required />',
            esc_attr( $this->options['ml-ovh-login'])
        );
    }

    public function ml_ovh_password_callback() {
        printf(
            '<input type="password" id="ml-ovh-password" name="ovh_newsletter_option_name[ml-ovh-password]" value="%s" class="regular-text ltr" required />',
            esc_attr( $this->options['ml-ovh-password'])
        );
    }
}

if( is_admin() )
    $ovh_newsletter_settings_page = new ovhNewsletterSettingsPage();
