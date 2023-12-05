<?php
include 'MASTER_KEYS.php';

$GLOBALS["DB"] = null;

function encrypt_json(&$json_data) {
    $key = hash('sha256', key_word, true);
    $aad = hash('sha512', authentication_phrase, true);

    $jsonData = json_encode($json_data);

    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-gcm'));
    $tag = "";
    $tagLength = 16;

    $encryptedData = openssl_encrypt($jsonData, 'aes-256-gcm', $key, $options=0, $iv, $tag, $aad, $tagLength);

    if($encryptedData === false) {
        return false;
    } else {
        return base64_encode($iv . $tag . $encryptedData);
    }
}

function decrypt_json($file_name) {
    $key = hash('sha256', key_word, true);
    $aad = hash('sha512', authentication_phrase, true);

    $encryptedDataWithIvTag = file_get_contents($file_name);

    $decodedData = base64_decode($encryptedDataWithIvTag);

    $ivLength = openssl_cipher_iv_length('aes-256-gcm');
    $iv = substr($decodedData, 0, $ivLength);
    $tagLength = 16;
    $tag = substr($decodedData, $ivLength, $tagLength);

    $encryptedData = substr($decodedData, $ivLength + $tagLength);

    $decryptedData = openssl_decrypt($encryptedData, 'aes-256-gcm', $key, $options=0, $iv, $tag, $aad);

    if($decryptedData === false) {
        return false;
    } else {
        return json_decode($decryptedData, true);
    }
}

function openDB() {
    $decryptedData = decrypt_json('db.json.enc');

    if($decryptedData === false) {
        return false;
    } else {
        $GLOBALS["DB"] = $decryptedData;
        return true;
    }
}

function saveDB() {
    $encryptedData = encrypt_json($GLOBALS["DB"]);

    if($encryptedData === false) {
        return false;
    } else {
        file_put_contents('db.json.enc', $encryptedData);
        return true;
    }
}

function save_chat($chat_hash, &$db_chat) {
    $new_chat = fopen("chats/" . $chat_hash . ".json.enc", "w");
    if($new_chat === false) {
        return false;
    }
    if(fwrite($new_chat, encrypt_json($db_chat)) === false) {
        return false;
    }
    fclose($new_chat);
    return true;
}

function open_chat($chat_hash) {
    return decrypt_json("chats/" . $chat_hash . ".json.enc");
}

?>
