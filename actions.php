<?php
    require_once('connect.php');

    class Action
    {
        static function log($user, $action, $action_param, $boss, $note)
        {
            global $connection;
            if ($stmt = $connection->prepare("INSERT INTO ".Table::LOGS." (user, time, action, action_param, boss_id, note, ip) VALUES (?, ?, ?, ?, ?, ?, ?)"))
            {
                $cur_time = time();
                $stmt->bind_param("iississ", $user, $cur_time, $action, $action_param, $boss, $note, $_SERVER['REMOTE_ADDR']);
                $stmt->execute();
                $stmt->close();
            }
        }

        static function restart()
        {
            global $connection;
            if ($update_stmt = $connection->prepare("UPDATE ".Table::BOSSES." SET note = '', death_time = 0, last_update = 0"))
            {
                $update_stmt->execute();
                $update_stmt->close();
            }
            Action::log($_SESSION['id'], "SERVER_RESTART", 0, 0, "");
        }

        static function cache($filename, $caching_time, $data_func)
        {
            if (!is_dir(dirname($filename)))
                mkdir(dirname($filename), 0700, true);
        
            if (!file_exists($filename) || filemtime($filename) + $caching_time < time())
            {
                $file = new SplFileObject($filename, 'w');
                $data = $data_func();
                $file->fwrite(json_encode($data));
                return $data;
            }
            return json_decode(file_get_contents($filename));
        }
    }
?>
