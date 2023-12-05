<?php
mb_internal_encoding("UTF-8");

/* AES-256-GCM - Crpyt and Decrypt (any text) */

function encrypt_AES256($stringToEncrypt, $secretKey, $add="") {
    $key = hash('sha256', $secretKey, true);
    $add = hash('sha512', $add, true);
    $ivLength = openssl_cipher_iv_length('aes-256-gcm');
    $iv = openssl_random_pseudo_bytes($ivLength);

    $tag = "";
    $encrypted = openssl_encrypt($stringToEncrypt, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, $add);

    return base64_encode($iv . $tag . $encrypted);
}

function decrypt_AES256($encrypted, $secretKey, $add="") {
    $key = hash('sha256', $secretKey, true);
    $add = hash('sha512', $add, true);
    $data = base64_decode($encrypted);

    $ivLength = openssl_cipher_iv_length('aes-256-gcm');
    $ivDec = substr($data, 0, $ivLength);
    $tagDec = substr($data, $ivLength, 16);

    return openssl_decrypt(substr($data, $ivLength + 16), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $ivDec, $tagDec, $add);
}

/* HASHING Functions */

function sha256($text, $raw=false) {
    return hash('sha256', $text, $raw);
}

function sha512($text, $raw=false) {
    return hash('sha512', $text, $raw);
}

function chat_hash($a, $b) {
    $arr_name = [$a, $b];
    sort($arr_name);
    return sha256($arr_name[0] . $arr_name[1]);
}

/* end-to-end Encryption */

function generate_EndToEnd_keys($keysName, $encryptionKey) {
    $config = [
        "digest_alg" => "sha512",
        "private_key_bits" => 4096,
        "private_key_type" => OPENSSL_KEYTYPE_RSA
    ];

    $privateKeyResource = openssl_pkey_new($config);

    if(!$privateKeyResource) {
        while ($msg = openssl_error_string()) {
            echo $msg . "<br>";
        }

        die("<br><br>Error during the generation of private key!<br>");
    }

    $success = openssl_pkey_export($privateKeyResource, $privateKeyString);

    if(!$success) {
        die("<br><br>Error during the exporting of private key.");
    }

    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-gcm'));
    $tag = "";

    $encryptedPrivateKey = openssl_encrypt(
        $privateKeyString,
        'aes-256-gcm',
        $encryptionKey,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    $encryptedData = json_encode([
        'iv' => base64_encode($iv),
        'tag' => base64_encode($tag),
        'key' => base64_encode($encryptedPrivateKey)
    ]);

    file_put_contents("endtoend_keys/private_" . $keysName . ".json", $encryptedData);

    $publicKeyDetails = openssl_pkey_get_details($privateKeyResource);

    if(!$publicKeyDetails) {
        die("Error getting public key details!<br>");
    }

    $publicKey = $publicKeyDetails['key'];
    file_put_contents("endtoend_keys/public_" . $keysName . ".pem", $publicKey);
}

function get_private_key($privateKeyFileName, $encryptionKey) {
    $encryptedData = file_get_contents("endtoend_keys/private_" . $privateKeyFileName . ".json");
    $data = json_decode($encryptedData, true);

    if(!$data) {
        return null;
    }

    $iv = base64_decode($data['iv']);
    $tag = base64_decode($data['tag']);
    $encryptedPrivateKey = base64_decode($data['key']);

    $decryptedPrivateKeyString = openssl_decrypt(
        $encryptedPrivateKey,
        'aes-256-gcm',
        $encryptionKey,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    if($decryptedPrivateKeyString === false) {
        return null;
    }

    return $decryptedPrivateKeyString;
}

function get_publick_key($id) {
    return file_get_contents("endtoend_keys/public_" . $id . ".pem");
}

function crypt_endToEnd($text, $public_key) {
    $cipherText = null;
    if(!openssl_public_encrypt($text, $cipherText, $public_key)) {
        return null;
    }
    return base64_encode($cipherText);
}

function validate_fingerprint($chatHash, $friend_id) {
    // Open chat file
    $DB_CHAT = decrypt_json("chats/" . $chatHash . ".json.enc");
    if(!$DB_CHAT) {
        return null; // public key status: unknown - error in fetching chat
    }

    // Decrypt friend's public key
    $enc_friend_fingerprint = $DB_CHAT["fingerprint_" . $friend_id];
    $privateKey = get_private_key($_SESSION["user_id"], $_SESSION['token']);
    $friend_fingerprint = null;
    if(!openssl_private_decrypt($enc_friend_fingerprint, $friend_fingerprint, $privateKey)) {
        return false;
    }
    // to complete

    return true;
}

/* RegEX Checks & other utilities */

function isUrl($url) {
    $regex = "/^(https?:\/\/)?[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}(:[0-9]{1,5})?(\/\S*)?$/";
    if(preg_match($regex, $url)) {
        return true;
    }
    return false;
}

function convertUrlsToLinks($text) {
    $urlRegex = "/(https?:\/\/[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}(?:\:[0-9]{1,5})?(?:\/\S*)?)|\b[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}\b/";
    return preg_replace_callback(
        $urlRegex,
        function($matches) {
            $url = $matches[0];
            if (!isUrl($url)) {
                return $url;
            }

            $displayUrl = str_replace(array('http://', 'https://', 'www.'), '', $url);
            if (!preg_match('/^(http|https):\/\//', $url)) {
                $url = 'http://www.' . $url;
            }

            return '<a href="' . $url . '" target="_blank" style="color: white; text-decoration: underline 1px white;">' . $displayUrl . '</a>';
        },
        $text
    );
}

function startsWith($haystack, $needle) {
    return strpos($haystack, $needle) === 0;
}

function array_remove($pos, &$array) {
    $out = $array[$pos];
    array_splice($array, $pos, 1);
    return $out;
}

function array_erase($elem, &$array) {
    $k = array_search($elem, $array);
    if($k === false) return false;
    array_remove($k, $array);
    return true;
}

function formatMicrotime($microtime, $format='H:i') {
    // return date('m/d/y - H:i', $microtime); // or 'm/d/y'
    return date($format, $microtime);
}

function gen_random_bytes($len) {
    $bytes = random_bytes($len);
    return bin2hex($bytes);
}

function add_entropy($key) {
    $result = '';
    $len = strlen($key);
    for ($i = 0; $i < $len; $i++) {
        $char = $key[$i];
        if (ctype_alpha($char)) {
            $result .= mt_rand(0, 1) ? strtoupper($char) : strtolower($char);
        } else {
            $result .= $char;
        }
    }
    return $result;
}

function new_messagge($type, $prop, $jsonMsg, $tk) {
    $new_msg = new stdClass;

    $len_id = 8;
    $id = gen_random_bytes($len_id);

    while (isset($jsonMsg["messages"][$id])) {
        $id = gen_random_bytes($len_id);
    }

    $new_msg->prop = $prop;
    if($type === "text") {
        $new_msg->type = "text";
    } else {
        $new_msg->type = encrypt_AES256($type, $tk);
    }
    $new_msg->timestamp = encrypt_AES256(strval(time()), $tk);

    return [$id, $new_msg];
}

?>
