<?php
/**
 * Template Name: Checkout Page Template
 *
 * 
 */
 
//require("/stripe/Stripe.php");v1.5
require( get_stylesheet_directory() . '/stripe/Stripe.php');
require_once(ABSPATH . WPINC . '/registration.php');  
global $wpdb, $user_ID, $reg_errors; 

$reg_errors = new WP_Error; 
$params = array(
  "testmode"   => "on",
  "private_live_key" => "mbtsAtXhvOuDdIBmKIjdsfdfLxHER1MXGj3mp",
  "public_live_key"  => "pk_TXQh9mMv2Jry6wqeQo85dfdfZxEQII9a5",
  "private_test_key" => "sk_test_MRkeT0QMzYkjnCruvsdfsadfVZkUzMM",
  "public_test_key"  => "pk_test_Z4ACb9FmWBeO2clOqidfasdfdsafGO4lB0"
);

if ($params['testmode'] == "on") {
  Stripe::setApiKey($params['private_test_key']);
  $pubkey = $params['public_test_key'];
} else {
  Stripe::setApiKey($params['private_live_key']);
  $pubkey = $params['public_live_key'];
}


if($_POST['stripeToken']!=""){

if($_POST['coupon_code']!='' && strlen($_POST['coupon_code']) > 0) {
          try {
                $coupon = Stripe_Coupon::retrieve($_POST['coupon_code']); //check coupon exists
                if($coupon !== NULL) {
                 $using_discount = true; //set to true our coupon exists or take the coupon id if you wanted to.
                 //$reg_errors->add( 'Coupon ', $using_discount);
                }
                // if we got here, the coupon is valid

             } catch (Exception $e) {
                // an exception was caught, so the code is invalid
                $message = $e->getMessage();
               // returnErrorWithMessage($message);
                $reg_errors->add( 'Coupon error', $message);
             }

}  

/**if ( 4 > strlen($_POST['username']) ) {
    $reg_errors->add( 'username_length', 'Username too short. At least 4 characters is required' );
}
    
if ( username_exists($_POST['username']))
    $reg_errors->add('user_name', 'Sorry, that username already exists!');
    
if ( ! validate_username($_POST['username'])) {
    $reg_errors->add( 'username_invalid', 'Sorry, the username you entered is not valid' );
}**/
    
if ( 5 > strlen($_POST['password'])) {
   $reg_errors->add( 'password', 'Password length must be greater than 5' );
}
    
if (!is_email($_POST['email'])) {
    $reg_errors->add( 'email_invalid', 'Email is not valid' );
}
    
if ( email_exists($_POST['email'])) {
    $reg_errors->add( 'email', 'Email Already in use' );
}


if ( 1 > count( $reg_errors->get_error_messages() ) ) {


        $userdata = array(
        'user_login'    =>   $_POST['email'],
        'user_email'    =>   $_POST['email'],
        'user_pass'     =>   $_POST['password'],       
        'first_name'    =>   $_POST['fname'],
        'last_name'     =>   $_POST['lname'],
        );
        
        $user = wp_insert_user($userdata);
        xprofile_set_field_data('Gender', $user, $_POST['field_2']);
        if($user){
        $description = "Name: ".$_POST['username']."-"."Email: ".$_POST['email']."-"."Name On Card: ".$_POST['usernameoncard'];
       
       try {
            $stripe = Stripe_Customer::create(array(
            'card' => $_POST['stripeToken'],
            'trial_end' => '1522681200',
            'plan' => 'learndash-course-638',
            'email' => strip_tags(trim($_POST['email'])),
            'description' => $description,
            'coupon'=> trim($_POST['coupon_code']),
          )); 
          //print_r($customer->id);  
          //print_r($user);
          //die();    
      add_user_meta($user, 'stripe_customer_id', $stripe->id, true ); //user meta
      add_user_meta($user, 'course_638_access_from',time(), true);   //user meta
      echo wp_new_user_enrollment_notification($user, $plaintext_pass = '' );  // for email send//

      //************************user session***********************//
      $user_session = get_user_by( 'ID',$user);//user session
      if( $user_session ) {    
          wp_set_current_user( $user_session->ID, $user_session->user_login );
          wp_set_auth_cookie( $user_session->ID );
          do_action( 'wp_login', $user_session->user_login );
      }  
      //*****************************************//
      wp_redirect('https://domain.com/?success=201'); exit;   
     
  }
  catch (Exception $e) {
      // redirect on failed payment
    wp_redirect( get_permalink()); exit;
   
  } 
       
//wp_redirect('https://leanbodycoaching.com/checkout/'); exit; 
  }   
}
 
}
