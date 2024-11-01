<?php

class TapChat {

    public $dbCustomerTable,$dbCustomerRequestTable;

    public function __construct() {
        global $wpdb;
        $this->_wpdb = $wpdb;
        $this->dbCustomerRequestTable = $this->_wpdb->prefix.'tapchat_customer_request';
        $this->dbCustomerTable = $this->_wpdb->prefix.'tapchat_customer';
        $this->options = get_option( 'tapchat_data' );
        $this->init();
        require_once( TAPCHAT_PLUGIN_DIR. "admin/Walker_PageDropdown_Multiple_tapchat.php");
        require_once( TAPCHAT_PLUGIN_DIR. "includes/class-tapchat-api.php"); 
        if(is_admin()):
            require_once( TAPCHAT_PLUGIN_DIR. "admin/tapchatAdmin.php"); 
        else:
            require_once( TAPCHAT_PLUGIN_DIR. "public/tapchatPublic.php"); 
        endif;
    }

    public function init(){
        add_action('init', array($this,'tapchat_custom_permalink'));
        add_filter('query_vars', array($this,'tapchat_custom_request'),10,1);
        
        add_action( 'wp_ajax_tapchat_request', array( $this, 'tapchat_request' ) );
		add_action( 'wp_ajax_tapchat_customer_status', array( $this, 'tapchat_customer_status' ) );
		add_action( 'wp_ajax_tapchat_leave_customer', array( $this, 'tapchat_leave_customer' ) );
		add_action( 'wp_ajax_tapchat_join_operator', array( $this, 'tapchat_join_operator' ) );
        
        
        add_action( 'wp_ajax_nopriv_tapchat_request', array( $this, 'tapchat_request' ) );
        add_action( 'wp_ajax_nopriv_tapchat_customer_status', array( $this, 'tapchat_customer_status' ) );
		add_action( 'wp_ajax_nopriv_tapchat_leave_customer', array( $this, 'tapchat_leave_customer' ) );
    }
    
    public function tapchat_custom_permalink(){
        $page_slug = 'tc-voice_text'; // slug of the page you want to be shown to
        $param     = 'tc-cid';       // param name you want to handle on the page

        add_rewrite_rule('tc-voice_text/?([^/]*)', 'index.php?pagename=' . $page_slug . '&' . $param . '=$matches[1]', 'top');
    }
    
    public function tapchat_custom_request($qvars){
        $qvars[] = 'tc-cid';
    	return $qvars;
    }

    private function get_operator_id($request){
        $operator_id = false;
        if(!empty($this->options['tapchat_admin'])):
            $operator_id = $this->options['tapchat_admin'];
        endif;
        return apply_filters('tapchat_set_operator_id',$operator_id,$request);
    }

