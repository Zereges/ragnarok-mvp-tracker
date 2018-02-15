<?php
    session_start();
    require_once('connect.php');

    if (isset($_SESSION['id']))
    {
        if ($update_stmt = $connection->prepare("UPDATE ".Table::USERS." SET last_update = ".time()." WHERE id = ?"))
        {
            $update_stmt->bind_param('i', $_SESSION['id']);
            $update_stmt->execute();
            $update_stmt->close();
        }

        if ($stmt = $connection->prepare(
        "
            SELECT ".Table::BOSSES.".id, death_time + min_time, death_time + min_time + max_time, note, ".Table::USERS.".name, type
            FROM ".Table::BOSSES."
            LEFT JOIN ".Table::USERS." ON ".Table::USERS.".id = ".Table::BOSSES.".last_update
        "
        ))
        {
            $stmt->execute();
            $stmt->bind_result($boss_id, $min_time, $max_time, $note, $user, $type);

            $arr = array();
            while ($stmt->fetch())
            {
                array_push($arr, array
                (
                    'id' => $boss_id,
                    'min_spawntime' => $min_time,
                    'max_spawntime' => $max_time,
                    'note' => $note,
                    'last_update' => $user,
                    'type' => $type,
                ));
            }
            $stmt->close();
            echo json_encode($arr);
        }
    }
?>
