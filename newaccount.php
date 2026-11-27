<?php
/*
 * newaccount.php
 *
 * Allows a user to create a new account
 *
 */

include_once 'config.php';
include_once 'db.php';

$page_title = 'New Account';
$js = 'js/edit_users.js';
include 'header.php';
?>

<div id='content' class='account-page'>
<div class='account-card account-card-wide'>

  <p class="account-eyebrow">UltraScan III &middot; LIMS</p>
  <h1 class="title">Create an account</h1>

<?php

if ( isset($message) )
   echo "<p class='message'>$message</p>\n";

echo<<<HTML

  <p>Please enter your contact information. All fields 
     are required.</p>

  <p class='account-note'>Please provide a valid mailing address for correspondence.</p>

  <form action="register.php" method="post"
        class='onsubmit-return-validate-this'>
  <table cellspacing='0' cellpadding='10'>
    <thead>
      <tr><th colspan='2'>Personal Information</th></tr>
    </thead>


    <tbody>

    <tr><th><label for='register-fname'>First Name:</label></th>
        <td><input id='register-fname' type='text' name='fname' size='40'
                   maxlength='64' value='$fname' /></td></tr>
    <tr><th><label for='register-lname'>Last Name:</label></th>
        <td><input id='register-lname' type='text' name='lname' size='40'
                   maxlength='64' value='$lname' /></td></tr>
    <tr><th><label for='register-organization'>Organization:</label></th>
        <td><input id='register-organization' type='text' name='organization' size='40'
                   maxlength='128' value='$organization' /></td></tr>
    <tr><th><label for='register-address'>Address:</label></th>
        <td><input id='register-address' type='text' name='address' size='40'
                   maxlength='128' value='$address' /></td></tr>
    <tr><th><label for='register-city'>City:</label></th>
        <td><input id='register-city' type='text' name='city' size='40'
                   maxlength='64' value='$city' /></td></tr>
    <tr><th><label for='register-state'>State (Province):</label></th>
        <td><input id='register-state' type='text' name='state' size='40'
                   maxlength='64' value='$state' /></td></tr>
    <tr><th><label for='register-zip'>Postal Code (Zip):</label></th>
        <td><input id='register-zip' type='text' name='zip' size='40'
                   maxlength='16' value='$zip' /></td></tr>
    <tr><th><label for='register-country'>Country:</label></th>
        <td><input id='register-country' type='text' name='country' size='40'
                   maxlength='64' value='$country' /></td></tr>
    <tr><th><label for='register-phone'>Phone:</label></th>
        <td><input id='register-phone' type='text' name='phone' size='40'
                   maxlength='64' value='$phone' /></td></tr>
    <tr><th><label for='register-email'>Email:</label></th>
        <td><input id='register-email' type='text' name='email' size='40'
                   maxlength='64' value='$email' /></td></tr>
    </tbody>
    <tfoot>
      <tr><td colspan='2'><input type='submit' name='create'
                           value='Create New Account' /></td></tr>
    </tfoot>
  </table>
  </form>

<p class="account-new"><a href="login.php">&larr; Back to sign in</a></p>
</div>
</div>

HTML;

include $common_dir . 'footer.php';
?>
