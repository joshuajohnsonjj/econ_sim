<?php
/*
results.php

Contains code for displaying game results from admin side
*/
	ini_set('display_errors', 1); error_reporting(-1); 
	include 'utils/sql_settup.php';
	require_once "../../tsugi/config.php";

	use \Tsugi\Core\LTIX;

	$LAUNCH = LTIX::session_start();

	if (!$USER->instructor)
		header("Location: ..");

	$selectedGame = $_GET['game'];

	$gameInfo = getGameInfo($mysqli, (int)$selectedGame);
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
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.16/css/jquery.dataTables.css">
  </head>
  <body style="background-color: #d3f6ff;">

  <input type="hidden" id="id" value="<?=$gameInfo['id']?>">
  <input type="hidden" id="eq" value="<?=$gameInfo['equilibrium']?>">
  
			<!-- TITLE BAR -->
  	<div class="title-bar" style="background-color: #0a4c6d">
	  <div class="title-bar-left">
	  	<div class="media-object" style="float: left;">
		    <div class="thumbnail" style="margin: 0; border: none;">
		      <img src="../assets/img/no_bg_monogram.png" height="100px" width="100px">
		    </div>
		</div>
	    <span class="title-bar-title">
	    	<h3 style="margin: 30px; font-weight: 500;">
	    		<?= $gameInfo['name'] ?> - Results
	    	</h3>
	    </span>
	  </div>
	  <div class="title-bar-right">
	  	<?php if (isset($_GET['usr'])) { ?>
	  		<img src="../assets/img/default_usr_img.jpeg" style="height: 40px; border-radius: 18px; float: right;">
	  		<p style="margin-top: 10px; padding-right: 50px">Logged in as: <?= $_SESSION['username'] ?></p>
	  		<button onclick="logout_usr()" class="alert button" style="margin-right: 60px;">
	  			<strong>Logout</strong> <i class="fas fa-sign-out-alt"></i>
	  		</button>
	  	<?php } ?>
	  </div>
	</div>
	<!-- end title bar -->
	<div style="background-color: #fcfcfc; width: 100%; height: 40px; margin-bottom: 50px">
		<button id="backButton" class="secondary button" style="float: left; margin-right: 20px" onclick="redirectAdimn(<?=$selectedGame?>)">
			<i class="far fa-caret-square-left"></i> Back
		</button>
		<div class="navButtons">
			<div id="avgButton" class="selected" style="border-right: 1px solid #666666" onclick="javascript:changeContent('avg')">
				Annual Averages
			</div>
			<div id="indivButton" class="nonselected" style="border-right: 1px solid #666666" onclick="javascript:changeContent('indiv')">
				Individual Submissions
			</div>
			<div id="otherButton" class="nonselected" onclick="changeContent('other')">
				Other Stuff??
			</div>
		</div>
	</div>

	<!-- MAIN CONTENT -->
	<div class="mainContent">
		<div id="avgSection" style="width: 1200px; background-color: #fcfcfc">
			<h4>Annual Averages</h4>
			<hr>
			<canvas id="chart" style="padding: 10px"></canvas>
		</div>
		<div id="indivSection" style="display: none;">
			<h4>Individual Submissions</h4>
			<hr>
			<div style="width: 98%; margin: auto; padding-bottom: 5px">
				<table id="table_id" class="display" width="100%"></table>
			</div>
		</div>
		<div id="otherSection" style="display: none;">
			<h4>Other Stuff??</h4>
			<hr>
			<p>OTHER STUFF HERE?  MAYBE....</p>
		</div>
	</div>


	<!-- hidden inputs for javasctipt -->
	<input id="numRounds" type="hidden" value="<?=$gameInfo['num_rounds']?>">
	<input id="gameId" type="hidden" value="<?=$gameInfo['id']?>">

	<!-- Bottom bar -->
	<footer class="footer"></footer>

	<script src="../js/vendor/jquery.js"></script>
    <script src="../js/vendor/what-input.js"></script>
    <script src="../js/vendor/foundation.js"></script>
    <script src="../js/app.js"></script>
    <script src="../js/node_modules/chart.js/dist/Chart.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.js"></script>
	<script src="http://localhost:8080/socket.io/socket.io.js"></script> 
	<script type="text/javascript">
		// connect to server
		var socket = io.connect('http://localhost:8080');

		// initialize variables needed for chart and table
		var tableData = [], indivData = [], indivData2 = [];
		var chart = null, table = null;
		var chartData = [], averages = [];

		// make explicit call to get data one time on load, them listen for dynamic updates thereafter
		// (populates the graph and chart on intial page load as well as refreshes)
		socket.emit('instructorGrabData');

		/* 
		\\\\when a student submits quantity from gaim_main.php////
		- tableData contains the data for table under "Individual Submissions" tab
		- tableData is an array of arrays - Nested arrays: first element is username, subsequent elements are data
		- chartData contains raw quantities from users
		- chart is indended to display average quantity submitted by all students in game for each year
		- averages contains this compiled data to display on chart
		*/
		socket.on('studentSubmitedQuantity', function(gameCollection) { 

			if (table) { // if table has already been created, clear it and empty the data array
        		table.destroy();
        		$('#table_id').empty();
        		tableData = [];
        	}

        	// clear arrays for chart
        	averages = []; chartData = [];

        	// loop thru gameCollection and grab the values to polulate graph and table
        	for (var i = 0; i < gameCollection.totalGameCount; i++) {
        		// check if current gameObject is of correct game session 
        		// SINGLEPLAYER
        		if (gameCollection.gameList[i]['singleGameObject'] && gameCollection.gameList[i]['singleGameObject']['gameId'] == $('#gameId').val()) {

        			// data for chart
        			indivData = gameCollection.gameList[i]['singleGameObject']['playerData'];
        			chartData.push(indivData);

        			// data for table (add username to front of individual data arrays)
        			indivData.splice(0,0,gameCollection.gameList[i]['singleGameObject']['player']);
        			tableData.push(indivData);

        			indivData = [];
        		}
        		// MULTIPLAYER
        		else if (gameCollection.gameList[i]['multiGameObject'] && gameCollection.gameList[i]['multiGameObject']['gameId'] == $('#gameId').val()) { 
        			// data for chart
        			indivData = gameCollection.gameList[i]['multiGameObject']['playerOneData'];
        			indivData2 = gameCollection.gameList[i]['multiGameObject']['playerTwoData'];
        			chartData.push(indivData);
        			chartData.push(indivData2);

        			// data for table (add username to front of individual data arrays)
        			indivData.splice(0,0,gameCollection.gameList[i]['multiGameObject']['playerOne']);
        			indivData2.splice(0,0,gameCollection.gameList[i]['multiGameObject']['playerTwo']);
        			tableData.push(indivData);
        			tableData.push(indivData2);

        			indivData = []; indivData2 = [];
        		}
        	} 

        	// create table
        	tableCallback(tableData);

        	// to change raw chart data to the desired array of averages for each year
        	var counter = 0, i = 1, sum = 0;
        	while (i <= 20) { // loop a maximum of 20 times (the max number of years for game)
        		for (j = 0; j < chartData.length; j++) { // 
        			sum += chartData[j][i];
        			counter++;
        		}
        		if (counter==0)
        			break;
        		averages.push((sum/counter).toFixed(2));
        		sum = 0; counter = 0; i++;
        	}

        	if (chart) { // if chart exists reset data and update it
        		chart.data.datasets[0].data = [];
            	chart.data.datasets[0].data = averages;
            	chart.update();
        	}
        	else // if chart doesnt exist yet, create it
        		graphCallback(averages, $('#eq').val());

		});

		function changeContent(section) {
			// display selected content
			$('#avgSection').css('display','none');
			$('#indivSection').css('display','none');
			$('#otherSection').css('display','none');
			$('#'+section+'Section').css('display','');

			// highlight selected button
			$('#avgButton').css('background-color','#767676');
			$('#indivButton').css('background-color','#767676');
			$('#otherButton').css('background-color','#767676');
			$('#'+section+'Button').css('background-color','#1779ba');
		}
		
		var columns = [{ title: "Yr. 1" },{ title: "Yr. 2" },{ title: "Yr. 3" },{ title: "Yr. 4" },{ title: "Yr. 5" },{ title: "Yr. 6" },{ title: "Yr. 7" }, { title: "Yr. 8" },{ title: "Yr. 9" },{ title: "Yr. 10" }, { title: "Yr. 11" }, { title: "Yr. 12" }, { title: "Yr. 13" },  { title: "Yr. 14" },  { title: "Yr. 15" }, { title: "Yr. 16" }, { title: "Yr. 17" }, { title: "Yr. 18" }, { title: "Yr. 19" }, { title: "Yr. 20" }, { title: "Yr. 21" }, { title: "Yr. 22" }, { title: "Yr. 23" }, { title: "Yr. 24" }, { title: "Yr. 25" }];
		columns = columns.splice(0, $('#numRounds').val());

		function tableCallback(data) {
			$.fn.dataTable.ext.errMode = 'none'; // supress error from not all columns being
		    table = $('#table_id').DataTable( {
		        data: data,
		        columns: [{ title: "Student" }].concat(columns)
		    } );
		}
		
		const graphLabels = ["Yr. 1", "Yr. 2", "Yr. 3", "Yr. 4", "Yr. 5", "Yr. 6", "Yr. 7", "Yr. 8", "Yr. 9", "Yr. 10", "Yr. 11", "Yr. 12", "Yr. 13",  "Yr. 14",  "Yr. 15",  "Yr. 16", "Yr. 17",  "Yr. 18",  "Yr. 19",  "Yr. 20",  "Yr. 21",  "Yr. 22",  "Yr. 23",  "Yr. 24",  "Yr. 25"];

		function graphCallback(data, eq) {
			const equilibrium = new Array(20).fill(eq);
			chart = new Chart($('#chart'), {
			    type: 'line',
			    data: {
			        labels: graphLabels.splice(0, $('#numRounds').val()),
			        datasets: [{
			            label: 'Average Quantity',
			            data: data,
			            fill: false,
			            borderColor: 'rgba(255,99,132,1)',
			            pointBackgroundColor: 'rgba(255,99,132,1)',
			            borderWidth: 3,
			            pointRadius: 5
			        },
			        {
			            label: 'Equilibrium',
			            data: equilibrium.splice(0, $('#numRounds').val()),
			            fill: false,
			            pointRadius: 0,
			            borderColor: 'rgba(0,0,255,1)',
			            borderWidth: 3
			        }]
			    },
			    options: {
			        scales: {
			            yAxes: [{
			                ticks: {
			                    beginAtZero:true
			                }
			            }]
			        },
			        animation: false
			    }
			});
		}

		function redirectAdimn(game) {
			urlPrefix = window.location.href.substr(0, window.location.href.indexOf('src'));
			window.location=urlPrefix+'src/admin_page.php?game='+game;
		}
	</script>

	<style type="text/css">
		html, body {
	  		height: 100%
	  	}
	  	.mainContent { min-height: 700px; }
	  	.footer {
			background-color: #0a4c6d;
			height: 50px;
			width: 100%;
			margin-top: 50px;
		}
		.navButtons > div {
			float: left;
			cursor: pointer;
			height: 40px;
			color: white;
			padding: 5px 15px 0 15px;
			vertical-align: middle;
		}
		.selected {
			background-color: #1779ba;
		}
		.nonselected {
			background-color: #767676;
		}
		.mainContent > div {
			filter: drop-shadow(3px 3px 5px black);
			border-radius: 5px;
			width: 1200px; 
			background-color: #fcfcfc;
			margin: 0 auto 0 auto;
		}
		.mainContent > div > h4 {
			text-align: center;
			font-weight: 450;
			padding-top: 10px;
		}
		hr {
			margin-bottom: 0.65rem;
			width: 80%;
		}

	</style>

  </body>