<?php
/*
 * create_instance.php
 *
 * Use the information in the metadata table to set up a new db instance
 *
 */
session_start();

// Are we authorized to view this page?
if ( ! isset($_SESSION['id']) )
{
  header('Location: index.php');
  exit();
} 

if ( ($_SESSION['userlevel'] != 4) &&
     ($_SESSION['userlevel'] != 5) )    // admin and super admin only
{
  header('Location: index.php');
  exit();
} 

include 'config.php';
include 'db.php';
include 'lib/utility.php';

// Start displaying page
$page_title = "Create DB Instance";
include 'header.php';
?>
<!-- Begin page content -->
<div id='content'>

  <h1 class="title">Create DB Instance</h1>
  <!-- Place page content here -->

<?php
  if ( isset( $_POST['step_2'] ) )
    do_step2();

  else if ( isset( $_SESSION['metadataID'] ) )
    do_step1();

  else
    echo "<p>Error: you need to execute this program from " .
         "<a href='view_metadata.php'>here</a>.</p>\n";

?>
</div>

<?php
include 'footer.php';
exit();

function do_step1()
{
  // First time here
  $metadataID = $_SESSION['metadataID'];
  unset( $_SESSION['metadataID'] );

echo "MetadataID = $metadataID<br />";
  // Double check if this has been done before
  $query  = "SELECT status FROM metadata " .
            "WHERE metadataID = $metadataID ";
  $result = mysql_query($query) 
            or die("Query failed : $query<br />\n" . mysql_error());

  if ( mysql_num_rows( $result ) != 1 ) 
  {
    error( "Error: metadata record not found." );
    return;
  }

  $status = '';
  list( $status ) = mysql_fetch_array( $result );
  if ( $status == 'completed' )
  {
    error( "Error: this database has already been set up." );
    return;
  }
echo "Metadata status = $status<br />";

  $query  = "SELECT institution, inst_abbrev, dbname, dbuser, dbpasswd, dbhost, limshost, " .
            "admin_email, admin_pw " .
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
        $admin_pw )   = mysql_fetch_array( $result );

  $new_secureuser = $inst_abbrev . '_sec';
  $new_securepw   = makeRandomPassword();
  $new_scriptfile = $new_dbname . '.sh';
  $new_grantsfile = $new_dbname . '_grants.sql';
  $new_hintsfile  = $new_dbname . '.txt';

  // Update the database right away with the secure user info
  $query  = "UPDATE metadata SET " .
            "secure_user = '$new_secureuser', " .
            "secure_pw = '$new_securepw' " .
            "WHERE metadataID = $metadataID ";
  mysql_query($query) 
        or die("Query failed : $query<br />\n" . mysql_error());

  global $sql_dir;

  $script = <<<TEXT
#!/bin/bash
# A script to create the $institution database

echo "Use the root DB password here";
read -s -p "Password: " DBPW

mysqladmin -u root -p\$DBPW CREATE $new_dbname
mysql -u root -p\$DBPW $new_dbname < $new_grantsfile

pushd $sql_dir
mysql -u root -p\$DBPW $new_dbname < us3.sql
mysql -u root -p\$DBPW $new_dbname < us3_procedures.sql
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
Investigator Email: $admin_email
Investigator Password: $admin_pw

LIMS URL:              http://$new_limshost/$new_dbname
TEXT;

  global $output_dir;
  $current_dir = getcwd();
  chdir( $output_dir );

  // Let's make sure the instance directory is there
  if ( ! file_exists( $new_dbname ) )
    mkdir( $new_dbname, 0755 );

  $instance_dir = $output_dir . $new_dbname . '/';
  file_put_contents( $instance_dir . $new_scriptfile, $script );
  file_put_contents( $instance_dir . $new_hintsfile,  $hints  );
  file_put_contents( $instance_dir . $new_grantsfile, $grants );
  chmod( $instance_dir . $new_scriptfile, 0755 );

  echo <<<HTML
  <p>Step 1</p>

  <p>Creating a database for $institution includes the following steps:</p>
  <ul><li>Create the database</li>
      <li>Enable the LIMS user</li>
      <li>Load the database definition</li>
      <li>Load the stored procedures</li>
  </ul>

  <p>Two script files called $new_scriptfile and $new_grantsfile have been 
     created for you in the us3 user&rsquo;s $instance_dir directory that
     do all of this. As the us3 user, execute the shell script $new_scriptfile 
     from there, as it depends on having the UltraScanIII sql script files 
     in a particular place relative to this directory. The grant file will 
     be included automatically. You will need to use the root password for 
     mysql each time a password is requested. Then click Next--&gt;</p>

  <form action={$_SERVER['PHP_SELF']} method='post' >
    <input type='submit' name='step_2' value='Next--&gt;' />
    <input type='hidden' name='metadataID' value='$metadataID' />
  </form>

