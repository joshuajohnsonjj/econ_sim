<?php
/* add_course.php

- handles adding a new course to mysql database, as well as deleting existing

Last Update:
*/
ini_set('display_errors', 1); error_reporting(-1);
// include 'sql_settup.php';
require_once "../../../tsugi/config.php";

use \Tsugi\Core\LTIX;

$LAUNCH = LTIX::session_start();

require_once('../../dao/QW_DAO.php');
use \QW\DAO\QW_DAO;

$p = $CFG->dbprefix;
$QW_DAO = new QW_DAO($PDOX, $p);


// Add new course
if (isset($_POST['name']) && isset($_POST['section']) && isset($_POST['avatar'])) { 
	$QW_DAO->addCourse($USER->email, $_POST['name'], $_POST['section'],$_POST['avatar']);
}
	// Delete existing course and its games
else { 
	$QW_DAO->addCourse($_POST['nadeleteIdme']
	
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
