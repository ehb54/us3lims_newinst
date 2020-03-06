<?php
/*
 * get_meta_info.php
 *
 * Include file to get and process some common user information
 *  This is used in two files --- request_new_instance.php
 *  and edit_metadata.php
 *
 */


$institution       = trim(substr(addslashes(htmlentities($_POST['institution'])), 0,255));
$inst_abbrev       = trim(substr(addslashes(htmlentities($_POST['inst_abbrev'])), 0,10));
$admin_fname       = trim(substr(addslashes(htmlentities($_POST['admin_fname'])), 0,30));
$admin_lname       = trim(substr(addslashes(htmlentities($_POST['admin_lname'])), 0,30));
$admin_email       = trim(substr(addslashes(htmlentities($_POST['admin_email'])), 0,63));
$lab_name          = trim(       addslashes(htmlentities($_POST['lab_name'])));
$lab_contact       = trim(       addslashes(htmlentities($_POST['lab_contact'])));
$location          = trim(substr(addslashes(htmlentities($_POST['location'])), 0, 255));
$instrument_name   = trim(       addslashes(htmlentities($_POST['instrument_name'])));
$instrument_serial = trim(       addslashes(htmlentities($_POST['instrument_serial'])));

// Let's do some error checking first of all
// -- most fields are required
$message = "";
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
