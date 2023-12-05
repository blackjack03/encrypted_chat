<?php
include 'database.php';
include 'crypt.php';

session_start();

if(isset($_SESSION['logged'])) {
    header("Location: dashboard.php");
}

$DB = null;

if(openDB() !== false) { // Open Database
    $DB = $GLOBALS["DB"];
} else {
    echo "<p style='color: red; font-weight: bold;'>Error!</p><br>";
}

/* Security Check */

$error = "";

function diff_min($lt) {
    return floor((time() - $lt) / 60);
}

$MAX_ERRORS = 5;
$BLOCK_MIN = 10;

if(!isset($_SESSION['try'])) {
    $_SESSION['try'] = 0;
}

if(isset($_SESSION['security_stop'])) {
    $tot_min = diff_min($_SESSION['last_time']);
    if($tot_min >= $BLOCK_MIN) {
        unset($_SESSION['security_stop']);
        $_SESSION['try'] = 0;
    }
}

/* Secure login (avoid brute-force attack) */

if($_POST && isset($DB)) {
    $userName = trim($_POST['username']);
    if(!isset($DB[md5($userName)])) {
        echo "<p style='color: red;'>Username doesn't exist!</p>";
    } else {
        if(!isset($_SESSION['security_stop'])) {
            if(password_verify($_POST['password'], $DB[md5($userName)]["password"])) {
                $_SESSION['logged'] = 1;
                $_SESSION['user_id'] = md5($userName);
                $_SESSION['username'] = $userName;
                $_SESSION['password'] = $_POST['password'];
                $_SESSION['type'] = $DB[$_SESSION['user_id']]["type"];
                $_SESSION['token'] = decrypt_AES256($DB[$_SESSION['user_id']]["token"], $_SESSION['password']);
                $_SESSION['fingerprint'] = decrypt_AES256($DB[$_SESSION['user_id']]["public_key_fingerprint"], $_SESSION["password"]);
                $_SESSION['timezone'] = decrypt_AES256($DB[$_SESSION['user_id']]["timezone"], $_SESSION['token']);
                unset($_SESSION['password']); // delete clear password from $_SESSION
                header("Location: dashboard.php");
            } else if($_POST['password'] == "") {
                $error = "<h4 style='color: red;'>Enter a Password!</h4>";
            } else {
                $error = "<h4 style='color: red;'>Wrong Password!</h4>";
                $_SESSION['try']++;
                if($_SESSION['try'] >= $MAX_ERRORS) {
                    $_SESSION['last_time'] = time();
                    $_SESSION['security_stop'] = 1;
                    $wait = $BLOCK_MIN - diff_min($_SESSION['last_time']);
                    $error = "<h4 style='color: red; line-height: 1.4;'>You have entered the wrong password too many times!<br>Wait " . $wait . " minute" . (($wait!=1)?"s":"") . " before trying again</h4>";
                }
            }
        }
    }
}

if(isset($_SESSION['security_stop'])) {
    $wait = $BLOCK_MIN - diff_min($_SESSION['last_time']);
    $error = "<h4 style='color: red; line-height: 1.4;'>You have entered the wrong password too many times!<br>Wait " . $wait . " minute" . (($wait!=1)?"s":"") . " before trying again</h4>";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="icon.ico">
    <link rel="stylesheet" href="style.css">
    <title>LOGIN - Tor Encrypted Chat</title>
    <style>
        form {
            background-color: #1f1f1f;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 350px;
            margin: 0 auto;
            box-shadow: 0px 0px 12px blueviolet;
        }

        label {
            font-weight: bold;
            color: blueviolet;
        }

        input[type="text"], input[type="password"] {
            width: 90%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 20px;
            border-radius: 4px;
            border: 1px solid blueviolet;
            background-color: #2c2c2c;
            color: white;
            font-size: 18px;
            outline: none;
            transition: 0.2s;
        }

        input[type="submit"] {
            background-color: blueviolet;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: 0.2s;
        }

        input[type="submit"]:hover {
            background-color: darkviolet;
        }

        input[type="submit"]:active {
            transform: scale(0.92);
        }

        input[type="text"]:focus, input[type="password"]:focus {
            box-shadow: 0px 0px 3px blueviolet;
        }
    </style>
</head>
<body>
    <a href="index.php" style="text-decoration: none;"><h1 class="title"><span style="color: blueviolet; text-shadow: 0px 0px 8px blueviolet;">TOR</span> <span class="underline">Encrypted</span> CHAT</h1></a>

    <?php
        if($error !== "") {
            echo $error;
        }
    ?>

    <form action="" method="POST" style="background: white; padding: 8px;">
        <label for="username">Username:</label>
        <br>
        <input type="text" id="username" name="username" autocomplete="off" required>
        <br><br>
        <label for="password">Password:</label>
        <br>
        <input type="password" id="password" name="password" autocomplete="off" required>
        <br>
        <br>
        <div style="width: 100%; margin: 0 auto; text-align: center;"><input type="submit" value="LOGIN"></div>
    </form>
</body>
</html>
