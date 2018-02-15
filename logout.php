<?php
    session_start();
    require_once('connect.php');
    require_once('actions.php');

    if ($stmt = $connection->prepare("UPDATE ".Table::USERS." SET token = ? WHERE id = ?"))
    {
        $null = NULL;
        $stmt->bind_param("si", $null, $_SESSION['id']);
        $stmt->execute();
        $stmt->close();
    }

    setcookie('remember_me');
    unset($_COOKIE['remember_me']);


    if (isset($_SESSION['id']))
        Action::log($_SESSION['id'], "USER_LOGOUT", "", 0, "");

    session_unset();
    session_destroy();
    header('Location: index.php');
?>
