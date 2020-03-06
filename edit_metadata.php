<?php
/*
 * edit_metadata.php
 *
 * A place to edit/update/process the metadata table
 *
 */
session_start();

// Are we authorized to view this page?
if ( ! isset($_SESSION['id']) )
{
  header('Location: index.php');
  exit();
} 

if ( ( $_SESSION['userlevel'] != 4 ) &&
     ( $_SESSION['userlevel'] != 5 ) )  // admin and superadmin only
{
  header('Location: index.php');
  exit();
}

include 'config.php';
include 'db.php';
include 'lib/utility.php';

if (isset($_POST['update']))
{
  do_update();
  exit();
}

if ( isset( $_POST['create'] ) )
{
  $metadataID = $_POST['metadataID'];

  // We have to check if all the fields have data before going on.
  $query  = "SELECT institution, inst_abbrev, dbname, dbuser, dbpasswd, dbhost, limshost, " .
            "admin_fname, admin_lname, admin_email, admin_pw, " .
            "lab_name, lab_contact, location, " .
            "instrument_name, instrument_serial, " .
            "status " .
            "FROM metadata " .
            "WHERE metadataID = $metadataID ";
  $result = mysql_query($query) 
            or die("Query failed : $query<br />\n" . mysql_error());

  $row    = mysql_fetch_array($result, MYSQL_ASSOC);

  $message = '';  
  foreach ($row as $key => $value)
  {
    $value = html_entity_decode( stripslashes( nl2br($value) ) );
    if ( empty( $value ) )
      $message .= "--$$key cannot be empty.<br />";
  }

  if ( ! empty( $message ) )
  {
    $_SESSION['message'] = $message;

    header("Location: $_SERVER[PHP_SELF]?edit=$metadataID");
    exit();
  }

  // Ok to go on
  $_SESSION['metadataID'] = $metadataID;

  header('Location: create_instance.php');
  exit();
}

if ( isset($_POST['email_login']) )
{
  // Get the record we need to edit
  if ( isset( $_POST['metadataID'] ) )
  {
    $metadataID = $_POST['metadataID'];
    email_login_info( $metadataID );
    $redirect = "?ID=$metadataID";
    $_SESSION['message'] = 'The email has been sent';
  }

  else
  {
    // How did we get here?
    $_SESSION['message'] = 'There was a problem with the email request.';
    $redirect = "";
  }

  header("Location: {$_SERVER['PHP_SELF']}$redirect");
  exit();
}

// Start displaying page
$page_title = 'Process LIMS Instance Requests';
$js = 'js/edit_metadata.js';
include 'header.php';
include 'lib/selectboxes.php';

?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Process LIMS Instance Requests</h1>
  <!-- Place page content here -->

<?php

if ( isset( $_SESSION['message'] ) )
{
  echo "<p class='message'>The following errors/conditions were noted:</p>\n";
  echo "<p class='message'>{$_SESSION['message']}</p>\n";
  unset( $_SESSION['message'] );
}

// Edit or display a record
if ( isset($_POST['edit']) || isset($_GET['edit']) )
  edit_record();

else if ( isset($_POST['login_info']) )
  login_info();

else
  display_record();

?>
</div>

<?php
include 'footer.php';
exit();

