<?php

class TapChatAdmin {

    private $options;

    public function __construct() {
        global $wpdb;
        $this->_wpdb = $wpdb;
        $this->init();
        $this->options = get_option( 'tapchat_data' );
    }

    public function init(){
        add_action( 'admin_menu', array($this,'tapchatMenu') );
        add_action( 'admin_init', array($this ,'tapchatSettings' ));
        add_filter( 'wp_dropdown_pages', array($this ,'tapchatMultiPage' ),10,3 );
        add_action( 'admin_enqueue_scripts', array($this,'tapchatEnqueueScripts') );
    }

    public function tapchatMenu(){
        add_menu_page(
            __( 'TapchatWp', 'tapchat' ),
            'TapchatWp',
            'manage_options',
            'tapchat',
            array($this ,'tapchatAdminPage'),
            TAPCHAT_PLUGIN_URL.'/assets/images/icon.png',
            6
        );
        add_submenu_page( 'tapchat', __( 'TapchatWp', 'tapchat' ), __( 'TapchatWp', 'tapchat' ),
    'manage_options', 'tapchat');
        add_submenu_page( 'tapchat', 'Call History', 'Call History',
    'manage_options', 'tapchat-call',array($this ,'tapchatAdminCallPage'));
    }

    public function tapchatAdminPage(){ ?>
        <div class="wrap">
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'tapchat_settings' );
                do_settings_sections( 'face-2-face-admin' );
                submit_button();
            ?>
            </form>
            <table class="form-table">
                <tr>
                    <th scope="row">Shortcode</th>
                    <td>
                    <input type="text" readonly value="[live_streaming]" onClick="this.select();">
                    </td>
                </tr>
            </table>
        </div>
      <?php  
    }

    public function tapchatAdminCallPage(){
        ob_start();
        include dirname( __FILE__ ) . '/templates/tapchat-call-history.php';
        echo ob_get_clean();
    }

    function tapchatSettings(){
        
        register_setting(
            'tapchat_settings', 
            'tapchat_data' 
        );

        add_settings_section(
            'tapchat_section', 
            'Tapchat Settings', 
            array( $this, 'tapchatSectionCallback' ),
            'face-2-face-admin'
        );  

        add_settings_field(
            'api_key',
            'API KEY', 
            array( $this, 'apiKeyCallback' ),
            'face-2-face-admin', 
            'tapchat_section'          
        );      

        add_settings_section(
            'tapchat_option_section', 
            '', 
            array( $this, 'adminSectionCallback' ),
            'face-2-face-admin'
        );  

        add_settings_field(
            'tapchat_admin',
            'Choose Operator', 
            array( $this, 'tapchatAdminCallback' ),
            'face-2-face-admin', 
            'tapchat_option_section'          
        ); 
        add_settings_field(
            'tapchat_include_pages',
            'Include on pages', 
            array( $this, 'tapchatIncludePageCallback' ),
            'face-2-face-admin', 
            'tapchat_option_section'          
        ); 
        add_settings_field(
            'tapchat_all_pages',
            'Show on all pages', 
            array( $this, 'tapchatAllPageCallback' ),
            'face-2-face-admin', 
            'tapchat_option_section'          
        ); 
        add_settings_field(
            'tapchat_exclude_pages',
            'Exclude on pages', 
            array( $this, 'tapchatExcludePageCallback' ),
            'face-2-face-admin', 
            'tapchat_option_section'          
        ); 
        
    }

    public function tapchatEnqueueScripts($hook)
    {
        if ( 'toplevel_page_tapchat' != $hook  && 'tapchatwp_page_tapchat-call' != $hook) {
            return;
        }
        $user = wp_get_current_user();
        wp_enqueue_style( "tapchat", plugin_dir_url( __DIR__ ) . 'assets/css/tapchat.css', array(), 0.01, 'all' ); 
        wp_enqueue_style( 'tapchat_admin_s2select_styles', plugin_dir_url( __FILE__ ) . 'css/select2.min.css');
        wp_enqueue_script( 'tapchat_admin_socket_script', plugin_dir_url( __FILE__ ) . 'js/socket.io.min.js', array(), '1.0' );
        wp_enqueue_script( 'tapchat_admin_s2select_script', plugin_dir_url( __FILE__ ) . 'js/select2.min.js', array(), '1.0' );
        wp_enqueue_script( 'tapchat_admin_script', plugin_dir_url( __FILE__ ) . 'js/tapchat-admin.min.js', array(), '1.0' );
        $localize_data = array('ajax_url'=>admin_url( 'admin-ajax.php' ),'user'=>$user->data->display_name,'tapchatn' =>wp_create_nonce('tapchat-nonce'));
        wp_localize_script( 'tapchat_admin_script', 'tapchat_data',$localize_data );
    }

    public function adminSectionCallback(){
        _e('<hr/><h4>Admin Setting</h4><hr/>');
    }

    public function tapchatSectionCallback(){
        _e('<hr/><h4>Tapchat Api Key</h4><hr/>');
    }

    public function apiKeyCallback(){
        printf(
            '<input type="text" id="api_key" name="tapchat_data[api_key]" value="%s" />',
            isset( $this->options['api_key'] ) ? esc_attr( $this->options['api_key']) : ''
        );
    }

    public function tapchatAdminCallback(){
        $selected = isset( $this->options['tapchat_admin'] ) ? esc_attr( $this->options['tapchat_admin']) : 0;
        $args = array('name'=>'tapchat_data[tapchat_admin]','id'=>'tapchat_admin','role'=>'administrator','selected'=>$selected,'show_option_none'=>'Select User');
        wp_dropdown_users( $args);
        
    }

    public function tapchatIncludePageCallback(){
        $selected = isset( $this->options['tapchat_include_pages'] ) ? (array) $this->options['tapchat_include_pages'] : array();
        $selected = array_map( 'esc_attr', $selected );
        $args = array('name'=>'tapchat_data[tapchat_include_pages][]','multiselect'=>'true','walker' => new Walker_PageDropdown_Multiple_Tapchat(),'id'=>'tapchat_include_pages','selected'=>$selected,'show_option_none'=>'Select Pages to show tapchat');
        wp_dropdown_pages( $args);
        printf(
            '<i>doesn\'t work when all page option is checked</i>'
        );
    }

    public function tapchatAllPageCallback(){
        printf(
            '<input type="checkbox" id="tapchat_all_pages" name="tapchat_data[tapchat_all_pages]" value="1" %s />',
            (isset( $this->options['tapchat_all_pages']) && $this->options['tapchat_all_pages']==1) ? esc_attr( 'checked="checked"') : ''
        );
    }

    public function tapchatExcludePageCallback(){
        $selected = isset( $this->options['tapchat_exclude_pages'] ) ? (array) $this->options['tapchat_exclude_pages'] : array();
        $selected = array_map( 'esc_attr', $selected );
        $args = array('name'=>'tapchat_data[tapchat_exclude_pages][]','multiselect'=>'true','walker' => new Walker_PageDropdown_Multiple_tapchat(),'id'=>'tapchat_exclude_pages','selected'=>$selected,'show_option_none'=>'Select Pages to show tapchat');
        wp_dropdown_pages( $args);
        printf(
            '<i>only work with all page option</i>'
        );
    }

    public function tapchatMultiPage($output, $parsed_args, $pages){
        if(!empty($parsed_args['multiselect'])){
            $output =  str_replace( '<select ', '<select multiple="multiple" ', $output );
        }
        return $output;
    }
}
new TapChatAdmin;