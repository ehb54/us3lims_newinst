<?php
/*
 * lost_password.php
 *
 * A form to allow user to create a new password
 *
 */

include 'config.php';

$page_title = 'Create Password';
include 'header.php';
?>
<div id='content' class='account-page'>
<div class='account-card'>

  <p class="account-eyebrow">UltraScan III &middot; LIMS</p>
  <h1 class="title">Password recovery</h1>

  <?php if ( isset($message) ) echo "<p class='message'>$message</p>\n"; ?>



  <form action="recover_password.php" method="post">
    <p>Enter your email address to recover your password.</p>

    <p><label for="recovery-email">Email address:</label> <input id="recovery-email" name="email_address" type="text" size='30'/></p>

    <p><input type="submit" name="Submit" value="Reset password"/></p>
  </form>

<p class="account-new"><a href="login.php">&larr; Back to sign in</a></p>
</div>
</div>

<?php
include $common_dir . 'footer.php';
?>
