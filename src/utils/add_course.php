<?php
/*
add_course.php

Contains code for adding a new course to mysql database, as well as deleting existing
*/

ini_set('display_errors', 1); error_reporting(-1);
include 'sql_settup.php';
require_once "../../../tsugi/config.php";

use \Tsugi\Core\LTIX;

$LAUNCH = LTIX::session_start();

if (isset($_POST['name']) && isset($_POST['section']) && isset($_POST['avatar'])) { // Add new course

	$add_course_sql = "INSERT INTO Courses (name, section, owner, avatar) VALUES ('".$_POST['name']."', '".$_POST['section']."', '".$USER->email."', '".$_POST['avatar']."')";

	if ($mysqli->query($add_course_sql) === TRUE) 
		header("Location: ../../src/admin_page.php");
	else 
	    echo "Error: " . $sql . "<br>" . $mysqli->error;
}
	// Delete existing course
else { 
	$delete_sql = "DELETE FROM Courses WHERE id=".$_POST['deleteId'];

	// Delete the course
	if ($mysqli->query($delete_sql) === TRUE) {

		$result = $mysqli->query("SELECT id FROM Games WHERE course_id=".$_POST['deleteId']);

		// then delete all games under that course id
		if ($result->num_rows > 0)
			while ($row = $result->fetch_assoc())
				$mysqli->query("DELETE FROM Games WHERE id=".$row['id']);

		header("Location: ../../src/admin_page.php");
	}
	else 
	    echo "Error: " . $delete_sql . "<br>" . $mysqli->error;
}