<?php
    session_start();
    require_once('connect.php');
    require_once('actions.php');

    mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ERROR);

    if (isset($_SESSION['id']))
    {
        if ($stmt = $connection->prepare('SELECT id, time, boss_id FROM '.Table::LOGS.' WHERE ACTION = "BOSS_UPDATE" AND user = ? ORDER BY time DESC LIMIT 1'))
        {
            $stmt->bind_param("i", $_SESSION['id']);
            $stmt->execute();
            $stmt->bind_result($log_id, $undone_time, $boss_id);
            if (!$stmt->fetch())
                die;
            $stmt->close();

            $update_time = 0;
            if ($upd_stmt = $connection->prepare('SELECT time, action_param, note, user FROM '.Table::LOGS.' WHERE action = "BOSS_UPDATE" AND boss_id = ? ORDER BY time DESC LIMIT 2'))
            {
                $upd_stmt->bind_param("i", $boss_id);
                $upd_stmt->execute();
                $upd_stmt->bind_result($update_time, $action_param, $note, $user);
                $upd_stmt->fetch();
                if (!$upd_stmt->fetch())
                    $update_time = 0;
                $upd_stmt->close();
            }

            if ($res_stmt = $connection->prepare('SELECT max(time) FROM '.Table::LOGS.' WHERE action = "SERVER_RESTART"'))
            {
                $res_stmt->execute();
                $res_stmt->bind_result($restart_time);
                $res_stmt->fetch();
                $res_stmt->close();
            }
            
            if ($undone_time > $restart_time)
            {
                if ($update_time < $restart_time)
                {
                    $note = "";
                    $action_param = 0;
                    $user = 0;
                }
                
                if ($update_stmt = $connection->prepare('UPDATE '.Table::LOGS.' SET action = "BOSS_UPDATE_UNDONE" WHERE id = ?'))
                {
                    $update_stmt->bind_param("i", $log_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                }    

                if ($update_stmt = $connection->prepare('UPDATE '.Table::BOSSES.' SET note = ?, death_time = ?, last_update = ? WHERE id = ?'))
                {
                    $update_stmt->bind_param("siii", $note, $action_param, $user, $boss_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
            }
        }
    }
?>
