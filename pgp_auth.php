<?php
include 'database.php';
include 'crypt.php';
include 'secure_delete_file.php';

session_start();

mb_internal_encoding("UTF-8");

if(openDB() === false) { // Open Database
    die("<p style='color: red; font-weight: bold;'>Error!</p><br>");
}

$DB = $GLOBALS["DB"];

if(!isset($_SESSION['logged'])) {
    header("Location: login.php");
    die();
}

if($_POST) {
    $chatHash = chat_hash($_SESSION["user_id"], $_SESSION["friend_id"]);
    $DB_CHAT = open_chat($chatHash);
    if(array_key_exists("AUTHORIZE", $_POST)) {
        $DB_CHAT["fingerprint_" . $_SESSION["user_id"]] = crypt_endToEnd($_SESSION['fingerprint'], get_public_key($_SESSION['friend_id']));
        save_chat($chatHash, $DB_CHAT);
        header("Location: chat_page_js.php?id=" . $_SESSION["friend_id"]);
    } else if (array_key_exists("REJECT", $_POST)) {
        array_erase($_SESSION["friend_id"] , $DB[$_SESSION["user_id"]]["friends"]);
        array_erase($_SESSION["user_id"] , $DB[$_SESSION["friend_id"]]["friends"]);
        saveDB();
        securelyDeleteFile_gutmann("chats/" . $chatHash . ".json.enc");
    } else if (array_key_exists("REJECT_BLOCK", $_POST)) {
        array_erase($_SESSION["friend_id"] , $DB[$_SESSION["user_id"]]["friends"]);
        array_erase($_SESSION["user_id"] , $DB[$_SESSION["friend_id"]]["friends"]);
        array_push($DB[$_SESSION["user_id"]]["blocked"], $_SESSION["friend_id"]);
        saveDB();
        securelyDeleteFile_gutmann("chats/" . $chatHash . ".json.enc");
    }
}

?>
