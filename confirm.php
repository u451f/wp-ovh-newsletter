<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once($_SERVER['DOCUMENT_ROOT'].'/wp-blog-header.php');
/*
@subscribe
This will be executed once the subscriber has clicked our link in the confirmation email.
Documentation: http://www.ovh.com/soapi/fr/?method=mailingListSubscriberAdd
*/
function subscribe($email) {
	$email = sanitize_email($email);
	$options = get_option('ovh_newsletter_option_name');
    $MLname = $options['ml-name'];
    $domain = $options['ml-domain'];
    $MLuser = $options['ml-ovh-login'];
    $MLpass = $options['ml-ovh-password'];
    if (validEmail($email) === true) {
        try {
            $soap = new SoapClient("https://www.ovh.com/soapi/soapi-re-1.44.wsdl");
            $session = $soap->login($MLuser, $MLpass, "fr", false);
            $soap->mailingListSubscriberAdd($session, $domain, $MLname, $email);
			_e("Subscription successful.", 'ovh-newsletter');
            sendAdminMail($email, "Success");
            $soap->logout($session);
        } catch(SoapFault $fault) {
			_e("Subscription failed.", 'ovh-newsletter');
            sendAdminMail($email, $fault);
        }
    }
	echo '<br /><a href="'.get_bloginfo('url').'">'.__('Back to ', 'ovh-newsletter').get_bloginfo('name').'</a>';
}

/* Create a hash with salt and verify against the $_GET values from the subscription page.*/
function verify_hash($email, $hash) {
	$options = get_option('ovh_newsletter_option_name');
    $salt = $options['ml-salt'];
	$saltyhash = md5($email.$salt);
	if ($saltyhash == $hash) {
		return TRUE;
	} else {
		_e("Error : This e-mail address appears to be incorrect.", 'ovh-newsletter');
		return FALSE;
	}
}

/**
Validate an email address.
Provide email address (raw input)
Returns true if the email address has the email
address format and the domain exists.
*/
function validEmail($email) {
   $isValid = true;
   $atIndex = strrpos($email, "@");
   if (is_bool($atIndex) && !$atIndex) {
      $isValid = false;
   } else {
      $domain = substr($email, $atIndex+1);
      $local = substr($email, 0, $atIndex);
      $localLen = strlen($local);
      $domainLen = strlen($domain);
      if ($localLen < 1 || $localLen > 64) {
         // local part length exceeded
         $isValid = false;
      } else if ($domainLen < 1 || $domainLen > 255) {
         // domain part length exceeded
         $isValid = false;
      } else if ($local[0] == '.' || $local[$localLen-1] == '.') {
         // local part starts or ends with '.'
         $isValid = false;
      } else if (preg_match('/\\.\\./', $local)) {
         // local part has two consecutive dots
         $isValid = false;
      } else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
         // character not valid in domain part
         $isValid = false;
      } else if (preg_match('/\\.\\./', $domain)) {
         // domain part has two consecutive dots
         $isValid = false;
      } else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$local))) {
         // character not valid in local part unless
         // local part is quoted
         if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\","",$local))) {
            $isValid = false;
         }
      }
      if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))) {
         // domain not found in DNS
         $isValid = false;
      }
   }
   return $isValid;
}

// Launch verification and if ok, then subscribe the email to the list.
if(isset($_GET['hash']) && isset($_GET['email'])) {
	$email = sanitize_email($_GET['email']);
	$hash = sanitize_text_field($_GET['hash']);
	if(verify_hash($email, $hash)) {
		subscribe($email);
	}
}
?>
