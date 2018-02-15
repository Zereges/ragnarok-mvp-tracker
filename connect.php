<?php
    require_once('config.php');

    $connection = new mysqli(Config::HOSTNAME, Config::USERNAME, Config::PASSWORD, Config::DATABASE);
    if (mysqli_connect_errno())
        die("Database Connection Failed: " . mysqli_connect_error());
?>
