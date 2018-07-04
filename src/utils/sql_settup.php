<?php
/*
sql_settup.php

This file contains initial setup for the 3 main tables in database. They should be commented out after the first
run. 
Next are a handfull of utility functions relating to the mysql database.
*/

DEFINE('DB_USERNAME', 'root');
DEFINE('DB_PASSWORD', 'root');
DEFINE('DB_HOST', 'localhost');
DEFINE('DB_DATABASE', 'econ_sim_data');

$mysqli = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

if (mysqli_connect_error()) {
	die('Connect Error ('.mysqli_connect_errno().') '.mysqli_connect_error());
}

// INITIALIZES TABLES
// =======================
// $usertbl = "CREATE TABLE Users (
// 	id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
// 	email VARCHAR(30) NOT NULL,
//  in_session INT(6) DEFAULT NULL,
//  opponent VARCHAR(15) DEFAULT NULL,
// 	reg_date TIMESTAMP
// )";
// if ($mysqli->query($usertbl) === TRUE) {
// 	echo "make table success";
// } else {
// 	echo 'failed: users ';
// }

// $coursetbl = "CREATE TABLE Courses (
// 	id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
// 	name VARCHAR(30) NOT NULL,
// 	section VARCHAR(30) NOT NULL,
// 	owner VARCHAR(30) NOT NULL,
// 	avatar VARCHAR(30) DEFAULT 'fa-chart-bar',
// 	reg_date TIMESTAMP
// )";
// if ($mysqli->query($coursetbl) === TRUE) {
// 	echo "make courses table success";
// } else {
// 	echo 'failed: courses ';
// }

// $gamestbl = "CREATE TABLE Games (
// 	id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
// 	name VARCHAR(30) NOT NULL,
//  live BOOLEAN DEAFAULT FALSE,
// 	type VARCHAR(30) NOT NULL,
// 	course_id VARCHAR(30) NOT NULL,
// 	difficulty VARCHAR(30) NOT NULL,
// 	mode VARCHAR(30) NOT NULL,
// 	market_struct VARCHAR(30) NOT NULL,
// 	macro_econ	VARCHAR(30) NOT NULL,
// 	rand_events BOOLEAN NOT NULL,
// 	time_limit INT(6) NOT NULL,
// 	num_rounds INT(6) NOT NULL,
// 	demand_intercept INT(6) NOT NULL,
// 	demand_slope INT(6) NOT NULL,
// 	fixed_cost INT(6) NOT NULL,
// 	const_cost INT(6) NOT NULL,
//  equilibrium INT(6) DEFAULT NULL,
// 	reg_date TIMESTAMP
// )";
// if ($mysqli->query($gamestbl) === TRUE) {
// 	echo "make games table success";
// } else {
// 	echo 'failed: games ';
// }
// =======================


// UTILITY FUNCTIONS
// =================
// Get instructor's saved courses
function getCourses($mysqli, $usr) {
	$result = $mysqli->query("SELECT * FROM Courses");
	$courses = [];

	if($result === FALSE)
		die("ERROR! Can't get courses."); 

	if ($result->num_rows > 0)
		while ($row = $result->fetch_assoc())
			if ($usr == $row["owner"])
				array_push($courses, $row);
	return $courses;
}

// for games screen get course name and section
function getCourseNameSection($mysqli, $id) {
	$result = $mysqli->query("SELECT name, section FROM Courses WHERE id=".$id);
	$info = [];

	if($result === FALSE)
		die("ERROR! Can't get course info."); 

	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		array_push($info, $row['name']);
		array_push($info, $row['section']);
	}
	return $info;
}

// get course's saved games & info
// --------------
function getGames($mysqli, $course) {
	$result = $mysqli->query("SELECT * FROM Games");
	$games = [];

	if($result === FALSE)
		die("ERROR! Can't get games."); 

	if ($result->num_rows > 0)
		while ($row = $result->fetch_assoc())
			if ($course == $row["course_id"])
				array_push($games, $row);
	return $games;
}

function getGameInfo($mysqli, $game) {
	$result = $mysqli->query('SELECT * FROM Games WHERE id='.$game);

	if($result === FALSE)
		die("ERROR! Can't get game info."); 

	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		return $row;
	}
}
// ---------------

// return if admin has toggled the session "on"
function sessionIsLive($mysqli, $id) {
	return $mysqli->query('SELECT live FROM Games WHERE id="'.$id.'" LIMIT 1')->fetch_assoc()['live'];
}

