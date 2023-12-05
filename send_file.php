<?php
include 'database.php';
include 'crypt.php';

session_start();

mb_internal_encoding("UTF-8");

if(!isset($_SESSION["friend_id"])) {
    header("Location: send_form.php");
    die();
}

function convertToBytes($value) {
    $value_length = strlen($value);
    $qty = intval(substr($value, 0, $value_length - 1));
    $unit = strtolower(substr($value, $value_length - 1));
    switch ($unit) {
        case 'k':
            $qty *= 1024;
            break;
        case 'm':
            $qty *= 1048576;
            break;
        case 'g':
            $qty *= 1073741824;
            break;
    }
    return $qty;
}

function bytesToMB($mb) {
    return $mb * 1024 * 1024;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['fileToUpload'])) {
        $file = $_FILES['fileToUpload'];

        $uploadedFileName = $file['name'];

        $MAX_FILE_SIZE = bytesToMB(1); // 1MB

        if($_SESSION['type'] === "ADMIN") {
            $upload_max_filesize = ini_get('upload_max_filesize');
            $MAX_FILE_SIZE = convertToBytes($upload_max_filesize); // MAX
        } else if($_SESSION['type'] === "MOD") {
            $MAX_FILE_SIZE = $MAX_FILE_SIZE = bytesToMB(12); // 12MB
        }

        // Max file size 1MB
        if ($file['size'] > $MAX_FILE_SIZE) {
            $maxMB = floor($MAX_FILE_SIZE / 1000000);
            die("File too large! Maximum size: $maxMB MB<br><a href='send_form.php' style='color: blue;'>OK</a><br>");
        }

        // Get the MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        // Determinate file type
        $fileType = pathinfo($file['name'], PATHINFO_EXTENSION);
        $type = "file"; // Default

        if (preg_match('/^audio\//', $mime_type)) {
            $type = "audio";
        } else if (preg_match('/^video\//', $mime_type)) {
            $type = "video";
        } else if (preg_match('/^image\//', $mime_type)) {
            $type = "image";
        }

        if (strtolower($fileType) === 'jpg' && preg_match('/^image\/jpeg/', $mime_type)) {
            $fileType = 'jpeg';
        }

        $fileClassification = $type;

        $base64File = base64_encode(file_get_contents($file['tmp_name']));

        $dataUrl = base64_encode($uploadedFileName) . "::data:$mime_type;base64,$base64File"; // to save

        // Crypt 'n Save
        $fname = gen_random_bytes(16);
        $f_key = add_entropy(gen_random_bytes(32));

        while ( file_exists("files/" . $fname . ".encf") ) {
            $fname = gen_random_bytes(16);
        }

        $new_file = fopen("files/" . $fname . ".encf", "w") or die("Unable to create file!<br>");

        fwrite($new_file, encrypt_AES256($dataUrl, $f_key));
        fclose($new_file);

        // Create new message
        $chatHash = chat_hash($_SESSION["user_id"], $_SESSION["friend_id"]);
        $DB_CHAT = decrypt_json("chats/" . $chatHash . ".json.enc");

        if($DB_CHAT === false) {
            die("Error during decryption of chat's files!<br>");
        }

        $sender_id = password_hash($_SESSION['token'], PASSWORD_BCRYPT);

        $new = new_messagge($type, $sender_id, $DB_CHAT, $_SESSION['chat_token']);

        $DB_CHAT["messages"][$new[0]] = $new[1];

        array_push($DB_CHAT["msg_order"], $new[0]);

        $DB_CHAT["messages"][$new[0]]->fname = encrypt_AES256($fname, $f_key);

        // end-to-end encryption
        $public_key = file_get_contents("endtoend_keys/public_" . $_SESSION['friend_id'] . ".pem");
        $ciphertext = null;

        if(openssl_public_encrypt($f_key, $ciphertext, $public_key)) {
            $DB_CHAT["messages"][$new[0]]->key_pgp = base64_encode($ciphertext);
        } else {
            die("<p style='color: red; font-weight: bold;'>Error during end-to-end encryption!</p><br>");
        }

        // end-to-end encryption (with my public key)
        $my_public_key = file_get_contents("endtoend_keys/public_" . $_SESSION['user_id'] . ".pem");
        $ciphertext2 = null;

        if(openssl_public_encrypt($f_key, $ciphertext2, $my_public_key)) {
            $DB_CHAT["messages"][$new[0]]->key_my_pgp = base64_encode($ciphertext2);
        } else {
            die("<p style='color: red; font-weight: bold;'>Error during end-to-end encryption!</p><br>");
        }

        // Save
        $new_chat = fopen("chats/" . $chatHash . ".json.enc", "w") or die("Unable to create file!<br>");
        fwrite($new_chat, encrypt_json($DB_CHAT));
        fclose($new_chat);

        header("Location: send_form.php");

    } else {
        die("Unknown error during file uploading!<br><a href='send_form.php' style='color: blue;'>OK</a><br>");
    }
} else {
    header("Location: send_form.php");
}

?>
