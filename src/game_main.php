<?php
/* game_main.php

- Game play for monopoly and oligopoly

On monopoly mode, a user enters directly into game and can start playing. There is one descision to be made, that being output quanity. Once submission is made, an overview of the past year's data is shown in a table along with a chart tracking the quanitity history. The user can then change views using the slide over menue to view more detailed info of different types. The slide over menue can also resummon the instructions modal.

On oligopoly mode, a waiting overlay is shown on top of the game screen preventing the user from starting until being matched with another player. When student submits, screen will wait to allow another submission unitl opponent has submitted as well. If one user quits, the other user is booted out to student.php and a message is displayed.

Last Update: Updated socket.io to Tsugi Websockets
*/

include 'utils/sql_settup.php';
require_once "../../tsugi/config.php";

use \Tsugi\Core\LTIX;
use Tsugi\Core\WebSocket;

$LAUNCH = LTIX::session_start();

// Render view
$OUTPUT->header();

// get the current games set up info
$gameInfo = getGameInfo((int)$_GET['session']);
$startGame = true;

// if multi mode (oligopoly) do not immediately start game - must wait to be matched with another player
if ($gameInfo['mode'] == 'multi') $startGame = false;

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
   	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.5.2/animate.css" />
   	<link rel="stylesheet" href="//cdn.jsdelivr.net/npm/alertifyjs@1.11.1/build/css/alertify.min.css"/>
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/alertifyjs@1.11.1/build/css/themes/default.min.css"/>

    <!--This func wouldn't work in safari unless it was up here -->
    <script type="text/javascript">
    	var firstSubmit = false;

    	// Func for off canvas menu to change screen content
    	function change_content(to_section) {
    		if (to_section == 'instructions') {
    			$('#beginModal').foundation('open');
    			return;
    		}

    		var headers = {"dashboard_section": "Dashboard", "income_section": "Income Statement", "cost_section": "Cost Data"};
    		var elements = document.getElementsByClassName("display_sections");

    		if (firstSubmit) { // make sure a submit has occured before being able to switch to info sections
	    		document.body.scrollTop = document.documentElement.scrollTop = 0; // force page to top

	    		for (var i = elements.length - 1; i >= 0; i--) {
	    			elements[i].style.display = "none";
	    		}

	    		document.getElementById(to_section).style.display = "";
	    		$('#dynamicHeader').text(headers[to_section]);

	    		init(to_section);
	    	}
	    	else { // if no submit yet, show message
		  		alertify.set('notifier','delay', 3);
				alertify.set('notifier','position', 'top-right');
				alertify.warning('<p style="text-align: center; margin: 0;"><i class="fas fa-exclamation-triangle"></i><br>Please  enter valid quantity!<br>(1-<?=$gameInfo['max_quantity']?> units)</p>');
	    	}
    	}
    </script>
  </head>
  <body style="background-color: #d3f6ff;">

	<div class="off-canvas position-right" id="offCanvas" data-off-canvas data-transition="overlap" style="background-color: #121212"> 

		<!-- Menu -->
		<ul class="vertical menu darken" style="color: white">
		  <li style="color: #8a8a8a; background: #2c2c2c; font-size: 0.9em; padding: 10px; margin-bottom: 10px">Menu</li>
		  <li><a onclick="change_content('dashboard_section')">Dashboard</a></li>
		  <li><a onclick="change_content('income_section')">Income Statement</a></li>
		  <li><a onclick="change_content('cost_section')">Cost Data</a></li>
		  <li><a onclick="change_content('instructions')">Instructions</a></li>
		</ul>

		<ul class="vertical menu" style="position: absolute; bottom: 0; width: 100%; margin-bottom: 25px">
			<li>
				<button onclick="leaveGame()" class="alert button" style="width: 125px; margin-left: auto; margin-right: auto;"><strong>Exit Game</strong></i></button>
			</li>
		</ul>
	</div>

	<!-- Multiplayer wait screen -->
	<div id="multWaitScreen" style="display: <?= !$startGame ? '' : 'none'?>; z-index: 100; height: 100%; position: fixed; top: 0; left: 0; width: 100%; ">
		<div style="background-color: white; height: 400px; width: 600px; margin: 150px auto; border-radius: 5px;">
			<button class="button" style="float: left; background-color: #cc4b37; margin: 10px; border-radius: 2px;" onclick="leaveGame();">
				<i class="fas fa-times"></i> Cancel
			</button>
			<i class="fas fa-spinner fa-3x fa-pulse" style="display: block; margin: auto; width: 49px; height: 49px; position: relative; top: 41%"></i>
			<h3 style="margin: auto; width: 276px; height: 50px; position: relative; top: 45%">Finding Opponent...</h3>
		</div>
	</div>

	<div class="off-canvas-content" data-off-canvas-content style="filter: <?= !$startGame ? 'blur(10px) brightness(0.8);' : 'none'?>">
	<!-- Your page content lives here -->
		<!-- title bar -->
		<div class="title-bar">
		  <div class="title-bar-left">
		  	<div class="media-object" style="float: left;">
			    <div class="thumbnail" style="margin: 0; border: none; background: none;">
			      <img src="../assets/img/no_bg_monogram.png" height="100px" width="100px">
			    </div>
			</div>
		    <span class="title-bar-title">
		    	<h3 style="margin: 30px 0 0 30px; font-weight: 500"><?= $gameInfo['name'] ?></h3>
		    	<h6 style="margin-left: 30px; display: none;">
		    		Opponent: <span class="competition"></span>
		    	</h6>
		    </span>
		  </div>
		  <div class="title-bar-right">
		  	<div style="margin-right: 50px;">
			  	<img src="../assets/img/default_usr_img.jpeg" style="height: 40px; border-radius: 18px; float: right;">
		  		<p style="padding-top: 10px; padding-right: 50px">Logged in as: <?= $USER->displayname ?></p>
		    </div>
		    <button class="menu-icon" type="button" data-open="offCanvas"></button>
		  </div>
		</div>
		<!-- end title bar -->

		<!-- Toolbar -->
		<div id="topToolbar">
			<h3 id="dynamicHeader" style="padding-top: 25px; padding-left: 25px; margin-bottom: 0; font-weight: 350; font-weight: 600;">Dashboard</h3>
			<div style="width: 100%; height: 60px; position: absolute; bottom: 0">
				<div class="grid-x" style="margin-left: 25px">
					<div class="cell large-1 "><p id="year"><b>Year:</b> 1</p></div>
					<div class="cell large-4">
						<p style="float: left; padding-right: 10px"><b>Timer: </b></p>
						<div id="progressContainer" class="success progress" aria-valuemax="100">
						  <span id="progressBar" class="progress-meter" style="width: 100%">
						  	<p id="timer" class="progress-meter-text" data-legnth="<?= $gameInfo['time_limit'] ?>"><?= $gameInfo['time_limit'] ?>:00</p>
						  </span>
						</div>
					</div>
					<div class="cell large-6">
						<p style="float: left; padding-right: 20px"><b>Quantity: </b></p>
						<input type="number" id="quantity" style="width: 125px; float: left;" min="1" max="500" placeholder="1 - <?=$gameInfo['max_quantity']?> Units">
						<button class="button" type="button" id="price_submit_btn" style="margin-left: 20px; font-weight: 500; float: left;">Submit</i></button>
						<span id="waitOppSub" style="display: none; text-decoration: none; border: none; outline: none;" data-tooltip tabindex="1" title="Waiting for opponent to submit quantity">
							<i class="fas fa-spinner fa-pulse fa-2x"></i>
						</span>
					</div>
				</div>
			</div>
		</div>
		<!--  End toolbar -->

		<input type="hidden" id="sessionId" value="<?=$_GET['session']?>">
		<input type="hidden" id="usrname" value="<?=$USER->email?>">
		<input type="hidden" id="opponent" value="">
		<input type="hidden" id="mode" value="<?=$gameInfo['mode']?>">

		<div id="mainContent"> 

			<!-- before first submission prompt -->
			<div id="preStartPrompt" style="width: 500px; margin: 280px auto 30px auto;">
				<h3 style="text-align: center; color: #bdbebf">
					<i class="far fa-play-circle fa-5x"></i><br>
					<strong style="font-weight: 500;">Enter Quantity to Begin!</strong>
				</h3>
			</div>

			<!-- Dashboard -->
			<div class="display_sections" id="dashboard_section"> 
				<div class="section_content" id="summarySection" style="display: none;">
					<div class="section_cell" style="float: left;">
						<h4 id="summaryYear" style="text-align: center; font-weight: 450">Summary for Year </h4>
						<hr style="margin-bottom: 30px">
						<table class="paleBlueRows">
							<tbody>
								<tr>
									<td>Market Price</td>
									<td><span id="marketPrice"></span></td>
								</tr>
								<tr>
									<td>Production Output</td>
									<td><span id="prodQuantity"></span></td>
								</tr>
								<tr>
									<td>Marginal Cost</td>
									<td>$<span id="unitCost"></span></td>
								</tr>
								<tr>
									<td>Total Cost</td>
									<td>$<span id="ttlCost"></span></p></td>
								</tr>
								<tr>
									<td>Revenue</td>
									<td><span id="revenue"></span></td>
								</tr>
								<tr>
									<td>Profit</td>
									<td><span id="profit"></span></p></td>
								</tr>
								<tr>
									<td><b>Cumulative Earnings</b></td>
									<td><b><span id="cumulative"></span></b></td>
								</tr>
							</tbody>
						</table>
					</div>
					<div class="section_cell cell_graph" style="float: right;">
						<h4 style="text-align: center; font-weight: 450">Shipments</h4>
						<hr style="margin-bottom: 0.65rem">
						<div class="graph">
							<canvas id="quantityChart"></canvas>
						</div>
					</div>
				</div>
			</div>
			<!-- --------- -->

			<!-- Income Statement -->
			<div class="display_sections" id="income_section" style="display: none;">
				<div class="section_content">
					<div style="min-height: 550px">
						<div class="section_cell" style="width: 700px; margin: 0 auto 50px auto;">
							<h4 style="text-align: center; font-weight: 450">Year <span class="yearSpan">1</span> Overview</h4>
							<hr style="margin-bottom: 20px">
							<table class="paleBlueRows">
								<tbody>
									<?=$gameInfo['mode'] == 'multi'?'<tr><td> </td><td><b>You</b></td><td><em>Opponent</em></td></tr>':''?>
									<tr>
										<td>Revenue</td>
										<td id="liRevenue"></td>
										<?=$gameInfo['mode'] == 'multi'?'<td id="liRevenueOpp"></td>':''?>
									</tr>
									<tr>
										<td>Net Profit</td>
										<td id="liNet"></td>
										<?=$gameInfo['mode'] == 'multi'?'<td id="liNetOpp"></td>':''?>
									</tr>
									<tr>
										<td>Return on Sales</td>
										<td id="liReturn"></td>
										<?=$gameInfo['mode'] == 'multi'?'<td id="liReturnOpp"></td>':''?>
									</tr>
									<tr>
										<td>Price</td>
										<td id="liPrice"></td>
									</tr>
								</tbody>
							</table>
						</div>
						<div style="margin-bottom: 50px; margin-top: -20px; text-align: center;">
							<i class="fas fa-angle-down fa-4x animated bounce" id="bouncingArrow"></i>
						</div>
					</div>
					<div id="animate0" class="section_cell" style="width: 500px; margin: 0 auto 50px auto;">
						<h3 style="text-align: center;"><strong>Historical Info</strong></h3>
					</div>
					<div id="animate1" class="section_cell cell_graph" style="float: left;">
						<h4 style="text-align: center; font-weight: 450">Annual Income</h4>
						<hr style="margin-bottom: 0.65rem">
						<div class="graph">
							<canvas id="incomeChart" ></canvas>
						</div>
					</div>
					<div id="animate2" class="section_cell cell_graph" style="float: right;">
						<h4 style="text-align: center; font-weight: 450">Cummulative Earnings</h4>
						<hr style="margin-bottom: 0.65rem">
						<div class="graph">
							<canvas id="cummulativeChart"></canvas>
						</div>
					</div>
					<div id="animate3" class="section_cell cell_graph" style="float: left; margin-top: 50px">
						<h4 style="text-align: center; font-weight: 450">Price</h4>
						<hr style="margin-bottom: 0.65rem">
						<div class="graph">
							<canvas id="priceChart"></canvas>
						</div>
					</div>
					<div id="animate4" class="section_cell cell_graph" style="float: right; margin-top: 50px">
						<h4 style="text-align: center; font-weight: 450">Shipments</h4>
						<hr style="margin-bottom: 0.65rem">
						<div class="graph">
							<canvas id="quantityChart2"></canvas>
						</div>
					</div>
				</div>
			</div>
			<!-- ---------------- -->

			<!-- Cost Data -->
			<div class="display_sections" id="cost_section" style="display: none;">
				<div class="section_content">
					<div style="min-height: 550px">
						<div class="section_cell" style="width: 700px; margin: 0 auto 50px auto;">
							<h4 style="text-align: center; font-weight: 450">Year <span class="yearSpan">1</span> Overview</h4>
							<hr style="margin-bottom: 20px">
							<table class="paleBlueRows">
								<tbody>
									<tr>
										<td>Shipments</td>
										<td id="liSales"></td>
									</tr>
									<tr>
										<td>Price</td>
										<td id="liPrice2"></td>
									</tr>
									<tr>
										<td>Marginal Cost</td>
										<td id="liMarginal"></td>
									</tr>
									<tr>
										<td>Production Cost</td>
										<td id="liProduction"></td>
									</tr>
								</tbody>
							</table>
						</div>
						<div style="margin-bottom: 50px; margin-top: -20px; text-align: center;">
							<i class="fas fa-angle-down fa-4x animated bounce" id="bouncingArrow"></i>
						</div>
					</div>
					<div id="animate0b" class="section_cell" style="width: 500px; margin: 0 auto 50px auto;">
						<h3 style="text-align: center;"><strong>Historical Info</strong></h3>
					</div>
					<div id="animate1b" class="section_cell cell_graph" style="float: left;">
						<h4 style="text-align: center; font-weight: 450">Production Cost</h4>
						<hr style="margin-bottom: 0.65rem">
						<div class="graph">
							<canvas id="costChart"></canvas>
						</div>
					</div>
					<div id="animate2b" class="section_cell cell_graph" style="float: right;">
						<h4 style="text-align: center; font-weight: 450">Marginal Cost</h4>
						<hr style="margin-bottom: 0.65rem">
						<div class="graph">
							<canvas id="marginalChart"></canvas>
						</div>
					</div>
					<div id="animate3b" class="section_cell cell_graph" style="float: left; margin-top: 50px">
						<h4 style="text-align: center; font-weight: 450">Average Total</h4>
						<hr style="margin-bottom: 0.65rem">
						<div class="graph">
							<canvas id="avgTotalChart"></canvas>
						</div>
					</div>
					<div id="animate4b" class="section_cell cell_graph" style="float: right; margin-top: 50px">
						<h4 style="text-align: center; font-weight: 450">Shipments</h4>
						<hr style="margin-bottom: 0.65rem">
						<div class="graph">
							<canvas id="quantityChart3"></canvas>
						</div>
					</div>
				</div>
			</div>
			<!-- --------- -->
		</div>
	</div>

	<!-- MODALS -->
	<!-- begining of game instructions -->
	<div class="reveal" id="beginModal" data-reveal data-animation-in="slide-in-up" style="border-radius: 5px; opacity: 0.9">
		<h2 style="text-align: left;"><strong>Instructions</strong></h2>
		<?php if ($gameInfo['market_struct']=='oligopoly') { ?>
			<p>In this simulation you will be the owner of a non-durable commodity, selling your product in a oligopolistic market environment. Your goal is to determine output levels in this strategically interactive environment in order to profit maximize.</p> 
			<p>For each of <?= $gameInfo['num_rounds'] ?> periods you will observe previous prices and choose a quantity to sell in the next period.  Since you are one of two firms selling in this market, your choice, along with your competitor’s choice will determine your profits each round.</p>
			<p>At the end of the simulation, cumulative profits will be measured and grading against a hypothetical firm acting optimally.</p>
		<?php } else { ?>
			<p>In this simulation you will be the owner of a non-durable commodity, selling your product in a monopolistic market environment. Your goal is to determine output levels in order to profit maximize.</p> 
			<p>Each period you will observe previous prices and choose a quantity to sell in the next period.  Since you are the only firm selling in this market, there is no industry market research to consult.</p>
			<p>At the end of the simulation, cumulative profits will be measured and grading against a hypothetical firm acting optimally.</p> 
		<?php } ?>
		<button class="close-button" data-close aria-label="Close reveal" type="button">
			<span aria-hidden="true">&times;</span>
		</button>
	</div>
	<!-- end game -->
	<div class="reveal" id="endModal" data-reveal data-animation-in="slide-in-up" style="border-radius: 5px; opacity: 0.9">
		<h2 style="text-align: center;"><strong>Game Over!</strong></h2>
		<p style="text-align: center;">(Dismiss to view final results)</p>
		<button class="close-button" data-close aria-label="Close reveal" type="button">
			<span aria-hidden="true">&times;</span>
		</button>
	</div>
	<!-- modal end -->

	<!-- Hidden inputs containing values from game setup (used by python script for calculations) -->
	<input id="dIntr" type="hidden" value="<?=$gameInfo['demand_intercept']?>">
	<input id="dSlope" type="hidden" value="<?=$gameInfo['demand_slope']?>">
	<input id="cCost" type="hidden" value="<?=$gameInfo['const_cost']?>">
	<input id="fCost" type="hidden" value="<?=$gameInfo['fixed_cost']?>">
	<input id="numRounds" type="hidden" value="<?=$gameInfo['num_rounds']?>">

	<!-- Bottom bar -->
	<footer class="footer" style="filter: <?= !$startGame ? 'blur(10px) brightness(0.7);' : 'none'?>"></footer>

	<?php
		$OUTPUT->footerStart();
	?>

    <script src="../js/vendor/jquery.js"></script>
    <script src="../js/vendor/what-input.js"></script>
    <script src="../js/vendor/foundation.js"></script>
    <script src="../js/app.js"></script>
    <script src="../js/node_modules/chart.js/dist/Chart.js"></script>
	<script src="//cdn.jsdelivr.net/npm/alertifyjs@1.11.1/build/alertify.min.js"></script>
    <script type="text/javascript">
    	// submissions
    	var quantity, oppQuantity;
    	const numRounds = parseInt($('#numRounds').val(), 10);

    	var gameIsComplete = false;
    	
    	broadcast_web_socket = null;
    	room_web_socket = null;

    	// Economic Data
    	// -------------
    	var year = 1;
    	var cumulativeRevenue = 0;
    	var cumulativeProfit = 0;
    	var cumulativeHistory = [];
    	var cumulativeProfHistory = [];
    	var quantityHistory = [];
    	var revenueHistory = [];
    	var profitHistory = [];
    	var ttlCostHist = [];
    	var avgTtlCostHist = [];
    	var priceHistory = [];
    	var marginalCostHist = [];

    	// opponent data for multiplayer games
    	var oppCumulativeRevenue = 0;
    	var oppCumulativeProfit = 0;
    	var oppCumulativeHistory = [];
    	var oppCumulativeProfHistory = [];
    	var oppProfitHistory = [];
    	var oppRevenueHistory = [];
    	var oppQuantityHistory = [];
    	// -------------------


	    var groupId = (Math.random()+1).toString(36).slice(2, 18);

	    if ('<?=$gameInfo['mode']?>' == 'single') {
	    	$('#beginModal').foundation('open');
	    } 
	    else {
	    	$.ajax({
		  		url: "utils/websocket_util.php", 
		  		method: 'POST',
	  			data: { action: 'join_multi', sessionId: $('#sessionId').val(), username: $('#usrname').val(), groupId: groupId },
	  			success: function(response) {
	  				// if response is returned, the user joined a waiting player, so game can start
	  				if (response) { 
	  					let json = JSON.parse(response);
	  					if (json[0].length > 10)
							groupId=json[0].substring(0, json[0].length - 1);
						else
							groupId=json[0];

	  					// join socket to room
	  					room_web_socket = tsugiNotifySocket(groupId);
	  					
	  					// hide the wait screen
						dismissWaitScreen();
						// open instructions
						$('#beginModal').foundation('open');

						// set labels based on opponent and groupId recieved
						let opp = json[1].substring(0, json[1].indexOf('@'));
						$('#opponent').val(opp);
						$('.competition').text(opp);
						$('span.title-bar-title > h6').css('display', 'inherit');

						// send message to room to notify other player that game is starting
						room_web_socket.onopen = function(evt) {
							room_web_socket.send($('#usrname').val());
						}

	  				}
	  				// if no response returned, user joined new game and is waiting for opponent
	  				else {
	  					if (groupId.length > 10)
							groupId=groupId.substring(0, groupId.length - 1);

					  	room_web_socket = tsugiNotifySocket(groupId);
					  	room_web_socket.onmessage = function(evt) {
					  		if (evt.data.includes('@')) { 
						        // hide the wait screen
								dismissWaitScreen();
								// open instructions
								$('#beginModal').foundation('open');

								// set labels based on opponent and groupId recieved
								let opp = evt.data.substring(0, evt.data.indexOf('@'));
								$('#opponent').val(opp);
								$('.competition').text(opp);
								$('span.title-bar-title > h6').css('display', 'inherit');
							}
							else if (evt.data = "exit") {
								const urlPrefix = window.location.href.substr(0, window.location.href.indexOf('src'));
								window.location = urlPrefix+'src/student.php?session=err2';	
							}
					    };
					}

	  			}
	  		});
	    }	    
		

		// singleplayer submission occured
		function getSingleResults(quantity) {
			var gameOver = true;
			if (year != numRounds) intervalId = setInterval(startTimer, 1000);

			$.ajax({
				url: "http://localhost:8888/cgi-bin/econ_test/single?quantity="+quantity+"&intercept="+$('#dIntr').val()+"&slope="+$('#dSlope').val()+"&fixed="+$('#fCost').val()+"&const="+$('#cCost').val(), 
				success: function(data) {
					var json = JSON.parse(data);

					$('#preStartPrompt').css('display','none');
					// Enable/update summary display content
					if (year != numRounds) {
					  	document.getElementById("summarySection").style.display = "";
					  	document.getElementById("summaryYear").innerHTML = "Summary for Year "+year;
					  	year+=1;
					  	$('.yearSpan').text(year-1);
					  	document.getElementById("year").innerHTML = "<b>Year:</b> "+year;
					  	gameOver = false;
					}

				  	// save equilibrium to database for display in instructor results
				  	$.ajax({
				  		url: "utils/game_util.php", 
				  		method: 'POST',
			  			data: { equilibrium: json['equilibrium'], id: $('#sessionId').val() }
			  		});

					// update values based on retrieved data
					cumulativeRevenue += json['totalRevenue'];
					cumulativeProfit += json['profit'];
					cumulativeHistory.push(cumulativeRevenue);
					cumulativeProfHistory.push(cumulativeProfit);
					profitHistory.push(json['profit']);
					revenueHistory.push(json['totalRevenue']);
					ttlCostHist.push(json['totalCost']);
					avgTtlCostHist.push(json['averageTotalCost']);
					quantityHistory.push(quantity);
					priceHistory.push(json['demand']);
					marginalCostHist.push(json['unitCost']);

			        // correctly format output with commas and negatives where neccissary
			        var marketPriceString, revenueString, profitString, cumulativeString;
			        if (json['demand'] < 0 ) marketPriceString = '-$'+(json['demand']*(-1)).toLocaleString();
			        else marketPriceString = '$'+json['demand'].toLocaleString();
			        if (json['totalRevenue'] < 0 ) revenueString = '-$'+(json['totalRevenue']*(-1)).toLocaleString();
			        else revenueString = '$'+json['totalRevenue'].toLocaleString();
			        if (json['profit'] < 0 ) profitString = '-$'+(json['profit']*(-1)).toLocaleString();
			        else profitString = '$'+json['profit'].toLocaleString();
			        if (cumulativeProfit < 0 ) cumulativeString = '-$'+(cumulativeProfit*(-1)).toLocaleString();
			        else cumulativeString = '$'+cumulativeProfit.toLocaleString();

					// Set text in summary section to represent retrieved data
					document.getElementById("marketPrice").innerHTML = marketPriceString;
					document.getElementById("prodQuantity").innerHTML = quantity+ " Units";
					document.getElementById("revenue").innerHTML = revenueString;
					document.getElementById("unitCost").innerHTML = json['unitCost'];
					document.getElementById("ttlCost").innerHTML = json['totalCost'].toLocaleString();
					document.getElementById("profit").innerHTML = profitString;
					document.getElementById("cumulative").innerHTML = cumulativeString;

					// set income screen stuff
					$('#liRevenue').text(revenueString);
					$('#liNet').text(profitString);
					$('#liPrice').text(marketPriceString);
					$('#liReturn').text(json['percentReturn'].toPrecision(4)+'%');

					// set cost screen stuff
					$('#liSales').text(quantity+" Units");
					$('#liPrice2').text('$'+json['demand'].toLocaleString());
					$('#liMarginal').text('$'+json['unitCost']+"/Unit");
					$('#liProduction').text('$'+json['totalCost'].toLocaleString());

					// redraw graph
					init('dashboard_section');
					init('income_section');
					init('cost_section');

					// enable button
					if (!gameOver) $('#price_submit_btn').prop('disabled', false);

					// call func to submit data in querry
					$.ajax({
				  		url: "utils/session.php", 
				  		method: 'POST',
			  			data: { action: 'update_gameSessionData', groupId: groupId, username: $('#usrname').val(), opponent: null, quantity: quantity, revenue: json['totalRevenue'],
			  				profit: json['profit'], percentReturn: json['percentReturn'].toPrecision(4), price: json['demand'], unitCost: json['unitCost'], totalCost: json['totalCost'], complete: gameOver?1:0, gameId: <?= $gameInfo['id'] ?>  }
			  		});


			  		// send message to notify instructor results to update
					if (!broadcast_web_socket) {
				  		broadcast_web_socket = tsugiNotifySocket(); 
				  		broadcast_web_socket.onopen = function(evt) { 
							broadcast_web_socket.send($('#sessionId').val());
						}
					}
					else
						broadcast_web_socket.send($('#sessionId').val());
					  		
				}	
			});
		}

		// // multiplayer submission occured
   		function getMultiResults(submitData) {
   			var gameOver = true;
   			if (year != numRounds) intervalId = setInterval(startTimer, 1000);

			$('#preStartPrompt').css('display','none');

			// hide spinner
			$('#waitOppSub').css('display', 'none');

			// set variables with submission for correct player
			if ($('#usrname').val() == submitData[0]) {
				oppQuantity = submitData[3];
				quantity = submitData[1];
			}
    		else {
    			oppQuantity = submitData[1];
    			quantity = submitData[3];
 			}

    		// call python script to get results
	    	$.ajax({
	    		url: "http://localhost:8888/cgi-bin/econ_test/multi?q1="+quantity+"&q2="+oppQuantity+"&intercept="+$('#dIntr').val()+"&slope="+$('#dSlope').val()+"&fixed="+$('#fCost').val()+"&const="+$('#cCost').val(), 
	    		success: function(data) {
				
					var json = JSON.parse(data);

					// Enable/update summary display content
					if (year != numRounds) {
					  	document.getElementById("summarySection").style.display = "";
					  	document.getElementById("summaryYear").innerHTML = "Summary for Year "+year;
					  	year+=1;
					  	$('.yearSpan').text(year-1);
					  	document.getElementById("year").innerHTML = "<b>Year: </b>"+year;
					  	gameOver = false;
					 }

				  	// save equilibrium to database for display in instructor results
				  	$.ajax({
				  		url: "utils/game_util.php", 
				  		method: 'POST',
			  			data: { equilibrium: json['equilibrium'], id: $('#sessionId').val() }
			  		});

					// update values based on retrieved data
					cumulativeRevenue += json['revenue1'];
					cumulativeProfit += json['profit1'];
					oppCumulativeRevenue += json['revenue2'];
					oppCumulativeProfit += json['profit2'];
					oppCumulativeHistory.push(oppCumulativeRevenue);
					oppCumulativeProfHistory.push(oppCumulativeProfit);
					cumulativeHistory.push(cumulativeRevenue);
					cumulativeProfHistory.push(cumulativeProfit);
					oppProfitHistory.push(json['profit2']);
					oppRevenueHistory.push(json['revenue2']);
					profitHistory.push(json['profit1']);
					revenueHistory.push(json['revenue1']);
					ttlCostHist.push(json['totalCost']);
					avgTtlCostHist.push(json['averageTotalCost']);
					quantityHistory.push(quantity);
					oppQuantityHistory.push(oppQuantity);
					priceHistory.push(json['demand']);
					marginalCostHist.push(json['unitCost']);

					// correctly format output with commas and negatives where neccissary
					var marketPriceString, revenue1String, revenue2String, profit1String, profit1String, cumulativeString;
			        if (json['demand'] < 0 ) marketPriceString = '-$'+(json['demand']*(-1)).toLocaleString();
			        else marketPriceString = '$'+json['demand'].toLocaleString();
			        if (json['revenue1'] < 0 ) revenue1String = '-$'+(json['revenue1']*(-1)).toLocaleString();
			        else revenue1String = '$'+json['revenue1'].toLocaleString();
			        if (json['revenue2'] < 0 ) revenue2String = '-$'+(json['revenue2']*(-1)).toLocaleString();
			        else revenue2String = '$'+json['revenue2'].toLocaleString();
			        if (json['profit1'] < 0 ) profit1String = '-$'+(json['profit1']*(-1)).toLocaleString();
			        else profit1String = '$'+json['profit1'].toLocaleString();
			        if (json['profit2'] < 0 ) profit2String = '-$'+(json['profit2']*(-1)).toLocaleString();
			        else profit2String = '$'+json['profit2'].toLocaleString();
			        if (cumulativeProfit < 0 ) cumulativeString = '-$'+(cumulativeProfit*(-1)).toLocaleString();
			        else cumulativeString = '$'+cumulativeProfit.toLocaleString();

					// Set text in summary section to represent retrieved data
					document.getElementById("marketPrice").innerHTML = marketPriceString;
					document.getElementById("prodQuantity").innerHTML = quantity + " Units";
					document.getElementById("revenue").innerHTML = revenue1String;
					document.getElementById("unitCost").innerHTML = json['unitCost'];
					document.getElementById("ttlCost").innerHTML = json['totalCost'];
					document.getElementById("profit").innerHTML = profit1String;
					document.getElementById("cumulative").innerHTML = cumulativeString;

					// add row for opponent's value after the row for current player's value
					var table = document.getElementsByClassName('paleBlueRows')[0];
					if (year == 2) {
						var row = table.insertRow(2);
						var cell1 = row.insertCell(0);
						var cell2 = row.insertCell(1);
						cell2.id="opponentProductionCell";
						cell1.innerHTML="Opponent Output";
					}
					$('#opponentProductionCell').text(oppQuantity+" Units");

					// set income screen stuff
					$('#liRevenue').html('<b>'+revenue1String+'</b>');
					$('#liRevenueOpp').html('<em>'+revenue2String+'</em>');
					$('#liNet').html('<b>'+profit1String+'</b>');
					$('#liNetOpp').html('<em>'+profit2String+'</em>');
					$('#liReturn').html('<b>'+json['percentReturn1'].toPrecision(4)+'%</b>');
					$('#liReturnOpp').html('<em>'+json['percentReturn2'].toPrecision(4)+'%</em>');
					$('#liPrice').text(marketPriceString);

					// set cost screen stuff
					$('#liSales').text(quantity+" Units");
					$('#liPrice2').text(marketPriceString);
					$('#liMarginal').text('$'+json['unitCost']+"/Unit");
					$('#liProduction').text('$'+json['totalCost']);

					// redraw graph
					init('dashboard_section');
					init('income_section');
					init('cost_section');

					// enable button
					if (!gameOver) $('#price_submit_btn').prop('disabled', false);

					console.log(groupId);

					// call func to submit data in query
					$.ajax({
				  		url: "utils/session.php", 
				  		method: 'POST',
			  			data: { action: 'update_gameSessionData', groupId: groupId, username: $('#usrname').val(), opponent: $('#opponent').val(), quantity: quantity, 
			  				revenue: json['revenue1'], profit: json['profit1'], percentReturn: json['percentReturn1'].toPrecision(4), price: json['demand'],
			  				unitCost: json['unitCost'], totalCost: json['totalCost'], complete: gameOver, gameId: <?= $gameInfo['id'] ?> }
			  		});


			  		// send global message for instructor results
			  		if ($('#usrname').val() == submitData[0]) {
			  			if (!broadcast_web_socket) { 
							broadcast_web_socket = tsugiNotifySocket(); 
							broadcast_web_socket.onopen = function(evt) {
								broadcast_web_socket.send($('#sessionId').val());
							}
			  			}
						broadcast_web_socket.send($('#sessionId').val());
					}
				}
			});
		}

    	// Scrolling animations
    	//---------------------------
    	var animated = [false,false,false];
    	var animatedB = [false,false,false];
    	$(window).scroll(function() { 
    	 	if(window.pageYOffset>55){
		    	if ($('#dynamicHeader').text() == 'Income Statement' && !animated[0]) {
		    		$('#animate0').addClass('animated flipInX').one('webkitAnimationEnd mozAnimationEnd', function() {
		    			$(this).removeClass('animated flipInX');
		    		});
		    		animated[0]=true;
		    	}
		    	else if ($('#dynamicHeader').text() == 'Cost Data' && !animatedB[0]) {
		    		$('#animate0b').addClass('animated flipInX').one('webkitAnimationEnd mozAnimationEnd', function() {
		    			$(this).removeClass('animated flipInX');
		    		});
		    		animatedB[0]=true;
		    	}
		    }
		    if(window.pageYOffset>205){
		    	if ($('#dynamicHeader').text() == 'Income Statement' && !animated[1]) {
		    		$('#animate1').addClass('animated slideInLeft').one('webkitAnimationEnd mozAnimationEnd', function() {
		    			$(this).removeClass('animated slideInLeft');
		    		});
		    		$('#animate2').addClass('animated slideInRight').one('webkitAnimationEnd mozAnimationEnd', function() {
		    			$(this).removeClass('animated slideInRight');
		    		});
		    		animated[1]=true;
		    	}
		    	else if ($('#dynamicHeader').text() == 'Cost Data' && !animatedB[1]) {
		    		$('#animate1b').addClass('animated slideInLeft').one('webkitAnimationEnd mozAnimationEnd', function() {
		    			$(this).removeClass('animated slideInLeft');
		    		});
		    		$('#animate2b').addClass('animated slideInRight').one('webkitAnimationEnd mozAnimationEnd', function() {
		    			$(this).removeClass('animated slideInRight');
		    		});
		    		animatedB[1]=true;
		    	}
		    }
		    if(window.pageYOffset>670){
		    	if ($('#dynamicHeader').text() == 'Income Statement' && !animated[2]) {
		    		$('#animate3').addClass('animated slideInLeft').one('webkitAnimationEnd mozAnimationEnd', function() {
		    			$(this).removeClass('animated slideInLeft');
		    		});
		    		$('#animate4').addClass('animated slideInRight').one('webkitAnimationEnd mozAnimationEnd', function() {
		    			$(this).removeClass('animated slideInRight');
		    		});
		    		animated[2]=true;
		    	}
		    	else if ($('#dynamicHeader').text() == 'Cost Data' && !animatedB[2]) {
		    		$('#animate3b').addClass('animated slideInLeft').one('webkitAnimationEnd mozAnimationEnd', function() {
		    			$(this).removeClass('animated slideInRight');
		    		});
		    		$('#animate4b').addClass('animated slideInRight').one('webkitAnimationEnd mozAnimationEnd', function() {
		    			$(this).removeClass('animated slideInRight');
		    		});
		    		animatedB[2]=true;
		    	}
		    }
		});
		// --------------------------

		// timer set up
	  var minute = $('#timer').attr('data-legnth');
	  var seconds = 0;
	  var totalSeconds = $('#timer').attr('data-legnth')*60;
	  var storeTotal = totalSeconds;
	  
	  var intervalId = null;
	  
	  function startTimer() {
	    --totalSeconds;

	    if (totalSeconds == 0) { // if time runs out notify user. submit quanitity. restart timer
	    	if ($('#quantity').val()=='')
	    		$('#quantity').val(quantity);

	    	alertify.set('notifier','delay', 3);
			alertify.set('notifier','position', 'top-right');
			alertify.error('<i class="fas fa-exclamation-circle"></i><br><strong>Year: '+year+ ' - Time\'s Up!</strong><br>'+$("#quantity").val()+' was submitted.');
			
	    	clearInterval(intervalId);
	    	$('#price_submit_btn').prop('disabled', true);
	    	
	    	submitResponse();
	    }

	    minute = Math.floor((totalSeconds)/60);
	    seconds = totalSeconds - (minute*60);
	    (seconds < 10) ? (seconds = "0" + seconds) : seconds = seconds;

	    // show progress bar visualizing time left
	    var percent = ((totalSeconds/storeTotal)*100).toPrecision(3);
	    if (percent <= 25) $('#progressContainer').attr('class', 'alert progress');
	    else if (percent <= 50) $('#progressContainer').attr('class', 'warning progress');
	    $('#progressBar').css("width", percent+"%");
	    document.getElementById("timer").innerHTML = minute+":"+seconds;
	  }

	  // Submit button Pressed
	  document.getElementById('price_submit_btn').addEventListener('click', function() {
  		// check validity
  		if ($('#quantity').val() >= 1 && $('#quantity').val() <= <?=$gameInfo['max_quantity']?>) {
  			firstSubmit = true;
	  		$('#price_submit_btn').prop('disabled', true); // disable submit button so it isn't pressed twice for same year
	  		if (year == numRounds) { // check if the game is over
	  			submitResponse();
	  			$('#endModal').foundation('open');
	  			clearInterval(intervalId);

	  			gameIsComplete = true;
	  		}
	  		else
	  			submitResponse();
	  	} else { // If user hasn't entered quantity or entered invalid quantity, button will shake and show message
	  		alertify.set('notifier','delay', 3);
			alertify.set('notifier','position', 'top-right');
			alertify.error('<p style="text-align: center; margin: 0;"><i class="fas fa-exclamation-triangle" style=""></i><br>Please  enter valid quantity!<br>(1-<?=$gameInfo["max_quantity"]?> units)</p>');
	  		$('#price_submit_btn').addClass('animated shake').one('webkitAnimationEnd mozAnimationEnd', function() {
    			$(this).removeClass('animated shake');
    		});
	  	}
	  });


	function submitResponse() {
		quantity = $("#quantity").val();
	  	$('#progressContainer').attr('class', 'success progress');

	  	// starts timer
	  	if (intervalId) {
	  		clearInterval(intervalId);
	  		totalSeconds = $('#timer').attr('data-legnth')*60;
	  		document.getElementById("timer").innerHTML = $('#timer').attr('data-legnth')+":00";
	  	}

	  	// the following sends the student input to server for saving, server will fire event to call
	  	// python scripts to get results

	  	// single player
	    if ($('#mode').val() == 'single') {

	    	// call function to get results from the year based on submission and update UI
	    	getSingleResults(quantity);
		}
		// Multiplayer mode
		else {
	  		$.ajax({
		  		url: "utils/websocket_util.php", 
		  		method: 'POST',
	  			data: { action: 'submit_multi', username: $('#usrname').val(), groupId: groupId, quantity: quantity },
	  			success: function(response) {
	  				// if response is returned, both players have submited. (This player submitted second)
	  				if (response) {  
	  					let json = JSON.parse(response); 
	  					
	  					
						room_web_socket.send($('#opponent').val());
						

	  					getMultiResults(json);
	  				}
	  				// if no response returned, user joined new game and is waiting for opponent
	  				else { 
	  					$('#waitOppSub').css('display', 'inherit');

	  					// wait for message that other player has submitted
					  	room_web_socket.onmessage = function(evt) { 
					  		if (evt.data==$('#usrname').val().substring(0, $('#usrname').val().indexOf('@'))) {
					  			// grab opponent's submission data from database
					  			$.ajax({
							  		url: "utils/websocket_util.php", 
							  		method: 'POST',
						  			data: { action: 'get_opponent_data', groupId: groupId },
						  			success: function(response) {
						  				var json = JSON.parse(response);
						  				var tmpArr = [$('#usrname').val(), quantity];
						  				json = tmpArr.concat(json);
						  				getMultiResults(json);
						  			}
						  		});
					  		}
					  		else if (evt.data = "exit") {
								const urlPrefix = window.location.href.substr(0, window.location.href.indexOf('src'));
								window.location = urlPrefix+'src/student.php?session=err2';	
							}
					    };
					}

	  			}
	  		});
		}
	}

	// hide the waiting overlay, allowing gameplay to start
	function dismissWaitScreen() {
		$('#multWaitScreen').css('display','none');
		$('.off-canvas-content').css('filter','none');
		$('.footer').css('filter','none');
	}

	function leaveGame() {
		const urlPrefix = window.location.href.substr(0, window.location.href.indexOf('src'));

		// if game is not over, remove student from session table so they can restart game
		if (!gameIsComplete)
			$.ajax({
		  		url: "utils/session.php", 
		  		method: 'POST',
	  			data: { action: 'remove_student', groupId: groupId }
	  		});

  		// if its a multi game, notify opponent
  		if (room_web_socket)
  			room_web_socket.send("exit");

  		// exit to student.php
  		if (!gameIsComplete)
  			window.location = urlPrefix+'src/student.php?session=left';
  		else
  			window.location = urlPrefix+'src/student.php?session=comp';
	}

	window.onbeforeunload = function () {
	    leaveGame();
	};


		// CHARTS SET UP \\
    	// ==============
    	var graphLabels = ["Yr. 1", "Yr. 2", "Yr. 3", "Yr. 4", "Yr. 5", "Yr. 6", "Yr. 7", "Yr. 8", "Yr. 9", "Yr. 10", "Yr. 11", "Yr. 12", "Yr. 13",  "Yr. 14",  "Yr. 15",  "Yr. 16", "Yr. 17",  "Yr. 18",  "Yr. 19",  "Yr. 20",  "Yr. 21",  "Yr. 22",  "Yr. 23",  "Yr. 24",  "Yr. 25"];
    	graphLabels = graphLabels.slice(0, $('#numRounds').val());

    	function init(to_section) {
    		if (to_section == "income_section") {
    			var usrIncomeData = [{
			            label: 'Your Revenue ($)',
			            data: revenueHistory,
			            backgroundColor: 'rgba(0, 0, 230, 0.2)',
			            borderColor: 'rgba(0, 0, 230, 1)',
			            borderWidth: 1
			        },
			        {
			        	label: 'Your Profit ($)',
			            data: profitHistory,
			            backgroundColor: 'rgba(0, 153, 255, 0.2)',
			            borderColor: 'rgba(0, 153, 255, 1)',
			            borderWidth: 1	
			        }];
			    var oppIncomeData = [{
			            label: $('#opponent').val()+'\'s Revenue ($)',
			            data: oppRevenueHistory,
			            backgroundColor: 'rgba(179, 0, 0, 0.2)',
			            borderColor: 'rgba(179, 0, 0, 1)',
			            borderWidth: 1
			        },
			        {
			        	label: $('#opponent').val()+'\'s Profit ($)',
			            data: oppProfitHistory,
			            backgroundColor: 'rgba(255, 128, 128, 0.2)',
			            borderColor: 'rgba(255, 128, 128, 1)',
			            borderWidth: 1	
			        }];
			    var usrCumulativeData = [{
			            label: 'Your Revenue ($)',
			            data: cumulativeHistory,
			            backgroundColor: [
			                'rgba(0, 0, 230, 0.2)'
			            ],
			            borderColor: [
			                'rgba(0, 0, 230,1)'
			            ],
			            borderWidth: 1
			        },
			        {
			            label: 'Your Profit ($)',
			            data: cumulativeProfHistory,
			            backgroundColor: [
			                'rgba(0, 153, 255, 0.2)'
			            ],
			            borderColor: [
			                'rgba(0, 153, 255,1)'
			            ],
			            borderWidth: 1
			        }]; 
			    var oppCumulativeData = [{
			            label: $('#opponent').val()+'\'s Revenue ($)',
			            data: oppCumulativeHistory,
			            backgroundColor: [
			                'rgba(179, 0, 0, 0.2)'
			            ],
			            borderColor: [
			                'rgba(179, 0, 0, 1)'
			            ],
			            borderWidth: 1
			        },
			        {
			            label: $('#opponent').val()+'\'s Profit ($)',
			            data: oppCumulativeProfHistory,
			            backgroundColor: [
			                'rgba(255, 128, 128, 0.2)'
			            ],
			            borderColor: [
			                'rgba(255, 128, 128, 1)'
			            ],
			            borderWidth: 1
			        }];
			    var usrQuantityData = {
					label: 'Your Sales (Units)',
					data: quantityHistory,
					backgroundColor: 'rgba(255, 165, 0, 0.2)',
					borderColor: 'rgba(255,165,0,1)',
					borderWidth: 1
				};
				var oppQuantityData = {
					label: $('#opponent').val()+"'s Sales (Units)",
					data: oppQuantityHistory,
					backgroundColor: 'rgb(255, 88, 51, 0.2)',
					borderColor: 'rgb(255, 88, 51, 1)',
					borderWidth: 1
				};
			    if ($('#mode').val() == 'single') {
					var displayIncomeData = {
						labels: graphLabels,
						datasets: usrIncomeData
					};
					var displayCumulativeData = {
						labels: graphLabels,
						datasets: usrCumulativeData
					};
					var displayQuantityData = {
						labels: graphLabels,
						datasets: [usrQuantityData]
					};
				}
				else {
					var displayIncomeData = {
						labels: graphLabels,
						datasets: usrIncomeData.concat(oppIncomeData)
					};
					var displayCumulativeData = {
						labels: graphLabels,
						datasets: usrCumulativeData.concat(oppCumulativeData)
					};
					var displayQuantityData = {
						labels: graphLabels,
						datasets: [usrQuantityData, oppQuantityData]
					};
				}
				var chartOptions = {
					maintainAspectRatio: false,
					scales: {
			            yAxes: [{
			                ticks: {
			                    beginAtZero:true
			                }
			            }]
			        }
				}

				new Chart(document.getElementById("incomeChart"), {
				    type: 'line',
				    data: displayIncomeData,
				    options: chartOptions
				});
				new Chart(document.getElementById("cummulativeChart"), {
				    type: 'line',
				    data: displayCumulativeData,
				    options: chartOptions
				});
				new Chart(document.getElementById("priceChart"), {
				    type: 'line',
				    data: {
				        labels: graphLabels,
				        datasets: [{
				            label: 'Price ($)',
				            data: priceHistory,
				            backgroundColor: [
				                'rgba(0, 99, 0, 0.2)'
				            ],
				            borderColor: [
				                'rgba(0,99,0,1)'
				            ],
				            borderWidth: 1
				        }]
				    },
				    options: chartOptions
				});
				new Chart(document.getElementById("quantityChart2"), {
				    type: 'line',
				    data: displayQuantityData,
				    options: chartOptions
				});
			}
			else if (to_section == "cost_section") {
				var usrData = {
					label: 'Yours Sales (Units)',
					data: quantityHistory,
					backgroundColor: 'rgba(255, 165, 0, 0.2)',
					borderColor: 'rgba(255,165,0,1)',
					borderWidth: 1
				}
				var oppData = {
					label: $('#opponent').val()+"'s Sales (Units)",
					data: oppQuantityHistory,
					backgroundColor: 'rgb(255, 88, 51, 0.2)',
					borderColor: 'rgb(255, 88, 51, 1)',
					borderWidth: 1
				}
				if ($('#mode').val() == 'single')
					var displayData = {
						labels: graphLabels,
						datasets: [usrData]
					}
				else
					var displayData = {
						labels: graphLabels,
						datasets: [usrData, oppData]
					}
				var chartOptions = {
					maintainAspectRatio: false,
					scales: {
			            yAxes: [{
			                ticks: {
			                    beginAtZero:true
			                }
			            }]
			        }
				}

				new Chart(document.getElementById("costChart"), {
				    type: 'line',
				    data: {
				        labels: graphLabels,
				        datasets: [{
				            label: 'Total Cost ($)',
				            data: ttlCostHist,
				            backgroundColor: [
				                'rgba(255, 99, 132, 0.2)'
				            ],
				            borderColor: [
				                'rgba(255,99,132,1)'
				            ],
				            borderWidth: 1
				        }]
				    },
				    options: chartOptions
				});
				new Chart(document.getElementById("marginalChart"), {
				    type: 'line',
				    data: {
				        labels: graphLabels,
				        datasets: [{
				            label: 'Marginal Cost ($/Unit)',
				            data: marginalCostHist,
				            backgroundColor: [
				                'rgba(188, 0, 255, 0.2)'
				            ],
				            borderColor: [
				                'rgba(188,0,255,1)'
				            ],
				            borderWidth: 1
				        }]
				    },
				    options: chartOptions
				});
				new Chart(document.getElementById("avgTotalChart"), {
				    type: 'line',
				    data: {
				        labels: graphLabels,
				        datasets: [
				        {
				        	label: 'Average Total Cost ($)',
				            data: avgTtlCostHist,
				            backgroundColor: [
				                'rgba(0, 99, 132, 0.2)',
				            ],
				            borderColor: [
				                'rgba(0,99,132,1)'
				            ],
				            borderWidth: 1	
				        }]
				    },
				    options: chartOptions
				});
				new Chart(document.getElementById("quantityChart3"), {
				    type: 'line',
				    data: displayData,
				    options: chartOptions
				});
			}
			else if (to_section == "dashboard_section") {
				var usrData = {
					label: 'Your Sales (Units)',
					data: quantityHistory,
					backgroundColor: 'rgba(255, 165, 0, 0.2)',
					borderColor: 'rgba(255,165,0,1)',
					borderWidth: 1
				};
				var oppData = {
					label: $('#opponent').val()+"'s Sales (Units)",
					data: oppQuantityHistory,
					backgroundColor: 'rgb(255, 88, 51, 0.2)',
					borderColor: 'rgb(255, 88, 51, 1)',
					borderWidth: 1
				};
				if ($('#mode').val() == 'single')
					var displayData = {
						labels: graphLabels,
						datasets: [usrData]
					}
				else
					var displayData = {
						labels: graphLabels,
						datasets: [usrData, oppData]
					}

				var chartOptions = {
					maintainAspectRatio: false,
					scales: {
			            yAxes: [{
			                ticks: {
			                    beginAtZero:true
			                }
			            }]
			        }
				}

				quantityChart = new Chart(document.getElementById("quantityChart"), {
				    type: 'line',
				    data: displayData,
				    options: chartOptions
				});
			}
		}

		// =========================

    </script>
  </body>

  <style type="text/css">
  	html, body {
  		height: 100%
  	}
  	body {
  		display: flex;
  		flex-direction: column;
  		overflow-x: hidden;
  	}

  	#mainContent { 
  		flex: 1 0 auto;
  		min-height: 300px;
  	}
  	.footer {
		background-color: #0a4c6d;
		height: 75px;
		flex-shrink: 0;
		margin-top: 100px;
		bottom: 0
	}
	#summarySection p {
		margin-bottom: 0.5rem;
	}
	.display_sections {
		width: 1150px;
		margin: auto;
	}
	.section_content {
		margin-top: 220px;
	}
	.section_cell {
		background-color: #fcfcfc;
		padding: 25px;
		filter: drop-shadow(3px 3px 5px black);
		border-radius: 5px;
	}
	.cell_graph {
		width: 550px;
		height: 460px; 
	}
	.graph {
		width: 520px;
		height: 360px;
	}
	ul a {
		color: white;
		color: #e6e6e6;
		font-weight: 480;
		font-size: 0.9em;
		line-height: 45px;
	}
	ul a:hover, a:focus {
		color: inherit;
	}
	ul li {
		padding-top: 5px;
		padding-bottom: 5px;
	}
	.darken li:hover {
		background: black;
	}
	#progressContainer {
		float: left;
	    width: 80%;
	    margin: 20px 10px 0 0;
	}
	.two-columns {
		columns: 2;
		list-style-type: none;
		width: 500px;
		margin: 0 auto 25px auto;
	}
	.two-columns li {
		line-height: 40px
	}
	.wow {
		visibility: hidden;
	}
	#bouncingArrow {
		-webkit-animation-iteration-count: infinite;
		-moz-animation-iteration-count: infinite;
		-webkit-animation-duration: 3s;
		-moz-animation-duration: 3s;
		color: #a8a8a4;
	}
	#topToolbar {
		width: 100%;
		height: 150px;
		background-color: #fcfcfc;
		filter: drop-shadow(0px 3px 5px black); 
		position: fixed;
		z-index: 2;
		margin-top: 0px;
	}
	.title-bar {
		background-color: #0a4c6d;
		position: sticky;
		position: -webkit-sticky;
		z-index: 2;
		top: 0;
	}
	.data_grid > .grid-x{
		margin-bottom: 15px;
	}
	.reveal {
		outline: none;
		box-shadow: none;
	}
	table.paleBlueRows {
	  font-family: "Times New Roman", Times, serif;
	  border: 1px solid #FFFFFF;
	  width: 100%;
	  height: 200px;
	  text-align: center;
	  border-collapse: collapse;
	}
	table.paleBlueRows td {
	  border: 1px solid #FFFFFF;
	  padding: 3px 2px;
	  width: 250px;
	}
	table.paleBlueRows tbody td {
	  font-size: 16px;
	}
	table.paleBlueRows tr:nth-child(even) {
	  background: #D0E4F5;
	}
  </style>
</html>

<?php
$OUTPUT->footerEnd();
