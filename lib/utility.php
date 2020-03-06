<?php
/*
 * utility.php
 *
 * A place to store a few common functions
 *
 */

function emailsyntax_is_valid($email)
{
  if ( strpos( $email, "@" ) === false ) return FALSE;

  list($local, $domain) = explode("@", $email);

  $pattern_local  = '/^([0-9a-zA-Z]*([-|_]?[0-9a-zA-Z]+)*)' .
                    '(([-|_]?)\.([-|_]?)[0-9a-zA-Z]*([-|_]?[0-9a-zA-Z]+)+)*([-|_]?)$/';

  $pattern_domain = '/^([0-9a-zA-Z]+([-]?[0-9a-zA-Z]+)*)' .
                    '(([-]?)\.([-]?)[0-9a-zA-Z]*([-]?[0-9a-zA-Z]+)+)*\.[a-z]{2,4}$/';

  $match_local  = preg_match($pattern_local, $local);
  $match_domain = preg_match($pattern_domain, $domain);

  if ( $match_local && $match_domain )
  {
    return TRUE;
  }

  return FALSE;
}

// Random Password generator. 
// http://www.phpfreaks.com/quickcode/Random_Password_Generator/56.php
function makeRandomPassword() {
  $salt = "abchefghjkmnpqrstuvwxyz0123456789";
  srand( (double)microtime() * 1234567 ); 
  $i    = 0;
  $pass = '';
  while ( $i <= 7 ) 
  {
    $num  = rand() % 33;
    $tmp  = substr($salt, $num, 1);
    $pass = $pass . $tmp;
    $i++;
  }
  return $pass;
}

// Function to email login information to the administrator
function email_login_info( $metadataID )
{
  $query  = "SELECT admin_fname, admin_lname, dbname, dbhost, limshost, " .
            "admin_email AS email, admin_pw, " .
            "secure_user, secure_pw " .
            "FROM metadata " .
            "WHERE metadataID = $metadataID ";
  $result = mysql_query($query) 
            or die("Query failed : $query<br />\n" . mysql_error());

  list( $fname,
        $lname,
        $new_dbname,
        $new_dbhost,
        $new_limshost,
        $email,
        $admin_pw,
        $new_secureuser,
        $new_securepw )   = mysql_fetch_array( $result );

  $hints = <<<TEXT
Database Setup Information

DB Connection Name: $new_secureuser
DB Password:        $new_securepw
Database Name:      $new_dbname
Host Address:       $new_dbhost


Admin Investigator Setup Information
Investigator Email:    $email
Investigator Password: $admin_pw

LIMS Setup
URL:                http://$new_limshost/$new_dbname
TEXT;

  // Mail the user

  global $org_name, $admin_email;

  $subject = "Your UltraScan database account";

  $message = "Dear $fname $lname,
  Your UltraScan database has been set up. Information for accessing
  it is as follows:
      
  $hints

  Please save this message for your reference.
  Thanks!
  The $org_name Admins.

  This is an automated email, do not reply!";

  $now = time();
  $headers = "From: $org_name Admin<$admin_email>"     . "\n";

  // Set the reply address
  $headers .= "Reply-To: $org_name<$admin_email>"      . "\n";
  $headers .= "Return-Path: $org_name<$admin_email>"   . "\n";

  // Try to avoid spam filters
  $headers .= "Message-ID: <" . $now . "info@" . $_SERVER['SERVER_NAME'] . ">\n";
  $headers .= "X-Mailer: PHP v" . phpversion()         . "\n";
  $headers .= "MIME-Version: 1.0"                      . "\n";
  $headers .= "Content-Transfer-Encoding: 8bit"        . "\n";

  mail($email, $subject, $message, $headers);

  echo "<p>The email has been sent.</p>\n" ;
}

/**
 * Generates a Universally Unique IDentifier, version 4.
 *
 * RFC 4122 (http://www.ietf.org/rfc/rfc4122.txt) defines a special type of Globally
 * Unique IDentifiers (GUID), as well as several methods for producing them. One
 * such method, described in section 4.4, is based on truly random or pseudo-random
 * number generators, and is therefore implementable in a language like PHP.
 *
 * We choose to produce pseudo-random numbers with the Mersenne Twister, and to always
 * limit single generated numbers to 16 bits (ie. the decimal value 65535). That is
 * because, even on 32-bit systems, PHP's RAND_MAX will often be the maximum *signed*
 * value, with only the equivalent of 31 significant bits. Producing two 16-bit random
 * numbers to make up a 32-bit one is less efficient, but guarantees that all 32 bits
 * are random.
 *
 * The algorithm for version 4 UUIDs (ie. those based on random number generators)
 * states that all 128 bits separated into the various fields (32 bits, 16 bits, 16 bits,
 * 8 bits and 8 bits, 48 bits) should be random, except : (a) the version number should
 * be the last 4 bits in the 3rd field, and (b) bits 6 and 7 of the 4th field should
 * be 01. We try to conform to that definition as efficiently as possible, generating
 * smaller values where possible, and minimizing the number of base conversions.
 *
 * @copyright   Copyright (c) CFD Labs, 2006. This function may be used freely for
 *              any purpose ; it is distributed without any form of warranty whatsoever.
 * @author      David Holmes <dholmes@cfdsoftware.net>
 *
 * @return  string  A UUID, made up of 32 hex digits and 4 hyphens.
 */

function uuid() {
   
    // The field names refer to RFC 4122 section 4.1.2

    return sprintf('%04x%04x-%04x-%03x4-%04x-%04x%04x%04x',
        mt_rand(0, 65535), mt_rand(0, 65535), // 32 bits for "time_low"
        mt_rand(0, 65535), // 16 bits for "time_mid"
        mt_rand(0, 4095),  // 12 bits before the 0100 of (version) 4 for "time_hi_and_version"
        bindec(substr_replace(sprintf('%016b', mt_rand(0, 65535)), '01', 6, 2)),
            // 8 bits, the last two of which (positions 6 and 7) are 01, for "clk_seq_hi_res"
            // (hence, the 2nd hex digit after the 3rd hyphen can only be 1, 5, 9 or d)
            // 8 bits for "clk_seq_low"
        mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535) // 48 bits for "node" 
    ); 
}

?>
