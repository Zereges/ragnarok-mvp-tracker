<?php
    class Config
    {
        // Database
        const HOSTNAME = 'host'; // hostname of MySQL server
        const USERNAME = 'user'; // username
        const PASSWORD = 'pass'; // password
        const DATABASE = 'database'; // database
        const TABLE_PREFIX = 'prefix'; // table prefix used

        // Server Info
        const SERVER_NAME = 'Ragnarok'; // Server name
        const SERVER_CP = 'https://link'; // Control panel link
        const SERVER_DB = 'https://www.ratemyserver.net/'; // Link to the database to use
        const SERVER_WIKI = 'https://irowiki.org/classic/'; // Link to the wiki to use
        const SERVER_CALC_STAT = 'https://rocalc.com/'; // Link to the stat calculator
        const SERVER_CALC_SKILL = 'http://irowiki.org/~himeyasha/skill4/'; // Link to the skill calculator

        // Guild Info
        const GUILD_NAME = "Guild"; // Guild name
    }

    class Table
    {
        const USERS = Config::TABLE_PREFIX.'_users';
        const LOGS = Config::TABLE_PREFIX.'_logs';
        const BOSSES = Config::TABLE_PREFIX.'_bosses';
    }

    // used sections
    $types = array(
        1 => "MVPs",
        2 => "MiniBosses",
        3 => "Guild Dungeons",
        4 => "Instances",
        // 5 => "Disabled",
    );
?>
