<?php
    session_start();
    require_once('connect.php');

    if (isset($_SESSION['id']))
    {
        if ($stmt = $connection->prepare("SELECT last_update FROM ".Table::USERS." WHERE id = ?"))
        {
            $stmt->bind_param('i', $_SESSION['id']);
            $stmt->execute();
            $stmt->bind_result($user_update);
            $stmt->fetch();
            $stmt->close();
        }

        if ($stmt = $connection->prepare("SELECT max(TIME) FROM ".Table::LOGS." WHERE action IN ('BOSS_UPDATE', 'SERVER_RESTART')"))
        {
            $stmt->execute();
            $stmt->bind_result($last_update);
            $stmt->fetch();
            $stmt->close();
        }

        if ($user_update <= $last_update)
            echo "1";
        else
            echo "";
    }
    else
        echo "";
?>
