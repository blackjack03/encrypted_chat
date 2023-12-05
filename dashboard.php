<?php
include 'database.php';
include 'crypt.php';

session_start();

mb_internal_encoding("UTF-8");
date_default_timezone_set($_SESSION['timezone']);

if(isset($_GET['exit'])) {
    session_destroy();
    header("Location: login.php");
}

if(!isset($_SESSION['logged'])) {
    header("Location: login.php");
}

$DB = null;

if(openDB() !== false) { // Open Database
    $DB = $GLOBALS["DB"];
} else {
    echo "<p style='color: red; font-weight: bold;'>Error during open DB!</p><br>";
    exit;
}

if($_POST) {
    if(array_key_exists("logout", $_POST)) {
        session_destroy();
        header("Location: login.php");
    }
}

$len_fr = count($DB[$_SESSION['user_id']]["friends"]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="icon.ico">
    <link rel="stylesheet" href="style.css?<?php echo time() . rand(); ?>">
    <style>
        .selected {
            background: blueviolet !important;
            color: white !important;
            box-shadow: 0px 0px 5px blueviolet;
        }
    </style> <!-- CSS for JS Version only -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script> <!-- JS Version only -->
    <script src="js_version/users_online.js?<?php echo time() . rand(); ?>"></script> <!-- JS Version only -->
    <script>
    	check_and_set_online("<?php echo $_SESSION["user_id"] ?>");
    </script> <!-- JS Version only -->
    <title>Dashboard - Encrypted Chat</title>
</head>
<body>
    <h1 class="title" style="cursor: default;"><span class="underline">Encrypted</span> CHAT</h1>

    <div class="container">
        <div class="yourId">Your ID: <span style="user-select: all;"><?php echo $_SESSION["user_id"]; ?></span></div>
        <?php
            $special = "";
            if($_SESSION['type'] == 'ADMIN') {
                $special = '<span style="color: gold; text-shadow: 0px 0px 3px gold;">';
            } else if($_SESSION['type'] == 'MOD') {
                $special = '<span style="color: blueviolet; text-shadow: 0px 0px 3px blueviolet;">';
            }
        ?>
        <h1 style="text-align: center; margin: 0; justify-self: center; align-self: center; grid-area: a;">Hi <?php echo (($special!="")?$special:"") . $_SESSION['username'] . (($special!="")?"</span>":""); ?></h1>
        <div class="list" style="grid-area: b;">
            <h4 style="text-align: center; color: gray; margin: 0;">Friends (<?php echo $len_fr; ?>)</h4>
            <hr width="30%" color="black" size="1">
            <div class="friends">
                <?php
                    for ($i = 0; $i < $len_fr; $i++) {
                        $idF = $DB[$_SESSION['user_id']]["friends"][$i];
                        // chat_page or chat_page_js for JS Version only
                        echo "<a href='chat_page_js.php?id=" . $idF . "' target='chats'><div>" . $DB[$idF]["username"] . "</div></a>";
                    }
                ?>
            </div>
        </div>
        <div class="chat" style="grid-area: c;">
            <iframe name="chats" class="ifchat" src="chat_page.php" id="iframechats" frameborder="0"></iframe>
            <div class="toolbar">
                <iframe src="send_form_file.php" class="ctool" frameborder="0"></iframe>
            </div>
        </div>
    </div>

    <form action="" method="POST" class="logout">
        <input type="submit" name="logout" value="LOGOUT" onclick="sessionStorage.clear();">
    </form>

    <script>
        $(document).ready(function() {
            $(".friends div").click(function() {
                $(".friends div").removeClass("selected");
                $(this).addClass("selected");
            });
        });

        function get_send_alert() {
            document.getElementById('iframechats').contentWindow.forceRefresh();
        }
    </script> <!-- JS Version only -->
</body>
</html>
