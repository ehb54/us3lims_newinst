<?php
/*
 * utility.php
 *
 * A place to store a few common functions
 *
 */

// The us3 account's home, without a shell (SELinux blocks exec() for httpd)
function us3_home()
{
  $entry = function_exists( 'posix_getpwnam' ) ? posix_getpwnam( 'us3' ) : false;
  return $entry ? $entry[ 'dir' ] : '/home/us3';
}

// A new instance's $site_author: newinst's own, set by the site; the placeholder while that is unset.
function instance_site_author( $configured )
{
  $configured = trim( (string) $configured );
  return ( $configured === '' || stripos( $configured, 'CHANGE-ME' ) === 0 )
         ? 'CHANGE-ME: site contact name and affiliation' : $configured;
}

function emailsyntax_is_valid($email)
{
  return filter_var( $email, FILTER_VALIDATE_EMAIL ) !== false;
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
function email_login_info( $metadataID, $link )
{
  $query  = "SELECT admin_fname, admin_lname, dbname, dbhost, limshost, " .
            "admin_email AS email, admin_pw, " .
            "secure_user, secure_pw " .
            "FROM metadata " .
            "WHERE metadataID = $metadataID ";
  $result = mysqli_query( $link, $query ) 
            or die("Query failed : $query<br />\n" . mysqli_error($link));

  list( $fname,
        $lname,
        $new_dbname,
        $new_dbhost,
        $new_limshost,
        $email,
        $admin_pw,
        $new_secureuser,
        $new_securepw )   = mysqli_fetch_array( $result );

  // A plain-text mail: the names and password as typed, not HTML-encoded
  $fname    = meta_text( $fname );
  $lname    = meta_text( $lname );
  $admin_pw = meta_text( $admin_pw );

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
URL:                https://$new_limshost/$new_dbname
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
  $headers .= "Content-Type: text/plain; charset=UTF-8" . "\n";
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

// Metadata values are stored HTML-encoded (htmlentities, ENT_COMPAT | ENT_HTML401), and every
// reader strips slashes and decodes them. These keep a value as it was typed through that.

// The value to store (HTML-encoded, not yet SQL-escaped) for what was typed. When the readers
// would change it, this adds to $message instead of storing it changed: a backslash (stripped),
// a control character (a line break would reach the generated scripts; line breaks and tabs
// are allowed where $multiline) or a stored form longer than $max (cut). $max 0: no limit.
function meta_value( $typed, $label, $max, &$message, $multiline = false )
{
  $typed = trim( (string) $typed );
  if ( strpos( $typed, '\\' ) !== false )
    $message .= "--$label cannot contain a backslash.<br />";

  if ( preg_match( $multiline ? '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/' : '/[\x00-\x1F\x7F]/', $typed ) )
    $message .= "--$label cannot contain "
              . ( $multiline ? 'control characters' : 'line breaks or other control characters' ) . ".<br />";

  $stored = htmlentities( $typed, ENT_COMPAT | ENT_HTML401 );
  if ( $max > 0 && strlen( $stored ) > $max )
    $message .= "--$label is too long: at most $max characters, where each &amp; &quot; &lt; &gt;"
              . " or accented letter counts as several.<br />";

  return $stored;
}

// A stored value as the readers see it: slashes stripped, HTML decoded. This is the plain
// text for the hints file and the email.
function meta_text( $stored )
{
  return html_entity_decode( stripslashes( (string) $stored ), ENT_COMPAT | ENT_HTML401 );
}

// A stored value for an HTML attribute or element: meta_text(), then escaped, so that
// ' " & < > show, and an edit form resubmits them, as they were typed.
function meta_html( $stored )
{
  return htmlspecialchars( meta_text( $stored ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 );
}

// Text for a comment line in a generated script or grants file: a line break would end the
// comment and run the rest. The institution comes from the public request form.
function comment_text( $text )
{
  return preg_replace( '/[\x00-\x1F\x7F]+/', ' ', (string) $text );
}

?>