    function tapchat_request() {
        if ( ! wp_verify_nonce( $_POST['tapchatn'], 'tapchat-nonce' ) ) {
            die ( 'Busted!');
        }
        $response = array('status'=>'error','message'=>'Something went wrong Please try again.');
        $operator_id = $this->get_operator_id($_POST);
        if($operator_id):
            $data = array();
            if(is_user_logged_in()):
                $current_user = wp_get_current_user();
                if($operator_id == $current_user->ID):
                    $response = array('status'=>'error','message'=>'cannot go live with your own Account.');
                else:
                    $data['full_name'] = $current_user->display_name;
                    $data['email'] = $current_user->user_email;
                    $data['phone'] = $current_user->billing_phone??"";
                    $data['wp_id'] = $current_user->ID;
                endif;
            else:
                if(sanitize_email($_POST['email']) && sanitize_text_field($_POST['full_name']) && sanitize_text_field($_POST['phone'])){
                    
                    $data['email'] = sanitize_email($_POST['email']);
                    $data['full_name'] = sanitize_text_field($_POST['full_name']);
                    $data['phone'] = sanitize_text_field($_POST['phone']);
                    $data['wp_id'] = 0;
                } else{
                    $response = array('status'=>'error','message'=>'Invalid Data.');
                }
            endif;

            if(!empty($data)):
                $tapchat_customer_id = $this->_wpdb->get_var("SELECT id FROM $this->dbCustomerTable WHERE email = '{$data['email']}' LIMIT 1");
                if($tapchat_customer_id){
                    $guest_id = $tapchat_customer_id;
                } else {
                    $format = array('%s','%s','%d','%d');
                    $this->_wpdb->insert($this->dbCustomerTable,$data,$format);
                    $guest_id = $this->_wpdb->insert_id;
                }
            endif;
            
            if(!empty($guest_id)){
                $new_req = true;
                $error = "";
                setcookie('_tapchat_user', $guest_id, time() + (86400 * 30), "/");
                $fetch_user_request = $this->_wpdb->get_row("SELECT id,request_status,updated_at FROM $this->dbCustomerRequestTable WHERE request_status = 'pending' and customer_id = '{$guest_id}' and operator_id = $operator_id");
                $data = array('customer_id' => $guest_id,'operator_id' => $operator_id,'request_status'=>'pending');
                $format = array('%d','%d','%s');
                $send_notification = false;
                if(empty($fetch_user_request)):
                    $fetch_user_request = $this->_wpdb->get_row("SELECT id,request_status,updated_at FROM $this->dbCustomerRequestTable WHERE request_status = 'started' and customer_id = '{$guest_id}' and operator_id = $operator_id");
                    if(!empty($fetch_user_request)):
                        $new_req = false;
                        $error = "Already On Call";
                    endif;
                else:
                    if(time()-strtotime($fetch_user_request->updated_at)>180):
                        $where = array('customer_id' => $guest_id,'operator_id' => $operator_id,'request_status' => 'pending');
                        $update_data = array("request_status"=>"missed");
                        $this->_wpdb->update($this->dbCustomerRequestTable, $update_data, $where);
                    else:
                        $error = "Waiting for Operator to respond your request.";
                        $new_req = false;
                    endif;
                endif;
                if($new_req):
                    $send_notification = true;
                    $this->_wpdb->insert($this->dbCustomerRequestTable, $data, $format);
                    $req_id = $this->_wpdb->insert_id;
                endif;
                if(!empty($error)){
                    $response = array('status'=>'error','message'=>$error);
                }else {
                    $response = array('status'=>'success','caller_req_id'=>$req_id,'message'=>'Waiting for Operator to respond your request.');
                }
                if($send_notification){
                    // push Notification
                    $device_token = get_user_meta($operator_id,'_tap_chat_oprator_token',true);
                    if($device_token){
                        $api_id = $this->options['api_key'];
                        $args = array(
                            'headers' => array('Accept' => 'application/vnd.hmrc.1.0+json',
                                                'Authorization' => 'Bearer '.$api_id
                            ),
                            'body'        => array(
                                'token' => $device_token,
                            ),
                        );
                        $resp = wp_remote_post( 'https://dashboard.tapchat.me/api/call/send-push-notification', $args );
    
                        $data = json_decode(wp_remote_retrieve_body($resp));

                        $response["notification_status"] = $data;
                        
                    }
                }
            }
        endif;
		wp_send_json($response);
		wp_die();
    }

    public function get_tapchat_requests(){
        $operator_id = get_current_user_id();

        if(!$operator_id){
            wp_send_json(array('status'=>false,'message'=>"Invalid Operator Request!"));exit;
        }

        $query ="SELECT CU.*, CR.id as req_id,CR.customer_id,CR.updated_at, CR.request_status from {$this->dbCustomerRequestTable} CR JOIN $this->dbCustomerTable CU on CR.customer_id=CU.id  where CR.operator_id = $operator_id";

        $rows = $this->_wpdb->get_results($query);

        return $rows;
    }

