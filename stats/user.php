<!DOCTYPE html>
<?php
    session_start();
    require_once('../connect.php');
    require_once('../actions.php');

    if (!isset($_SESSION['id']))
    {
        header('Location: index.php');
        exit();
    }

    function get_user_stats($id)
    {
        global $connection;
        $query = 
        '
            SELECT boss_name, COUNT(*) cnt
            FROM '.Table::LOGS.' logs
            JOIN '.Table::BOSSES.' bosses ON boss_id = bosses.id
            WHERE ACTION = "BOSS_UPDATE" AND USER = ?
            GROUP BY boss_name
            ORDER BY cnt DESC
        ';

        $result = array();
        $arr = array();
        if ($stmt = $connection->prepare($query))
        {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->bind_result($boss, $count);
            
            while ($stmt->fetch())
            {
                array_push($arr, array(
                    "boss" => $boss,
                    "count" => $count
                ));
            }
            $stmt->close();
        }

        if (count($arr) == 0)
            return NULL;

        $result["max"] = array($arr[0]);
        $result["min"] = array($arr[count($arr) - 1]);

        if (count($arr) >= 8)
        {
            for ($i = 1; $i <= 3; ++$i)
            {
                array_push($result["max"], $arr[$i]);
                array_push($result["min"], $arr[count($arr) - 1 - $i]);
            }
        }

        $query = 
        '
            SELECT boss_name
            FROM '.Table::BOSSES.'
            WHERE id NOT IN (
                SELECT boss_id
                FROM '.Table::LOGS.'
                WHERE USER = ?
                GROUP BY boss_id
            )
        ';

        $result["zero"] = array();
        if ($stmt = $connection->prepare($query))
        {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->bind_result($boss);
            
            while ($stmt->fetch())
                $result["zero"][] = $boss;
            $stmt->close();
        }

        $query = 
        '
            SELECT DATE(FROM_UNIXTIME(time)), COUNT(*) cnt
            FROM '.Table::LOGS.'
            WHERE action = "BOSS_UPDATE" AND USER = ?
            GROUP BY DATE(FROM_UNIXTIME(time))
            ORDER BY cnt DESC;
        ';
        $result["date"] = array();
        if ($stmt = $connection->prepare($query))
        {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->bind_result($date, $count);
            
            while ($stmt->fetch())
                $result["date"][] = array(
                    "date" => $date,
                    "count" => $count
                );
            $stmt->close();
        }
        

        return $result;
    }
    

    $data = Action::cache(Config::CACHING_DIR . "user_" . $_SESSION['id'], Config::CACHING_TIME, function()
    {
        return get_user_stats($_SESSION['id']);
    });
?>

<html>
<head>
    <meta charset="UTF-8">
    <meta author="Zereges">
    <title><?= Config::GUILD_NAME ?> - MVP Tracker - <?= Config::SERVER_NAME ?> (User trivia)</title>
    <link rel="icon" type="image/png" href="http://www.ekirei.cz/site_icon_eki.png">
    <link rel="stylesheet" href="../main.css">
    <script type="text/javascript" src="lib/jquery-1.11.3.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/cookie-bar/cookiebar-latest.min.js?forceLang=en&theme=altblack&remember=365"></script>
    <style>
    div div
    {
        padding: 5px;
        font-size: 1.3em;
    }
    </style>
</head>
<body>
<h2>Hello <?= $_SESSION['name'] ?>! Here are few interesting pieces of information about you:</h2>
<div style="padding: 30px">
<?php
    if (is_null($data))
        echo "<div>I've spent time developing this and you don't even try to use it?</div>\n";
    else if ($data["max"][0]["boss"] == $data["min"][0]["boss"])
        echo "<div>Oh, come on. There surely are different bosses then ".$data["boss"].". You killed that ".$data["count"]." times already?</div>\n";
    else
    {
        echo "<div>\n";
        echo "You surely want a card from ".$data["max"][0]["boss"].", since you killed it ".$data["max"][0]["count"]." times already.\n";
        echo "<small>You'd also like these ";
        for ($i = 1; $i < count($data["max"]); ++$i)
        {
            echo $data["max"][$i]["boss"]." (".$data["max"][$i]["count"]."x)";
            if ($i != count($data["max"]) - 1)
                echo ", ";
        }
        echo ".</small>\n";
        echo "</div>\n";

        echo "<div>\n";
        echo "You don't seem to be interested in ".$data["min"][0]["boss"].", since you only killed it ".$data["min"][0]["count"]." times.\n";
        echo "<small>You don't like these eighter: ";
        for ($i = 1; $i < count($data["min"]); ++$i)
        {
            echo $data["min"][$i]["boss"]." (".$data["min"][$i]["count"].")";
            if ($i != count($data["min"]) - 1)
                echo ", ";
        }
        echo ".</small>\n";
        echo "</div>\n";

        if (count($data["zero"]) == 0)
            echo "<div>By the way. There are no bosses you haven't killed yet. Congratulatins!</div>\n";
        else
        {
            echo "<div>By the way, go check out ";
            $cnt = count($data["zero"]);
            if ($cnt >= 5)
                $cnt = 5;
            $randpick = array_rand($data["zero"], $cnt);
            for ($i = 0; $i < $cnt; ++$i)
                echo $data["zero"][$randpick[$i]] . ", ";
            echo "since you never tried those.</div>";
        }

        echo "<div>\n";
        echo "Your most active day was ".$data["date"][0]["date"].". ".$data["date"][0]["count"]." bosses died by your hand that day.\n";
        if (count($data["date"]) > 1)
            echo "<small>You were also active on ".$data["date"][1]["date"]." (".$data["date"][1]["count"]." kills).</small>\n";
        echo "</div>\n";

        $total = 0;
        for ($i = 0; $i < count($data["date"]); ++$i)
            $total += $data["date"][$i]["count"];
        echo "<div>You've killed $total bosses during ".count($data["date"])." active days. <small>That's ".intval($total / count($data["date"]))." per day on average.</small></div>\n";
    }
?>
</div>
</body>
</html>
