<?php
/*
 * links.php
 *
 * Include file that contains links
 *  Needs session_start(), config.php
 *
 */

echo<<<HTML
<div id='sidebar' style='padding-bottom:30em;'>

  <a href='http://$org_site/index.php'>Welcome!</a>

HTML;

  // level 5 = super admin ( developer )
  if ( isset($_SESSION['userlevel']) &&
             $_SESSION['userlevel'] == 5 )
  {
    echo <<<HTML
      <h4>Admin</h4>
      <a href='http://$org_site/mysql_admin.php'>MySQL</a>
      <a href='http://$org_site/edit_users.php'>Edit User Info</a>
      <a href='http://$org_site/view_users.php'>View User Info</a>
      <a href='http://$org_site/view_all.php'>View All Users</a>

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
      <a href='http://$org_site/edit_users.php'>Edit User Info</a>
      <a href='http://$org_site/view_users.php'>View User Info</a>
      <a href='http://$org_site/view_all.php'>View All Users</a>

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
      <a href='http://$org_site/view_users.php'>View User Info</a>
      <a href='http://$org_site/view_all.php'>View All Users</a>

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
      <a href='http://$org_site/profile.php?edit=12'>Change My Info</a>
      <a href="partners.php">Partners</a>
      <a href="contacts.php">Contacts</a>
      <a href="mailto:dzollars@gmail.com">Webmaster</a>
      <a href='http://$org_site/logout.php'>Logout</a>

HTML;
  }

  // Links for non-logged in users
  else
  {
      echo <<<HTML
      <a href="request_new_instance.php">Request Instance</a>
      <a href="partners.php">Partners</a>
      <a href="contacts.php">Contacts</a>
      <a href="mailto:dzollars@gmail.com">Webmaster</a>
      <a href='https://$org_site/login.php'>Login</a>

HTML;
  }

?>

</div>