    function tapchat_customer_status() {
        if ( ! wp_verify_nonce( $_POST['tapchatn'], 'tapchat-nonce' ) ) {
            die ( 'Busted!');
        }
        $error = false;
        $response = array('status'=>false);
        $operator_id = $this->get_operator_id($_POST);
        if($operator_id):
            if(isset($_COOKIE['_tapchat_user'])):
                $user_id = sanitize_text_field($_COOKIE['_tapchat_user']);
            else:
                $error = true;
                $response = array('status'=>false,'message'=>'Unable to find your Request. Please submit again!');
            endif;
        else:
            $error = true;
            $response = array('status'=>false,'message'=>'Operator not Active!');
        endif;
        
        if(!$error):
            $fetch_user_request = $this->_wpdb->get_row("SELECT id,request_status FROM $this->dbCustomerRequestTable WHERE user_id = '{$user_id}' and operator_id = $operator_id", ARRAY_A);
            if(!empty($fetch_user_request)):
                if($fetch_user_request['request_status']=='pending'):
                    $response = array('status'=>'pending','message'=>'Waiting for vendor to respond your request.');
                elseif($fetch_user_request['request_status']=='started'):
                    $response = array('status'=>'started','message'=>'');
                elseif($fetch_user_request['request_status']=='declined'):
                    $response = array('status'=>'declined','message'=>'Admin is busy. Unable to answer.');
                endif;
            endif;
        endif;
		
		wp_send_json($response);
		wp_die();
    }
    
    function tapchat_leave_customer() {
        if ( ! wp_verify_nonce( $_POST['tapchatn'], 'tapchat-nonce' ) ) {
            die ( 'Busted!');
        }
        $response = array('status'=>false);
        $operator_id = $this->get_operator_id($_POST);
        if($operator_id):
            if(isset($_POST['participant_id'])):
                $user_id = sanitize_text_field($_POST['participant_id']);
            elseif(isset($_COOKIE['_tapchat_user'])):
                $user_id = sanitize_text_field($_COOKIE['_tapchat_user']);
            endif;
            if(!empty($user_id)):
                $fetch_user_request = $this->_wpdb->get_row("SELECT id,request_status FROM $this->dbCustomerRequestTable WHERE customer_id = $user_id and operator_id = $operator_id and request_status in ('started','pending')");
                $where = array('customer_id' => $user_id,'operator_id' => $operator_id,'request_status'=>$fetch_user_request->request_status);
                if($fetch_user_request->request_status=='started'):
                    $data = array("request_status"=>"completed");
                else:
                    $data = array('request_status'=>'missed');
                endif;
                $response = array('status'=>true);
                $this->_wpdb->update($this->dbCustomerRequestTable, $data, $where);
            endif;
        endif;
        wp_send_json($response);
		wp_die();
    }

    function tapchat_join_operator() {
        if ( ! wp_verify_nonce( $_POST['tapchatn'], 'tapchat-nonce' ) ) {
            $response = array('status'=>false,'error'=>'Something went wrong.');
        }
        $response = array('status'=>false,'error'=>'please try again.');
        $operator_id = $this->get_operator_id($_POST);
        if($operator_id):
            if(isset($_POST['participant_id'])):
                $room_data = $this->get_room_url();
                $user_id = sanitize_text_field($_POST['participant_id']);
                $where = array('customer_id' => $user_id,'operator_id' => $operator_id);
                if($room_data){
                    $data = array("request_status"=>"started");
                    $response = array('status'=>true,'url'=>$room_data->url,'token'=>$room_data->token);
                } else {
                    $data = array("request_status"=>"failed");
                    $response = array('status'=>false,'error'=>"Unable to create room please check you API key.");
                }
                $this->_wpdb->update($this->dbCustomerRequestTable, $data, $where);
            endif;
        endif;
        wp_send_json($response);
		wp_die();
    }

    private function get_room_url(){
        $api_id = $this->options['api_key'];
        $args = array(
            'headers' => array('Accept' => 'application/vnd.hmrc.1.0+json',
                                'Authorization' => 'Bearer '.$api_id
                        ),
        );
        $response = wp_remote_get( 'https://dashboard.tapchat.me/api/call/create-room', $args );

        $data = json_decode(wp_remote_retrieve_body($response));

        if(isset($data->url)){
            return $data;
        } else {
            return false;
        }
    }
}