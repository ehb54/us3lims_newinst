<?php
/*
 * links.php
 *
 * Include file that contains links
 *  Needs session_start(), config.php
 *
 * The links are relative on purpose. They used to be absolute http:// URLs, so
 * every click after an https login dropped back to http and left the secure
 * session cookie behind. Relative keeps whatever scheme the visitor arrived on.
 * login.php stays absolute https, because arriving on http and then logging in
 * over http is the one case worth forcing.
 */

echo<<<HTML
<div id='sidebar' class='pb-30em'>

  <a href='index.php'>Welcome!</a>

HTML;

  // level 5 = super admin ( developer )
  if ( isset($_SESSION['userlevel']) &&
             $_SESSION['userlevel'] == 5 )
  {
    echo <<<HTML
      <h4>Admin</h4>
      <a href='mysql_admin.php'>MySQL</a>
      <a href='edit_users.php'>Edit User Info</a>
      <a href='view_users.php'>View User Info</a>
      <a href='view_all.php'>View All Users</a>

      <h4>Instances</h4>
      <a href="request_new_instance.php">Request Instance</a>
      <a href="view_metadata.php">View Requests</a>

HTML;
  }

  // userlevel 4 = admin
  if ( isset($_SESSION['userlevel']) &&
             $_SESSION['userlevel'] == 4 )
  {
    echo <<<HTML
      <h4>Admin</h4>
      <a href='edit_users.php'>Edit User Info</a>
      <a href='view_users.php'>View User Info</a>
      <a href='view_all.php'>View All Users</a>

      <h4>Instances</h4>
      <a href="request_new_instance.php">Request Instance</a>
      <a href="view_metadata.php">View Requests</a>

HTML;
  }

  // userlevel 3 = superuser
  if ( isset($_SESSION['userlevel']) &&
             $_SESSION['userlevel'] == 3 )
  {
    echo <<<HTML
      <h4>Admin</h4>
      <a href='view_users.php'>View User Info</a>
      <a href='view_all.php'>View All Users</a>

      <h4>Instances</h4>
      <a href="request_new_instance.php">Request Instance</a>

HTML;
  }

  // all others
  if ( isset($_SESSION['userlevel']) &&
             $_SESSION['userlevel'] < 3 )
  {
    echo <<<HTML
      <h4>Instances</h4>
      <a href="request_new_instance.php">Request Instance</a>

HTML;
  }

  // Links for all logged in users
  if ( isset($_SESSION['id']) )
  {
    echo <<<HTML
      <h4>General</h4>
      <a href='profile.php?edit=12'>Change My Info</a>
      <a href="contacts.php">Contacts</a>
      <a href='logout.php'>Logout</a>

HTML;
  }

  // Links for non-logged in users
  else
  {
      echo <<<HTML
      <a href="request_new_instance.php">Request Instance</a>
      <a href="contacts.php">Contacts</a>
      <a href='https://$org_site/login.php'>Login</a>

HTML;
  }

?>

</div>
