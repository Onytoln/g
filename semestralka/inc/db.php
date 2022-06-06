<?php
try{
  $db = new PDO('mysql:host=127.0.0.1;dbname=gryl00;charset=utf8', 'gryl00', 'Lirveshk6KohivEdTos');
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exc){
    header("Location: index.php");
    exit();
}