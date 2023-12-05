<?php
include 'database.php';
include 'crypt.php';

session_start();

mb_internal_encoding("UTF-8");

if(openDB() === false) { // Open Database
    die("-1");
}

if(!isset($_SESSION['logged'])) {
    header("Location: login.php");
    die();
}

$DB_CHAT = null;

// Function to parse the messages
function parse_msg($msg, $privateKey, $msg_id) {
    $personal = [];

    $delAllow = false;

    if(password_verify($_SESSION['token'], $msg["prop"])) {
        $personal = ["key_my_pgp", "my"]; // json prop, CSS class
        $delAllow = true;
    } else {
        $personal = ["key_pgp", "fr"];
    }

    if(!$delAllow) {
        if($_SESSION["type"] == "ADMIN") {
            $delAllow = true;
        } else if ($_SESSION["type"] == "MOD" && $GLOBALS["DB"][$_SESSION["friend_id"]]["type"] != "ADMIN") {
            $delAllow = true;
        }
    }

    $aes_key = decrypt_endToEnd($msg[$personal[0]], $privateKey);

    if($aes_key === false) {
        die("-1");
    }

    $TYPE = "text";
    if($msg["type"] !== "text") {
        $TYPE = decrypt_AES256($msg["type"], $_SESSION['chat_token']);
    }

    $timestamp = intval(decrypt_AES256($msg["timestamp"], $_SESSION['chat_token']));

    $DIVdate = $_SESSION['div_date'];

    // $delThisButton = ($delAllow) ? '<div><a href="del_msg.php?id=' . $msg_id . '&fr_id=' . $_SESSION["friend_id"] . '" class="aDelMsg"><button class="delMsg">X</button></a></div>' : ''; // no-JS version
    /* For JS Version only */
    $jsFunction = "jsDelMsg('$msg_id', '" . $_SESSION["friend_id"] . "');";
    $delThisButton = ($delAllow) ? '<div><button class="delMsg" onclick="' . $jsFunction . '">X</button></div>' : '';

    // Default start & end string for each messagge
    $start_str = "<div class='" . $personal[1] . "' id='" . $msg_id . "'>$delThisButton<div style='overflow-y: auto;'>";
    $end_str   = "</div><div class='time'>" . formatMicrotime($timestamp) . "</div></div>";

	// Date
    $dt = formatMicrotime($timestamp, 'm/d/y');
    if($DIVdate != $dt) {
    	echo "<div class='dayView' title='mm/dd/yy'>$dt</div>";
        $_SESSION['div_date'] = $dt;
    }

    if($TYPE == "text") {
    	$raw_msg = decrypt_AES256($msg["enc_msg"], $aes_key);
        $first_bake = nl2br($raw_msg, false);
        $second_bake = preg_replace('/\*\*\*(.*?)\*\*\*/', '<b>$1</b>', $first_bake);
        $third_bake = preg_replace('/\'\'\'(.*?)\'\'\'/', '<i>$1</i>', $second_bake);
        $fourth_bake = preg_replace('/___(.*?)___/', '<u>$1</u>', $third_bake);
        $decryptedMessage = convertUrlsToLinks($fourth_bake);
        return $start_str . $decryptedMessage . $end_str;
    } else { // if file
        $dec_name = decrypt_AES256($msg["fname"], $aes_key);
        $file = decrypt_AES256(file_get_contents("files/". $dec_name . ".encf"), $aes_key);

        if($file === false) {
            die("-1");
        }

        // Get file info
        $file_info = explode("::", $file);
        $file_name = base64_decode($file_info[0]);

        // Handle big file
        if(filesize("files/". $dec_name . ".encf") > 4194304) { // 4MB gross
            return $start_str . "<a href='view_big_file_js.php?fname=$dec_name&TYPE=$TYPE&id=$msg_id&t=$timestamp' style='color: white; text-decoration: underline 2px white; cursor: pointer;' target='_blank'>$file_name</a>" . $end_str;
        }

        $fsplit = explode(".", $file_name);
        $ftype = strtolower(end($fsplit));

        if($TYPE == "video" || $TYPE == "audio") {
            $extVideoAud = "<div style='color: transparent; width: 1px; height: 3px;'></div><a href='view_big_file_js.php?fname=$dec_name&TYPE=$TYPE&id=$msg_id&t=$timestamp' target='_blank'><i class='fa-regular fa-arrow-up-right-from-square fa-lg' style='color: white;'></i></a>";
        }

        if($TYPE == "image") {
            return $start_str . "<a href='view_big_file_js.php?fname=$dec_name&TYPE=$TYPE&id=$msg_id&t=$timestamp' target='_blank'><img src='$file_info[1]' title='$file_name' style='max-width: 100%;'></a>" . $end_str;
        } else if($TYPE == "video") {
            return $start_str . "<video style='max-width: 100%;' controls><source src='$file_info[1]' type='video/$ftype'>Your browser does not support the video tag.</video><br>" . $extVideoAud . $end_str;
        } else if($TYPE == "audio") {
            $audioTypes = [
                'mp3'  => 'audio/mpeg',
                'ogg'  => 'audio/ogg',
                'wav'  => 'audio/wav',
                'aac'  => 'audio/aac',
                'flac' => 'audio/flac',
                'opus' => 'audio/opus',
                'webm' => 'audio/webm',
                'm4a'  => 'audio/x-m4a',
                '3gp'  => 'audio/3gpp',
                '3g2'  => 'audio/3gpp2'
            ];

            $aud_mime = $audioTypes[$ftype] ?? 'unknown';
            $html_type = ($aud_mime!='unknown')?"type='" . $aud_mime . "'":"";
            return $start_str . "<audio style='max-width: 100%;' controls><source src='$file_info[1]' $html_type>Your browser does not support the audio element.</audio><br>" . $extVideoAud . $end_str;
        } else {
            return $start_str . "<a href='$file_info[1]' style='color: white; text-decoration: underline 2px white; cursor: pointer;' download='$file_name'>$file_name</a>" . $end_str;
        }
    }

}


