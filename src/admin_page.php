<?php
/* 
admin_page.php

- Instructor UI
- Redirects from index.php if user is instructor
- Handles UI for creating course/game, updating game, toggling game session on/off, and deletion of course/game

Initially displays courses view. This view displays an instructors saved courses and allows for creating new saved course. Clicking into course goes to games view. This view displays the saved games within a course and allows for 1) creating new saved game within course, 2) deleting the course which in turn deletes all contained games and sends user back to courses view. Clicking into game goes to individual game view. Three options are shown at any point. The first is a toggle switch to start/stop session - this toggles the ability for students to be able to enter into the game and play it. Starting the session will display an id which is to be given to students to join the game. Stopping the session will end ability to join the game and clear the game's data. The second button is dynamic. When session is not live, it is the "Game Setup" button which allows user to view current game configuration as well as make updates and save them. When session is live, it is "View Results" button which redirects to results.php. The third is a delete button which deletes the current game.

*/
	ini_set('display_errors', 1); error_reporting(-1); 
	include 'utils/sql_settup.php';
	require_once "../../tsugi/config.php";

	use \Tsugi\Core\LTIX;

	$LAUNCH = LTIX::session_start();

	// if student manages to navigate to this page, they will automatically be redirected back to student side
	if (!$USER->instructor)
		header("Location: ..");

	$courses = [];
	$games = [];
	$selectedCourseName = NULL;
	$selectedCourseSection = NULL;

	// if neither course nor game in query string, show courses view
	// call func from sql_settup to get the instructor's saved courses
	if (!isset($_GET['course']) && !isset($_GET['game']))	
		$courses = getCourses($USER->email);
	
	// if course in query string, show games view
	// get current course name using course id in query and get course's games to list on screen
	if (isset($_GET["course"])) {
		$selectedCourse = getCourseNameSection($_GET["course"]);
		$selectedCourseName = $selectedCourse[0];
		$selectedCourseSection = $selectedCourse[1];
		$games = getGames($_GET["course"]);
	}

	// if game is in querystring, show individual game view
	isset($_GET['game']) ? $selectedGame = $_GET['game'] : $selectedGame = NULL;

	$gameInfo = NULL;
	$sessionRunning = FALSE;

	// get the details of the selected game and check if it is toggled on/off
	if ($selectedGame) {
		$gameInfo = getGameInfo((int)$selectedGame);
		$sessionRunning = sessionIsLive($gameInfo['id']);
	}

	// different icons for the different types of available games
	// (currently only econ games supported, but potential to add other types of courses. Maerketing and accounting show as examples)
	$gameTypeIcons = ["econ"=>"fa-money-bill", "market"=>"fa-chart-line", "account"=>"fa-calculator"];
?>

