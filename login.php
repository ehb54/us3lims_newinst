<?php
/*
 * login.php
 *
 * Login page
 *
 */

include_once 'config.php';
include 'require_https.php';

$page_title = "Login";
include 'header.php';

if (! isset($message) ) $message = "";

echo<<<HTML

<div id='content' class='account-page'>
<div class='account-card'>

  <p class="account-eyebrow">UltraScan III &middot; LIMS</p>
  <h1 class="title">Sign in to LIMS</h1>
  <p class="account-intro">Sign in to access your laboratory workspace.</p>
  <p class='message'>$message</p>

  <form method='post' action='checkuser.php'>
    <table cellspacing='0' cellpadding='7'>
      <tr><td><label for='login-email'>Email address:</label></td>
          <td><input id='login-email' type='text' name='email' autocomplete='username' maxlength='64' size='20'
                     class='w-20em' /></td></tr>

      <tr><td><label for='login-password'>Password:</label></td>
          <td><input id='login-password' type='password' name='password' autocomplete='current-password' maxlength='32'
                     size='20' class='w-20em'/></td></tr>

      <tr><td><input type='submit' name='Submit' value='Sign In'/></td></tr>
    </table>
  </form>

  <p><a href='https://$org_site/lost_password.php'>Forgot your password?</a></p>

  <p class='account-new'>New to UltraScan LIMS? <a href='https://$org_site/newaccount.php'>Create an account &rarr;</a></p>
</div>

</div>

HTML;

include $common_dir . 'footer.php';
?>
