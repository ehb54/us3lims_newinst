<?php
/*
 * makeconfig.php
 *
 * Creates a config.php file
 *
 */
session_start();

/*
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
*/

include 'config.php';
include 'db.php';

// The legacy mode remains the default for existing operational callers.
$base_overlay_mode = $_SERVER['argc'] == 5 &&
                     $_SERVER['argv'][4] == '--base-overlay';

// Make sure there is a parameter
if ( $_SERVER['argc'] != 4 && !$base_overlay_mode )
{
  echo "Usage: php makeconfig.php <db_name> <orgsite> <ipaddress> [--base-overlay]\n";
  exit();
}

$new_dbname     = $_SERVER['argv'][1];
$new_orgsite    = $_SERVER['argv'][2];
$new_ipaddress  = $_SERVER['argv'][3];

if ( $base_overlay_mode &&
     !preg_match( '/^uslims3_[A-Za-z0-9_]+$/', $new_dbname ) )
{
  fwrite( STDERR, "Invalid db_name for base-overlay configuration\n" );
  exit( 1 );
}


$query  = "SELECT institution, dbuser, dbpasswd, dbhost, " .
          "secure_user, secure_pw, " .
          "admin_fname, admin_lname, admin_email, admin_pw, lab_contact " .
          "FROM metadata " .
          "WHERE dbname = '$new_dbname' ";

$result = mysqli_query( $link, $query ) 
          or die("Query failed : $query<br />\n" . mysqli_error($link));

if ( mysqli_num_rows( $result ) != 1 )
{
  echo "$new_dbname not found\n";
  exit();
}

list( $institution,
      $new_dbuser,
      $new_dbpasswd,
      $new_dbhost,
      $secure_user,
      $secure_pw,
      $admin_fname,
      $admin_lname,
      $admin_email,
      $admin_pw,
      $lab_contact )   = mysqli_fetch_array( $result );

$today  = date("Y\/m\/d");
$year   = date( "Y" );

#$lab_contact = preg_replace( "/\r|\n/", "<br />", $lab_contact );
$lab_contact = preg_replace( "/\r/", "<br />", $lab_contact );

if ( $base_overlay_mode )
{
  require_once __DIR__ . '/lib/dbinst_config_generator.php';

  $config_root = '/home/us3/lims/etc/config';
  $dbinst_dir = rtrim( $dest_path, '/' ) . '/' . $new_dbname;
  us3_newinst_require_dbinst_loader( $dbinst_dir );

  $base_values = us3_dbinst_config_load_base( $config_root );
  if ( $base_values[ 'ipaddr' ] !== $new_ipaddress &&
       $base_values[ 'ipa_ext' ] !== $new_ipaddress )
    us3_newinst_config_fail(
      'the requested host address matches neither v1 base ipaddr nor ipa_ext' );

  $overlay_values = array(
    'org_site'       => "$new_orgsite/$new_dbname",
    'admin'          => "$admin_fname $admin_lname",
    'admin_phone'    => $lab_contact,
    'admin_email'    => $admin_email,
    'dbusername'     => $new_dbuser,
    'dbpasswd'       => $new_dbpasswd,
    'dbname'         => $new_dbname,
    // Preserve existing makeconfig.php behavior: dbinst web access is local.
    'dbhost'         => 'localhost',
    'secure_user'    => $secure_user,
    'secure_pw'      => $secure_pw,
    'last_update'    => $today,
    'copyright_date' => $year
  );

  $written = us3_newinst_write_base_overlay(
    $new_dbname, $overlay_values, $config_root, $dbinst_dir );
  echo "Created " . $written[ 'overlay' ] . "\n";
  echo "Created " . $written[ 'shim' ] . "\n";
  exit();
}

// create config.php script
$text = <<<TEXT
<?php
/*  Database and other configuration information - Required!!  
 -- Configure the Variables Below --

*/

