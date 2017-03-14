<?php

include 'mysql_connect.php';

$update = $db->prepare("INSERT INTO `feedback`(`office`, `name`, `tell`, `email`, `article`) VALUES (:office, :name, :tell,:email, :article)");

$update->bindValue(':office', $_GET['office']);
$update->bindValue(':name', $_GET['name']);
$update->bindValue(':tell', $_GET['tell']);
$update->bindValue(':email', $_GET['email']);
$update->bindValue(':article', $_GET['article']);

$update->execute();
