<?php 

	include 'database.php';

	$id 		= $_POST['id'];
	$username 	= $_POST['username'];
	$email 		= $_POST['email'];
	$pwd 		= $_POST['pwd'];

	$link->query("UPDATE users SET username = '".$username."',email = '".$email."',pwd = '".$pwd."' WHERE id = '".$id."'");


?>