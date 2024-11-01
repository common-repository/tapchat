<?php

/**
 * Fired during plugin activation
 *
 * @link       https://tapchat.mer/
 * @since      1.0.0
 *
 * @package    TapChat
 * @subpackage TapChat/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    TapChat
 * @subpackage TapChat/includes
 * @author     Phillip Dane <info@tapchat.me>
 */
class TapChat_API {

	protected $apiKey;
    private $options;

    public function __construct() {
        global $wpdb;
        $this->_wpdb = $wpdb;
        $this->dbCustomerRequestTable = $this->_wpdb->prefix.'tapchat_customer_request';
        $this->dbCustomerTable = $this->_wpdb->prefix.'tapchat_customer';
		$this->options = get_option( 'tapchat_data' );
        $this->userId = '';
        $this->routes_init();
    }

    public function routes_init(){   
        add_action('rest_api_init', array($this,'register_custom_rest_routes'));
    }
    
    public function register_custom_rest_routes(){

		register_rest_route('tapchat', '/login/', array(
            'methods'  => 'POST',
            'callback' => array($this,'operator_login'),
            'permission_callback'=>function() { return ''; }
        ));
        
        register_rest_route('tapchat', '/create-call-session/', array(
            'methods' => 'POST',
            'callback' => array($this,'tapchat_accept_call_request'),
            'permission_callback'=>function() { return ''; }
        ));
        
        register_rest_route('tapchat', '/get-call-history/', array(
            'methods'  => 'POST',
            'callback' => array($this,'get_call_history'),
            'permission_callback'=>function() { return ''; }
        ));
        
		register_rest_route('tapchat', '/end-call-session/', array(
            'methods'  => 'POST',
            'callback' => array($this,'disconnect_call_session'),
            'permission_callback'=>function() { return ''; }
        ));

        register_rest_route('tapchat', '/save-push-token/', array(
            'methods'  => 'POST',
            'callback' => array($this,'savePushToken'),
            'permission_callback'=>function() { return ''; }
        ));

        register_rest_route('tapchat', '/decline-call/', array(
            'methods'  => 'POST',
            'callback' => array($this,'decline_call'),
            'permission_callback'=>function() { return ''; }
        ));

        register_rest_route('tapchat', '/get-customer-info/', array(
            'methods'  => 'POST',
            'callback' => array($this,'getCustomerInfo'),
            'permission_callback'=>function() { return ''; }
        ));

        register_rest_route('tapchat', '/send-notification/', array(
            'methods'  => 'POST',
            'callback' => array($this,'send_notification'),
            'permission_callback'=>function() { return ''; }
        ));

        register_rest_route('tapchat', '/settings/', array(
            'methods'  => 'POST',
            'callback' => array($this,'app_setting'),
            'permission_callback'=>function() { return ''; }
        ));
        
    }
    
    function operator_login($request){
        $creds = array();
        $creds['user_login'] = $request["username"];
        $creds['user_password'] =  $request["password"];
        $creds['remember'] = true;
        $user = wp_signon( $creds, false );
        if ( is_wp_error($user) ) {
            wp_send_json(array("code"=>'invalid_creds','message'=>'Incorrect username or password.'),401);
            exit;
        } else {
            $device_token =  $request["device_token"];
            $api_key = get_user_meta($user->ID,'tap_chat_api_access',true);
            if(empty($api_key)){
                $api_key = bin2hex(random_bytes(32));
                update_user_meta($user->ID, 'tap_chat_api_access', $api_key);
            }
            if(!empty($device_token)){
                update_user_meta($this->userId,'_tap_chat_oprator_token',$device_token);
            }
        }
        
        $user->data->avtar = get_avatar_url( $user->data->ID );
        unset($user->data->user_pass);
        //$user->data->notification_setting = get_user_meta($user->ID,'app-notification',true);
        $user->data->access_token = $api_key;
        return $user->data;
    }
    
    public function tapchat_accept_call_request($request){

		$response = array('code'=>"auth_error",'message'=>"Fail to authanticate.");
		if(empty($_SERVER['HTTP_TC_ACCESS_TOKEN'])){
		    wp_send_json($response,401);
		    exit;
		}
        $this->get_tapchat_admin($request);
        if($this->userId):
            $api_key = get_user_meta($this->userId,'tap_chat_api_access',true);
            if($api_key != $_SERVER['HTTP_TC_ACCESS_TOKEN']){
                wp_send_json($response,401);
		        exit;
            }
            if(isset($request['request_id'])):
                $request_id = $request['request_id'];
				$query= $this->_wpdb->prepare( "SELECT * FROM $this->dbCustomerRequestTable WHERE id = %d and operator_id = %d", array($request_id,$this->userId) );
				$row = $this->_wpdb->get_row($query);
				if(!empty($row)){
				    if($row->request_status=="pending"){
				        $room_data = $this->get_room_url();
    					$where = array('id' => $request_id,'operator_id' => $this->userId);
    					if($room_data){
    						$data = array("request_status"=>"started");
    						return array('url'=>$room_data->url,'token'=>$room_data->token);
    					} else {
    						$data = array("request_status"=>"failed");
    						$response = array('data'=>array('code'=>'api_error','message'=>"Unable to create room please check your API key."),'status'=>'422');
    					}
    					$this->_wpdb->update($this->dbCustomerRequestTable, $data, $where);
				    } else {
				        $response = array('data'=>array('code'=>"call_status",'message'=>"Call requested already in status - {$row->request_status}"));
				    }
				} else {
				    $response = array('data'=>array('code'=>"invalid_data",'message'=>"Request id not available."),'status'=>'400');
				}
			else:
			    $response = array('data'=>array('code'=>"missing_param",'message'=>"Required Parameter missing."),'status'=>'400');
            endif;
        else:
            $response = array('data'=>array('code'=>"missing_param",'message'=>"Required Parameter missing."),'status'=>'400');
        endif;
        wp_send_json($response['data'],$response['status']??200);
		wp_die();
    }
    
