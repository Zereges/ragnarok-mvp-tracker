function format_time(date)
{
    return ('0' + date.getHours()).slice(-2) + ":" + ('0' + date.getMinutes()).slice(-2) + ":" + ('0' + date.getSeconds()).slice(-2);
}

function format_date(date)
{
    return format_time(date) + " (" + ('0' + date.getDate()).slice(-2) + "." + ('0' + (date.getMonth() + 1)).slice(-2) + "." + date.getFullYear() + ")";
}

function debug_log(str)
{
    var IS_DEBUG = false;
    // var IS_DEBUG = true;
    if (IS_DEBUG)
        console.log("DEBUG (" + format_time(new Date()) + "): " + str);
}

function init()
{
    MVP_TYPE_DISABLED = 5;
    MVP_TYPE_ENDLESS = 4;
    TIME_TO_ALIVE = 1000 * 60 * 60 * 12;
    time_diff = new Date(1000 * document.getElementById("curtime").innerHTML) - new Date();
    update_time();
    document.getElementById("curtime").style.visibility = "";
 
    check_updates(true);
}

function check_updates(forced = false)
{
    debug_log("check_updates() call" + (forced ? " forced" : ""));
    $.ajax({
        url: 'check_updates.php',
        async: true,
        success: function(is_new)
        {
            debug_log("check_updates() return = '" + is_new + "'");
            if (is_new || forced)
                get_updates();
        },
        complete: function()
        {
            debug_log("check_updates() schedule");
            setTimeout(check_updates, 2000);
        }
    });
}

function update_spawntimes()
{
    debug_log("update_spawntimes() call");
    var now = new Date(new Date().getTime() + time_diff);
    for (var id in mvp_data)
    {
        if (mvp_data.hasOwnProperty(id))
        {
            if (mvp_data[id].type == MVP_TYPE_DISABLED)
                continue;

            var tr = document.getElementById("mvp_" + id);
            if (mvp_data[id].type == MVP_TYPE_ENDLESS)
            {
                var time = new Date(1000 * mvp_data[id].spawntime.min);
                if (time.getTime() + TIME_TO_ALIVE < now.getTime())
                    tr.childNodes[2].childNodes[0].innerHTML = "AVAILABLE!";
                else
                    tr.childNodes[2].childNodes[0].innerHTML = format_date(time);
            }
            else
            {
                var min_time = new Date(1000 * mvp_data[id].spawntime.min);
                var max_time = new Date(1000 * mvp_data[id].spawntime.max);
                if (max_time.getTime() + TIME_TO_ALIVE < now.getTime())
                    tr.childNodes[2].childNodes[0].innerHTML = "ALIVE!";
                else
                    tr.childNodes[2].childNodes[0].innerHTML = format_time(min_time) + " - " + format_time(max_time);
            }
            var note = tr.childNodes[0].childNodes[1];
            if (mvp_data[id].note)
            {
                note.style.visibility = 'visible';
                note.childNodes[1].innerHTML = mvp_data[id].note;
            }
            else
            {
                note.style.visibility = 'hidden';
                note.childNodes[1].innerHTML = "";
            }
            tr.childNodes[3].innerHTML = mvp_data[id].last_update;
        }
    }
}

window.onload = init;

function update_time()
{
    var now = new Date(new Date().getTime() + time_diff);
    document.getElementById("curtime").innerHTML = format_date(now);
    setTimeout(update_time, 1000);
}

function update_status()
{
    debug_log("update_status() call");
    var now = new Date(new Date().getTime() + time_diff);

    for (var id in mvp_data)
    {
        if (mvp_data.hasOwnProperty(id))
        {
            var tr = document.getElementById("mvp_" + id);
            if (mvp_data[id].type == MVP_TYPE_ENDLESS)
            {
                var max_time = new Date(1000 * mvp_data[id].spawntime.min);
                if (max_time.getTime() + TIME_TO_ALIVE < now.getTime())
                {
                    tr.childNodes[2].childNodes[0].innerHTML = "AVAILABLE!";
                    tr.className = "boss_ready_recent";
                }
                else if (max_time < now)
                    tr.className = "boss_ready";
                else
                    tr.className = "boss_dead";
                tr.childNodes[2].childNodes[1].innerHTML = "&nbsp;";
 
            }
            else if (mvp_data[id].type != MVP_TYPE_DISABLED)
            {
                var min_time = new Date(1000 * mvp_data[id].spawntime.min);
                var max_time = new Date(1000 * mvp_data[id].spawntime.max);

                if (max_time.getTime() + TIME_TO_ALIVE < now.getTime())
                {
                    tr.className = "boss_ready_recent";
                    tr.childNodes[2].childNodes[0].innerHTML = "ALIVE!";
                    tr.childNodes[2].childNodes[1].innerHTML = "&nbsp;";
                }
                else if (max_time < now)
                {
                    tr.className = "boss_ready";
                    tr.childNodes[2].childNodes[1].innerHTML = "(100%)";
                }
                else if (max_time > now && now > min_time)
                {
                    tr.className = "boss_almost";
                    var time_length = max_time.getTime() - min_time.getTime();
                    var percent = (now.getTime() - min_time.getTime()) / (max_time.getTime() - min_time.getTime());
                    tr.childNodes[2].childNodes[1].innerHTML = "(" + Math.trunc(100 * percent) + "%)";
                }
                else
                {
                    var minsTillSpawn = Math.floor((min_time.getTime() - now.getTime()) / 1000 / 60);
                    tr.className = "boss_dead";
                    tr.childNodes[2].childNodes[1].innerHTML = minsTillSpawn + "min";
                }

            }
        }
    }

    if (typeof statusTimeout != 'undefined')
        clearTimeout(statusTimeout);
    statusTimeout = setTimeout(update_status, 5000);
    debug_log("update_status() scheduled"); 
}

function post_update(updateid)
{
    $.post('update.php', $('#' + updateid).serialize(), function()
    {
        get_updates();
    });

    $('#' + updateid).children("input.fieldnote").val("");
    $('#' + updateid).children("input.fieldtime").val("");
}

function get_updates()
{
    debug_log("get_updates() call");
    mvp_data = {};
    $.ajax({
        url: 'get_updates.php',
        dataType: 'json',
        async: true,
        success: function(data)
        {
            debug_log("get_updates() return");
            for (var i = 0; i < data.length; ++i)
            {
                var id = data[i].id;
                mvp_data[id] = {};
                mvp_data[id].spawntime = {};
                mvp_data[id].spawntime.min = data[i].min_spawntime;
                mvp_data[id].spawntime.max = data[i].max_spawntime;
                mvp_data[id].last_update = data[i].last_update != null ? data[i].last_update : "";
                mvp_data[id].note = data[i].note;
                mvp_data[id].type = data[i].type;
            }
            update_spawntimes();
            update_status();
        }
    });
}

function undolastaction()
{
    $.post('undo.php', function()
    {
        get_updates();
    });
}