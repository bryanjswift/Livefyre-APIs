<?php

define('LFTOKEN_MAX_AGE', 86400);

class Livefyre_Token {
    static function from_user($user, $max_age=LFTOKEN_MAX_AGE) {
        $secret = $user->get_domain()->get_key();
        $args = array('auth', $user->get_domain()->get_host(), $user->get_uid());

        
        $data = lftokenCreateData(gmdate('c'), $max_age, $args);
        $data = 'lftoken,2011-10-12T19:27:19+00:00,86400,auth,ssosandbox.fyre.co,_u21';
        $value = lftokenCreateToken($data, base64_decode($secret));
        return $value;
    }
}

function getHmacsha1Signature($key, $data) {
        //convert binary hash to BASE64 string
    	return base64_encode(lfhmacsha1($key, $data));
}

// encrypt a base string w/ HMAC-SHA1 algorithm
function lfhmacsha1($key,$data) {
    	$blocksize=64;
    	$hashfunc='sha1';
    	if (strlen($key)>$blocksize) {
            	$key=pack('H*', $hashfunc($key));
    	}
    	$key=str_pad($key,$blocksize,chr(0x00));
    	$ipad=str_repeat(chr(0x36),$blocksize);
    	$opad=str_repeat(chr(0x5c),$blocksize);
    	$hmac = pack( 'H*',$hashfunc( ($key^$opad).pack( 'H*',$hashfunc( ($key^$ipad).$data ) ) ) );
    	return $hmac;
}

function lfxor_these($first, $second) {
    	$results=array();
   for ($i=0; $i < strlen($first); $i++)
   {
            	array_push($results, $first[$i]^$second[$i]);
   }
   return implode($results);
}

function lfhasNoComma($str) {
    	return !preg_match('/\,/', $str);
}

function lftokenCreateData($now, $duration, $args=array()) {
    	//Create the right data input for Livefyre authorization
    	$filtered_args = array_filter($args, 'lfhasNoComma');
    	if (count($filtered_args)==0 or count($args)>count($filtered_args)) {
            	return -1;
    	}

    	array_unshift($filtered_args, "lftoken", $now, $duration);
    	$data=implode(',',$filtered_args);
    	return $data;
}

function lftokenCreateToken($data, $key) {
    	//Create a signed token from data
    	$clientkey = lfhmacsha1($key,"Client Key");
    	$clientkey_sha1 = sha1($clientkey, true);
    	$temp = lfhmacsha1($clientkey_sha1,$data);
    	$sig = lfxor_these($temp,$clientkey);
    	$base64sig = base64_encode($sig);
    	return base64_encode(implode(",",array($data,$base64sig)));
}

function lftokenValidateResponse($data, $response, $key) {
    	//Validate a response from Livefyre
    	$serverkey = lfhmacsha1(base64_decode($key),"Server Key");
    	$temp = lfhmacsha1($serverkey,$data);
    	return ($response == $temp);
}
/*
//Generate a token:
$secret= 'Enter your secret key here';
$args=array('auth', 'Enter your fyre.co domain name here (eg yourdomain.fyre.co)', 'Unique user id of authenticated user from your system');
$data=lftokenCreateData(gmdate('c'), 86400, $args);
$value= lftokenCreateToken($data,base64_decode($secret));
*/
?>