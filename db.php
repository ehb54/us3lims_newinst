<?php

include 'config.php';

$link = mysqli_connect( $dbhost, $dbusername, $dbpasswd, $dbname ) 
        or die("Could not connect to database server, db $dbhost, $dbname.");

?>
