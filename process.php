<?php
function sendConfirmationMail($email) {
	require_once($_SERVER['DOCUMENT_ROOT'].'/wp-blog-header.php');

	$email = sanitize_email($email);

	$options = get_option('ovh_newsletter_option_name');
    $MLname = $options['ml-name'];
    $domain = $options['ml-domain'];
    $siteURL = get_bloginfo('url');
    $MLconfirmURL = plugins_url('', __FILE__).'/confirm.php';

	$originatingIP = $_SERVER['REMOTE_ADDR'];
	$date = date('d/m/Y \à G:i');
	$from = $MLname.'-subscribe@'.$domain;
	$hash = create_hash($email);

	$headers[] = 'From: '."$from\n";
	$headers[] = "Content-Type: text/plain; charset = \"UTF-8\";\n";
	$headers[] = "Content-Transfer-Encoding: 8bit\n";
	$subject = __("Newsletter subscription request", 'ovh-newsletter') . " $domain";
	$message = __("Hello,\n\nThank you for your subscription to our newsletter.\n\nTo confirm your subscription, please click this link:  $MLconfirmURL?hash=$hash&&email=$email. \n\nIf you did not issue this subscription request, you can simply ignore this email.", 'ovh-newsletter');
	$message = wordwrap($message, 70);
	wp_mail($email, $subject, $message, $headers);
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
// this file processes the data,s end an email and sends back JSON
$errors         = array();      // array to hold validation errors
$data           = array();      // array to pass back data
// validate the variables ======================================================
if (empty($_POST['mail'])) {
	$errors['mail'] = __('E-mail address required.', 'ovh-newsletter');
}
if (!validEmail($_POST['mail'])) {
	$errors['mail'] = __('E-mail address invalid.', 'ovh-newsletter');
}
// return a response ===========================================================
// if there are any errors in our errors array, return a success boolean of false
if ( ! empty($errors)) {
	// if there are items in our errors array, return those errors
	$data['success'] = false;
	$data['errors']  = $errors;
} else {
	// show a message of success and provide a true success variable
	$data['success'] = true;
	//$data['message'] = _e('Thank you for your subscription. You will receive a confirmation e-mail shortly.', 'ovh-newsletter');
	$data['message'] = 'Thank you for your subscription. You will receive a confirmation e-mail shortly.';
	sendConfirmationMail($_POST['mail']);
}
// return all our data to an AJAX call
echo json_encode($data);
