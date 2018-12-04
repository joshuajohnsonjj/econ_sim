<?php
/* results.php

- uses socket.io to live update the results of a game across three categories: output, price, revenue, profit
- presents as chart of averages, and table with individual values
- up to two table rows can be selected to display in chart form

Initially shows "annual averages," which is table showing average quantities submitted. There are buttons to change which value is tracked on chart. Tabs at to can switch to individual view which shows a table of all users in the sessino and their values over the years. The instructor can select 1 to 2 students to show graphicaly on a slide up modal.

*/

ini_set('display_errors', 1); error_reporting(-1); 
include 'utils/sql_settup.php';
require_once "../../tsugi/config.php";

use \Tsugi\Core\LTIX;
use Tsugi\Core\WebSocket;

$LAUNCH = LTIX::session_start();
// Render view
$OUTPUT->header();

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
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/zf/dt-1.10.18/b-1.5.2/sl-1.2.6/datatables.min.css"/>
  </head>
  <body style="background-color: #d3f6ff;">

  <input type="hidden" id="id" value="<?=$gameInfo['id']?>">
  <input type="hidden" id="eq" value="<?=$gameInfo['equilibrium']?>">
  
			<!-- TITLE BAR -->
  	<div class="title-bar" style="background-color: #0a4c6d">
	  <div class="title-bar-left">
	  	<div class="media-object" style="float: left;">
		    <div class="thumbnail" style="margin: 0; border: none; background: none;">
		      <img src="../assets/img/no_bg_monogram.png" height="100px" width="100px">
		    </div>
		</div>
	    <span class="title-bar-title">
	    	<h3 style="margin: 30px; font-weight: 500;">
	    		<?= $gameInfo['name'] ?> Results
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
			<i class="fas fa-angle-left"></i> Back
		</button>
		<div class="navButtons">
			<div id="avgButton" class="selected" style="border-right: 1px solid #666666" onclick="javascript:changeContent('avg')">
				Annual Averages
			</div>
			<div id="indivButton" class="nonselected" style="border-right: 1px solid #666666" onclick="javascript:changeContent('indiv')">
				Individual Submissions
			</div>
		</div>
	</div>

	<!-- MAIN CONTENT -->
	<div class="mainContent">
		<div>
			<h4>Annual Averages</h4>
			<hr>
			<div id="valueDisplaySelector" class="grid-x">
				<div class="cell small-3"><button onclick="changeDisplayValue(this)">Quantity</button></div>
				<div class="cell small-3"><button onclick="changeDisplayValue(this)">Price</button></div>
				<div class="cell small-3"><button onclick="changeDisplayValue(this)">Revenue</button></div>
				<div class="cell small-3"><button onclick="changeDisplayValue(this)">Profit</button></div>
			</div>
		</div>
		<div id="avgSection" style="width: 1200px; background-color: #fcfcfc">
			<canvas id="chart" style="padding: 10px"></canvas>
		</div>
		<div id="indivSection" style="display: none;">
			<div style="width: 98%; margin: auto; padding-bottom: 5px">
				<table id="table_id" class="display" width="100%"></table>
			</div>
		</div>
	</div>

	<!-- Modal - displays data for selected students on "individual submissions" section -->
	<div class="reveal" id="chartModal" data-reveal data-animation-in="slide-in-up" style="border-radius: 5px; opacity: 0.9">
		<h4><strong>Compare Student(s)</strong></h4>
		<canvas id="revealChart"></canvas>
		<button class="close-button" data-close aria-label="Close reveal" type="button">
			<span aria-hidden="true">&times;</span>
		</button>
	</div>


	<!-- hidden inputs for javasctipt -->
	<input id="numRounds" type="hidden" value="<?=$gameInfo['num_rounds']?>">
	<input id="gameId" type="hidden" value="<?=$gameInfo['id']?>">

	<!-- Bottom bar -->
	<footer class="footer"></footer>

	<?php
		$OUTPUT->footerStart();
	?>

	<script src="../js/vendor/jquery.js"></script>
    <script src="../js/vendor/what-input.js"></script>
    <script src="../js/vendor/foundation.js"></script>
    <script src="../js/app.js"></script>
    <script src="../js/node_modules/chart.js/dist/Chart.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/v/zf/dt-1.10.18/b-1.5.2/sl-1.2.6/datatables.min.js"></script>
	<script type="text/javascript">
		broadcast_web_socket = tsugiNotifySocket(); 
  		broadcast_web_socket.onmessage = function(evt) { 
  			// check to see if message came from correct gameId, if so update results 
  			if (evt.data=='<?=$gameInfo['id']?>') {
  				updateResults();
  			}
	    };

		// initialize variables needed for chart and table
		var tableData = [], indivData = [], indivData2 = [];
		var chart = null, revealChart = null;
		var chartData = [], averages = [];

		const valTypes = {"Quantity":"player_quantity", "Price":"price", "Revenue":"player_revenue", "Profit":"player_profit"};
		var selectedValType = "player_quantity"; // initially default to displaying production quantity

		// make explicit call to get data one time on load, them listen for dynamic updates thereafter
		// (populates the graph and chart on intial page load as well as refreshes)
		updateResults();

		/* 
		\\\\when a student submits quantity from gaim_main.php////
		- tableData contains the data for table under "Individual Submissions" tab
		- tableData is an array of arrays - Nested arrays: first element is username, subsequent elements are data
		- chartData contains raw quantities from users
		- chart is indended to display averages of all students for selected value
		- averages contains this compiled data to display on chart
		*/

		function updateResults() {
			if ($.fn.dataTable.isDataTable( '#table_id' ) ) { // if table has already been created, clear it and empty the data array
        		$('#table_id').DataTable().destroy();
        		$('#table_id').empty();
        		tableData = [];
        	}

        	// clear arrays
        	averages = []; chartData = [];

        	// ajax to get data from sql for chart and table displays
        	$.ajax({
		  		url: "utils/session.php", 
		  		method: 'POST',
	  			data: { action: 'retrieve_gameSessionData', gameId: <?=$gameInfo['id']?>, valueType: selectedValType },
	  			success: function(response) {
	  				var json = JSON.parse(response);

	  				for (var i=0; i < Object.keys(json).length; i++) {
	  					// data for chart
	  					indivData = json[i]['data'];
	  					chartData.push(indivData);

	  					// data for table (add username to front of individual data arrays)
	        			indivData = [json[i]['username'].substr(0, json[i]['username'].indexOf('@'))];
	        			if ('<?=$gameInfo["market_struct"]?>'=='oligopoly')
	        				indivData = indivData.concat(json[i]['group'])
	        			indivData = indivData.concat(json[i]['data']);
	        			tableData.push(indivData);

	        			indivData = [];

	  				} 

	  				// create table
		        	tableCallback(tableData);

		        	// to change raw chart data to the desired array of averages for each year
		        	var counter = 0, i = 0, sum = 0;
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
		        		graphCallback(averages, $('#eq').val(), 'Quantity');

	  			}
	  		});
        }


		function changeContent(section) {
			// set header
			const header = $('.mainContent').find('h4');
			if (header.text() == 'Annual Averages') header.text('Individual Submissions');
			else header.text('Annual Averages');

			// display selected content
			$('#avgSection').css('display','none');
			$('#indivSection').css('display','none');
			$('#'+section+'Section').css('display','');

			// highlight selected button
			$('#avgButton').css('background-color','#767676');
			$('#indivButton').css('background-color','#767676');
			$('#'+section+'Button').css('background-color','#1779ba');
		}
		
		var columns = [{ title: "Yr. 1" },{ title: "Yr. 2" },{ title: "Yr. 3" },{ title: "Yr. 4" },{ title: "Yr. 5" },{ title: "Yr. 6" },{ title: "Yr. 7" }, { title: "Yr. 8" },{ title: "Yr. 9" },{ title: "Yr. 10" }, { title: "Yr. 11" }, { title: "Yr. 12" }, { title: "Yr. 13" },  { title: "Yr. 14" },  { title: "Yr. 15" }, { title: "Yr. 16" }, { title: "Yr. 17" }, { title: "Yr. 18" }, { title: "Yr. 19" }, { title: "Yr. 20" }, { title: "Yr. 21" }, { title: "Yr. 22" }, { title: "Yr. 23" }, { title: "Yr. 24" }, { title: "Yr. 25" }];
		columns = columns.splice(0, $('#numRounds').val()); 

		if ('<?=$gameInfo["market_struct"]?>'=='oligopoly') 
			columns = [{ title: "Student" }, { title: "Group" }].concat(columns)
		else
			columns = [{ title: "Student" }].concat(columns)

		function tableCallback(data) {
			$.fn.dataTable.ext.errMode = 'none'; // supress error from not all columns being
		    var table = $('#table_id').DataTable( {
		        data: data,
		        columns: columns,
		        destroy: true,
		        dom: 'Bfrtip',
		        select: {
		        	style: "multi"
		        },
		        buttons: [
		        	{
		        		text: "Show Graph",
		        		action: function() { // Displays modal with the data from the selected row(s)
		        			var rowData = table.rows({selected: true }).data().toArray();
		        			revealChartCallback(rowData, rowData.length);
		        			$('#chartModal').foundation('open');
		        		}
		        	}
		        ]
		    });

		    table.buttons().disable();

		    // limit the number of selected rows to a max of 2
			table.on( 'select', function ( e, dt, type, ix ) {
			   var selected = dt.rows({selected: true});
			   table.buttons().enable();
			   if ( selected.count() > 2 ) {
			      dt.rows(ix).deselect();
			   }
			} );
			// if no buttons selected, show graph button should be disabled
			table.on( 'deselect', function ( e, dt, type, ix ) {
				var selected = dt.rows({selected: true});
			    if ( selected.count() == 0 ) {
			       table.buttons().disable();
			    }
			} );
		}
		
		var graphLabels = ["Yr. 1", "Yr. 2", "Yr. 3", "Yr. 4", "Yr. 5", "Yr. 6", "Yr. 7", "Yr. 8", "Yr. 9", "Yr. 10", "Yr. 11", "Yr. 12", "Yr. 13",  "Yr. 14",  "Yr. 15",  "Yr. 16", "Yr. 17",  "Yr. 18",  "Yr. 19",  "Yr. 20",  "Yr. 21",  "Yr. 22",  "Yr. 23",  "Yr. 24",  "Yr. 25"];
		graphLabels = graphLabels.splice(0, $('#numRounds').val());

		function graphCallback(data, eq, valType) { 
			// valType used to check if selected value is quantity. if so show equilibrium on chart. hide otherwise..
			var equilibrium = new Array(20).fill(eq);

			var fullDataObj = {
			        labels: graphLabels,
			        datasets: [{
			            label: 'Average Value',
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
			    };

			chart = new Chart($('#chart'), {
			    type: 'line',
			    data: fullDataObj,
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

		function revealChartCallback(data, count) {
			const name1 = data[0][0];
			const data1 = data[0].slice(2);
			const equilibrium = new Array(20).fill($('#eq').val()).splice(0, $('#numRounds').val());

			// create data object based on number of selected students (1 or 2)
			if (count == 1) 
				var dataObj = {
				        labels: graphLabels,
				        datasets: [{
				            label: name1,
				            data: data1,
				            fill: false,
				            borderColor: 'rgba(255,99,132,1)',
				            pointBackgroundColor: 'rgba(255,99,132,1)',
				            borderWidth: 3,
				            pointRadius: 4
				        },
				        {
				            label: 'Equilibrium',
				            data: equilibrium,
				            fill: false,
				            pointRadius: 0,
				            borderColor: 'rgba(0,0,255,1)',
				            borderWidth: 3
				        }]
				    };
			else {
				const name2 = data[1][0];
				const data2 = data[1].slice(2);

				var dataObj = {
			        labels: graphLabels,
			        datasets: [{
			            label: name1,
			            data: data1,
			            fill: false,
			            borderColor: 'rgba(255,99,132,1)',
			            pointBackgroundColor: 'rgba(255,99,132,1)',
			            borderWidth: 3,
			            pointRadius: 5
			        },
			        {
			            label: name2,
			            data: data2,
			            fill: false,
			            borderColor: 'rgba(232, 228, 0,1)',
			            pointBackgroundColor: 'rgba(232, 228, 0,1)',
			            borderWidth: 3,
			            pointRadius: 5
			        },
			        {
			            label: 'Equilibrium',
			            data: equilibrium,
			            fill: false,
			            pointRadius: 0,
			            borderColor: 'rgba(0,0,255,1)',
			            borderWidth: 3
			        }]
			    };
			}
			revealChart = new Chart($('#revealChart'), {
			    type: 'line',
			    data: dataObj,
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

		// on backbutton press
		function redirectAdimn(game) {
			urlPrefix = window.location.href.substr(0, window.location.href.indexOf('src'));
			window.location=urlPrefix+'src/admin_page.php?game='+game;
		}

		// handler for value selector buttons on individual submissions section
		$("#valueDisplaySelector").find("button").first().addClass('selectedValue'); // initial highlighted value is quanitity
		function changeDisplayValue(element) {
			// change colors
			$("#valueDisplaySelector").find("button").removeClass('selectedValue');
			$(element).addClass('selectedValue');
			selectedValType = valTypes[$(element).text()];

			// get appropriate values and populate chart/table
			// ajax to get data from sql for chart and table displays

			if ($.fn.dataTable.isDataTable( '#table_id' ) ) { // if table has already been created, clear it and empty the data array
        		$('#table_id').DataTable().destroy();
        		$('#table_id').empty();
        		tableData = [];
        	}

        	$.ajax({
		  		url: "utils/session.php", 
		  		method: 'POST',
	  			data: { action: 'retrieve_gameSessionData', gameId: <?=$gameInfo['id']?>, valueType: selectedValType },
	  			success: function(response) {
	  				var json = JSON.parse(response);

	  				// clear arrays
	  				chartData=[];tableData=[];indivData=[];averages=[];

	  				for (var i=0; i < Object.keys(json).length; i++) {
	  					// data for chart
	  					indivData = json[i]['data'];
	  					chartData.push(indivData);

	  					// data for table (add username to front of individual data arrays)
	        			indivData = [json[i]['username'].substr(0, json[i]['username'].indexOf('@'))]
	        			indivData = indivData.concat(json[i]['data']);
	        			tableData.push(indivData);

	        			indivData = [];

	  				} 

	  				// create table
		        	tableCallback(tableData);

		        	// to change raw chart data to the desired array of averages for each year
		        	var counter = 0, i = 0, sum = 0;
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

		        	// update chart
	        		chart.data.datasets[0].data = [];
	            	chart.data.datasets[0].data = averages;
	            	if ($(element).text() != "Quantity")
	            		chart.data.datasets[1].data = [];
	            	else
	            		chart.data.datasets[1].data = new Array(20).fill($('#eq').val());
	            	chart.update();

	  			}
	  		});
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
		.mainContent {
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
			margin-bottom: 0.8rem;
			width: 80%;
		}
		#valueDisplaySelector {
			width: 50%;
			margin: auto;
		}
		.cell > button {
			width: 80%;
			height: 50px;
			margin: auto;
			background: linear-gradient(141deg, #0fb88a 20%, #0fb8ad 80%);
			border-radius: 24px; 
			color: white;
		}
		.cell > button:hover {
			cursor: pointer;
		}
		.selectedValue {
			background: green !important;
			transform: scale(1.25);
		}
		.reveal {
			outline: none;
			box-shadow: none;
		}
	</style>
  </body>

 <?php
$OUTPUT->footerEnd();