HTML;
}

function do_step2()
{
  $metadataID = $_POST['metadataID'];

  setup_DB( $metadataID );

  $query  = "SELECT institution, dbname, dbuser, dbpasswd, dbhost " .
            "FROM metadata " .
            "WHERE metadataID = $metadataID ";
  $result = mysql_query($query) 
            or die("Query failed : $query<br />\n" . mysql_error());

  if ( mysql_num_rows( $result ) != 1 ) return;

  list( $institution,
        $new_dbname,
        $new_dbuser,
        $new_dbpasswd,
        $new_dbhost )    = mysql_fetch_array( $result );

  global $full_path;
  $makeconfigfile = $full_path . 'makeconfig.php';
 
  $setupLIMS = <<<TEXT
#!/bin/bash
# A script to create the $institution LIMS

DIR=\$(pwd)
htmldir="/srv/www/htdocs/uslims3"

echo "Use the us3 password here";
svn co svn://us3@svn.aucsolutions.com/us3_lims/trunk \$htmldir/$new_dbname
mkdir \$htmldir/$new_dbname/data
#sudo chgrp apache \$htmldir/$new_dbname/data
chmod g+w \$htmldir/$new_dbname/data

new_orgsite=$(hostname)
new_ipaddress=$(resolveip -s `hostname`)

#Now make the config.php file
php $makeconfigfile $new_dbname \$new_orgsite \$new_ipaddress
vi \$htmldir/$new_dbname/config.php
TEXT;

  global $output_dir;
  $instance_dir = $output_dir . $new_dbname . '/';
  $new_LIMSfile = $new_dbname . 'LIMS.sh';
  file_put_contents( $instance_dir . $new_LIMSfile, $setupLIMS );
  chmod( $instance_dir . $new_LIMSfile, 0755 );

  echo <<<HTML
  <p>Step 2</p>

  <p>Setting up the LIMS code involves the following steps:</p>

  <ul><li>Check out LIMS code for $institution</li>
      <li>Create the LIMS data directory</li>
      <li>Create the config.php file</li>
  </ul>

  <p>A script file called $new_LIMSfile has been created for you in the us3 
     user&rsquo;s $instance_dir directory that does all of this. As the us3 
     user, execute the script. At the end of the process the script will
     present the generated config.php for you to edit. Double check the file 
     using this information:</p>

  <table cellspacing='0' cellpadding='3' style='text-align:left;'>
    <tr><th>Database name:</th><td>$new_dbname</td></tr>
    <tr><th>Database user:</th><td>$new_dbuser</td></tr>
    <tr><th>DB User Password:</th><td>$new_dbpasswd</td></tr>
    <tr><th>Server name:</th><td>$new_dbhost</td></tr>
    <tr><th>Global DB User:</th><td>gfac</td></tr>
    <tr><th>Global DB password:</th><td>backend</td></tr>
    <tr><th>Global DB name:</th><td>gfac</td></tr>
    <tr><th>Global DB host:</th><td>uslims3.uthscsa.edu</td></tr>
  </table>

  <p>The database instance has been created.</p>

HTML;

  // Mail the administrator automatically
  email_login_info( $metadataID );
}