    public function get_call_history($request){
        
        $response = array('code'=>"auth_error",'message'=>"Fail to authanticate.");
		if(empty($_SERVER['HTTP_TC_ACCESS_TOKEN'])){
		    wp_send_json($response);
		    exit;
		}
		
        $this->get_tapchat_admin($request);
        if(!$this->userId){
            wp_send_json(array('code'=>'invalid_request','message'=>"Invalid Operator Request!"));
            exit;
        }
        $api_key = get_user_meta($this->userId,'tap_chat_api_access',true);
        if($api_key != $_SERVER['HTTP_TC_ACCESS_TOKEN']){
            wp_send_json($response);
	        exit;
        }
        $userData = array();
        $status = $request['status'];
        $query ="SELECT id,customer_id, updated_at, request_status from {$this->dbCustomerRequestTable} where operator_id = $this->userId and request_status='$status'";

        $rows = $this->_wpdb->get_results($query);

        if(!empty($rows) && count($rows) > 0){
            foreach($rows as $key => $value){
                $user_info = $this->get_tapchat_userdata($value->customer_id);
                $data['full_name'] = $user_info->full_name;
                $data['customer_id'] = $value->customer_id;
                $data['request_id'] = $value->id;
                $data['requested_at'] = $value->updated_at;
                $data['status'] = $value->request_status;
                $data['image'] = get_avatar_url(  $user_info->wp_id );
                $userData[] = $data;
            }
            wp_send_json(array('requests'=>$userData));
            exit;
        }else{
            wp_send_json(array('code'=>'no_data','message'=>'No one here yet, but they are coming.','requests'=>[]));
            exit;
        }
    }
    
    public function disconnect_call_session($request){

        $response = array('code'=>"auth_error",'message'=>"Fail to authanticate.");
		if(empty($_SERVER['HTTP_TC_ACCESS_TOKEN'])){
		    wp_send_json($response);
		    exit;
		}
		
        $this->get_tapchat_admin($request);
        if(!$this->userId){
            wp_send_json(array('code'=>'invalid_request','message'=>"Invalid Operator Request!"));
            exit;
        } else {
            $api_key = get_user_meta($this->userId,'tap_chat_api_access',true);
            if($api_key != $_SERVER['HTTP_TC_ACCESS_TOKEN']){
                wp_send_json($response);
    	        exit;
            }
			if(isset($request['request_id'])){
				$query= $this->_wpdb->prepare( "SELECT * FROM $this->dbCustomerRequestTable WHERE id = %d and operator_id = %d", array($request['request_id'],$this->userId) );
				$row = $this->_wpdb->get_row($query);
				if(!empty($row)){
				    if($row->request_status=="started"){
    					$where = array('id' => $request['request_id'],'operator_id' => $this->userId);
						$data = array("request_status"=>"completed");
						$response = array('code'=>"call_ended",'message'=>"Call Disconnected!");
    					$this->_wpdb->update($this->dbCustomerRequestTable, $data, $where);
				    } else {
				        $response = array('code'=>"call_status_fail",'message'=>"Call requested already in status - {$row->request_status}");
				    }
				} else {
					$response = array('code'=>"invalid_request",'message'=>"Fail to update call status!");
				}
			} else {
				$response = array('code'=>"missing_param",'message'=>"Request ID not available.");
			}
		}
		wp_send_json($response);
		exit;
    }

    public function getCustomerInfo($request){
        $response = array('code'=>"auth_error",'message'=>"Fail to authanticate.");
		if(empty($_SERVER['HTTP_TC_ACCESS_TOKEN'])){
		    wp_send_json($response);
		    exit;
		}
		
        $customerId = $request['customer_id'];
        $user_info = $this->get_tapchat_userdata($customerId);
        $data['user_name'] = $user_info->full_name;
        $data['image'] = get_avatar_url( $user_info->wp_id );
        return $data;
    }

    private function get_tapchat_admin($request){
		$user_id = false;
        if(isset($request['operator_id']) && $request['operator_id']==$this->options['tapchat_admin']){
            $user_id = $this->options['tapchat_admin'];
        }
        $this->userId = apply_filters('tapchat_set_admin',$user_id,$request);
    }
    
