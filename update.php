<?php
    session_start();
    require_once('connect.php');
    require_once('actions.php');

    if (isset($_POST['id']))
    {
        $update_id = $_POST['id'];
        $update_note = $_POST['note'];
        $update_time = time() - 60 * intval($_POST['time']);

        if ($update_stmt = $connection->prepare("UPDATE ".Table::BOSSES." SET note = ?, death_time = ?, last_update = ? WHERE id = ?"))
        {
            $update_stmt->bind_param('siii', $update_note, $update_time, $_SESSION['id'], $update_id);
            $update_stmt->execute();
            $update_stmt->close();
        }

        Action::log($_SESSION['id'], "BOSS_UPDATE", $update_time, $update_id, $update_note);
    }
?>
