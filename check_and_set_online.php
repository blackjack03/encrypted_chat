<?php
// Open online status file
$file_path = "js_version/users_online_status.json";
$online_file = file_get_contents($file_path);
$online_users = json_decode($online_file, true); // file var
unset($online_file);

function saveDB() {
    global $file_path;
    global $online_users;
    file_put_contents($file_path, json_encode($online_users));
}

function is_online($id) {
    global $online_users;
    
    if(!isset($online_users[$id])) {
    	return false;
    }

    $time1 = time();
    $time2 = $online_users[$id];

    $diff = abs($time1 - $time2);

    // Refresh every 15s, user is offline if last refresh of his ID was more than 20s ago
    if($diff > 20) {
        return false;
    }

    return true;
}

function set_offline($id) {
    global $online_users;
    unset($online_users[$id]);
    saveDB();
}

if($_POST && isset($_POST["ver_id"])) {
    if(!is_online($_POST["ver_id"])) {
        set_offline($_POST["ver_id"]);
           echo "0";
    } else {
        echo "1";
    }
}
else
{

// Check online status of ALL users & 'garbage collector'
foreach ($online_users as $key => $value) {
    if(!is_online($key)) {
        set_offline($key);
    }
}

} // end else

// Set user online
if($_POST) {
    if(isset($_POST["id"])) {
    	$online_users[$_POST["id"]] = time();
    	saveDB();
    }
}

?>
