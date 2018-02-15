<?php
    session_start();
    require_once('connect.php');
    require_once('actions.php');

    if (isset($_SESSION['id']))
    {
        if ($stmt = $connection->prepare("SELECT id FROM ".Table::LOGS." where action"))
    }
?>
