<?php
$host = "sql301.infinityfree.com";
$user = "if0_41832308";
$pass = "inspirorelia";
$dbname ="if0_41832308_dbasset";

$conn = mysqli_connect($host,$user, $pass, $dbname );

if (!$conn){
    die("Connection failed:" . mysqli_connect_error());
}
?>
