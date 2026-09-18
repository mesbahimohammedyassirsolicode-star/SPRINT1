<?php
$host = "127.0.0.1";
$dbname = "cooking_app";
$username = "root";
$password = "";
 $connx = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password);


   
  ?>