function setup_DB( $metadataID )
{
  // Let's just get everything we're going to need
  $query  = "SELECT institution, dbname AS new_dbname, dbuser AS new_dbuser, " .
            "dbpasswd AS new_dbpasswd, dbhost AS new_dbhost, " .
            "admin_fname, admin_lname, admin_email, admin_pw, " .
            "lab_name, lab_contact, " .
            "instrument_name, instrument_serial, " .
            "status " .
            "FROM metadata " .
            "WHERE metadataID = $metadataID ";
  $result = mysql_query($query) 
            or die("Query failed : $query<br />\n" . mysql_error());

  $row    = mysql_fetch_array($result, MYSQL_ASSOC);

  // Create local variables
  foreach ($row as $key => $value)
  {
    $$key = (empty($value)) ? "" : html_entity_decode( stripslashes( nl2br($value) ) );
  }

  $admin_pw_hash = MD5( $admin_pw );

  // Now switch databases
  $link2 = mysql_connect( $new_dbhost, $new_dbuser, $new_dbpasswd ) 
           or die("Could not connect to database server. ");

  mysql_select_db( $new_dbname, $link2 ) 
          or die("Could not select $new_dbname database. " );

  // Administrator record
  $guid = uuid();
  $query  = "INSERT INTO people SET " .
            "personGUID = '$guid', " .
            "fname = '$admin_fname', " .
            "lname = '$admin_lname', " .
            "email = '$admin_email', " .
            "password = '$admin_pw_hash', " .
            "organization = '$institution', " .
            "activated = true, " .
            "userlevel = 3 ";
  mysql_query($query) 
        or die("Query failed : $query<br />\n" . mysql_error());
  $admin_id = mysql_insert_id();

  // The institution's lab
  // One is already created in the sql scripts
  $query  = "UPDATE lab SET " .
            "name = '$lab_name', " .
            "building = '$lab_contact', " .
            "dateUpdated = NOW() " .
            "WHERE labID = 1 ";
  mysql_query($query) 
        or die("Query failed : $query<br />\n" . mysql_error());
  $lab_id = 1;

  // The instrument in the lab
  $query  = "INSERT INTO instrument SET " .
            "name = '$instrument_name', " .
            "labID = '$lab_id', " .
            "serialNumber = '$instrument_serial', " .
            "dateUpdated = NOW() ";
  mysql_query($query) 
        or die("Query failed : $query<br />\n" . mysql_error());
  $instrument_id = mysql_insert_id();

  // Set permits for these users to use the instrument
  $query  = "INSERT INTO permits SET " .
            "personID = $admin_id, " .
            "instrumentID = $instrument_id ";
  mysql_query($query) 
        or die("Query failed : $query<br />\n" . mysql_error());

  // Create a couple of abstract channels
  $query  = "INSERT INTO abstractChannel SET " .
            "abstractChannelID   = 1, " .
            "channelType         = 'sample', " .
            "channelShape        = 'sector', " .
            "abstractChannelGUID = 'fa703797-caff-1c44-3d0d-4f89149c0fe0', " .
            "name                = 'UTHSCSA Abstract Channel #1', " .
            "number              = 101, " .
            "radialBegin         = 0.0, " .
            "radialEnd           = 0.0, " .
            "degreesWide         = 0.0, " .
            "degreesOffset       = 0.0, " .
            "radialBandTop       = 0.0, " .
            "radialBandBottom    = 0.0, " .
            "radialMeniscusPos   = 0.0, " .
            "dateUpdated         = NOW()";
  mysql_query($query) 
        or die("Query failed : $query<br />\n" . mysql_error());

  $query  = "INSERT INTO abstractChannel SET " .
            "abstractChannelID   = 2, " .
            "channelType         = 'sample', " .
            "channelShape        = 'sector', " .
            "abstractChannelGUID = 'dfb2aa83-a0c1-b724-59d4-4ddbd1e41cc2', " .
            "name                = 'UTHSCSA Abstract Channel #2', " .
            "number              = 102, " .
            "radialBegin         = 0.0, " .
            "radialEnd           = 0.0, " .
            "degreesWide         = 0.0, " .
            "degreesOffset       = 0.0, " .
            "radialBandTop       = 0.0, " .
            "radialBandBottom    = 0.0, " .
            "radialMeniscusPos   = 0.0, " .
            "dateUpdated         = NOW()";
  mysql_query($query) 
        or die("Query failed : $query<br />\n" . mysql_error());

  // Create a couple of channels to be used
  // For now just choose abstract channels to base them on
  $guid = uuid();
  $query  = "INSERT INTO channel SET " .
            "abstractChannelID = 1, " .
            "channelGUID = '$guid', " .
            "comments = 'Record generated automatically by newlims program.' , " .
            "dateUpdated = NOW() ";
  mysql_query($query) 
        or die("Query failed : $query<br />\n" . mysql_error());

  $guid = uuid();
  $query  = "INSERT INTO channel SET " .
            "abstractChannelID = 2, " .
            "channelGUID = '$guid', " .
            "comments = 'Record generated automatically by newlims program.' , " .
            "dateUpdated = NOW() ";
  mysql_query($query) 
        or die("Query failed : $query<br />\n" . mysql_error());

  // Add the us3 admins to this database
  add_admins( $link2 );

  // Update status in metadata DB
  global $link, $dbhost, $dbusername, $dbpasswd, $dbname;
  $link = mysql_connect( $dbhost, $dbusername, $dbpasswd ) 
          or die("Could not connect to database server. ");
  mysql_select_db($dbname, $link) 
          or die("Could not select database $dbname. " );
  $query  = "UPDATE metadata SET " .
            "status = 'completed' " .
            "WHERE metadataID = $metadataID ";
  mysql_query($query) 
        or die("Query failed : $query<br />\n" . mysql_error());

}

