<?php
/*
game_main.php

Contains code for the game UI.
*/
	include 'utils/sql_settup.php';
	require_once "../../tsugi/config.php";

	use \Tsugi\Core\LTIX;

	$LAUNCH = LTIX::session_start();

	if ($USER->instructor)
		header("Location: ..");

	$gameInfo = getGameInfo($mysqli, (int)$_GET['session']);
	$startGame = true;

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
    		var headers = {"dashboard_section": "Dashboard", "income_section": "Income Statement", "cost_section": "Cost Data","industry_section": "Industry Data", "settings_section": "Settings"};
    		var elements = document.getElementsByClassName("display_sections");

    		if (firstSubmit) {
	    		document.body.scrollTop = document.documentElement.scrollTop = 0; // force page to top

	    		for (var i = elements.length - 1; i >= 0; i--) {
	    			elements[i].style.display = "none";
	    		}

	    		document.getElementById(to_section).style.display = "";
	    		$('#dynamicHeader').text(headers[to_section]);

	    		init(to_section);
	    	}
	    	else
	    		$('#emptyInputNotice').foundation('show');
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
		  <li><a onclick="change_content('industry_section')">Industry Data</a></li>
		  <li><a onclick="change_content('settings_section')">Settings</a></li>
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
			<button class="button" style="float: left; background-color: #cc4b37; margin: 10px; border-radius: 2px;" onclick="leaveGame();window.history.back();">
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
			    <div class="thumbnail" style="margin: 0; border: none;">
			      <img src="../assets/img/no_bg_monogram.png" height="100px" width="100px">
			    </div>
			</div>
		    <span class="title-bar-title">
		    	<h3 style="margin: 30px 0 0 30px; font-weight: 500"><?= $gameInfo['name'] ?></h3>
		    	<h6 id="opponentHeading" style="margin-left: 30px">
		    		<?= $gameInfo['mode'] == 'multi' ? 'Opponent: ' : ''?>
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
						<p style="float: left; padding-right: 20px"><b>Enter quantity: </b></p>
						<form>
						<span id="emptyInputNotice" tabindex="1" data-tooltip data-click-open="false" data-disable-hover="true" data-allow-html="true" title='<p style="text-align: center; margin: 0;"><i class="fas fa-exclamation-triangle" style=""></i><br>Please valid enter quantity!<br>(0-500 units)</p>'>
							<input type="number" id="quantity" min="1" max="500" placeholder="1 - 500 Units" oninput="$('#emptyInputNotice').foundation('hide');">
						</span>
						<button class="button" type="button" id="price_submit_btn" style="margin-left: 20px; font-weight: 500">Submit</i></button>
						<span id="waitOppSub" style="display: none;" data-tooltip tabindex="1" title="Waiting for opponent to submit quantity">
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
						<hr>
						<br>
						<p>&bull; The market price last year was <span id="marketPrice"></span></p>
						<p id="opponentQuantity" style="display: none">&bull; Your opponent's production was <span></span></p>
						<p>&bull; Your production was <span id="prodQuantity"></span> units, thus your revenue was <span id="revenue"></span></p>
						<p>&bull; Marginal cost was $<span id="unitCost"></span> per unit, and total production cost was $<span id="ttlCost"></span></p>
						<p>&bull; Your profit for the year was <span id="profit"></span></p>
						<br>
						<p>&bull; Cumulative earnings are <span id="cumulative"></span></p>
					</div>
					<div class="section_cell cell_graph" style="float: right;">
						<h4 style="text-align: center; font-weight: 450">Shipments</h4>
						<hr style="margin-bottom: 0.65rem">
						<canvas id="quantityChart"></canvas>
					</div>
				</div>
			</div>
			<!-- --------- -->

			<!-- Income Statement -->
			<div class="display_sections" id="income_section" style="display: none;">
				<div class="section_content">
					<div style="min-height: 550px">
						<div class="section_cell" style="width: 700px; margin: 0 auto 50px auto;">
							<h4 style="text-align: center; font-weight: 450">Year <span id="yearSpan">1</span> Overview</h4>
							<hr>
							<br>
							<p style="float:right; margin: 0 75px 0 0; display:<?=$gameInfo['mode'] == 'multi'?'':'none'?>;">
								<strong>You</strong> / <em> Opponent</em>
							</p>
							<ul class="two-columns">
								<li>Revenue</li>
								<li>Net Profit</li>
								<li>Return on Sales</li>
								<li>Price</li>
								<li id="liRevenue" style="text-align: right;">$</li>
								<li id="liNet" style="text-align: right;">$</li>
								<li id="liReturn" style="text-align: right;">%</li>
								<li id="liPrice" style="text-align: right;">$</li>
							</ul>
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
						<canvas id="incomeChart" ></canvas>
					</div>
					<div id="animate2" class="section_cell cell_graph" style="float: right;">
						<h4 style="text-align: center; font-weight: 450">Cummulative Earnings</h4>
						<hr style="margin-bottom: 0.65rem">
						<canvas id="cummulativeChart"></canvas>
					</div>
					<div id="animate3" class="section_cell cell_graph" style="float: left; margin-top: 50px">
						<h4 style="text-align: center; font-weight: 450">Price</h4>
						<hr style="margin-bottom: 0.65rem">
						<canvas id="priceChart"></canvas>
					</div>
					<div id="animate4" class="section_cell cell_graph" style="float: right; margin-top: 50px">
						<h4 style="text-align: center; font-weight: 450">Shipments</h4>
						<hr style="margin-bottom: 0.65rem">
						<canvas id="quantityChart2"></canvas>
					</div>
				</div>
			</div>
			<!-- ---------------- -->

			<!-- Cost Data -->
			<div class="display_sections" id="cost_section" style="display: none;">
				<div class="section_content">
					<div style="min-height: 550px">
						<div class="section_cell" style="width: 700px; margin: 0 auto 50px auto;">
							<h4 style="text-align: center; font-weight: 450">Year <span id="yearSpan2">1</span> Overview</h4>
							<hr>
							<br>
							<ul class="two-columns">
								<li>Shipments</li>
								<li>Price</li>
								<li>Marginal Cost</li>
								<li>Production Cost</li>
								<li id="liSales" style="text-align: right;">Units</li>
								<li id="liPrice2" style="text-align: right;">$</li>
								<li id="liMarginal" style="text-align: right;">$/Unit</li>
								<li id="liProduction" style="text-align: right;">$</li>
							</ul>
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
						<canvas id="costChart"></canvas>
					</div>
					<div id="animate2b" class="section_cell cell_graph" style="float: right;">
						<h4 style="text-align: center; font-weight: 450">Marginal Cost</h4>
						<hr style="margin-bottom: 0.65rem">
						<canvas id="marginalChart"></canvas>
					</div>
					<div id="animate3b" class="section_cell cell_graph" style="float: left; margin-top: 50px">
						<h4 style="text-align: center; font-weight: 450">Average Total</h4>
						<hr style="margin-bottom: 0.65rem">
						<canvas id="avgTotalChart"></canvas>
					</div>
					<div id="animate4b" class="section_cell cell_graph" style="float: right; margin-top: 50px">
						<h4 style="text-align: center; font-weight: 450">Shipments</h4>
						<hr style="margin-bottom: 0.65rem">
						<canvas id="quantityChart3"></canvas>
					</div>
				</div>
			</div>
			<!-- --------- -->

			<!-- Industry Data -->
			<div class="display_sections" id="industry_section" style="display: none;">

			</div>
			<!-- ------------- -->

			<!-- Settings  -->
			<div class="display_sections" id="settings_section" style="display: none;">

			</div>
			<!-- --------- -->
		</div>
	</div>

	<!-- END GAME MODAL -->
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

    <script src="../js/vendor/jquery.js"></script>
    <script src="../js/vendor/what-input.js"></script>
    <script src="../js/vendor/foundation.js"></script>
    <script src="../js/app.js"></script>
    <script src="../js/node_modules/chart.js/dist/Chart.js"></script>
	<script src="//cdn.jsdelivr.net/npm/alertifyjs@1.11.1/build/alertify.min.js"></script>
	<script src="http://localhost:8080/socket.io/socket.io.js"></script>
    <script type="text/javascript"> 
    	// STUFF FOR SOCKET.IO
    	// ===================
    	// connect to server 
	    var socket = io.connect('http://localhost:8080');
	    var groupId = window.location.hash.substring(1), gameObject;

	    if (groupId == '')
			socket.emit('joinGame', { // add user to gameObject as a player
				id: $('#sessionId').val(),
				username: $('#usrname').val(),
				mode: $('#mode').val()
			});

		// when a student first enters game
		socket.on('studentJoinedGame', function(gameObj) {
			console.log('Generated groupId: '+gameObj['groupId']);
			groupId = gameObj['groupId'];

			// if multi game is full, players have been matched successfully
			if ($('#mode').val() == 'multi' && gameObj['full']) { 
				// hide the wait screen
				dismissWaitScreen();
	
				// grab opponent from gameObject
				var opp;
				if ($('#usrname').val() == gameObj['playerOne']) opp = gameObj['playerTwo'].substr(0, gameObj['playerTwo'].indexOf('@'));
				else opp = gameObj['playerOne'].substr(0, gameObj['playerOne'].indexOf('@'));
				$('#opponentHeading').text('Opponent: '+opp);
				$('#opponent').val(opp);
			}

			window.location.href = window.location.href+"#"+groupId;
		});

		// singleplayer submission occured
		socket.on('singleplayerSubmission', function(quantity) {
			intervalId = setInterval(startTimer, 1000);

			jQuery.get("../../../cgi-bin/econ_test/single?quantity="+quantity+"&intercept="+$('#dIntr').val()+"&slope="+$('#dSlope').val()+"&fixed="+$('#fCost').val()+"&const="+$('#cCost').val(), function(data) {
				var json = JSON.parse(data);
				console.log(json);

				// Enable/update summary display content
			  	document.getElementById("summarySection").style.display = "";
			  	document.getElementById("summaryYear").innerHTML = "Summary for Year "+year;
			  	year+=1;
			  	$('#yearSpan').text(year-1);
			  	$('#yearSpan2').text(year-1);
			  	document.getElementById("year").innerHTML = "<b>Year:</b> "+year;

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
		        if (cumulativeRevenue < 0 ) cumulativeString = '-$'+(cumulativeRevenue*(-1)).toLocaleString();
		        else cumulativeString = '$'+cumulativeRevenue.toLocaleString();

				// Set text in summary section to represent retrieved data
				document.getElementById("marketPrice").innerHTML = marketPriceString;
				document.getElementById("prodQuantity").innerHTML = quantity;
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
				if (year != numRounds+1) $('#price_submit_btn').prop('disabled', false);
			});
		});

		// multiplayer submission occured
    	socket.on('multiplayerSubmission', function(gameObj) {
    		// verify that submission came from this specific groupId. If not, ignore it
			if (gameObj['id'] != groupId) {
				console.log('submission from other game. Ignoring...');
				return;
			}

			gameObject = gameObj;

			// if the legnths of the data arrays for both players are unequal, wait for other player to submit
			if ((gameObject['p1Data'].length != gameObject['p2Data'].length)) {
				if ((gameObject['username']==$('#usrname').val())) { // display waiting spinner for submitted player
					$('#waitOppSub').css('display', '');
				}
			}
			else { // both players have submitted now
				intervalId = setInterval(startTimer, 1000);

				// hide spinner
				$('#waitOppSub').css('display', 'none');

				// grab opponent's most recent submission value
				if ($('#usrname').val() == gameObject['p1']) {
					var oppQuantity = gameObject['p2Data'][gameObject['p2Data'].length-1];
					var quantity = gameObject['p1Data'][gameObject['p1Data'].length-1];
				}
	    		else {
	    			var oppQuantity = gameObject['p1Data'][gameObject['p1Data'].length-1];
	    			var quantity = gameObject['p2Data'][gameObject['p2Data'].length-1];
	    		}

	    		// call python script to get results
		    	jQuery.get("../../../cgi-bin/econ_test/multi?q1="+quantity+"&q2="+oppQuantity+"&intercept="+$('#dIntr').val()+"&slope="+$('#dSlope').val()+"&fixed="+$('#fCost').val()+"&const="+$('#cCost').val(), function(data) {
					
					var json = JSON.parse(data);
					console.log(json);

					// Enable/update summary display content
				  	document.getElementById("summarySection").style.display = "";
				  	document.getElementById("summaryYear").innerHTML = "Summary for Year "+year;
				  	year+=1;
				  	$('#yearSpan').text(year-1);
				  	$('#yearSpan2').text(year-1);
				  	document.getElementById("year").innerHTML = "<b>Year: </b>"+year;

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
			        if (cumulativeRevenue < 0 ) cumulativeString = '-$'+(cumulativeRevenue*(-1)).toLocaleString();
			        else cumulativeString = '$'+cumulativeRevenue.toLocaleString();

					// Set text in summary section to represent retrieved data
					document.getElementById("marketPrice").innerHTML = marketPriceString;
					$('#opponentQuantity').css('display', '');
					$('#opponentQuantity > span').text(oppQuantity+ " units.");
					document.getElementById("prodQuantity").innerHTML = quantity;
					document.getElementById("revenue").innerHTML = revenue1String;
					document.getElementById("unitCost").innerHTML = json['unitCost'];
					document.getElementById("ttlCost").innerHTML = json['totalCost'];
					document.getElementById("profit").innerHTML = profit1String;
					document.getElementById("cumulative").innerHTML = cumulativeString;

					// set income screen stuff
					$('#liRevenue').html('<b>'+revenue1String+'</b> / <em>'+revenue2String+'</em>');
					$('#liNet').html('<b>'+profit1String+'</b> / <em>'+profit2String+'</em>');
					$('#liPrice').text(marketPriceString);
					$('#liReturn').html('<b>'+json['percentReturn1'].toPrecision(4)+'%</b> / <em>'+json['percentReturn2'].toPrecision(4)+'%</em>');

					// set cost screen stuff
					$('#liSales').text(quantity+" Units");
					$('#liPrice2').text('$'+json['demand']);
					$('#liMarginal').text('$'+json['unitCost']+"/Unit");
					$('#liProduction').text('$'+json['totalCost']);
					// $('#liShipments').text(quantity+' Units');

					// redraw graph
					init('dashboard_section');
					init('income_section');
					init('cost_section');

					// enable button
					if (year != numRounds+1) $('#price_submit_btn').prop('disabled', false);
				});
			}
		});

		// student exits game early, or cancels during player match
		socket.on('gameExited', function(user) { 
			// when one player quits, both players will be booted from game
			// for the user that did not press the exit button, display error message notifying them of what happened, 
			// (their opponent quit)
			urlPrefix = window.location.href.substr(0, window.location.href.indexOf('src'));
			if ($('#usrname').val() != user) 
				window.location = urlPrefix+'src/student.php?session=err2';
			else
				window.location = urlPrefix+'src/student.php';
		});

		function leaveGame() { // fires when one player hits exit game button in side menu
			console.log(groupId);
			socket.emit('leaveGame', $('#usrname').val(), groupId);
		}
		// ==================

    	// TODO -- handle refreshes
    	if (performance.navigation.type == 1) {
    		dismissWaitScreen();

    		// rejoin socket.io room
    		socket.emit('gameRefreshed', groupId);

    		// SET ALL DATA SO FAR... SAVE IN SESSION VARIABLE???
    		
    	}

    	// Scrolling animations
    	//---------------------------
    	var animated = [false,false,false];
    	var animatedB = [false,false,false];
    	$(window).scroll(function() { 
    	 	if(window.pageYOffset>70){
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
		    if(window.pageYOffset>215){
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

	    const numRounds = parseInt($('#numRounds').val(), 10);

    	// Economic Data
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

    	// Func for off canvas menu to change screen content
    	function change_content(to_section) {
    		var headers = {"dashboard_section": "Dashboard", "income_section": "Income Statement", "cost_section": "Cost Data","industry_section": "Industry Data", "settings_section": "Settings"};
    		var elements = document.getElementsByClassName("display_sections");

    		if (firstSubmit) {
	    		document.body.scrollTop = document.documentElement.scrollTop = 0; // force page to top

	    		for (var i = elements.length - 1; i >= 0; i--) {
	    			elements[i].style.display = "none";
	    		}

	    		document.getElementById(to_section).style.display = "";
	    		$('#dynamicHeader').text(headers[to_section]);

	    		init(to_section);
	    	}
	    	else
	    		$('#emptyInputNotice').foundation('show');
    	}

    	// CHARTS SET UP \\
    	// =================
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


		// timer set up
	  var minute = $('#timer').attr('data-legnth');
	  var seconds = 0;
	  var totalSeconds = $('#timer').attr('data-legnth')*60;
	  var storeTotal = totalSeconds;
	  
	  var intervalId = null;
	  
	  function startTimer() {
	    --totalSeconds;

	    if (totalSeconds == 0) { // if time runs out notify user. submit quanitity. restart timer
	    	alertify.set('notifier','delay', 5);
			alertify.set('notifier','position', 'top-right');
			alertify.error('<i class="fas fa-exclamation-circle"></i><br><strong>Year: '+year+ ' - Time\'s Up!</strong><br>'+$("#quantity").val()+' was submitted.');
	    	clearInterval(intervalId);
	    	// $('#price_submit_btn').prop('disabled', true);
	    	// submitResponse();
	    }

	    minute = Math.floor((totalSeconds)/60);
	    seconds = totalSeconds - (minute*60);
	    (seconds < 10) ? (seconds = "0" + seconds) : seconds = seconds;

	    var percent = ((totalSeconds/storeTotal)*100).toPrecision(3);
	    if (percent <= 25) $('#progressContainer').attr('class', 'alert progress');
	    else if (percent <= 50) $('#progressContainer').attr('class', 'warning progress');
	    $('#progressBar').css("width", percent+"%");
	    document.getElementById("timer").innerHTML = minute+":"+seconds;
	  }

	  // Submit button Pressed
	  document.getElementById('price_submit_btn').addEventListener('click', function(){
	  		if ($('#quantity').val() >= 1 && $('#quantity').val() <= 500) {
	  			firstSubmit = true;
		  		$('#price_submit_btn').prop('disabled', true);
		  		$('#preStartPrompt').css('display','none');
		  		if (year == numRounds) {
		  			submitResponse();
		  			$('#endModal').foundation('open');
		  			clearInterval(intervalId);
		  		}
		  		else
		  			submitResponse();
		  	} else { // If user hasn't entered quantity, button will shake
		  		$('#emptyInputNotice').foundation('show');
		  		$('#price_submit_btn').addClass('animated shake').one('webkitAnimationEnd mozAnimationEnd', function() {
		    			$(this).removeClass('animated shake');
		    			$('#emptyInputNotice').foundation('hide');
		    		});
		  	}
	  });


	function submitResponse() {
		var quantity = $("#quantity").val();
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
	    	// socket.io -- Save submission to player's data in gameObject
	    	socket.emit('updateData', {
	    		groupId: groupId,
	    		username: $('#usrname').val(),
	    		mode: 'single',
	    		value: quantity
	    	});
		}
		// Multiplayer mode
		else {
			// socket.io -- Save submission to player's data in gameObject
	    	socket.emit('updateData', {
	    		groupId: groupId,
	    		username: $('#usrname').val(),
	    		mode: 'multi',
	    		value: quantity
	    	});
		}
	}

	function dismissWaitScreen() {
		$('#multWaitScreen').css('display','none');
		$('.off-canvas-content').css('filter','none');
		$('.footer').css('filter','none');
	}

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
	canvas {
		height: 350px !important;
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
	#emptyInputNotice:focus {
		outline-width: 0;
		box-shadow: none;
	}
	#emptyInputNotice {
		width: 150px;
		float: left;
		border: none;
		cursor: inherit;
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
  </style>
</html>
