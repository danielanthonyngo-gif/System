<?php
session_start();
session_unset();    // Tinatanggal lahat ng session variables
session_destroy();  // Winawasak ang session file
header("Location: login.php");
exit();
?>