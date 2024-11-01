<?php
/**
* Plugin Name: TapChat
* Plugin URI: https://tapchat.me/
* Description: This pluin provide an integration between tapchat Mobile App
* Version: 1.0.3
* Stable tag: 1.0.3
* Author: Phillip Dane
* Author URI: https://www.linkedin.com/in/phillipdane/
* WP tested up to: 6.0
**/

global $TapChat;
define("TAPCHAT_PLUGIN_URL",plugin_dir_url( __FILE__ ));
define("TAPCHAT_PLUGIN_DIR",plugin_dir_path( __FILE__ ));

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

add_action('plugins_loaded', 'tapchat_init', 0);

function tapchat_init(){
    global $TapChat;
    require_once( TAPCHAT_PLUGIN_DIR. "includes/class-tapchat.php");
    $TapChat = new TapChat;
}
/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-tapchat-activator.php
 */

function activate_f2f_cart() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-tapchat-activator.php';
    TapChat_Activator::activate();
}

register_activation_hook( __FILE__, 'activate_f2f_cart' );