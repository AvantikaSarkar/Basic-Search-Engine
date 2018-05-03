<?php


$pdo = new PDO('mysql:host=localhost;dbname=mysearch','root','');

$pdo->query("SELECT * FROM search_index");

?>