<!doctype html>
<html class="no-js" lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Econ Sims</title>
    <link rel="stylesheet" href="../css/foundation.css">
    <link rel="stylesheet" href="../css/app.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.13/css/all.css" integrity="sha384-DNOHZ68U8hZfKXOrtjWvjxusGo9WQnrNx2sqG0tfsghAvtVlRW3tvkXWZh58N9jp" crossorigin="anonymous">
  </head>
  <body style="background-color: #d3f6ff;">
  
  	<!-- TITLE BAR => displays ND monogram, title, and name of user-->
  	<div class="title-bar" style="background-color: #0a4c6d">
	  <div class="title-bar-left">
	  	<div class="media-object" style="float: left;">
		    <div class="thumbnail" style="margin: 0; border: none;">
		      <img src="../assets/img/no_bg_monogram.png" height="100px" width="100px">
		    </div>
		</div>
	    <span class="title-bar-title">
	    	<h3 style="margin: 30px 0 5px 30px; font-weight: 500;">
	    		<?= $selectedCourseName ? $selectedCourseName : ($gameInfo ? $gameInfo['name'] : "Welcome, Admin!") ?>
	    	</h3>
	    	<h6 style="margin-left: 30px"><?= $selectedCourseSection ? 'Section '.$selectedCourseSection : ''?></h6>
	    	<h6 id="sessionIdSubheader" style="display: <?= $sessionRunning ? '' : 'none' ?>; margin-left: 30px"> 
				<?= $gameInfo ? "Session ID: ".$gameInfo['id'] : "" ?>
			</h6>
	    </span>
	  </div>
	  <div class="title-bar-right">
	  		<img src="../assets/img/default_usr_img.jpeg" style="height: 40px; border-radius: 18px; float: right;">
	  		<p style="margin-top: 10px; padding-right: 50px">Logged in as: <?= $USER->displayname ?></p>
	  </div>
	</div>
	<!-- end title bar -->

	<!-- if on games view or individual game view, display a back button -->
	<button id="backButton" class="secondary button" style="float: left;" onclick="window.location = window.location.origin+window.location.pathname+'<?= $gameInfo ? "?course=".$gameInfo["course_id"] : "" ?>'">
		<i class="fas fa-angle-left"></i> Back
	</button>

	<!-- MAIN CONTENT -->
	<div class="grid-container" style="margin-top: 5%;">
		<div>
			<!-- prompt_symbol is dynamic icon next to subheader. A plus symbol is displayed on courses and games view. A trash can symbol is displayed on games view  -->
			<i id="prompt_symbol" class="fas fa-check-circle fa-2x" style="float: left; padding-right: 15px"></i>
			<a onclick="deleteCourse()" style="display: <?= isset($_GET['course']) ? '' : 'none' ?>">
				<i class="grow fas fa-trash fa-2x" style="float: right; margin-left: 40px"></i>
			</a>
			<form id="deleteCourseForm" method="post" action="utils/add_course.php">
				<input type="hidden" name="deleteId" value="<?= isset($_GET['course'])?$_GET['course']:NULL ?>">
			</form>
			<a id="addNewModal">
				<i class="grow fas fa-plus fa-2x" style="float: right;"></i>
			</a>
			<h4 id="selection_prompt" style="font-weight: 540;"> Select Course</h4>
		</div>
		<hr>
		<br>

		<!-- Display Message if no content (no courses or no games added yet) -->
		<?php 
		if((isset($_GET['course'])&&count($games)==0&&$txt='Games')||(!$_GET&&count($courses)==0&&$txt='Courses')){
		?>
			<div style="width: 500px; margin: 70px auto 30px auto;">
				<h3 style="text-align: center; color: #bdbebf">
					<i class="fas fa-exclamation-circle fa-6x"></i><br>
					<strong style="font-weight: 500;">No Saved <?=$txt?>!<br>Add One to Get Started.</strong>
				</h3>
			</div>
		<?php } ?>

		<!-- SELECT COURSE SECTION -->
		<div id="course_options" style="display: none">
			<?php 
			$course_num = 0;
			$course_backgrounds = ["background: linear-gradient(141deg, #0fb88a 20%, #0fb8ad 80%);",
							"background: linear-gradient(141deg, #0fb8ad 20%, #1fc8db 80%);",
							"background: linear-gradient(141deg, #1fc8db 20%, #24b0e2 80%);"];

			// loop thru courses for instructor and display as tiles on screen, in rows of 3
			foreach ($courses as $course) { 				
				if ($course_num == 0) {  ?>
					<div class="grid-x grid-padding-x small-up-2 medium-up-3" style="margin-bottom: 30px;">
			<?php } ?>
						<div class="cell grow">
						  	<a onclick="course_selected('<?= $course["id"] ?>')">
						      	<div class="card" style="<?= htmlspecialchars($course_backgrounds[$course_num]) ?>">
							        <div class="card-section">
							        	<i class="fas <?= $course["avatar"]?$course["avatar"]:'fa-chart-bar' ?> fa-7x float-center game_options_content"></i>
							        	<h4 class="game_options_content" style="font-weight: 300">
							        		<?= $course["name"] ?>
							        	</h4>
							        	<h6 style="font-weight: 300; text-align: center;">
							        		Section <?= $course["section"] ?>
										</h6>
							        </div>
							    </div>
						 	</a>
						</div>
			<?php if ($course_num == 2) { ?>
					</div>
			<?php } 
			$course_num == 2 ? $course_num = 0 : $course_num++; 
			}	?>  
		</div>
		<!-- end course options section -->

		<!-- SELECT GAME SECTION -->
		<div id="game_options" style="display: none;">
			<?php 
			$game_num = 0;
			$backgrounds = ["background: linear-gradient(141deg, #5b18bf 20%, #6858f1 80%);",
							"background: linear-gradient(141deg, #6858f1 20%, #4c55f7 80%);",
							"background: linear-gradient(141deg, #4c55f7 20%, #008dd1 80%);"];

			// display games within selected course as tiles in rows of 3
			foreach ($games as $game) {				
				if ($game_num == 0) {  ?>
					<div class="grid-x grid-padding-x small-up-2 medium-up-3" style="margin-bottom: 30px;">
			<?php } ?>
						<div class="cell grow">
						  	<a onclick="game_selected('<?= $game["id"] ?>')">
						      	<div class="card" style="<?= htmlspecialchars($backgrounds[$game_num]) ?>">
							        <div class="card-section">
							        	<i class="fas <?= $gameTypeIcons[$game['type']] ?> fa-7x float-center game_options_content"></i>
							        	<h4 class="game_options_content" style="font-weight: 300">
							        		<?= $game["name"] ?>
							        	</h4>
							        	<h6 style="font-weight: 300; text-align: center;">
							        		<?= $game['market_struct']=='monopoly'?'Monopoly':($game['market_struct']=='oligopoly'?'Oligopoly':($game['market_struct']=='perfect'?'Perfect Competition':'Monopolistic Competition')) ?>
							        		<?= $game['difficulty']?' - '.$game['difficulty']:''?>
										</h6>
							        </div>
							    </div>
						 	</a>
						</div>
			<?php if ($game_num == 2) { ?>
					</div>
			<?php } 
			$game_num == 2 ? $game_num = 0 : $game_num++; 
			}	?>
		</div>
		<!-- end game options -->

		<!-- IDIVIDUAL GAME SECTION -->
		<div id="game_details" style="margin-bottom: 35px">
			<div id="session_toggle" class="grid-x grid-padding-x small-up-2 medium-up-3" style="display: none">
				<div id="toggleColor" class="cell mode-cell grow" onclick="toggleSession('<?= $gameInfo["id"] ?>','<?= addslashes($gameInfo["name"]) ?>')" style="cursor: pointer; background: linear-gradient(141deg, <?= !$sessionRunning ? '#008c0b 20%, #1fa82f 80%);' : '#f1c00b 20%, #e8d722 80%);' ?>">
					<i id="toggleIcon" class="far fa-<?= !$sessionRunning ? 'play' : 'stop' ?>-circle fa-2x mode_options_content" style="float: left; padding-left: 50px"></i>
					<h4 id="toggleText" class="mode_options_content" style="font-weight: 300"><?= !$sessionRunning ? 'Start Session' : 'End Session' ?></h4>
				</div>
			</div>
			<div id="view_game" class="grid-x grid-padding-x small-up-2 medium-up-3" style="margin-top: 25px; display: none;">
				<div id="dynamicButtonFunc" class="cell mode-cell grow" style="cursor: pointer; background: linear-gradient(141deg, #0fb8ad 20%, #1fc8db 80%);">
					<i id="dynamicButtonIcon" class="<?= $sessionRunning ? 'far fa-eye' : 'fas fa-pencil-alt' ?> fa-2x mode_options_content" style="cursor: pointer; float: left; padding-left: 50px"></i>
					<h4 id="dynamicButtonText" class="mode_options_content" style="font-weight: 300"><?= $sessionRunning ? 'Game Results' : 'Game Setup' ?></h4>
				</div>
			</div>
			<div id="delete" class="grid-x grid-padding-x small-up-2 medium-up-3" style="margin-top: 25px; display: none;">
				<div class="cell mode-cell grow" style="cursor: pointer; background: linear-gradient(141deg, #bc0d0d 20%, #f73d3d 80%);" onclick="deleteGame()">
					<i class="fas fa-trash-alt fa-2x mode_options_content" style="float: left; padding-left: 50px"></i>
					<h4 class="mode_options_content" style="font-weight: 300">Delete Game</h4>
				</div>
				<form id="deleteForm" method="post" action="utils/game_util.php">
					<input type="hidden" name="deleteId" value="<?= $gameInfo['id'] ?>">
					<input type="hidden" name="deletedGameCourse" value="<?= $gameInfo['course_id'] ?>">
				</form>
			</div>
		</div>
	</div>
	
	<!-- MODALS -->
	<!-- ============================== -->
	<!-- Add New Course Modal -->
	<div class="small reveal" id="newCourseModal" data-reveal data-animation-in="slide-in-up" data-animation-out="slide-out-down" style="border-radius: 5px; opacity: 0.925; overflow-x: hidden;">
		<div style="border-radius: 5px; background-color: #0a4c6d; margin: -19px -22px 20px -19px; width: 1201px;">
			<h2 style="padding: 15px"><strong style="color: #FFF;"><i class="fas fa-cog"></i> Add New Course</strong></h2>
		</div>
		<form id="form-style-1" method="post" action="utils/add_course.php">
			<input type="text" name="name" placeholder="Course Name" required>
			<input type="number" name="section" placeholder="Section Number" required>
			<div class="dropdown">
			  <button id="avatarBtn" type="button" class="dropbtn button">Select an Avatar (optional):</button>
			  <div class="dropdown-content">
			  	<a onclick="avatarSelected('fa-chart-bar')"><i class="fas fa-chart-bar"></i> Bar Chart</a>
			  	<a onclick="avatarSelected('fa-balance-scale')"><i class="fas fa-balance-scale"></i> Balance Scale</a>
			    <a onclick="avatarSelected('fa-book')"><i class="fas fa-book"></i> Book</a>
			    <a onclick="avatarSelected('fa-briefcase')"><i class="fas fa-briefcase"></i> Briefcase</a>
			    <a onclick="avatarSelected('fa-certificate')"><i class="fas fa-certificate"></i> Certificate</a>
			    <a onclick="avatarSelected('fa-clipboard')"><i class="fas fa-clipboard"></i> Clipboard</a>
			    <a onclick="avatarSelected('fa-comments')"><i class="fas fa-comments"></i> Comments</a>
			    <a onclick="avatarSelected('fa-hand-holding-usd')"><i class="fas fa-hand-holding-usd"></i> Hand</a>
			    <a onclick="avatarSelected('fa-chalkboard-teacher')"><i class="fas fa-chalkboard-teacher"></i> Instructor</a>
			    <a onclick="avatarSelected('fa-lightbulb')"><i class="fas fa-lightbulb"></i> Lightbulb</a>
			    <a onclick="avatarSelected('fa-dollar-sign')"><i class="fas fa-dollar-sign"></i> Money</a>
			    <a onclick="avatarSelected('fa-file-alt')"><i class="fas fa-file-alt"></i> Paper</a>
			    <a onclick="avatarSelected('fa-user-graduate')"><i class="fas fa-user-graduate"></i> Student</a>
			    <a onclick="avatarSelected('fa-university')"><i class="fas fa-university"></i> University</a>
			  </div>
			</div>
			<input id="avatarInput" type="hidden" name="avatar">
			<div style="width: 220px; margin: 50px auto 0 auto">
				<button class="button success" style="width: 220px; height: 80px; border-radius: 5px;"><h4 style="color: white"><i class="fas fa-save"></i> <strong>Save Course </strong></h4></button>
			</div>
		</form>

		<button class="close-button" data-close aria-label="Close modal" type="button">
			<i style="color: #FFF" class="fas fa-times-circle"></i>
		</button>
	</div>

	<!-- Add New Game Modal -->
	<div class="large reveal" id="newGameModal" data-reveal data-animation-in="slide-in-up" data-animation-out="slide-out-down" style="background-color: #f7f7f7; border-radius: 5px; opacity: 0.925; min-height: 600px;overflow-x: hidden;">
		
		<div style="border-radius: 5px; background-color: #0a4c6d; margin: -19px -24px -10px -19px; width: 1201px;">
			<h2 style="padding: 15px 0 5px 15px"><strong style="color: #FFF;"><?= $gameInfo ? "Edit Game" : "Add New Game" ?></strong></h2>
			<h4 id="gameConfigSubtitle" style="margin-left: 25px; padding-bottom: 10px; color: white; display: none;">
				<i class="fas fa-wrench"></i> Game Configuration
			</h4>
			<h4 id="gameTypeSubtitle" style="margin-left: 25px; color: white; padding-bottom: 10px;">
				<i class="far fa-check-circle"></i> Select Game Type
			</h4>
		</div>
		<br>
		<div id="gameTypeSection">
			<div id="econ_type" class="grid-x grid-padding-x small-up-2 medium-up-3">
				<div class="cell mode-cell" onclick="toGameConfig('econ')" style="cursor: pointer; background: linear-gradient(141deg, #0fb88a 20%, #0fb8ad 80%);">
					<i class="fas fa-money-bill fa-2x mode_options_content" style="float: left; padding-left: 65px"></i>
					<h4 class="mode_options_content" style="font-weight: 300">Economics</h4>
				</div>
			</div>
			<div id="market_type" class="grid-x grid-padding-x small-up-2 medium-up-3" style="margin-top: 25px">
				<div class="cell mode-cell" style="background: linear-gradient(141deg, #0fb8ad 20%, #1fc8db 80%);">
					<span data-tooltip data-position="right" data-alignment="bottom" tabindex="3" title="Coming Soon!">
					<i class="fas fa-chart-line fa-2x mode_options_content" style="float: left; padding-left: 65px"></i>
					<h4 class="mode_options_content" style="font-weight: 300">Marketing</h4>
					</span>
				</div>
			</div>
			<div id="account_type" class="grid-x grid-padding-x small-up-2 medium-up-3" style="margin: 25px 0 25px 0">
				<div class="cell mode-cell" style="background: linear-gradient(141deg, #1fc8db 20%, #24b0e2 80%);">
					<span data-tooltip data-position="right" data-alignment="bottom" tabindex="3" title="Coming Soon!">
					<i class="fas fa-calculator fa-2x mode_options_content" style="float: left; padding-left: 65px"></i>
					<h4 class="mode_options_content" style="font-weight: 300">Accounting</h4>
					</span>
				</div>
			</div>
		</div>
		<div id="gameConfigSection" style="display: none;">
			<button class="button secondary" onclick="backToGameType()" style="display: <?= $gameInfo ? 'none' : ''?>">
				<i class="fas fa-chevron-left"></i> Back to Game Types
			</button>
			<br>
			<div class="configContainer">
				<h4 class="configHeader"><strong>Market Structure</strong></h4>
				<div class="grid-x grid-margin-x" style="height: 140px">
				  <div id="perfect" class="cell small-5 large-offset-1" style="cursor: pointer; background: <?= $gameInfo["market_struct"] == "perfect" ? "green" : "linear-gradient(141deg, #6dc2f4 20%, #4f7fcc 80%)" ?>; height: 120px; border-radius: 10px" onclick="structClicked('perfect')">
				  	<div class="verticalyCenter">
					  	<i class="fas fa-globe fa-2x game_config_content" style="float: left; margin-left: 40px"></i>
					  	<span data-tooltip tabindex="1" title="Students will act like a commodity producer along side 1000 other firms and have only a few choices in this simulation. They will see historic prices over the last 50 periods and will also see what the market output is in each period." data-click-open="false">
					  		<h4 class="game_config_content" style="font-weight: 300">Perfect Competition</h4>
					  	</span>
					</div>
				  </div>
				  <div id="monopoly" class="cell small-5" style="cursor: pointer; background: <?= $gameInfo["market_struct"] == "monopoly" ? "green" : "linear-gradient(141deg, #6dc2f4 20%, #4f7fcc 80%)" ?>; height: 120px; border-radius: 10px" onclick="structClicked('monopoly')">
					<div class="verticalyCenter">  	
					  	<i class="fas fa-crown fa-2x game_config_content" style="float: left; margin-left: 40px"></i>
					  	<span data-tooltip tabindex="1" title="There is	only 1 firm in this market selling a unique product. There are no close substitutes. Entry into the market is completely blocked." data-click-open="false">
					  		<h4 class="game_config_content" style="font-weight: 300">Monopoly</h4>
					  	</span>
					</div>
				  </div>
				</div>
				<div class="grid-x grid-margin-x" style="height: 140px">
				  <div id="oligopoly" class="cell small-5 large-offset-1" style="cursor: pointer; background: <?= $gameInfo["market_struct"] == "oligopoly" ? "green" : "linear-gradient(141deg, #6dc2f4 20%, #4f7fcc 80%)" ?>; height: 120px; border-radius: 10px" onclick="structClicked('oligopoly')">
					<div class="verticalyCenter">
					  	<i class="fas fa-users fa-2x game_config_content" style="float: left; margin-left: 40px"></i>
					  	<span data-tooltip tabindex="1" title="There are 2 firms selling differentiated products. Each firm has significant market power. Entry into the market is very difficult." data-click-open="false">
					  		<h4 class="game_config_content" style="font-weight: 300">Oligopoly</h4>
					  	</span>
					</div>
				  </div>
				  <div id="monopolistic" class="cell small-5" style="cursor: pointer; background: <?= $gameInfo["market_struct"] == "monopolistic" ? "green" : "linear-gradient(141deg, #6dc2f4 20%, #4f7fcc 80%)" ?>; height: 120px; border-radius: 10px" onclick="structClicked('monopolistic')">
					<div class="verticalyCenter">
					  	<i class="fas fa-balance-scale fa-2x game_config_content" style="float: left; margin-left: 40px"></i>
					  	<span data-tooltip tabindex="1" title="Students will make choices on multiple variables. These choices will allow students to create differentiation in their product as they compete to gain market share and ultimately maximize profits." data-click-open="false">
					  		<h4 class="game_config_content" style="font-weight: 300">Monopolistic Competition</h4>
					  	</span>
					</div>
				  </div>
				</div>
			</div>
			<div id="dynamicConfigOptions" style="display: none;">
				<!-- hideForMonopOligop class indicates these options are hidden for monopoly and oligopoly market selections -->
				<!-- hideForPerfect and hideForMonComp behave similarly -->
				<!-- changeBack class will unhide elements when market selection is changed -->
				<div class="configContainer hideForMonopOligop changeBack">
					<h4 class="configHeader"><strong>Difficulty</strong></h4>
					<div class="grid-x grid-margin-x" style="height: 100px">
					  <div id="principles" class="cell small-5 large-offset-2" style="cursor: pointer; background: <?= $gameInfo["difficulty"] == "principles" ? "green" : "linear-gradient(141deg, #1fc8db 20%, #6dc2f4 80%)" ?>; height: 75px; border-radius: 10px; margin: 0 auto auto;" onclick="diffClicked('principles')">
					  	<div class="verticalyCenter">
						  	<i class="fas fa-dice-one fa-2x game_config_content" style="float: left; margin-left: 40px"></i>
						  	<span class="changeBack hideForMonComp" title="1 Variable: Output" data-tooltip tabindex="1" data-click-open="false">
						  		<h4 class="game_config_content" style="font-weight: 300">Principles</h4>
						  	</span>
						  	<span class="changeBack hideForPerfect" title="2 Variables: Output & Prices" data-tooltip tabindex="1" data-click-open="false">
						  		<h4 class="game_config_content" style="font-weight: 300">Principles</h4>
						  	</span>
						</div>
					  </div>
					</div>
					<div class="grid-x grid-margin-x hideForPerfect changeBack" style="height: 100px">
					  <div id="intermediate" class="cell small-5" style="cursor: pointer; background: <?= $gameInfo["difficulty"] == "intermediate" ? "green" : "linear-gradient(141deg, #1fc8db 20%, #6dc2f4 80%)" ?>; height: 75px; border-radius: 10px; margin: 0 auto auto;" onclick="diffClicked('intermediate')">
					  	<div class="verticalyCenter">
						  	<i class="fas fa-dice-two fa-2x game_config_content" style="float: left; margin-left: 40px"></i>
						  	<span data-tooltip tabindex="1" title="5 Variables: Output, Prices, Marketing, Facility Development, & Product Development" data-click-open="false">
						  		<h4 class="game_config_content" style="font-weight: 300">Intermediate</h4>
						  	</span>
						</div>
					  </div>
					</div>
					<div class="grid-x grid-margin-x" style="height: 100px">
					  <div id="advanced" class="cell small-5" style="cursor: pointer; background: <?= $gameInfo["difficulty"] == "advanced" ? "green" : "linear-gradient(141deg, #1fc8db 20%, #6dc2f4 80%)" ?>; height: 75px; border-radius: 10px; margin: 0 auto auto;" onclick="diffClicked('advanced')">
					  	<div class="verticalyCenter">
						  	<i class="fas fa-dice-three fa-2x game_config_content" style="float: left; margin-left: 40px"></i>
						  	<span class="changeBack hideForMonComp" title="2 Variables: Output & Production Level" data-tooltip tabindex="1" data-click-open="false">
						  		<h4 class="game_config_content" style="font-weight: 300">Advanced</h4>
						  	</span>
						  	<span class="changeBack hideForPerfect" title="7 Variables: Output, Prices, Marketing, Facility Development, Product Development, Human Capital Development, & Distribution Development" data-tooltip tabindex="1" data-click-open="false">
						  		<h4 class="game_config_content" style="font-weight: 300">Advanced</h4>
						  	</span>
						</div>
					  </div>
					</div>
				</div>
				<div class="configContainer hideForMonComp hideForMonopOligop changeBack">
					<h4 class="configHeader"><strong>Macroeconomy</strong></h4>
					<div class="grid-x grid-margin-x" style="height: 140px">
					  <div id="stable" class="cell small-5 large-offset-1" style="cursor: pointer; background: <?= $gameInfo["macro_econ"] == "stable" ? "green" : "linear-gradient(141deg, #4f7fcc 20%, #4f3aad 80%)" ?>; height: 120px; border-radius: 10px" onclick="macroClicked('stable')">
					  	<div class="verticalyCenter">
						  	<i class="fas fa-stream fa-2x game_config_content" style="float: left; margin-left: 40px"></i>
						  	<span data-tooltip tabindex="1" title="No change in GDP and CPI." data-click-open="false">
						  		<h4 class="game_config_content" style="font-weight: 300">Stable</h4>
						  	</span>
						</div>
					  </div>
					  <div id="growth" class="cell small-5" style="cursor: pointer; background: <?= $gameInfo["macro_econ"] == "growth" ? "green" : "linear-gradient(141deg, #4f7fcc 20%, #4f3aad 80%)" ?>; height: 120px; border-radius: 10px" onclick="macroClicked('growth')">
					  	<div class="verticalyCenter">
						  	<i class="fas fa-chart-line fa-2x game_config_content" style="float: left; margin-left: 40px"></i>
						  	<span data-tooltip tabindex="1" title="High inflation." data-click-open="false">
						  		<h4 class="game_config_content" style="font-weight: 300">Growth</h4>
						  	</span>
						</div>
					  </div>
					</div>
				</div>
				<div class="configContainer" style="padding-bottom: 40px">
					<h4 class="configHeader"><strong>Preferences</strong></h4>
					<div class="preferencesSection" style="width: 400px; margin-left: auto; margin-right: auto;">
						<div class="hideForPerfect hideForMonComp changeBack">
							<div style="height: 30px">
								<h6 style="float: left; font-weight: 485">Demand Intercept: </h6>
								<p id="sliderOutput1" style="float: left; margin-left: 5px"><?= $gameInfo ? $gameInfo['demand_intercept'] : '3000' ?></p>
					    		<div style="float: right; width: 190px">
									<div id="s1" class="slider" data-slider data-initial-start="<?= $gameInfo ? $gameInfo['demand_intercept'] : '3000' ?>" data-end="5000" data-start="500">
									  <span class="slider-handle" data-slider-handle role="slider" tabindex="1"></span>
									  <span class="slider-fill" data-slider-fill></span>
									  <input type="hidden" value="<?= $gameInfo ? $gameInfo['demand_intercept'] : '3000' ?>">
									</div>
								</div>
				    		</div>
				    		<hr>
				    		<div style="height: 30px">
								<h6 style="float: left; font-weight: 485">Demand Slope: </h6>
								<p id="sliderOutput2" style="float: left; margin-left: 5px"><?= $gameInfo ? $gameInfo['demand_slope'] : '10' ?></p>
					    		<div style="float: right; width: 190px">
									<div id="s2" class="slider" data-slider data-initial-start="<?= $gameInfo ? $gameInfo['demand_slope'] : '10' ?>" data-end="25" data-start="1">
									  <span class="slider-handle" data-slider-handle role="slider" tabindex="1"></span>
									  <span class="slider-fill" data-slider-fill></span>
									  <input type="hidden" value="<?= $gameInfo ? $gameInfo['demand_slope'] : '10' ?>">
									</div>
								</div>
				    		</div>
				    		<hr>
				    		<div style="height: 30px">
								<h6 style="float: left; font-weight: 485">Fixed Cost: </h6>
								<p id="sliderOutput3" style="float: left; margin-left: 5px"><?= $gameInfo ? $gameInfo['fixed_cost'] : '200' ?></p>
					    		<div style="float: right; width: 190px">
									<div id="s3" class="slider" data-slider data-initial-start="<?= $gameInfo ? $gameInfo['fixed_cost'] : '200' ?>" data-end="500" data-start="50">
									  <span class="slider-handle" data-slider-handle role="slider" tabindex="1"></span>
									  <span class="slider-fill" data-slider-fill></span>
									  <input type="hidden" value="<?= $gameInfo ? $gameInfo['fixed_cost'] : '200' ?>">
									</div>
								</div>
				    		</div>
				    		<hr>
				    		<div style="height: 30px">
								<h6 style="float: left; font-weight: 485">Constant Cost: </h6>
								<p id="sliderOutput4" style="float: left; margin-left: 5px"><?= $gameInfo ? $gameInfo['const_cost'] : '40' ?></p>
					    		<div style="float: right; width: 190px">
									<div id="s4" class="slider" data-slider data-initial-start="<?= $gameInfo ? $gameInfo['const_cost'] : '40' ?>" data-end="100" data-start="10">
									  <span class="slider-handle" data-slider-handle role="slider" tabindex="1"></span>
									  <span class="slider-fill" data-slider-fill></span>
									  <input type="hidden" value="<?= $gameInfo ? $gameInfo['const_cost'] : '40' ?>">
									</div>
								</div>
				    		</div>
				    		<hr>
				    	</div>
						<div style="height: 30px">
							<h6 style="float: left; font-weight: 485">Round Time Limit: </h6>
							<p id="sliderOutput5" style="float: left; margin-left: 5px"><?= $gameInfo ? $gameInfo['time_limit'] : '2' ?> min(s)</p>
				    		<div style="float: right; width: 190px">
								<div id="s5" class="slider" data-slider data-initial-start="<?= $gameInfo ? $gameInfo['time_limit'] : '2' ?>" data-end="5" data-start="1">
								  <span class="slider-handle" data-slider-handle role="slider" tabindex="1"></span>
								  <span class="slider-fill" data-slider-fill></span>
								  <input type="hidden" value="<?= $gameInfo ? $gameInfo['time_limit'] : '2' ?>">
								</div>
							</div>
			    		</div>
			    		<hr>
			    		<div style="height: 30px">
							<h6 style="float: left; font-weight: 485">Number of Rounds: </h6>
							<p id="sliderOutput6" style="float: left; margin-left: 5px"><?= $gameInfo ? $gameInfo['num_rounds'] : '10' ?></p>
							<div style="float: right; width: 190px">
								<div id="s6" class="slider" data-slider data-initial-start="<?= $gameInfo ? $gameInfo['num_rounds'] : '10' ?>" data-end="20" data-start="1">
								  <span class="slider-handle" data-slider-handle role="slider" tabindex="1"></span>
								  <span class="slider-fill" data-slider-fill></span>
								  <input type="hidden" value="<?= $gameInfo ? $gameInfo['num_rounds'] : '10' ?>">
								</div>
							</div>
						</div>
						<hr>
						<div style="height: 30px">
							<h6 style="float: left; font-weight: 485">Game Title: </h6>
							<input class="input-group-field" type="text" style="width: 200px; float: right;" id="title" value="<?= $gameInfo ? $gameInfo['name'] : '' ?>" placeholder="Enter title...">
						</div>
					</div>
				</div>
				<!-- hidden inputs for new game modal - above button groups correspond to an input for saving to mysql entry for game -->
				<form id="newGameForm" method="post" action="utils/game_util.php">
					<input type="hidden" name="gameName" id="gameName">
					<input type="hidden" name="type" id="type">
					<input type="hidden" name="mode" id="mode" value="<?= $gameInfo ? $gameInfo['mode'] : ''?>">
					<input type="hidden" name="difficulty" id="diff" value="<?= $gameInfo ? $gameInfo['difficulty'] : ''?>">
					<input type="hidden" name="market_struct" id="marStr" value="<?= $gameInfo ? $gameInfo['market_struct'] : ''?>">
					<input type="hidden" name="macroEconomy" id="macrEcon" value="<?= $gameInfo ? $gameInfo['macro_econ'] : ''?>">
					<input type="hidden" name="limit" id="limit">
					<input type="hidden" name="numRounds" id="numRnd">
					<input type="hidden" name="demand_intercept" id="dIntr">
					<input type="hidden" name="demand_slope" id="dSlope">
					<input type="hidden" name="fixed_cost" id="fCost">
					<input type="hidden" name="const_cost" id="cCost">
					<input type="hidden" name="course_id" value="<?= isset($_GET['course']) ? $_GET['course'] : $gameInfo['course_id'] ?>">
					<input type="hidden" name="gameId" value="<?= isset($_GET['game']) ? $_GET['game'] : NULL ?>">
					<div style="width: 250px; margin: 0 auto 0 auto">
						<button type="button" class="button success" style="width: 250px; height: 100px; border-radius: 5px;" onclick="createGame()"><h4 style="color: white"><i class="fas fa-save"></i> <strong>Save Game </strong></h4></button>
					</div>
				</form>
			</div>
		</div>

		<button class="close-button" data-close aria-label="Close modal" type="button">
			<i class="fas fa-times-circle" style="color: #FFF"></i>
		</button>
	</div>
	<!-- ============================== -->
	<!-- end modals -->
	</div>

	<input type="hidden" id="sessionRunning" value="<?= $sessionRunning ?>">
	<input type="hidden" id="id" value="<?= $gameInfo['id'] ?>">

	<!-- Bottom bar -->
	<footer class="footer"></footer>

    <script src="../js/vendor/jquery.js"></script>
    <script src="../js/vendor/what-input.js"></script>
    <script src="../js/vendor/foundation.js"></script>
    <script src="../js/app.js"></script>
    <script type="text/javascript">

    	// back button in new game modal
    	function backToGameType() {
    		$('#gameTypeSection').css("display", "");
    		$('#gameTypeSubtitle').css('display', '');
    		$('#gameConfigSection').css('display', "none");
    		$('#gameConfigSubtitle').css('display','none');
    	}

    	// display warning message when delete button is pressed
		function deleteCourse() {
			if (confirm("Are you sure you want to delete this course?\nThis action will also delete any games saved within course.")) 
				$('#deleteCourseForm').submit();
		
    	}

    	// add click event to reveal modal to add corse/game
       	$('#addNewModal').click(function() {
    		if (window.location.search.includes("course="))
    			$('#newGameModal').foundation('open');
    		else {
    			$('#newCourseModal').foundation('open');
    		}
    	});

       	// configuring main back button based on current page
    	if (window.location.search.includes("course=")) {
    		$('#backButton').css('display','');
    		game_transitions();
    	}
    	else if (window.location.search.includes("game=")){
    		$('#backButton').css('display','');
    		gameDetail_transitions();
    	}
    	else {
    		$('#backButton').css('display','none');
    		$("#course_options").fadeIn(1000);
    	}

    	// when a course or game is selected, add the appropriate id to the query string, causing the screen's content to change accordingly
    	function course_selected(course) {
    		window.location.search = "course="+course;
    	}
    	function game_selected(game) {
    		window.location.search = "game="+game;
    	}

    	function game_transitions() { // fade transitions to show saved games
    		document.getElementById("selection_prompt").innerHTML = "Select Game";
    		$("#course_options").fadeOut(250, function() {
    			$("#game_options").fadeIn(1000);
    		});
    	}

    	// staggered fade in affect for three buttons on individual game screen
    	function gameDetail_transitions() {
    		document.getElementById("selection_prompt").innerHTML = "Game Options";
    		$("#game_options").fadeOut(250, function() {
    			document.getElementById("addNewModal").style.display = "none";
    			document.getElementById("prompt_symbol").className = "fas fa-clipboard-list fa-2x";

    			$('#session_toggle').fadeIn(500);
    			$('#view_game').fadeIn(1000); 
    			$('#delete').fadeIn(1500);
    		});
    	}

    	function toggleSession(id, gameName, mode) { // makes call to change session live status
    		var priceHistory = []; var shockYear=0;
    		if ('<?= $gameInfo["market_struct"] ?>' == 'perfect') {
    			// if toggling a perfect comp game, generate the 25 yr price history
    			priceHistory = [50];
    			for (var i=1;i<25;i++) {
		    		var prevPrice = priceHistory[i-1];

		    		// if 5th year or 5 years since sock, allow random shock occurance (1 in 5 probability of occuring)
		    		if (i >= 5 && i-shockYear >= 5 && getRandomArbitrary()==3) {
		    			price = 0.5+0.999*prevPrice+random(1,2)+random(0,10);
		    			shockYear=i;
		    		}
		    		else 
		    			price = 0.5+0.999*prevPrice+random(1,2);

		    		priceHistory.push(price.toFixed(2));
		    	}
    		}
		    $.ajax({
		    	url: "utils/session.php",
		    	method: "POST",
		    	data: {action: "toggle", id: id, priceHist: priceHistory.join()},
		    	success: function(toggledOn) {
		    		// update screen accordingly based on weather going online or offline
		    		if (toggledOn) { 
		    			$('#sessionIdSubheader').css('display', '');
		    			$('#toggleColor').css('background', "linear-gradient(141deg, #f1c00b 20%, #e2d009 80%)");
		    			$('#toggleIcon').removeClass(); $('#dynamicButtonIcon').removeClass();
		    			$('#dynamicButtonIcon').addClass('far fa-eye fa-2x mode_options_content');
		    			$('#toggleIcon').addClass('far fa-stop-circle fa-2x mode_options_content');
		    			$('#dynamicButtonText').text('Game Results');
		    			$('#toggleText').text('End Session');
		    			$('#sessionRunning').val(1);
		    		}
		    		else {
		    			$('#sessionIdSubheader').css('display', 'none');
		    			$('#toggleColor').css('background', "linear-gradient(141deg, #008c0b 20%, #01a071 80%)");
		    			$('#toggleIcon').removeClass(); $('#dynamicButtonIcon').removeClass();
		    			$('#dynamicButtonIcon').addClass('fas fa-pencil-alt fa-2x mode_options_content');
		    			$('#toggleIcon').addClass('far fa-play-circle fa-2x mode_options_content');
		    			$('#dynamicButtonText').text('Game Setup');
		    			$('#toggleText').text('Start Session');
		    			$('#sessionRunning').val(0);
		    		}
		    	}
		    });
    	}

    	// click handler for game results/edit game button
	    $(document).on('click', '#dynamicButtonFunc', function() { 
	    	if ($('#sessionRunning').val() == 1)
	    		redirectResultsPage();
	    	else
	    		editGame();
	    });

    	// stuff for creating new game/ editing existing
    	// ====================

    	// connect slider values with lables
    	$('#s1').on('moved.zf.slider', function() {
    		$('#sliderOutput1').html($(this).children('.slider-handle').attr('aria-valuenow'));
    	});
    	$('#s2').on('moved.zf.slider', function() {
    		$('#sliderOutput2').html($(this).children('.slider-handle').attr('aria-valuenow'));
    	});
    	$('#s3').on('moved.zf.slider', function() {
    		$('#sliderOutput3').html($(this).children('.slider-handle').attr('aria-valuenow'));
    	});
    	$('#s4').on('moved.zf.slider', function() {
    		$('#sliderOutput4').html($(this).children('.slider-handle').attr('aria-valuenow'));
    	});
    	$('#s5').on('moved.zf.slider', function() {
    		$('#sliderOutput5').html($(this).children('.slider-handle').attr('aria-valuenow')+" min(s)");
    	});
    	$('#s6').on('moved.zf.slider', function() {
    		$('#sliderOutput6').html($(this).children('.slider-handle').attr('aria-valuenow'));
    	});

    	// after user selects game type, go to appropriate configuration page (only econ type currently)
    	function toGameConfig(type) {
    		document.getElementById('gameTypeSection').style.display = "none";
    		$('#gameTypeSubtitle').css('display', 'none');
    		document.getElementById('gameConfigSection').style.display = "";
    		$('#gameConfigSubtitle').css('display','');
    		document.getElementById('type').value = type;
    	}

    	// initially only the market structure seletion is shown on config page
    	// when structure is selected, show the appopriate options for that structure
    	function structClicked(id) {
    		var options = ['monopolistic', 'monopoly', 'perfect', 'oligopoly'];

    		// change colors - selected option will be green
    		document.getElementById(options[0]).style.background = "linear-gradient(141deg, #6dc2f4 20%, #4f7fcc 80%)";
    		document.getElementById(options[1]).style.background = "linear-gradient(141deg, #6dc2f4 20%, #4f7fcc 80%)";
    		document.getElementById(options[2]).style.background = "linear-gradient(141deg, #6dc2f4 20%, #4f7fcc 80%)";
    		document.getElementById(options[3]).style.background = "linear-gradient(141deg, #6dc2f4 20%, #4f7fcc 80%)";
    		document.getElementById(id).style.background = "green";

    		// set market struct value in form
    		document.getElementById('marStr').value = id;

    		// set single or multi player player
    		if (id=='oligopoly')
    			document.getElementById('mode').value = 'multi';
    		else
    			document.getElementById('mode').value = 'single';

    		// show dynamic config options based on market struct selection
    		dynamicConfigFunc(id);
    	}

    	//changes colors and sets input to match selection for difficulty and macro economy options
    	function diffClicked(id) {
    		var options = ['principles', 'intermediate', 'advanced'];
    		document.getElementById(options[0]).style.background = "linear-gradient(141deg, #1fc8db 20%, #6dc2f4 80%)";
    		document.getElementById(options[1]).style.background = "linear-gradient(141deg, #1fc8db 20%, #6dc2f4 80%)";
    		document.getElementById(options[2]).style.background = "linear-gradient(141deg, #1fc8db 20%, #6dc2f4 80%)";
    		document.getElementById(id).style.background = "green";
    		document.getElementById('diff').value = id;
    	}
    	function macroClicked(id) {
    		var options = ['stable', 'growth'];
    		document.getElementById(options[0]).style.background = "linear-gradient(141deg, #4f7fcc 20%, #4f3aad 80%)";
    		document.getElementById(options[1]).style.background = "linear-gradient(141deg, #4f7fcc 20%, #4f3aad 80%)";
    		document.getElementById(id).style.background = "green";
    		document.getElementById('macrEcon').value = id;
    	}

    	// when user clicks to create game / save update
    	function createGame() {
    		document.getElementById('limit').value = $('#s5').children('.slider-handle').attr('aria-valuenow');
    		document.getElementById('numRnd').value = $('#s6').children('.slider-handle').attr('aria-valuenow');
    		document.getElementById('dIntr').value = $('#s1').children('.slider-handle').attr('aria-valuenow');
    		document.getElementById('dSlope').value = $('#s2').children('.slider-handle').attr('aria-valuenow');
    		document.getElementById('fCost').value = $('#s3').children('.slider-handle').attr('aria-valuenow');
    		document.getElementById('cCost').value = $('#s4').children('.slider-handle').attr('aria-valuenow');
    		document.getElementById('gameName').value = document.getElementById('title').value;

    		$("#newGameForm").submit();
    	}

    	// view game button pressed
    	function editGame() {
    		$('#newGameModal').foundation('open');
    		document.getElementById('gameTypeSection').style.display = "none";
    		document.getElementById('gameConfigSection').style.display = "";
    		$('#gameTypeSubtitle').css('display', 'none');
			$('#gameConfigSubtitle').css('display','');

			dynamicConfigFunc('<?=$gameInfo["market_struct"]?>');
    	}
    	// ----------------

    	// called when user selects an avatar on the course selection modal, save it to input on new course form
    	function avatarSelected(type) {
    		$('#avatarBtn').html('<i class="fas '+type+' fa-2x"></i>');
    		$('#avatarInput').val(type);
    	}

    	// display warning message for game deletion
    	function deleteGame() {
    		if (confirm('Delete game?\nThis action cannot be reversed.'))
    			$('#deleteForm').submit();
    	}

    	// when user selects results button, send to results.php
    	function redirectResultsPage() {
    		urlPrefix = window.location.href.substr(0, window.location.href.indexOf('src'));
    		window.location=urlPrefix+'src/results.php?game='+$('#id').val();
    	}

    	// handles displaying the relevant options for the selected market structure in the new game/edit game modal
    	function dynamicConfigFunc(marStr) {
    		$('#dynamicConfigOptions').css('display','inherit');

    		switch (marStr) {
    			case 'monopoly':
    			case 'oligopoly':
    				$('#dynamicConfigOptions').find('.changeBack').css('display','inherit');
    				$('.hideForMonopOligop').css('display','none');
    				break;
    			case 'perfect':
    				$('#dynamicConfigOptions').find('.changeBack').css('display','inherit');
    				$('.hideForPerfect').css('display','none');
    				break;
    			case 'monopolistic':
    				$('#dynamicConfigOptions').find('.changeBack').css('display','inherit');
    				$('.hideForMonComp').css('display','none');
    				break;
    		}
    	}


    	// Helper function for economic calculations. Accepts a mean and std deviation and returns rand num
		function random(m, s) {
		  return m + 2.0 * s * (Math.random() + Math.random() + Math.random() - 1.5);
		}
		// Returns random number between 1 and 5, determening if shock will occur
		function getRandomArbitrary() {
		    return (Math.random() * (6 - 1) + 1).toPrecision(1);
		}

    </script>

  </body>

  <style type="text/css">
  	html, body {
  		height: 100%;
  		margin: 0;
  	}
  	a { color: inherit; }
  	a:hover { color: inherit; }
  	.game_options_content {
  		display: block;
  		text-align: center;
  		margin-top: 20%;
  	}
  	.game_config_content {
  		display: block;
  		margin-left: 90px;
  		color: white;
  	}
  	.verticalyCenter {
		position: relative;
		top: 50%;
		transform: translateY(-50%);
  	}
  	.card {
  		color: #fff;
  		height: 400px;
  		border-radius: 20px;
  	}
  	.cell { height: 400px; }
  	.mode-cell {
  		height: 150px;
  		border-radius: 20px;
  		color: #fff;
  		margin-left: auto;
  		margin-right: auto;
  	}
  	.mode_options_content {
  		text-align: center;
  		margin-top: 16%;
  	}
	.grid-container {
		min-height: calc(100vh - 295px);
	}
	.footer {
		background-color: #0a4c6d;
		height: 75px;
	}
	.configHeader {
   		padding-top: 15px;
   		margin: 5px 0 20px 65px;
	}
	#form-style-1 input {
		height: 50px;
		font-size: 25px;
		border: none;
		border-bottom: 1px solid #7a7a7a;
		margin-bottom: 25px;
	}
	#form-style-1 input:focus {
		outline-width: 0;
		box-shadow: none;
	}
	.dropbtn {
	    padding: 16px;
	    font-size: 16px;
	    border: none;
	    cursor: pointer;
	    min-width: 224px;
	}
	.dropdown {
	    position: relative;
	    display: inline-block;
	}
	.dropdown-content {
	    display: none;
	    position: absolute;
	    background-color: #f9f9f9;
	    min-width: 200px;
	    box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
	    z-index: 1;
	    max-height: 145px;
	    overflow: scroll;
	}
	.dropdown-content i {
	    padding-right: 10px;
	    width: 30px
	}
	.dropdown-content a {
	    color: black;
	    padding: 12px 16px;
	    text-decoration: none;
	    display: block;
	}
	.dropdown-content a:hover {  background-color: #f1f1f1 }
	.dropdown:hover .dropdown-content { display: block; }
	.dropdown:hover .dropbtn { background-color: #3e8e41; }
	.has-tip {
		cursor: pointer;
		outline: none;
		box-shadow: none;
		display: inline;
		border: none;
	}
	.configContainer {
		filter: drop-shadow(3px 3px 5px black);
		width: 750px;
		margin: 0 auto 50px auto;
		background-color: #f7f7f7;
		border-radius: 5px;
		padding: 10px;
	}
	.grow:hover {
		transform: scale(1.1);
	}
	.grow[class*="trash"]:hover {
		color: red;
		transform: scale(1.25);
	}
	.grow[class*="plus"]:hover {
		color: green;
		transform: scale(1.25);
	}
  </style>
</html>