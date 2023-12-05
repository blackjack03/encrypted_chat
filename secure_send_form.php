<?php
include 'database.php';
include 'crypt.php';

session_start();

mb_internal_encoding("UTF-8");

if(!isset($_SESSION['logged'])) {
    header("Location: login.php");
}

$DB = null;
$DB_CHAT = null;

if(openDB() !== false) { // Open Database
    $DB = $GLOBALS["DB"];
} else {
    echo "<p style='color: red; font-weight: bold;'>Error!</p><br>";
    exit;
}

if(isset($_FILES['uploadedFile']) && isset($_SESSION['friend_id'])) {
    $txt_msg = null;

    $tempFile = $_FILES['uploadedFile']['tmp_name'];
    $txt_msg = file_get_contents($tempFile);

    // Delete TMP file
    unlink($tempFile);

    if($txt_msg === null) {
        header("Location: send_form_file.php");
        die();
    }

    $chatHash = chat_hash($_SESSION["user_id"], $_SESSION["friend_id"]);
    $DB_CHAT = decrypt_json("chats/" . $chatHash . ".json.enc");

    if($DB_CHAT === false) {
        die("Error during decryption of chat's files!<br>");
    }

    // AES key generation
    $k = add_entropy(gen_random_bytes(32));

    $sender_id = password_hash($_SESSION['token'], PASSWORD_BCRYPT);

    $new = new_messagge('text', $sender_id, $DB_CHAT, $_SESSION['chat_token']);

    $DB_CHAT["messages"][$new[0]] = $new[1];

    array_push($DB_CHAT["msg_order"], $new[0]);

    // AES first encryption
    $DB_CHAT["messages"][$new[0]]->enc_msg = encrypt_AES256($txt_msg, $k);

    // end-to-end encryption
    $public_key = file_get_contents("endtoend_keys/public_" . $_SESSION['friend_id'] . ".pem");
    $ciphertext = null;

    if(openssl_public_encrypt($k, $ciphertext, $public_key)) {
        $DB_CHAT["messages"][$new[0]]->key_pgp = base64_encode($ciphertext);
    } else {
        die("<p style='color: red; font-weight: bold;'>Error during end-to-end encryption!</p><br>");
    }

    // end-to-end encryption (with my public key)
    $my_public_key = file_get_contents("endtoend_keys/public_" . $_SESSION['user_id'] . ".pem");
    $ciphertext2 = null;

    if(openssl_public_encrypt($k, $ciphertext2, $my_public_key)) {
        $DB_CHAT["messages"][$new[0]]->key_my_pgp = base64_encode($ciphertext2);
    } else {
        die("<p style='color: red; font-weight: bold;'>Error during end-to-end encryption!</p><br>");
    }

    // Save
    $new_chat = fopen("chats/" . $chatHash . ".json.enc", "w") or die("Unable to create file!<br>");
    fwrite($new_chat, encrypt_json($DB_CHAT));
    fclose($new_chat);

    header("Location: send_form_file.php");
} // end $_POST

?>
