<?php
/*
 * selectboxes.php
 * arrays of things for this group, to be included in other files
 *
 */

// A list of request status possibilities
$request_status['pending']   = "Pending";
$request_status['completed'] = "Completed";
$request_status['denied']    = "Denied";

// Function to create a dropdown for request status
function request_status_select( $select_name, $current_status = NULL )
{
  global $request_status;

  $text = "<select name='$select_name' size='1'>\n" .
                  "  <option value='None'>Please select...</option>\n";
  foreach ( $request_status as $status => $display )
  {
    $selected = ( $current_status == $status ) ? " selected='selected'" : "";
    $text .= "  <option value='$status'$selected>$display</option>\n";
  }

  $text .= "</select>\n";

  return $text;
}


