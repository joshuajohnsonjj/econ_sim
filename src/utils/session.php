<?php 
/*
session.php

Handles requests related to a particular game session, including loading admin results, toggleing the game session on/off,
entering students to game, and handling student submissions.
*/

ini_set('display_errors', 1); error_reporting(-1);
require_once "../../../tsugi/config.php";

use \Tsugi\Core\LTIX;

$LAUNCH = LTIX::session_start();

$mysqli = new mysqli('localhost', 'root', 'root', 'econ_sim_data');

if (mysqli_connect_error()) {
	die('Connect Error ('.mysqli_connect_errno().') '.mysqli_connect_error());
}

if (isset($_POST["checkExistance"])) { // Called when student tries to enter game
	// get current status of the "live" column for entered game id
	if ($mysqli->query('SELECT live FROM Games WHERE id="'.$_POST["id"].'" LIMIT 1')->fetch_assoc()['live'])
		header("Location: ../game_main.php?session=".$_POST['id']);
	else
		header("Location: ../student.php?session=err");
}

// called when admin starts/stops a session
// set the "live" column in Game table
else if ($_POST['action'] == 'toggle') {
	$toggledOn = false;
	// Get game's live status and change it to opposite of current value
	$result = $mysqli->query('SELECT live FROM Games WHERE id='.$_POST["id"].' LIMIT 1');
	if ($result->fetch_assoc()['live'])
		$mysqli->query("UPDATE Games Set live=0 WHERE id='".$_POST["id"]."'");
	else {
		$toggledOn = true;
		$mysqli->query("UPDATE Games Set live=1 WHERE id='".$_POST["id"]."'");
	}
	echo $toggledOn;
}


$mysqli->close();