<?php 

	include 'database.php';

	$id = $_POST['id'];

	$link->query("DELETE FROM users WHERE id = '".$id."'");

	$query = $link->query("SELECT * FROM users");
	$result = array();

	while ($rowData = $query->fetch_assoc()) {
		$result[] = $rowData;
	}

	echo json_encode($result);