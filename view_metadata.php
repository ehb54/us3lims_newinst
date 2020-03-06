<?php
/*
 * view_metadata.php
 *
 * Display the entire metadata table, allowing individual records to be edited
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

// Start displaying page
$page_title = 'View New LIMS Requests';
$js  = 'js/export.js,js/sorttable.js';
include 'header.php';

?>
<!-- Begin page content -->
<div id='content'>

  <h1 class='title'><?php echo $page_title; ?></h1>
  <!-- Place page content here -->

<?php
// Display a table
$table = create_table();
echo $table;

$_SESSION['print_title'] = "LIMS v3 Metadata";
$_SESSION['print_text']  = $table;

?>
</div>

<?php
include 'footer.php';
exit();

// Function to display a table of all records
function create_table()
{
  $query  = "SELECT metadataID, institution, admin_fname, admin_lname, updateTime " .
            "FROM metadata " .
            "ORDER BY updateTime DESC ";
  $result = mysql_query($query) 
            or die("Query failed : $query<br />\n" . mysql_error());

  $table = <<<HTML
  <form action="{$_SERVER['PHP_SELF']}" method="post" >
  <table cellspacing='0' cellpadding='7' class='style1 sortable' style='width:95%;'>
    <thead>
      <tr>
          <th>Institution</th>
          <th>Admin_fname</th>
          <th>Admin_lname</th>
          <th>UpdateTime</th>
      </tr>
    </thead>

    <tfoot>
      <tr><td colspan='5'>
                          <input type='button' value='Print Version' 
                                 onclick='print_version();' /></td></tr>
    </tfoot>

    <tbody>
HTML;

  while ( $row = mysql_fetch_array($result) )
  {
    foreach ($row as $key => $value)
    {
      $$key = (empty($value)) ? "&nbsp;" : stripslashes( nl2br($value) );
    }

$table .= <<<HTML
      <tr>
          <td><a href='edit_metadata.php?ID=$metadataID'>$institution</a></td>
          <td>$admin_fname</td>
          <td>$admin_lname</td>
          <td>$updateTime</td>
      </tr>
HTML;

  }

  $table .= <<<HTML
    </tbody>
  </table>
  </form>

HTML;

  return $table;
}

?>