// Function to update the current record
function do_update()
{
  include 'get_meta_info.php';
  $metadataID   =                                     $_POST['metadataID'];
  $dbname       = trim(substr(addslashes(htmlentities($_POST['dbname'])), 0,30));
  $dbuser       = trim(substr(addslashes(htmlentities($_POST['dbuser'])), 0,30));
  $dbpasswd     = trim(substr(addslashes(htmlentities($_POST['dbpasswd'])), 0,30));
  $dbhost       = trim(substr(addslashes(htmlentities($_POST['dbhost'])), 0,30));
  $limshost     = trim(substr(addslashes(htmlentities($_POST['limshost'])), 0,30));
  $admin_pw1    = trim(substr(addslashes(htmlentities($_POST['admin_pw1'])), 0,80));
  $admin_pw2    = trim(substr(addslashes(htmlentities($_POST['admin_pw2'])), 0,80));
  $status       =                                     $_POST['status'];

  if ( empty( $dbname ) )
    $message .= "--db name cannot be empty.<br />";

  if ( empty( $dbuser ) )
    $message .= "--db user name cannot be empty.<br />";

  if ( empty( $dbpasswd ) )
    $message .= "--dbpassword cannot be empty.<br />";

  if ( empty( $dbhost ) )
    $message .= "--db host cannot be empty.<br />";

  if ( empty( $limshost ) )
    $message .= "--lims host cannot be empty.<br />";

  if ( $admin_pw1 != $admin_pw2 )
    $message .= "--administrator passwords do not match.<br />";

  // Check $inst_abbrev, which needs to be unique
  $inst_abbrev = preg_replace( "/ /", "_", $inst_abbrev );
  $query  = "SELECT COUNT(*) FROM metadata " .
            "WHERE inst_abbrev = '$inst_abbrev' " .
            "AND metadataID != $metadataID ";
  $result =  mysql_query($query)
             or die("Query failed : $query<br />\n" . mysql_error());
  list( $count ) = mysql_fetch_array( $result );
  if ( $count > 0 )
    $message .= "--abbreviation $inst_abbrev is already in use.<br />";

  if ( empty( $message ) )
  {
    $admin_pw_text    = ( empty( $admin_pw1 ) ) 
                      ? "" : "admin_pw = '$admin_pw1', ";

    $query = "UPDATE metadata " .
             "SET institution  = '$institution', " .
             "inst_abbrev = '$inst_abbrev', " .
             "dbname  = '$dbname', " .
             "dbuser  = '$dbuser', " .
             "dbpasswd  = '$dbpasswd', " .
             "dbhost  = '$dbhost', " .
             "limshost  = '$limshost', " .
             "admin_fname  = '$admin_fname', " .
             "admin_lname  = '$admin_lname', " .
             "admin_email  = '$admin_email', " .
             $admin_pw_text .
             "lab_name  = '$lab_name', " .
             "lab_contact  = '$lab_contact', " .
             "location = '$location', " .
             "instrument_name  = '$instrument_name', " .
             "instrument_serial  = '$instrument_serial', " .
             "status  = '$status', " .
             "updateTime = NOW() " .
             "WHERE metadataID = $metadataID ";

    mysql_query($query)
      or die("Query failed : $query<br />\n" . mysql_error());

    header("Location: $_SERVER[PHP_SELF]?ID=$metadataID");
  }

  else
  {
    $_SESSION['message'] = $message;

    header("Location: $_SERVER[PHP_SELF]?edit=$metadataID");
  }

}

// Function to display and navigate records
function display_record()
{
  // Find a record to display
  $metadataID = get_id();
  if ($metadataID === false)
    return;

  $query  = "SELECT institution, inst_abbrev, dbname, dbuser, dbpasswd, dbhost, limshost, " .
            "admin_fname, admin_lname, admin_email, " .
            "lab_name, lab_contact, location, " .
            "instrument_name, instrument_serial, " .
            "status " .
            "FROM metadata " .
            "WHERE metadataID = $metadataID ";
  $result = mysql_query($query) 
            or die("Query failed : $query<br />\n" . mysql_error());

  $row    = mysql_fetch_array($result, MYSQL_ASSOC);

  // Create local variables; make sure IE displays empty cells properly
  foreach ($row as $key => $value)
  {
    $$key = (empty($value)) ? "&nbsp;" : html_entity_decode( stripslashes( nl2br($value) ) );
  }

  global $request_status;               // From lib/selectboxes.php
  $status = $request_status[ $status ];

echo<<<HTML
  <form action="{$_SERVER['PHP_SELF']}" method='post'>
  <table cellspacing='0' cellpadding='10' class='style1'>
    <thead>
      <tr><th colspan='8'>Process LIMS Instance Requests</th></tr>
    </thead>
    <tfoot>
      <tr><td colspan='2'><input type='submit' name='edit' value='Edit' />
                          <input type='submit' name='create' value='Create Instance' />
                          <input type='submit' name='login_info' value='Display Login Info' />
                          <input type='submit' name='email_login' value='Email Login Info' />
                          <input type='hidden' name='metadataID' value='$metadataID' />
          </td></tr>
    </tfoot>
    <tbody>
      <tr><th colspan='2'>Information about the Database</th></tr>
      <tr><th>Institution:</th>
          <td>$institution</td></tr>
      <tr><th>Institution Abbreviation:</th>
          <td>$inst_abbrev</td></tr>
      <tr><th>Database Name:</th>
          <td>$dbname</td></tr>
      <tr><th>The LIMS Username:</th>
          <td>$dbuser</td></tr>
      <tr><th>The LIMS Password:</th>
          <td>$dbpasswd</td></tr>
      <tr><th>The DB Server Name:</th>
          <td>$dbhost</td></tr>
      <tr><th>The LIMS Server Name:</th>
          <td>$limshost</td></tr>
      <tr><th colspan='2'>Information about the Administrator</th></tr>
      <tr><th>First Name:</th>
          <td>$admin_fname</td></tr>
      <tr><th>Last Name:</th>
          <td>$admin_lname</td></tr>
      <tr><th>Email:</th>
          <td>$admin_email</td></tr>
      <tr><th colspan='2'>Information about the Facility</th></tr>
      <tr><th>The Facility Name:</th>
          <td>$lab_name</td></tr>
      <tr><th>Facility Contact Information:</th>
          <td>$lab_contact</td></tr>
      <tr><th>Brief location of the facility:</th>
          <td>$location</td></tr>
      <tr><th colspan='2'>Information about the AUC Instrument</th></tr>
      <tr><th>The name of the AUC Instrument:</th>
          <td>$instrument_name</td></tr>
      <tr><th>The Instrument Serial #:</th>
          <td>$instrument_serial</td></tr>
      <tr><th>Status:</th>
          <td>$status</td></tr>
    </tbody>
  </table>
  </form>

HTML;
}

