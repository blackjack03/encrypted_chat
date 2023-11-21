<?php

session_start();

if(isset($_SESSION["logged"])) {
    header("Location: dashboard.php");
}

// header("Location: index.html");

echo file_get_contents("home.html");

?>
