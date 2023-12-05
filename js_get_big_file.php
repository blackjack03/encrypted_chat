<?php
include 'database.php';
include 'crypt.php';

session_start();

mb_internal_encoding("UTF-8");

if(!isset($_SESSION['logged']) || !$_POST) {
    header("Location: login.php");
    die();
}

/* FUNCTIONS */
function getFileChunk($handle, $init, $readBytes) {
    if(!$handle) {
        return false;
    }

    fseek($handle, $init);
    $data = fread($handle, $readBytes);

    return $data;
}


$q = $_POST['f'];

/* Get query */
$info = json_decode($_POST['q'], true);
$dec_name = $info['fname'];
$msg_id = $info['id'];

/* DECRYPT FILE */
/* Open Chat */
$chatHash = chat_hash($_SESSION["user_id"], $_SESSION["friend_id"]);
$DB_CHAT = decrypt_json("chats/" . $chatHash . ".json.enc");

if($DB_CHAT === false) {
    die("-1");
}

/* Get Private Key */
$privateKey = get_private_key($_SESSION["user_id"], $_SESSION['token']);

// Get messagge
$msg = $DB_CHAT["messages"][$msg_id];

/* Gets keys to decrypt file */
$personal = [];

$delAllow = false;

if(password_verify($_SESSION['token'], $msg["prop"])) {
    $personal = ["key_my_pgp", "my"]; // json prop, CSS class
    $delAllow = true;
} else {
    $personal = ["key_pgp", "fr"];
}

$aes_key = null;

if(openssl_private_decrypt(base64_decode($msg[$personal[0]]), $aes_key, $privateKey) === false) {
    die("-1");
}

/* Decrypt file */
$file = decrypt_AES256(file_get_contents("files/". $dec_name . ".encf"), $aes_key);
$file_info = explode("::", $file);
$file_name = base64_decode($file_info[0]);

$fbytes = strlen($file_info[1]);

/* Create TMP File */
$handle = tmpfile();
fwrite($handle, $file_info[1]);
rewind($handle); // Set pointer to the head of file
// Free
unset($file);
unset($file_info);

$default_chunk_size = 1 * (1024 ** 2); // 1MB

while ($default_chunk_size%3 != 0) {
	$default_chunk_size++;
}

/* Calls handle */
if($q == 'get_file_name') {
    echo base64_encode($file_name) . "::" . md5($file_name) . "::" . $fbytes . "::" . $default_chunk_size;
} else if ($q == 'get_chunk') {
    $counter = $_POST['r'];

    $from = $default_chunk_size * $counter;
    echo getFileChunk($handle, $from, $default_chunk_size);

    if(feof($handle)) {
        echo "::EOF";
    }
}

// Delete TMP file
fclose($handle);

?>