// Function to figure out which record to display
function get_id()
{
  // See if we are being directed to a particular record
  if (isset($_GET['ID']))
    return( $_GET['ID'] );

  // We don't know which record, so just find the first one
  $query  = "SELECT metadataID FROM metadata " .
            "ORDER BY updateTime DESC " .
            "LIMIT 1 ";
  $result = mysql_query($query)
      or die("Query failed : $query<br />\n" . mysql_error());

  if (mysql_num_rows($result) == 1)
  {
    list($metadataID) = mysql_fetch_array($result);
    return( $metadataID );
  }

  // If we're here, there aren't any records
echo<<<HTML
  <form action='{$_SERVER[PHP_SELF]}' method='post'>
  <table cellspacing='0' cellpadding='10' class='style1'>
    <thead>
      <tr><th colspan='2'>Process LIMS Instance Requests</th></tr>
    </thead>
    <tfoot>
      <tr><td colspan='2'><input type='submit' name='new' value='New' />
          </td></tr>
    </tfoot>
    <tbody>
      <tr><th>Status:</th>
          <td>There are no records to display</td></tr>
    </tbody>
  </table>
  </form>

HTML;

  return( false );
}

// Function to edit a record
function edit_record()
{
  // Get the record we need to edit
  if ( isset( $_POST['edit'] ) )
    $metadataID = $_POST['metadataID'];

  else if ( isset( $_GET['edit'] ) )
    $metadataID = $_GET['edit'];

  else
  {
    // How did we get here?
    echo "<p>There was a problem with the edit request.</p>\n";
    return;
  }

  $query  = "SELECT institution, inst_abbrev, dbname, dbuser, dbpasswd, dbhost, limshost, " .
            "admin_fname, admin_lname, admin_email, " .
            "lab_name, lab_contact, location, " .
            "instrument_name, instrument_serial, " .
            "status " .
            "FROM metadata " .
            "WHERE metadataID = $metadataID ";
  $result = mysql_query($query) 
            or die("Query failed : $query<br />\n" . mysql_error());

  $row = mysql_fetch_array($result);


  $institution         = html_entity_decode( stripslashes( $row['institution'] ) );
  $inst_abbrev         = html_entity_decode( stripslashes( $row['inst_abbrev'] ) );
  $dbname              = html_entity_decode( stripslashes( $row['dbname'] ) );
  $dbuser              = html_entity_decode( stripslashes( $row['dbuser'] ) );
  $dbpasswd            = html_entity_decode( stripslashes( $row['dbpasswd'] ) );
  $dbhost              = html_entity_decode( stripslashes( $row['dbhost'] ) );
  $limshost            = html_entity_decode( stripslashes( $row['limshost'] ) );
  $admin_fname         = html_entity_decode( stripslashes( $row['admin_fname'] ) );
  $admin_lname         = html_entity_decode( stripslashes( $row['admin_lname'] ) );
  $admin_email         = html_entity_decode( stripslashes( $row['admin_email'] ) );
  $lab_name            = html_entity_decode( stripslashes( $row['lab_name'] ) );
  $lab_contact         = html_entity_decode( stripslashes( $row['lab_contact'] ) );
  $location            = html_entity_decode( stripslashes( $row['location'] ) );
  $instrument_name     = html_entity_decode( stripslashes( $row['instrument_name'] ) );
  $instrument_serial   = html_entity_decode( stripslashes( $row['instrument_serial'] ) );
  $status              =                                   $row['status'];


  $status      = $row['status'];
  $status_text = request_status_select( 'status', $status );

echo<<<HTML
  <form action="{$_SERVER['PHP_SELF']}" method="post">
  <table cellspacing='0' cellpadding='10' class='style1'>
    <thead>
      <tr><th colspan='8'>Process LIMS Instance Requests</th></tr>
    </thead>
    <tfoot>
      <tr><td colspan='2'><input type='submit' name='update' value='Update' />
                          <input type='hidden' name='metadataID' value='$metadataID' />
                          <input type='reset' /></td></tr>
    </tfoot>
    <tbody>

    <tr><th colspan='2'>Information about the Database</th></tr>
    <tr><th>Institution:</th>
        <td><input type='text' name='institution' size='40'
                   maxlength='255' value='$institution' /></td></tr>
    <tr><th>Short Abbreviation for the Institution (10 chars max):</th>
        <td><input type='text' name='inst_abbrev' size='40'
                   maxlength='10' value='$inst_abbrev' /></td></tr>
    <tr><th>Database Name:</th>
        <td><input type='text' name='dbname' size='40'
                   maxlength='30' value='$dbname' /></td></tr>
    <tr><th>The LIMS Username:</th>
        <td><input type='text' name='dbuser' size='40'
                   maxlength='30' value='$dbuser' /></td></tr>
    <tr><th>The LIMS Password:</th>
        <td><input type='text' name='dbpasswd' size='40'
                   maxlength='30' value='$dbpasswd' /></td></tr>
    <tr><th>The DB Server Name:</th>
        <td><input type='text' name='dbhost' size='40'
                   maxlength='30' value='$dbhost' /></td></tr>
    <tr><th>The LIMS Server Name:</th>
        <td><input type='text' name='limshost' size='40'
                   maxlength='30' value='$limshost' /></td></tr>
    <tr><th colspan='2'>Information about the Administrator</th></tr>
    <tr><th>First Name:</th>
        <td><input type='text' name='admin_fname' size='40'
                   maxlength='30' value='$admin_fname' /></td></tr>
    <tr><th>Last Name:</th>
        <td><input type='text' name='admin_lname' size='40'
                   maxlength='30' value='$admin_lname' /></td></tr>
    <tr><th>Email:</th>
        <td><input type='text' name='admin_email' size='40'
                   maxlength='63' value='$admin_email' /></td></tr>
    <tr><th>Password (for no change leave blank):</th>
        <td><input type='password' name='admin_pw1' size='40'
                   maxlength='80' /></td></tr>
    <tr><th>Password again (must match):</th>
        <td><input type='password' name='admin_pw2' size='40'
                   maxlength='80' /></td></tr>
    <tr><th colspan='2'>Information about the Lab</th></tr>
    <tr><th>Facility Name:</th>
        <td><input type='text' name='lab_name' size='40' 
                   maxlength='80' value='$lab_name' /></td></tr>
    <tr><th>Facility Contact Information:</th>
        <td><textarea name='lab_contact' rows='6' cols='65' 
                      wrap='virtual'>$lab_contact</textarea></td></tr>
    <tr><th>Brief location info (for a listing):</th>
        <td><input type='text' name='location' size='40' 
                   maxlength='255' value='$location' /></td></tr>
    <tr><th colspan='2'>Information about the AUC Instrument</th></tr>
    <tr><th>The name of the AUC Instrument:</th>
        <td><input type='text' name='instrument_name' size='40' 
                   maxlength='80' value='$instrument_name' /></td></tr>
    <tr><th>The Instrument Serial #:</th>
        <td><input type='text' name='instrument_serial' size='40' 
                   maxlength='80' value='$instrument_serial' /></td></tr>
    <tr><th>Status:</th>
        <td>$status_text</td></tr>


    </tbody>
  </table>
  </form>

HTML;
}

