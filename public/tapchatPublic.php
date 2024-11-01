<?php 

class TapChatFrontend {

	protected $options;

	public function __construct() {
       	$this->options = get_option( 'tapchat_data' );
       	$this->_init();
    }

    public function _init(){
    	add_action( 'wp_enqueue_scripts', array($this, 'enqueue_style') );
    	add_filter('template_redirect', array($this,'tapchat_custom_template'),10,1);
    	add_shortcode( 'live_streaming', array($this,'_show_go_live') );
		add_action( 'wp_footer', array($this,'_show_go_live_pages'), 10 );
		add_action( 'wp_enqueue_scripts', array($this, 'enqueue_scripts') );
    }

    public function enqueue_style() {
    	wp_enqueue_style( "tapchat-font-awesome", "//cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/all.min.css", array(), 0.01, 'all' ); 
		wp_enqueue_style( "tapchat-public", plugin_dir_url( __DIR__ ) . 'assets/css/tapchat.css', array(), 0.01, 'all' ); 
    }

    public function enqueue_scripts() {
		wp_enqueue_script( 'tapchat_socket_script', 'https://cdn.socket.io/4.5.0/socket.io.min.js', array(), '1.0' );
		wp_register_script( 'tapchat-public', plugin_dir_url( __FILE__ ) . 'assets/js/tapchat-public.min.js', array( 'jquery' ), '0.01', true );
    	$localize_data = apply_filters('tapchat_localize',array('customer'=>$this->_get_log_user_detail() ,'ajax_url'=>admin_url( 'admin-ajax.php' ),'tapchatn' =>wp_create_nonce('tapchat-nonce')));
        wp_localize_script( 'tapchat-public', 'tapchat_data',$localize_data );
		wp_enqueue_script( 'tapchat-public' );
    }

    public function _get_log_user_detail(){
    	if( is_user_logged_in()){
    		$current_user = wp_get_current_user();
    		$data = array('name'=>$current_user->data->display_name,'email'=>$current_user->data->user_email);
    	}
    	else{
    		$data = array('name'=>'','email'=>'');
    	}
		return $data;
    }
    
    public function tapchat_custom_template(){
        if (get_query_var('tc-cid')) {
            $gateway = sanitize_text_field( get_query_var('tc-cid') );
            if ( file_exists( dirname( __FILE__ ) . '/templates/tapchat.php') ) {
				require(dirname( __FILE__ ) . '/templates/tapchat.php');
			} else {
                _e("Something went wrong");
            }
        }
    }

    public function _show_go_live($args){
		
    	if(!empty($this->options['tapchat_admin'])){
			ob_start();
			include dirname( __FILE__ ) . '/templates/tapchat_icon.php';
	    	return ob_get_clean();
	    } else {
	    	return 'Admin Setting Pending.';
	    }
    }

	public function _show_go_live_pages()
	{
		$show_tapchat = false;
		if(!is_page()){
			return false;
		}
		
		$include_to = isset( $this->options['tapchat_include_pages'] ) ?  $this->options['tapchat_include_pages'] : array();
		$include_to = array_map( 'esc_attr', $include_to );

		$show_all_pages = isset( $this->options['tapchat_all_pages'] ) ? esc_attr( $this->options['tapchat_all_pages']) : '';
		if(!empty($show_all_pages)){
			$exclude_from = isset( $this->options['tapchat_exclude_pages'] ) ? $this->options['tapchat_exclude_pages'] : array();
			$exclude_from = array_map( 'esc_attr', $exclude_from );
		}
		
		if(!empty($include_to) && in_array(get_the_ID(),$include_to)){
			$show_tapchat = true;
		}
		if(!empty($show_all_pages)){
			$show_tapchat = true;
			
			if(!empty($exclude_from) && in_array(get_the_ID(),$exclude_from)){
				$show_tapchat = false;
			}
		}
		if($show_tapchat && !has_shortcode( get_the_content(get_the_ID()), 'live_streaming') ){
			
			if(!empty($this->options['tapchat_admin'])){
				
				ob_start();
				include dirname( __FILE__ ) . '/templates/tapchat_icon.php';
				$output = ob_get_clean();
			} else {
				$output = 'Admin Setting Pending.';
			}
			_e($output);
		}
	}
}

new TapChatFrontend;