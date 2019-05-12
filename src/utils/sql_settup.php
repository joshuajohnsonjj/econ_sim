<?php
/* sql_settup.php

- Contains initial setup for the mysql tables in database. Commented out after the first run. 
- Below are a handfull of utility functions relating to the mysql database.

GameSessionData holds the data for each round for every student. Each student will have their own row for each game that they play. The Sessions table is used to help with multi player games, there will be one row for a session (not for each student) - this table simply holds the groupId and the two players 

Last Update:
8.1.18
Add Sessions table 
*/

// DEFINE('DB_USERNAME', 'root');
// DEFINE('DB_PASSWORD', 'root');
// DEFINE('DB_HOST', 'localhost');
// DEFINE('DB_DATABASE', 'econ_sim_data');

// $mysqli = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

// if (mysqli_connect_error()) {
// 	die('Connect Error ('.mysqli_connect_errno().') '.mysqli_connect_error());
// }

// INITIALIZES TABLES
// =======================
// $coursetbl = "CREATE TABLE Courses (
	// id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	// name VARCHAR(30) NOT NULL,
	// section VARCHAR(30) NOT NULL,
	// owner VARCHAR(30) NOT NULL,
	// avatar VARCHAR(30) DEFAULT 'fa-chart-bar',
	// reg_date TIMESTAMP
// )";
// if ($mysqli->query($coursetbl) === TRUE) {
// 	echo "make courses table success";
// } else {
// 	echo 'failed: courses ';
// }
// // gamestbl contains setup info for all created games
// $gamestbl = "CREATE TABLE Games (
	// id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	// name VARCHAR(30) NOT NULL,
 // 	live BOOLEAN DEAFAULT FALSE,
	// type VARCHAR(30) NOT NULL,
	// course_id VARCHAR(30) NOT NULL,
	// difficulty VARCHAR(30) NOT NULL,
	// mode VARCHAR(30) NOT NULL,
	// market_struct VARCHAR(30) NOT NULL,
	// macro_econ	VARCHAR(30) NOT NULL,
	// rand_events BOOLEAN NOT NULL,
	// time_limit INT(6) NOT NULL,
	// num_rounds INT(6) NOT NULL,
	// demand_intercept INT(6) NOT NULL,
	// demand_slope INT(6) NOT NULL,
	// fixed_cost INT(6) NOT NULL,
	// const_cost INT(6) NOT NULL,
	// equilibrium INT(6) DEFAULT NULL,
 // price_hist 	VARCHAR(300) 	DEFAULT NULL,
	// reg_date TIMESTAMP
// )";
// if ($mysqli->query($gamestbl) === TRUE) {
// 	echo "make games table success";
// } else {
// 	echo 'failed: games ';
// }
// // gameSessionData contains data all current or finished game sessions (one entry for each player)
// $gameSessionData = "CREATE TABLE GameSessionData (
	// id 					INT(6)			UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	// complete 			BOOLEAN			DEFAULT FALSE,
	// groupId				VARCHAR(10)		NOT NULL,
	// player 				VARCHAR(30) 	NOT NULL,
	// opponent 			VARCHAR(30) 	DEFAULT NULL,
	// player_quantity 	VARCHAR(300) 	NOT NULL,
	// player_profit 		VARCHAR(300) 	NOT NULL,
	// player_revenue 		VARCHAR(300) 	NOT NULL,
	// player_return 		VARCHAR(300) 	NOT NULL,
	// price 				VARCHAR(300)	NOT NULL,
	// unit_cost 			VARCHAR(300)	NOT NULL,
	// total_cost			VARCHAR(300)	NOT NULL
// 	)";
// if ($mysqli->query($gameSessionData) === TRUE) {
// 	echo "make gameSessionData success";
// } else {
// 	echo 'failed: gameSessionData ';
// }
// // Sessions contains all live game sessions
// $sessions = "CREATE TABLE Sessions (
	// id 					INT(6)			UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	// groupId				VARCHAR(10)		NOT NULL,
	// gameId 				Int(6)			NOT NULL,
	// p1 					VARCHAR(30) 	DEFAULT NULL,
	// p1Data 				INT(20)			DEFAULT NULL,
// 	)";
// if ($mysqli->query($sessions) === TRUE) {
// 	echo "make Sessions success";
// } else {
// 	echo 'failed: Sessions ';
// }
// =======================

require_once('../../dao/QW_DAO.php');

use \QW\DAO\QW_DAO;

$p = $CFG->dbprefix;
$QW_DAO = new QW_DAO($PDOX, $p);

// Get instructor's saved courses from Courses table
function getCourses($usr) {
	$response = $QW_DAO->geCourses($usr);

	return $response;
}

// for games screen get course name and section
function getCourseNameSection($id) {
	$response = $QW_DAO->getCourseNameSection($id);
	$info = [];

	if($response === FALSE)
		die("ERROR! Can't get course info."); 

	if ($response) {
		array_push($info, $response['name']);
		array_push($info, $response['section']);
	}

	return $info;
}

// get course's saved games & info
// --------------
function getGames($course) {
	$response = $QW_DAO->getGames($course);
	// return the games contained by a specified course
	// $result = $mysqli->query("SELECT * FROM Games");
	// $games = [];

	// if($result === FALSE)
	// 	die("ERROR! Can't get games."); 

	// if ($result->num_rows > 0)
	// 	while ($row = $result->fetch_assoc())
	// 		if ($course == $row["course_id"])
	// 			array_push($games, $row);

	return $response;
}

// return the details for a specified game
function getGameInfo($game) {
	$response = $QW_DAO->getGameInfo($game);
	// $result = $mysqli->query('SELECT * FROM Games WHERE id='.$game);
	return $response;
	// if($result === FALSE)
	// 	die("ERROR! Can't get game info."); 

	// if ($result->num_rows > 0) {
	// 	$row = $result->fetch_assoc();
	// 	return $row;
	// }
}
// ---------------

// return boolean - game session is "live" (joinable by students) or not 
function sessionIsLive($id) {
	// return $mysqli->query('SELECT live FROM Games WHERE id="'.$id.'" LIMIT 1')->fetch_assoc()['live'];
	return $QW_DAO->sessionIsLive($id);
}