if($_POST && isset($_SESSION["friend_id"])) // ->
{

if(!isset($_SESSION['old_id'])) {
    $_SESSION['old_id'] = $_SESSION["friend_id"];
    $_SESSION['div_date'] = "";
} else {
    if($_SESSION['old_id'] != $_SESSION["friend_id"]) {
        $_SESSION['old_id'] = $_SESSION["friend_id"];
        $_SESSION['div_date'] = "";
    }
}

$chatHash = chat_hash($_SESSION["user_id"], $_SESSION["friend_id"]);
$DB_CHAT = decrypt_json("chats/" . $chatHash . ".json.enc");

if($DB_CHAT === false) {
    die("-1");
}

$privateKey = get_private_key($_SESSION["user_id"], $_SESSION['token']);

// Get token chat
$_SESSION['chat_token'] = decrypt_endToEnd($DB_CHAT["token_" . $_SESSION["user_id"]], $privateKey);

if($_SESSION['chat_token'] === false) {
    die("-1");
}

$arr = $DB_CHAT["msg_order"];
$chat_len = count($arr);

if($_POST['f'] == "getArr") {
    $user = $GLOBALS["DB"][$_SESSION["friend_id"]]["username"];
    if($DB_CHAT["fingerprint_" . $_SESSION["friend_id"]] == "") {
        echo "notStillAllowed:" . $user;
        return;
    } else if ($DB_CHAT["fingerprint_" . $_SESSION["user_id"]] == "") {
        echo "authorizeRequest:" . $user;
        return;
    }

    if(!isset($_POST['from'])) {
        echo json_encode($arr);
    } else {
        echo json_encode(array_slice($arr, $_POST['from']));
    }
} else if($_POST['f'] == "getPers") {
    $req_array = json_decode($_POST['req'], true);
    $req_len = count($req_array);

    for ($i = 0; $i < $req_len; $i++) {
        echo parse_msg($DB_CHAT["messages"][$req_array[$i]], $privateKey, $req_array[$i]);
    }
} else if ($_POST['f'] == "getAll") {
    for ($i = 0; $i < $chat_len; $i++) {
        echo parse_msg($DB_CHAT["messages"][$arr[$i]], $privateKey, $arr[$i]);
    }
} else if ($_POST['f'] == "getByID") {
    echo parse_msg($DB_CHAT["messages"][$_POST['id']], $privateKey, $_POST['id']);
} else if ($_POST['f'] == "getOtherMsg") {
    // Open settings file
    $file_settings = file_get_contents("js_version/settings.json");
    $settings = json_decode($file_settings, true);
    unset($file_settings);

    $new_old_msg = array();

    $max_messagges = $settings["max_messagges"];

    if($max_messagges > $_POST['to']) {
        $from = 0;
    } else {
        $from = $_POST['to'] - $max_messagges;
    }

    for ($i = $from; $i < $_POST['to']; $i++) {
        array_push($new_old_msg, $arr[$i]);
    }

    echo json_encode($new_old_msg);
} else if($_POST['f'] == "SET_dayView") {
	$_SESSION['div_date'] = $_POST["d"];
}

} // end $_POST

?>
