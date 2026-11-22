<?php

## Failed mysqli calls return false here, as PHP 7.4 does by default, and the code checks for that.
## PHP 8.1+ throws mysqli_sql_exception by default instead, which would skip those checks.
mysqli_report( MYSQLI_REPORT_OFF );

include 'config.php';

$link = mysqli_connect( $dbhost, $dbusername, $dbpasswd, $dbname ) 
        or die("Could not connect to database server, db $dbhost, $dbname.");

?>