// Function to add the us3 admins to the current database
function add_admins( $link2 )
{
  // Start queries
  $email_list = array();
  $query  = "SELECT email FROM people ";
  $result = mysql_query( $query, $link2 );
  while ( list( $email ) = mysql_fetch_array( $result ) )
    $email_list[] = $email;

  // Guard against possibility that one of us is the one requesting
  if ( in_array( 'demeler@gmail.com', $email_list ) )
  {
    $query  = "UPDATE people SET ";
    $where  = "WHERE email   = 'demeler@gmail.com' ";
  }

  else
  {
    $guid   = uuid();
    $query  = "INSERT INTO people SET " .
              "personGUID    = '$guid', " .
              "email = 'demeler@gmail.com', ";
    $where  = "";
  }

  $query   .= "fname         = 'Borries', " .
              "lname         = 'Demeler', " .
              "address       = '32 Campus Drive', " .
              "city          = 'Missoula', " .
              "state         = 'MT', " .
              "zip           = '59812', " .
              "country       = 'US', " .
              "phone         = '406-285-1935', " .
              "password      = MD5(''), " .                # password must be set
              "organization  = 'University of Montana', " .
              "username      = 'us3demeler', " .
              "activated     = 1, " .
              "userlevel     = 4 " .
              $where ;
  $result   = mysql_query( $query, $link2 );
  if ( ! $result )
    echo "Query failed : $query\n" . mysql_error();

  if ( in_array( 'alexsav.science@gmail.com', $email_list ) )
  {
    $query  = "UPDATE people SET ";
    $where  = "WHERE email   = 'alexsav.science@gmail.com' ";
  }

  else
  {
    $guid   = uuid();
    $query  = "INSERT INTO people SET " .
              "personGUID    = '$guid', " .
              "email = 'alexsav.science@gmail.com', ";
    $where  = "";
  }

  $query   .= "fname         = 'Alexey', " .
              "lname         = 'Savelyev', " .
              "address       = '32 Campus Drive', " .
              "city          = 'Missoula', " .
              "state         = 'MT', " .
              "zip           = '59812', " .
              "country       = 'US', " .
              "phone         = '555-555-5555', " .
              "password      = MD5(''), " .                 # password must be set
              "organization  = 'University of Montana', " .
              "username      = 'us3savelyev', " .
              "activated     = 1, " .
              "userlevel     = 5 " .
              $where ;
  $result = mysql_query( $query, $link2 );
  if ( ! $result )
    echo "Query failed : $query\n" . mysql_error();

  if ( in_array( 'gegorbet@gmail.com', $email_list ) )
  {
    $query  = "UPDATE people SET ";
    $where  = "WHERE email   = 'gegorbet@gmail.com' ";
  }

  else
  {
    $guid   = uuid();
    $query  = "INSERT INTO people SET " .
              "personGUID    = '$guid', " .
              "email = 'gegorbet@gmail.com', ";
    $where  = "";
  }

  $query   .= "fname         = 'Gary', " .
              "lname         = 'Gorbet', " .
              "address       = '32 Campus Drive', " .
              "city          = 'Missoula', " .
              "state         = 'MT', " .
              "zip           = '59812', " .
              "country       = 'US', " .
              "phone         = '832-466-9211', " .
              "password      = MD5(''), " .                # password must be set
              "organization  = 'University of Montana', " .
              "username      = 'us3gorbet', " .
              "activated     = 1, " .
              "userlevel     = 4 " .
              $where ;
  $result = mysql_query( $query, $link2 );
  if ( ! $result )
    echo "Query failed : $query\n" . mysql_error();

}

?>
