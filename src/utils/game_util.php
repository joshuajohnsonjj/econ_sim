<?php
/*
add_game.php

Code for saving game, as well as deleting from mysql database.
When saving, checks if game exists alreay to decide wheter to save as new,
or update existing.
*/

ini_set('display_errors', 1); error_reporting(-1);
include 'sql_settup.php';

// saves equilibrium to game in table
if (isset($_POST['equilibrium'])) {
	$mysqli->query("UPDATE Games Set equilibrium='".$_POST['equilibrium']."' WHERE id=".$_POST['id']);
}
// Create/save game game
else if (isset($_POST['mode']) && isset($_POST['difficulty']) && isset($_POST['market_struct']) 
	&& isset($_POST['macroEconomy']) && isset($_POST['randEvents']) && isset($_POST['limit']) 
	&& isset($_POST['numRounds']) && isset($_POST['course_id']) && isset($_POST['gameName'])) {

	if ($_POST['randEvents'] == "true")
		$rand = true;
	else
		$rand = false;

	// check if game already exists, if so, update rather than create new
	if ($_POST['gameId']) {
		$sql = "UPDATE Games SET name='".$_POST['gameName']."', difficulty='".$_POST['difficulty']."', mode='".$_POST['mode']."', market_struct='".$_POST['market_struct']."', macro_econ='".$_POST['macroEconomy']."', rand_events='".$rand."', time_limit='".$_POST['limit']."', num_rounds='".$_POST['numRounds']."', demand_intercept='".$_POST['demand_intercept']."', demand_slope='".$_POST['demand_slope']."', fixed_cost='".$_POST['fixed_cost']."', const_cost='".$_POST['const_cost']."' WHERE course_id=".$_POST['course_id'];
		if ($mysqli->query($sql) === TRUE) 
			header("Location: ../../src/admin_page.php?game=".$_POST['gameId']);
		else 
		    echo "Error: " . $sql . "<br>" . $mysqli->error;
	}
	else {
		$sql = "INSERT INTO Games (name, type, course_id, difficulty, mode, market_struct, macro_econ, rand_events, time_limit, num_rounds, demand_intercept, demand_slope, fixed_cost, const_cost)
		VALUES ('".$_POST['gameName']."', '".$_POST['type']."', '".$_POST['course_id']."', '".$_POST['difficulty']."', '".$_POST['mode']."', '".$_POST['market_struct']."', '".$_POST['macroEconomy']."', '".$rand."', '".$_POST['limit']."', '".$_POST['numRounds']."', '".$_POST['demand_intercept']."', '".$_POST['demand_slope']."', '".$_POST['fixed_cost']."', '".$_POST['const_cost']."')";
		if ($mysqli->query($sql) === TRUE) 
			header("Location: ../../src/admin_page.php?course=".$_POST['course_id']);
		else 
		    echo "Error: " . $sql . "<br>" . $mysqli->error;
	}	
}

// Delete game
else if (isset($_POST['deleteId']) && isset($_POST['deletedGameCourse'])) { 
	$delete_sql = "DELETE FROM Games WHERE id='".$_POST['deleteId']."'";

	if ($mysqli->query($delete_sql) === TRUE) 
		header("Location: ../../econ_sim/src/admin_page.php?course=".$_POST['deletedGameCourse']);
	else 
	    echo "Error: " . $delete_sql . "<br>" . $mysqli->error;
}