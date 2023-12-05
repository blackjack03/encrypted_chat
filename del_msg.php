<?php
include 'database.php';
include 'crypt.php';
include 'secure_delete_file.php';

session_start();

mb_internal_encoding("UTF-8");

if(openDB() === false) { // Open Database
    die("<p style='color: red; font-weight: bold;'>Error!</p><br>");
}

if(!isset($_SESSION['logged'])) {
    header("Location: login.php");
}

// Check auth
$delAllow = false;
if($_SESSION["type"] == "ADMIN") {
    $delAllow = true;
}

$DB_CHAT = null;
$chatHash = null;

$TYPE = "text";

if(isset($_SESSION["friend_id"])) {
    $chatHash = chat_hash($_SESSION["user_id"], $_SESSION["friend_id"]);
    $DB_CHAT = open_chat($chatHash);

    if($DB_CHAT === false) {
        die("Error during decryption of chat's files!<br>");
    }
}

if($_POST) {
    if(array_key_exists("yesDel", $_POST)) {
        if($DB_CHAT["messages"][$_POST['d_msg_id']]["type"] !== "text") {
            $TYPE = decrypt_AES256($DB_CHAT["messages"][$_POST['d_msg_id']]["type"], $_SESSION['chat_token']);
        }

        if($TYPE !== "text") {
            $personal = [];

            if(password_verify($_SESSION['token'], $DB_CHAT["messages"][$_POST['d_msg_id']]["prop"])) {
                $personal = ["key_my_pgp", "my"]; // json prop, CSS class
            } else {
                $personal = ["key_pgp", "fr"];
            }

            $privateKey = get_private_key($_SESSION["user_id"], $_SESSION['token']);
            $aes_key = null;

            if(openssl_private_decrypt(base64_decode($DB_CHAT["messages"][$_POST['d_msg_id']][$personal[0]]), $aes_key, $privateKey) === false) {
                die("Fatal error during end-to-end decryption!<br>");
            }

            $file_name = decrypt_AES256($DB_CHAT["messages"][$_POST['d_msg_id']]["fname"], $aes_key);
            // unlink("files/" . $file_name . ".encf");  // Delete file
            securelyDeleteFile_DoD("files/" . $file_name . ".encf"); // Secure delete file
        }

    	$msgKey = array_search($_POST['d_msg_id'], $DB_CHAT["msg_order"]);
        array_splice($DB_CHAT["msg_order"], $msgKey, 1); // delete messagge ref from array of msg order
        unset($DB_CHAT["messages"][$_POST['d_msg_id']]); // delete messagge

        $new_chat = fopen("chats/" . $chatHash . ".json.enc", "w") or die("Unable to create file!<br>");
        fwrite($new_chat, encrypt_json($DB_CHAT));
        fclose($new_chat);

        // header("Location: chat_page_js.php?id=" . $_POST["frID"]); /* no-JS Version */
    } else {
        // header("Location: chat_page_js.php?id=" . $_POST["frID"]); /* no-JS Version */
    }

    echo '<script>if(sessionStorage.getItem("forceRfhDel") !== null) {window.parent.close_formDelMsg();window.parent.forceRefresh();sessionStorage.removeItem("forceRfhDel");}window.location.replace("about:blank");</script>'; // JS Version only

    die();
} else {
    if(!isset($_GET['id']) || !isset($_GET['fr_id'])) {
        if(!isset($_GET['fr_id'])) {
            // header("Location: chat_page_js.php"); /* no-JS Version */
        } else {
            // header("Location: chat_page_js.php?id=" . $_GET['fr_id']); /* no-JS Version */
        }
    } else {
        if(!$delAllow) {
            // Check auth (if I don't have it yet)
            if(!password_verify($_SESSION['token'], $DB_CHAT[$_GET['id']]["prop"])) {
                $delAllow = true;
            } else {
                if ($_SESSION["type"] == "MOD" && $GLOBALS["DB"][$_GET['fr_id']]["type"] != "ADMIN") {
                    $delAllow = true;
                }
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <style>
    	.yes, .no {
          width: 125px;
          height: 60px;
          font-size: 20px;
          font-weight: bold;
          font-family: Arial, Helvetica, sans-serif;
          border-radius: 12px;
          border: 2px solid blueviolet;
          cursor: pointer;
          transition: 0.2s;
      }

      .yes:active, .no:active {
          transform: scale(0.9);
      }

      .yes {
          color: white;
          background-color: blueviolet;
      }

      .yes:hover {
          box-shadow: 0px 0px 12px blueviolet;
      }

      .no {
          background: transparent;
          color: blueviolet;
      }

      .no:hover {
          background-color: blueviolet;
          color: white;
      }

      .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 9;
            background: rgba(255, 255, 255, 0.65);
            display: none;
        }

      .loading div {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            height: 50%; /* Circle size */
            aspect-ratio: 1 / 1;
            border-radius: 50%;
            border: 5px solid blueviolet;
            border-bottom-color: transparent; /* rgb(189, 129, 245) */
            animation: spin 1s linear infinite;
            /* box-shadow: 0px 0px 3px blueviolet; */
        }

        .loading div::after {
            content: "";
            display: block;
            padding-bottom: 100%;
        }

        @keyframes spin {
            to {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script> <!-- JS Version only -->
    <script>
        var btnClick = "yes";

        $(document).ready(function() {
            $("#noBtn").click(function() {
                btnClick = "no";
            });

            $("#delForm").submit(function(event) {
                // window.parent.close_formDelMsg();
                if(btnClick != "no") {
                	document.querySelector(".loading").style.display = "block";
                    sessionStorage.setItem("forceRfhDel", "1");
                } else {
                	window.parent.close_formDelMsg();
                }
            });
        });
    </script> <!-- JS Version only -->
    <title>Delete Messagge</title>
</head>
<body>
    <?php
        if(!$delAllow) {
            die("<h1 class='notAllowTitle'>You do not have permission to delete this message!</h1>");
        }
    ?>
    <form action="" method="post" id="delForm">
    	<input type="hidden" name="d_msg_id" value="<?php echo $_GET['id']; ?>">
        <input type="hidden" name="frID" value="<?php echo $_GET['fr_id']; ?>">
        <br>
        <h1 class="title" style="font-size: 30px;">Are you sure you want to delete the selected message?</h1>
        <br><br>
        <div style="display: flex; align-items: center; justify-content: space-around;"><input type="submit" name="yesDel" value="YES" class="yes"><input type="submit" value="NO" class="no" id="noBtn"></div>
    </form>
    
    <div class="loading"><div></div></div>
</body>
</html>
