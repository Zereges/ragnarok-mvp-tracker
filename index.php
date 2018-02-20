<!DOCTYPE html>
<?php
    session_set_cookie_params(60 * 60 * 24 * 7, '/mvp-tracker/reborn/');
    session_start();
    require_once('connect.php');
    require_once('actions.php');

    const SECRET_KEY = "<REDACTED>";
    const VERSION = "1.2"; // Version number

    mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ERROR);
?>
<html>
    <head>
        <meta charset="UTF-8">
        <meta author="Zereges">
        <title><?= Config::GUILD_NAME ?> - MVP Tracker - <?= Config::SERVER_NAME ?></title>
        <link rel="icon" type="image/png" href="http://www.ekirei.cz/site_icon_eki.png">
        <link rel="stylesheet" href="main.css">
        <script type="text/javascript" src="lib/jquery-1.11.3.min.js"></script>
        <script type="text/javascript" src="main.js"></script>
        <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/cookie-bar/cookiebar-latest.min.js?forceLang=en&theme=altblack&remember=365"></script>
    </head>
    <body>
    <h1><?= Config::GUILD_NAME ?> MVP Tracker - <?= Config::SERVER_NAME ?></h1><br>
<?php
    function try_login()
    {
        if (!isset($_SESSION['username']))
        {
            $cookie = isset($_COOKIE['remember_me']) ? $_COOKIE['remember_me'] : '';
            if ($cookie)
            {
                list ($username, $token, $hash) = explode(":", $cookie);
                if (!hash_equals(hash_hmac("sha256", "$username:$token", SECRET_KEY), $hash))
                    return false;

                global $connection;
                if ($stmt = $connection->prepare("SELECT id, name, token FROM ".Table::USERS." WHERE login = ?"))
                {
                    $stmt->bind_param("s", $username);
                    $stmt->execute();
                    $stmt->bind_result($id, $name, $usertoken);
                    $fetch = $stmt->fetch();
                    $stmt->close();

                    if ($fetch && hash_equals($usertoken, $token))
                    {
                        $_SESSION['username'] = $username;
                        $_SESSION['name'] = $name;
                        $_SESSION['id'] = $id;
                        return true;
                    }
                }
            }
            return false;
        }
        return true;
    }


    if (isset($_POST['login']) && isset($_POST['pass']))
    {
        $username = $_POST['login'];

        if ($stmt = $connection->prepare("SELECT name, id FROM ".Table::USERS." WHERE login = ? and pass = ?"))
        {
            $passhash = md5($_POST['pass']);
            $stmt->bind_param("ss", $username, $passhash);
            $stmt->execute();
            $stmt->bind_result($name, $id);
            $fetch = $stmt->fetch();
            $stmt->close();
            if ($fetch)
            {
                $_SESSION['username'] = $username;
                $_SESSION['name'] = $name;
                $_SESSION['id'] = $id;

                $token = openssl_random_pseudo_bytes(128 / 8);
                if ($token_stmt = $connection->prepare("UPDATE ".Table::USERS." SET token = ? WHERE login = ?"))
                {
                    $token_stmt->bind_param("ss", $token, $username);
                    $token_stmt->execute();
                    $token_stmt->close();
                }

                $cookie = "$username:$token";
                $hash = hash_hmac("sha256", $cookie, SECRET_KEY);
                $cookie .= ":$hash";
                setcookie("remember_me", $cookie, time() + 60 * 60 * 24 * 150);

                Action::log($_SESSION['id'], "LOGIN_SUCCESS", "", 0, "");
            }
            else
            {
                echo "Invalid Login Credentials";
                Action::log(0, "LOGIN_FAILED", $username, 0, "");
            }
        }
    }

    if (!try_login())
    {
?>
        <form method="POST" action="index.php">
            <div align="center">
                <div style="width: 200px" align="right"><label>Login: </label><input type="text" name="login" class="fieldlogin"></div>
                <div style="width: 200px" align="right"><label>Pass: </label><input type="password" name="pass" class="fieldlogin"></div>
                <button type="submit" class="loginbutton">Login</button>
            </div>
        </form>
<?php
    }
    else
    {
?>
        Current Time: <span id="curtime" style="visibility: hidden"><?php echo time() ?></span><br>
        Current User: <?= $_SESSION['name'] ?> <a href="logout.php" class="logout">[Logout]</a><br>
        <form method="post" target="_self" onsubmit="return confirm('Are you sure? This will reset all timers.');" style="display: inline;">
            <input name="restart_init" type="hidden" value="1">
            <input class="loginbutton" value="Server Restart" type="submit">
        </form>
        <button class="loginbutton" onclick="if (confirm('This will undo your last boss update. Are you sure?')) undolastaction()">Undo my last action</button>
<?php
        if (isset($_POST['restart_init']))
            Action::restart();

        foreach ($types as $type => $desc)
        {
            $order_type = $type == 4 ? "map DESC" : "boss_name, map";
            $interval_desc = $type == 4 ? "hrs" : "min";
            $interval_dur = $type == 4 ? 60 * 60 : 60;

            if ($stmt = $connection->prepare("SELECT id, boss_name, min_time, min_time + max_time, map FROM ".Table::BOSSES." WHERE type = $type ORDER BY $order_type"))
            {
                $stmt->execute();
                $stmt->bind_result($id, $boss_name, $min_time, $max_time, $map);
                echo "<h2>$desc</h2>";
                echo "<table align=\"center\">";
                echo "<tr><th width=\"200\">Name</th><th width=\"100\">Map</th><th width=\"220\">Spawntime</th><th width=\"100\">Last update</th><th>Note &amp; Time</th><th width=\"100\">Interval</th></tr>";
                while ($stmt->fetch())
                {
                    $boss_name = "<b>$boss_name</b>" . "<span class=\"boss_note\" style=\"visibility: hidden\">[note]<span class=\"boss_notetext\"></span></span>";
                    $interval = "" . intval($min_time / $interval_dur) . "~" . intval($max_time / $interval_dur) . " $interval_desc";
                    $note_input = "<input class=\"fieldnote\" name=\"note\" maxlength=\"80\" type=\"text\" onkeypress=\"if (event.keyCode == 13) post_update('update_$id') \">";
                    $time_input = "<input class=\"fieldtime\" name=\"time\" maxlength=\"8\" type=\"text\" onkeypress=\"if (event.keyCode == 13) post_update('update_$id') \">";
                    $button_input = "<button class=\"setbutton\" type=\"button\" onclick=\"post_update('update_$id');\">Set</button>";
                    $id_input = "<input name=\"id\" type=\"hidden\" value=\"$id\">";
                    $form = "<form class=\"settime\" id=\"update_$id\">$note_input $time_input$button_input$id_input</form>";
                    echo "<tr id=\"mvp_$id\"><td align=\"left\">$boss_name</td><td align=\"left\">$map</td><td><span></span><span class=\"boss_chance\"></span></td><td></td><td>$form</td><td>$interval</td></tr>";
                }
                echo "</table><br>";
            }
        }
    }
?>
    <div class="menu">
        <div class="linkwrapper"><a target="_blank" href="<?= Config::SERVER_CP ?>">Control Panel</a></div>
        <div class="linkwrapper"><a target="_blank" href="<?= Config::SERVER_DB ?>">Database</a></div>
        <div class="linkwrapper"><a target="_blank" href="<?= Config::SERVER_WIKI ?>">Wikipedia</a></div>
        <div class="linkwrapper"><a target="_blank" href="<?= Config::SERVER_CALC_STAT ?>">Stat Calculator</a></div>
        <div class="linkwrapper"><a target="_blank" href="<?= Config::SERVER_CALC_SKILL ?>">Skill Calculator</a></div>
    </div>
    <div style="position: fixed; bottom: 5px; text-align: right;">©Zereges, Version <?= VERSION ?>, <a href="changelog.txt" target="_blank">Changelog</a>. <small>Based on Gandi's <a href="https://github.com/dangerH/RO-MVP-Timer" target="_blank">MVP Tracker</a>.</small></div>
  </body>
</html>