\$cfgfile            = exec( "ls ~us3/lims/.us3lims.ini" );
\$configs            = parse_ini_file( \$cfgfile, true );
\$org_name           = 'UltraScan3 LIMS portal';
\$org_site           = '$new_orgsite/$new_dbname';
\$site_author        = 'Borries Demeler, University of Lethbridge';
\$site_keywords      = 'ultrascan analytical ultracentrifugation lims';
                      # The website keywords (meta tag)
\$site_desc          = 'Website for the UltraScan3 LIMS portal'; # Site description

\$admin              = '$admin_fname $admin_lname';
\$admin_phone        = '$lab_contact'; #'Office: <br />Fax: ';
\$admin_email        = '$admin_email';

\$dbusername         = '$new_dbuser';  # the name of the MySQL user
\$dbpasswd           = '$new_dbpasswd';  # the password for the MySQL user
\$dbname             = '$new_dbname';  # the name of the database
\$dbhost             = 'localhost'; # the host on which MySQL runs, generally localhost

// Secure user credentials
\$secure_user        = '$secure_user'; # the secure username that UltraScan3 uses
\$secure_pw          = '$secure_pw';   # the secure password that UltraScan3 uses

// Global DB
\$globaldbuser       = 'gfac';  # the name of the MySQL user
\$globaldbpasswd     = \$configs[ 'gfac' ][ 'password' ]; # the password for the MySQL user
\$globaldbname       = 'gfac';  # the name of the database
\$globaldbhost       = 'localhost'; # the host on which MySQL runs, generally localhost

\$ipaddr             = '$new_ipaddress'; # the primary IP address of the host machine
\$ipa_ext            = '$new_ipaddress'; # the external IP address of the host machine
\$udpport            = 12233; # the port to send udp messages to

\$top_image          = '#';  # name of the logo to use
\$top_banner         = 'images/#';  # name of the banner at the top

\$full_path          = '$dest_path$new_dbname/';  # Location of the system code
\$data_dir           = '$dest_path$new_dbname/data/'; # Full path
\$submit_dir         = '/srv/www/htdocs/uslims3/uslims3_data/'; # Full path
\$class_dir          = '/srv/www/htdocs/common/class/';       # Production class path
//\$class_dir          = '/srv/www/htdocs/common/class_devel/'; # Development class path
//\$class_dir          = '/srv/www/htdocs/common/class_local/'; # Local class path
\$disclaimer_file    = ''; # the name of a text file with disclaimer info

// Dates
date_default_timezone_set( 'America/Chicago' );
\$last_update        = '$today'; # the date the website was last updated
\$copyright_date     = '$year'; # copyright date
\$current_year       = date( 'Y' );

\$enable_GMP         = false;
  
// Important - if \$enable_PAM is changed, make sure to run
  // from directory ~us3/lims/database/utilities
  // 1. php uslims_permissions.php --grant-integrity uslims3_Demo --all-users --grant-integrity-fix
  // 2. php uslims_permissions.php --grant-integrity uslims3_Demo --grant-integrity-fix
\$enable_PAM         = false;

//////////// End of user specific configuration

// ensure a trailing slash
if ( \$data_dir[strlen(\$data_dir) - 1] != '/' )
  \$data_dir .= '/';

if ( \$submit_dir[strlen(\$submit_dir) - 1] != '/' )
  \$submit_dir .= '/';

if ( \$class_dir[strlen(\$class_dir) - 1] != '/' )
  \$class_dir .= '/';

/* Define our file paths */
if ( ! defined('HOME_DIR') ) 
{
  define('HOME_DIR', \$full_path );
}

if ( ! defined('DEBUG') ) 
{
  define('DEBUG', false );
}

\$is_cli = php_sapi_name() == 'cli';

include_once "elog.php";

TEXT;

if ( file_exists( $dest_path . $new_dbname ) )
  file_put_contents( $dest_path . "$new_dbname/config.php", $text );

else
{
  global $data_dir;

  file_put_contents( $data_dir . 'config.php', $text );
}
