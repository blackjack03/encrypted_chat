<?php
include 'database.php';
include 'crypt.php';

session_start();

mb_internal_encoding("UTF-8");

if(!isset($_SESSION['logged'])) {
    header("Location: login.php");
}

if(!isset($_SESSION["refresh"])) {
    $_SESSION["refresh"] = true;
}

$DB = null;
$DB_CHAT = null;

if(openDB() !== false) { // Open Database
    $DB = $GLOBALS["DB"];
} else {
    echo "<p style='color: red; font-weight: bold;'>Error!</p><br>";
    exit;
}

if(isset($_SESSION["friend_id"]) && !isset($_GET["id"]) && !$_POST) {
    unset($_SESSION["friend_id"]);
}

// New Chat function
function create_new_chat($chatHash) {
    // Create new chat
    $new_chat = fopen("chats/" . $chatHash . ".json.enc", "w") or die("Unable to create file!<br>");

    $chat_token = add_entropy(gen_random_bytes(32));

    // end-to-end encryption
    $public_key = file_get_contents("endtoend_keys/public_" . $_SESSION['friend_id'] . ".pem");
    $ciphertext = null;
    if(!openssl_public_encrypt($chat_token, $ciphertext, $public_key)) {
        die("<p style='color: red; font-weight: bold;'>Error during end-to-end encryption 1!</p><br>");
    }

    // end-to-end encryption (with my public key)
    $my_public_key = file_get_contents("endtoend_keys/public_" . $_SESSION['user_id'] . ".pem");
    $ciphertext2 = null;
    if(!openssl_public_encrypt($chat_token, $ciphertext2, $my_public_key)) {
        die("<p style='color: red; font-weight: bold;'>Error during end-to-end encryption 2!</p><br>");
    }

    // Encrypt with friend's public key my Fingerprint
    $cipherFingerPrint = null;
    if(!openssl_public_encrypt($_SESSION['fingerprint'], $cipherFingerPrint, $public_key)) {
        die("<p style='color: red; font-weight: bold;'>Error during end-to-end encryption of fingerprint!</p><br>");
    }

    $new_std_msg = new stdClass;
    $new_std_msg->{"fingerprint_" . $_SESSION["friend_id"]} = "";
    $new_std_msg->{"fingerprint_" . $_SESSION["user_id"]} = $cipherFingerPrint;
    $new_std_msg->{"token_" . $_SESSION["friend_id"]} = base64_encode($ciphertext);
    $new_std_msg->{"token_" . $_SESSION["user_id"]} = base64_encode($ciphertext2);
    $new_std_msg->msg_order = array();
    $new_std_msg->messages = new stdClass;

    fwrite($new_chat, encrypt_json($new_std_msg));
    fclose($new_chat);
}