    private function get_tapchat_userdata($customer_id){
        $query= $this->_wpdb->prepare( "SELECT full_name,email,phone,wp_id FROM $this->dbCustomerTable WHERE id = %d", array($customer_id) );
		$row = $this->_wpdb->get_row($query);
		if(empty($row)){
		   $row =  array('full_name'=>'No Name');
		}
		return (object)$row;
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

    public function decline_call($request){

        $response = array('code'=>"auth_error",'message'=>"Fail to authanticate.");
		if(empty($_SERVER['HTTP_TC_ACCESS_TOKEN'])){
		    wp_send_json($response);
		    exit;
		}
		
        $this->get_tapchat_admin($request);
        if(!$this->userId){
            wp_send_json(array('code'=>'invalid_request','message'=>"Invalid Operator Request!"));
            exit;
        }
        
        $api_key = get_user_meta($this->userId,'tap_chat_api_access',true);
        if($api_key != $_SERVER['HTTP_TC_ACCESS_TOKEN']){
            wp_send_json($response);
	        exit;
        }
        if(isset($request['request_id'])):
            $request_id = $request['request_id'];
			$query= $this->_wpdb->prepare( "SELECT * FROM $this->dbCustomerRequestTable WHERE id = %d and operator_id = %d", array($request_id,$this->userId) );
			$row = $this->_wpdb->get_row($query);
			if(!empty($row)){
			    if($row->request_status=="pending"){
					$where = array('id' => $request_id,'operator_id' => $this->userId);
					$data = array("request_status"=>"declined");
					$response = array('code'=>'call_declined','message'=>"Call request declined");
					$this->_wpdb->update($this->dbCustomerRequestTable, $data, $where);
			    } else {
			        $response = array('code'=>"call_status",'message'=>"Call requested already in status - {$row->request_status}");
			    }
			} else {
			    $response = array('code'=>"invalid_data",'message'=>"Request id not available.");
			}
		else:
		    $response = array('code'=>"missing_param",'message'=>"Required Parameter missing.");
        endif;
        wp_send_json($response);
		exit;
    }

    function savePushToken($request){
        $response = array('code'=>"auth_error",'message'=>"Fail to authanticate.");
		if(empty($_SERVER['HTTP_TC_ACCESS_TOKEN'])){
		    wp_send_json($response);
		    exit;
		}
		
        $this->get_tapchat_admin($request);
        if(!$this->userId){
            wp_send_json(array('code'=>'invalid_request','message'=>"Invalid Operator Request!"));
            exit;
        }

        $deviceSerial = $request['deviceToken'];
        
        if(!empty($deviceSerial)){
            update_user_meta($this->userId,'_tap_chat_oprator_token',$deviceSerial);
            $response = array('message'=>"Token saved.");
        }else{
            $response = array('code'=>"missing_param",'message'=>"Required Parameter missing.");
        }
        wp_send_json($response);
        exit;
    }

    /***
     * ToDo: For Vinay
     * Please use postman and send request to
     * https://www.theoddmarket.com/wp-json/go-live/send-message';
     * Method: POST
     * Data : user_id,vendor_id
     **/

    public function send_notification($request){
        if(isset($request['vendor_id']))
            $request['seller_id'] = $request['vendor_id'];

        unset($request['vendor_id']);

        $operator_id = $this->get_face_2_face_admin($request);

        if(!$operator_id){
            wp_send_json(array('status'=>'error','message'=>"Invalid Operator Request!"));exit;
        }

        $userId = $request['user_id'];
        if(is_numeric($userId)):
            $user_info = get_userdata($userId);
            $member_phone_number=get_user_meta( $userId,'phone_number',true );
            $member_email=$user_info->user_email;
        else:
            $guest_info= str_replace('wp','',$userId);
            $f2f_guest_id =  str_replace('f2fn','',$guest_info);
            $user_data = $this->_wpdb->get_row("SELECT phone,email FROM $this->dbGuestTable WHERE id = {$f2f_guest_id} LIMIT 1");
            $member_phone_number=$user_data['phone'];
            $member_email=$user_data['email'];
        endif;
        $content = "Sorry we missed you earlier but we are available to call now. Please visit ".get_site_url()." and Click tapchats.";
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail( $member_email, 'Operator is online', $content,$headers  );
        
        // //UM()->Messaging_API()->api()->create_conversation( $userId , $vendorId );
        //$senderid = Joy_Of_Text_Plugin()->settings->get_smsprovider_settings('jot-smssenderid');
        //$data = Joy_Of_Text_Plugin()->currentsmsprovider->send_smsmessage($member_phone_number, $_POST['content'],$senderid);
        wp_send_json(array("status"=>'message-sent','message'=>'User Has Been Informed by sms and site message.'));
        exit;
    }

    public function app_setting($request){
        $vendorId = $request['vendor_id'];
        $value = $request['value'];
        update_user_meta( $vendorId, 'app-notification', $value);
        if($value){
            wp_send_json(array('message' => 'Push notification has been enabled !'));
        }else{
            wp_send_json(array('message'=>'Push notification has been disabled !'));
        }
        exit;
    }
}

new TapChat_API;