// Function to display the instance's login information
function login_info()
{
  // Get the record we need to edit
  if ( isset( $_POST['metadataID'] ) )
    $metadataID = $_POST['metadataID'];

  else
  {
    // How did we get here?
    echo "<p>There was a problem with the login info request.</p>\n";
    return;
  }

  $query  = "SELECT institution, inst_abbrev, dbname, dbuser, dbpasswd, dbhost, limshost, " .
            "admin_email, admin_pw, " .
            "secure_user, secure_pw " .
            "FROM metadata " .
            "WHERE metadataID = $metadataID ";
  $result = mysql_query($query) 
            or die("Query failed : $query<br />\n" . mysql_error());

  list( $institution,
        $inst_abbrev,
        $new_dbname,
        $new_dbuser,
        $new_dbpasswd,
        $new_dbhost,
        $new_limshost,
        $admin_email,
        $admin_pw,
        $new_secureuser,
        $new_securepw )   = mysql_fetch_array( $result );

  $new_grantsfile = $new_dbname . '_grants.sql';

  global $sql_dir;

  $script = <<<TEXT
#!/bin/bash
# A script to create the $institution database

echo "Use the root password in all cases here";

mysqladmin -u root -p CREATE $new_dbname
mysql -u root -p $new_dbname < $new_grantsfile

pushd $sql_dir
mysql -u root -p $new_dbname < us3.sql
mysql -u root -p $new_dbname < us3_procedures.sql
popd
TEXT;

  $grants = <<<TEXT
--
-- $new_grantsfile
--
-- Establishes the grants needed for the $institution database
--

GRANT ALL ON $new_dbname.* TO $new_dbuser@localhost IDENTIFIED BY '$new_dbpasswd';
GRANT ALL ON $new_dbname.* TO $new_dbuser@'%' IDENTIFIED BY '$new_dbpasswd';
GRANT EXECUTE ON $new_dbname.* TO $new_secureuser@'%' IDENTIFIED BY '$new_securepw' REQUIRE SSL;
GRANT ALL ON $new_dbname.* TO us3php@localhost;
GRANT ALL ON $new_dbname.* TO us3php@$new_dbhost;

TEXT;

  $hints = <<<TEXT
Database Setup Information

DB Connection Name: $new_secureuser
DB Password:        $new_securepw
Database Name:      $new_dbname
Host Address:       $new_dbhost


Admin Investigator Setup Information
Investigator Email:    $admin_email
Investigator Password: $admin_pw

LIMS Setup
URL:                http://$new_limshost/$new_dbname
DB User:            $new_dbuser
DB Pw:              $new_dbpasswd
DB Name:            $new_dbname
DB Host:            $new_dbhost 
TEXT;

  global $full_path;
  $makeconfigfile = $full_path . 'makeconfig.php';
 
  $setupLIMS = <<<TEXT
#!/bin/bash
# A script to create the $institution LIMS

DIR=\$(pwd)
htmldir="/srv/www/htdocs\uslims3"

echo "Use the us3 password here";
svn co svn://us3@bcf.uthscsa.edu/us3_lims/trunk \$htmldir/$new_dbname
mkdir \$htmldir/$new_dbname/data
#sudo chgrp apache \$htmldir/$new_dbname/data
chmod g+w \$htmldir/$new_dbname/data

#Now make the config.php file
php $makeconfigfile $new_dbname
vi \$htmldir/$new_dbname/config.php

TEXT;

  echo <<<HTML

  <h3>Login hints</h3>
  <pre>$hints</pre>

  <h3>The database creation script</h3>
  <pre>$script</pre>

  <h3>The grants script</h3>
  <pre>$grants</pre>

  <h3>The LIMS setup script</h3>
  <pre>$setupLIMS</pre>

HTML;
}

?>