if($_GET) {
    if(isset($_GET['id'])) {
        $_SESSION["friend_id"] = $_GET['id'];

        $chatHash = chat_hash($_SESSION["user_id"], $_SESSION["friend_id"]);

        if( !file_exists("chats/" . $chatHash . ".json.enc") ) {
            create_new_chat($chatHash);
        }

    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CHAT</title>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://jackprogram.altervista.org/libraries/font_awesome_pro.js"></script>
    <style>
        body {
            margin: 0;
            background: whitesmoke;
            display: flex;
            justify-content: start;
            flex-direction: column;
            font-family: arial;
        }

        .titleDiv {
            width: 100%;
            height: 50px;
            text-align: center;
            background: gray;
            position: fixed;
            top: 0;
            left: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid black;
            box-shadow: 0px 3px 5px 0px rgba(0, 0, 0, 0.7);
        }

        .titleDiv h2 {
            margin: 0;
            color: whitesmoke;
            text-shadow: 0px 0px 1px white;
        }

        .chatScreen {
            position: fixed;
            width: 99%;
            height: calc(100% - 60px);
            left: 0;
            bottom: 0;
            background: transparent;
            padding: 4px;
            overflow-x: hidden;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            align-items: start;
        }

        .chatScreen div {
            padding: 2px;
            font-size: 18px;
            font-family: arial;
        }

        .chatScreen .my, .chatScreen .fr {
            display: grid;
            margin-bottom: 5px;
            min-width: 120px;
            max-width: 40%;
            padding: 3px;
            border-radius: 8px;
        }

        .chatScreen .my {
            align-self: end;
            background: blueviolet;
            color: white;
        }

        .chatScreen .fr {
            align-self: start;
            background: gray;
            color: white;
        }

        .time {
            font-size: 14px !important;
            margin-top: 4px;
        }

        .refresh {
            position: fixed;
            top: 10px;
            left: 80px;
            z-index: 9;
            width: 30px;
            height: 30px;
            background: lime;
            border: none;
            color: black;
            font-weight: bold;
            font-size: 18px;
            border-radius: 8px;
            box-shadow: 0px 0px 4px lime;
            cursor: pointer;
            transition: 0.2s;
        }

        .refresh:hover {
            box-shadow: 0px 0px 1px lime;
        }

        .refresh:active {
            transform: scale(0.9);
        }

        .dayView {
            align-self: center;
            width: 100px;
            min-height: 30px;
            background: yellow;
            border-radius: 12px;
            font-weight: bold;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .autoRefForm {
            position: fixed;
            top: 10px;
            left: 25px;
            width: 30px;
            height: 30px;
            margin: 0;
            padding: 0;
            z-index: 9;
        }

        .autoRefForm input {
            width: 100%;
            height: 100%;
            display: block;
            border: none;
            outline: none;
            cursor: pointer;
            transition: 0.2s;
        }

        .delMsg {
            float: right;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            border: none;
            background: red;
            font-family: arial;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            color: white;
            transition: 0.15s;
        }

        .delMsg:hover {
            box-shadow: 0px 0px 6px red;
        }

        .delMsg:active {
            transform: scale(0.9);
        }

        #formDelMsg {
            position: fixed;
            top: 52.5%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 80%;
            height: 70%;
            box-shadow: 0px 0px 6px blueviolet;
            background-color: white;
            border-radius: 8px;
            display: none;
        }
        
        .loading {
            position: absolute;
            width: 100%;
            height: 100%;
            z-index: 9;
            background: rgba(255, 255, 255, 0.7);
            display: none; /* temp */
        }
        
        /* Loading */
        .loading div {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 15%; /* Circle size */
            aspect-ratio: 1 / 1;
            border-radius: 50%;
            border: 5px solid blueviolet;
            border-bottom-color: transparent; /* or rgb(189, 129, 245) */
            animation: spin 1s linear infinite;
            /* box-shadow: 0px 0px 3px blueviolet; */
            display: none; /* temp */
        }

        .loading div::after {
            content: "";
            display: block;
            padding-bottom: 100%;
        }

        .lockScrollIcon {
            z-index: 9;
            position: fixed;
            width: 50px;
            height: auto;
            right: 10px;
            top: 14px;
            font-size: 20px;
            color: white;
            text-shadow: 0px 0px 1px white;
            cursor: pointer;
        }

        @keyframes spin {
            to {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }
    </style>
    <style>
        #authorizeRequest, #notStillAllowed {
            width: 80%;
            height: 70%;
            align-self: center;
        }

        #authorizeRequest {
            /* todo */
        }

        #notStillAllowed {
            background-color: #191919;
            color: white;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 18px;
        }
    </style> <!-- CSS for auth -->
    <script>
        Array.prototype.remove = function (pos) {
            this.splice(pos, 1);
        }

        Array.prototype.erase = function (el) {
            let p = this.indexOf(el);
            if(p != -1) {
                this.splice(p, 1);
            } else {
                console.warn(el, ": item not found!");
            }
        }
    </script>
    <script>
        function jsDelMsg(msg_id, frID) {
            let src = `del_msg.php?id=${msg_id}&fr_id=${frID}`;
            document.getElementById("formDelMsg").src = src;
            $("#formDelMsg").show(400);
        }

        function close_formDelMsg(duration=400) {
            $("#formDelMsg").hide(duration);
        }
    </script>
</head>
<body>
    <?php
        if(isset($_SESSION["friend_id"])) {
           echo "<div class='titleDiv'><h2>" . $DB[$_SESSION["friend_id"]]["username"] . "</h2></div>";
        } else {
            echo "<div class='titleDiv'><h2 style='text-align: center;'>Select a Chat</h2></div>\n<style>body{background: rgb(232,232,232);}</style>";
        }
    ?>

    <div class="lockScrollIcon" onclick="change_scroll_block();">
        <i class="fa-regular fa-lock"></i>
    </div>

    <div class="chatScreen" id="chatScreen"></div>
    <div class="loading"><div></div></div>

    <iframe id="formDelMsg" src="" frameborder="0"></iframe>

    <script src="js_version/get_chat.js?<?php echo time() . rand(); ?>"></script>
    <script>
        function forceRefresh() {
            call_chat(true);
        }
    </script>
</body>
</html>
