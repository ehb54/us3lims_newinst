<?php
/*
 * get_meta_info.php
 *
 * Include file to get and process some common user information
 *  This is used in two files --- request_new_instance.php
 *  and edit_metadata.php
 *
 */


// Stored HTML-encoded and SQL-escaped. meta_value() (lib/utility.php) refuses, in $message,
// a value that the readers would change, instead of storing it changed.
$message = "";
$institution       = addslashes( meta_value( $_POST['institution'],       'institution',                 255, $message ) );
$inst_abbrev       = addslashes( meta_value( $_POST['inst_abbrev'],       'institution abbreviation',     10, $message ) );
$admin_fname       = addslashes( meta_value( $_POST['admin_fname'],       'administrator first name',     30, $message ) );
$admin_lname       = addslashes( meta_value( $_POST['admin_lname'],       'administrator last name',      30, $message ) );
$admin_email       = addslashes( meta_value( $_POST['admin_email'],       'administrator email address',  63, $message ) );
$lab_name          = addslashes( meta_value( $_POST['lab_name'],          'facility name',                 0, $message ) );
$lab_contact       = addslashes( meta_value( $_POST['lab_contact'],       'facility contact info',         0, $message, true ) );
$location          = addslashes( meta_value( $_POST['location'],          'location info',               255, $message ) );
$instrument_name   = addslashes( meta_value( $_POST['instrument_name'],   'instrument name',               0, $message ) );
$instrument_serial = addslashes( meta_value( $_POST['instrument_serial'], 'instrument serial number',      0, $message ) );

// Let's do some error checking first of all
// -- most fields are required
if ( empty($institution) )
  $message .= "--institution is missing<br />";

if ( empty($inst_abbrev) )
  $message .= "--institution abbreviation is missing<br />";

if ( empty($admin_fname) )
  $message .= "--administrator first name is missing<br />";

if ( empty($admin_lname) )
  $message .= "--administrator last name is missing<br />";

if ( empty($admin_email) )
  $message .= "--administrator email address is missing<br />";

if (! emailsyntax_is_valid($admin_email) )
{
  $message .= "--administrator email is not a valid email address<br />";
  $admin_email = '';
}

if ( empty($lab_name) )
  $message .= "--facility name is missing<br />";

if ( empty($lab_contact) )
  $message .= "--facility contact info is missing<br />";

if ( empty($location) )
  $message .= "--location info is missing<br />";

if ( empty($instrument_name) )
  $message .= "--instrument name is missing<br />";

if ( empty($instrument_serial) )
  $message .= "--instrument serial number is missing<br />";
?>
