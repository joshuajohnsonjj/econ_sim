<?php
/* perfect_game.php

- Gameplay for perfect competition

User enters directly into game and can start playing. There are 1 to 2 descisions to be made, depending on the difficulty level set by instructor.
Principles:output=Q
Advanced: output=Q and production level

Once submission is made, an overview of the past year's data is shown in a table. The user can use slide over menue to get more specific info. User can also see industry data which is averages on results from other students over the years

Last Update: now using Tsugi WebSockets
*/
include 'utils/sql_settup.php';
require_once "../../tsugi/config.php";

use \Tsugi\Core\LTIX;
use Tsugi\Core\WebSocket;

$LAUNCH = LTIX::session_start();

// Render view
$OUTPUT->header();

if ($USER->instructor)
	header("Location: ..");

$gameInfo = getGameInfo((int)$_GET['session']);
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

    		var headers = {"dashboard_section": "Dashboard", "income_section": "Income Statement", "cost_section": "Cost Data", "industry_section": "Industry Data"};
    		var elements = document.getElementsByClassName("display_sections");

    		if (firstSubmit) {
	    		document.body.scrollTop = document.documentElement.scrollTop = 0; // force page to top

	    		for (var i = elements.length - 1; i >= 0; i--) {
	    			elements[i].style.display = "none";
	    		}

	    		document.getElementById(to_section).style.display = "";
	    		$('#dynamicHeader').text(headers[to_section]);

	    		if (to_section!='industry_section')
	    			init(to_section);
	    	}
	    	else {
	    		alertify.set('notifier','delay', 5);
				alertify.set('notifier','position', 'top-right');
				alertify.error('<p style="text-align: center; margin: 0;"><i class="fas fa-exclamation-triangle" style=""></i><br>Please  enter valid quantity!<br>(1-500 units)</p>');
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
		  <?php if ($gameInfo['market_struct']=='monopolistic'||$gameInfo['market_struct']=='perfect') { ?>
		  		<li><a onclick="change_content('industry_section')">Industry Data</a></li>
		  <?php } ?>
		  <li><a onclick="change_content('instructions')">Instructions</a></li>
		</ul>

		<ul class="vertical menu" style="position: absolute; bottom: 0; width: 100%; margin-bottom: 25px">
			<li>
				<button onclick="leaveGame()" class="alert button" style="width: 125px; margin-left: auto; margin-right: auto;"><strong>Exit Game</strong></i></button>
			</li>
		</ul>
	</div>

	<div class="off-canvas-content" data-off-canvas-content>
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
					<div class="cell large-7">
						<p style="float: left; padding-right: 10px"><b>Output: </b></p>
						<input type="number" style="width: 125px; float: left;"id="quantity" min="1" max="500" placeholder="1 - 500 Units">
						<?php if ($gameInfo['difficulty'] == 'advanced') { ?>
							<p style="float: left; margin: auto 15px auto 15px;"><b>Production Level: </b></p>
							<select id="productionLevelInput" style="width: 70px; float: left;">
								<option value="1">1</option>
								<option value="2">2</option>
								<option value="3">3</option>
								<option value="4">4</option>
								<option value="5">5</option>
						    </select>
						<?php } ?>
						<button class="button" type="button" id="price_submit_btn" style="margin-left: 20px; font-weight: 500;float: left">Submit</i></button>
					</div>
				</div>
			</div>
		</div>
		<!--  End toolbar -->

		<input type="hidden" id="sessionId" value="<?=$_GET['session']?>">
		<input type="hidden" id="usrname" value="<?=$USER->email?>">
		<input type="hidden" id="mode" value="<?=$gameInfo['mode']?>">

		<div id="mainContent"> 

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
									<td><span id="prodQuantity"></span> Units</td>
								</tr>
								<tr>
									<td>Revenue</td>
									<td><span id="revenue"></span></td>
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
				<div class="section_content preStartPrompt">
					<div class="section_cell cell_graph" style="float: left;">
						<h2 style="text-align: left;"><strong>Instructions</strong></h2>
						<p style="text-align: left;">In this simulation you will be the owner of a non-durable commodity, selling your product in a perfectly competitive market environment. Your goal is to determine productions levels in the face of fluctuating prices, in order to profit maximize.</P>
						<p>For each of <?= $gameInfo['num_rounds'] ?> periods you will observe prices, choose a quantity to sell and determine productivity changes for next period.  To make your decisions easier, consult the market research reports detailing the industry averages.</p>
						<p>At the end of the simulation, cumulative profits will be measured and graded against the average firm.</p>
						<p>The initial market report tracks the price history in your market for the previous 25 years. You will now enter the market such that your first year corresponds with the market's twenty sixth year.</p>
					</div>
					<div class="section_cell cell_graph" style="float: right;">
						<h2 style="text-align: left;"><strong>Initial Market Report</strong></h2>
						<div style="width: 100%; height: 350px">
							<canvas id="twentyFiveYrHist"></canvas>
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
							<hr style="margin-bottom: 30px">
							<table class="paleBlueRows">
								<tbody>
									<tr>
										<td>Revenue</td>
										<td><span id="liRevenue"></span></td>
									</tr>
									<tr>
										<td>Net Profit</td>
										<td><span id="liNet"></span></td>
									</tr>
									<tr>
										<td>Return on Sales</td>
										<td><span id="liReturn"></span></td>
									</tr>
									<tr>
										<td>Price</td>
										<td><span id="liPrice"></span></td>
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
							<canvas style="height: 360px" id="incomeChart" ></canvas>
						</div>
					</div>
					<div id="animate2" class="section_cell cell_graph" style="float: right;">
						<h4 style="text-align: center; font-weight: 450">Cummulative Earnings</h4>
						<hr style="margin-bottom: 0.65rem">
						<div class="graph">
							<canvas style="height: 360px" id="cummulativeChart"></canvas>
						</div>
					</div>
					<div id="animate3" class="section_cell cell_graph" style="float: left; margin-top: 50px">
						<h4 style="text-align: center; font-weight: 450">Price</h4>
						<hr style="margin-bottom: 0.65rem">
						<div class="graph">
							<canvas style="height: 360px" id="priceChart"></canvas>
						</div>
					</div>
					<div id="animate4" class="section_cell cell_graph" style="float: right; margin-top: 50px">
						<h4 style="text-align: center; font-weight: 450">Shipments</h4>
						<hr style="margin-bottom: 0.65rem">
						<div class="graph">
							<canvas style="height: 360px" id="quantityChart2"></canvas>
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
							<hr style="margin-bottom: 30px">
							<table class="paleBlueRows">
								<tbody>
									<tr>
										<td>Shipments</td>
										<td><span id="liSales"></span></td>
									</tr>
									<tr>
										<td>Production</td>
										<td><span id="liProdLevel"></span></td>
									</tr>
									<tr>
										<td>Price</td>
										<td><span id="liPrice2"></span></td>
									</tr>
									<tr>
										<td>Marginal Cost</td>
										<td><span id="liMarginal"></span></td>
									</tr>
									<tr>
										<td>Production Cost</td>
										<td><span id="liProduction"></span></td>
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
							<canvas style="height: 360px" id="costChart"></canvas>
						</div>
					</div>
					<div id="animate2b" class="section_cell cell_graph" style="float: right;">
						<h4 style="text-align: center; font-weight: 450">Marginal Cost</h4>
						<hr style="margin-bottom: 0.65rem">
						<div class="graph">
							<canvas style="height: 360px" id="marginalChart"></canvas>
						</div>
					</div>
					<div id="animate3b" class="section_cell cell_graph" style="float: left; margin-top: 50px">
						<h4 style="text-align: center; font-weight: 450">Average Total</h4>
						<hr style="margin-bottom: 0.65rem">
						<div class="graph">
							<canvas style="height: 360px" id="avgTotalChart"></canvas>
						</div>
					</div>
					<div id="animate4b" class="section_cell cell_graph" style="float: right; margin-top: 50px">
						<h4 style="text-align: center; font-weight: 450">Shipments</h4>
						<hr style="margin-bottom: 0.65rem">
						<div class="graph">
							<canvas style="height: 360px" id="quantityChart3"></canvas>
						</div>
					</div>
					<div id="animate5b" class="section_cell cell_graph" style="float: left; margin-top: 50px">
						<h4 style="text-align: center; font-weight: 450">Production Level</h4>
						<hr style="margin-bottom: 0.65rem">
						<div class="graph">
							<canvas style="height: 360px" id="prodLevelChart"></canvas>
						</div>
					</div>
				</div>
			</div>
			<!-- --------- -->

			<!-- Industry Data -->
			<div class="display_sections" id="industry_section" style="display: none;">
				<div class="section_content">
					<div class="industrySectionCell">
						<div>
							<h4>Industry Averages</h4>
							<hr>
							<div id="valueDisplaySelector" class="grid-x">
								<div class="cell small-3"><button onclick="changeDisplayValue(this)">Output</button></div>
								<div class="cell small-3"><button onclick="changeDisplayValue(this)">Price</button></div>
								<div class="cell small-3"><button onclick="changeDisplayValue(this)">Revenue</button></div>
								<div class="cell small-3"><button onclick="changeDisplayValue(this)">Profit</button></div>
							</div>
						</div>
						<div style="margin-top:15px; height:510px;background-color: #fcfcfc">
							<canvas id="industryChart" style="padding: 10px; height:510px;"></canvas>
						</div>
					</div>
				</div>
			</div>
			<!-- ------------- -->
		</div>
	</div>

	<!-- MODALS -->
	<!-- begining of game instructions -->
	<div class="reveal" id="beginModal" data-reveal data-animation-in="slide-in-up" style="border-radius: 5px; opacity: 0.9;">
		<div class="instructionsStuff">
			<h2 style="text-align: left;"><strong>Instructions</strong></h2>
			<p style="text-align: left;">In this simulation you will be the owner of a non-durable commodity, selling your product in a perfectly competitive market environment. Your goal is to determine productions levels in the face of fluctuating prices, in order to profit maximize.</P>
			<p>For each of <?= $gameInfo['num_rounds'] ?> periods you will observe prices, choose a quantity to sell and determine productivity changes for next period.  To make your decisions easier, consult the market research reports detailing the industry averages.</p>
			<p>At the end of the simulation, cumulative profits will be measured and graded against the average firm.</p>
		</div>
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
	<input id="numRounds" type="hidden" value="<?=$gameInfo['num_rounds']?>">

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
    <script src="../node_modules/chartjs-plugin-annotation/chartjs-plugin-annotation.js"></script>
	<script src="//cdn.jsdelivr.net/npm/alertifyjs@1.11.1/build/alertify.min.js"></script>
    <script type="text/javascript">
    	broadcast_web_socket= null;
    	// from submission
    	var quantity; var prodLevel;
    	const numRounds = parseInt($('#numRounds').val(), 10);
    	const valTypes = {"Output":"player_quantity", "Price":"price", "Revenue":"player_revenue", "Profit":"player_profit"};
    	var industryChart = null;

    	// Economic Data
    	// -------------
    	var year = 1; var shockYear = 0;
    	var cumulativeRevenue = 0;
    	var cumulativeProfit = 0;
    	var cumulativeHistory = [];
    	var cumulativeProfHistory = [];
    	var quantityHistory = [];
    	var revenueHistory = [];
    	var profitHistory = [];
    	var ttlCostHist = [];
    	var avgTtlCostHist = [];
    	var marginalCostHist = [];
    	var productionLevelHist = [];
    	var equilibriumHist =[];

    	priceHistory = [];
    	// -------------------

		var groupId = (Math.random()+1).toString(36).slice(2, 18);


    	// display the generated 25 year history on chart in "Initial Market Report Section"
    	var graphLabels = ["Yr. 1", "Yr. 2", "Yr. 3", "Yr. 4", "Yr. 5", "Yr. 6", "Yr. 7", "Yr. 8", "Yr. 9", "Yr. 10", "Yr. 11", "Yr. 12", "Yr. 13",  "Yr. 14",  "Yr. 15",  "Yr. 16", "Yr. 17",  "Yr. 18",  "Yr. 19",  "Yr. 20",  "Yr. 21",  "Yr. 22",  "Yr. 23",  "Yr. 24",  "Yr. 25"];
    	let twentyFiveYrHist = graphLabels;
    	var histLabels = ["Yr. -4", "Yr. -3", "Yr. -2", "Yr. -1", "Yr. 0"];
    	
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

		// For perfect competition game mode, an initial period of 25 years of prices are generated using autoregressive process w/ shock
    	// this is generated on admin side when session is toggled on so that all players have same price history
    	// retrieve price history from sql table w/ ajax
    	$.ajax({
	  		url: "utils/game_util.php", 
	  		method: 'POST',
  			data: { action: 'getHistory', id: <?=$gameInfo['id']?> },
  			success: function(response) {
  				priceHistory=response.split(',');

  				var genPriceData = [{
					label: 'Price ($)',
					data: priceHistory,
					backgroundColor: 'rgba(24, 188, 24, 0.2)',
					borderColor: 'rgba(24, 188, 24,1)',
					borderWidth: 1
				}];
				var displayGenPriceData = {
					labels: twentyFiveYrHist,
					datasets: genPriceData
				};

				console.log(twentyFiveYrHist);


  				new Chart(document.getElementById("twentyFiveYrHist"), {
				    type: 'line',
				    data: displayGenPriceData,
				    options: chartOptions
				});
  			}	
  		});

		

		// singleplayer submission occured
		function getResults(quantity, prodLevel) {
			var gameOver = true;
			if (year != numRounds) intervalId = setInterval(startTimer, 1000);

			// CALCULATIONS && IMPORTANT VALUES
			// --------------------------------
			const maxQ=500;
			var totalCost; var price;

			// total cost calculations
			switch (prodLevel) { // production level determines cost function
				case 1:
					totalCost = 0.025*Math.pow(quantity,3)-5*Math.pow(quantity,2)+300*quantity+500;
					break;
				case 2:
					totalCost = 0.025*Math.pow(quantity,3)-7.5*Math.pow(quantity,2)+602.5*quantity+1000;
					break;
				case 3:
					totalCost = 0.025*Math.pow(quantity,3)-10*Math.pow(quantity,2)+1030*quantity+1500;
					break;
				case 4:
					totalCost = 0.025*Math.pow(quantity,3)-12.5*Math.pow(quantity,2)+1602.5*quantity+2000;
					break;
				case 5:
					totalCost = 0.025*Math.pow(quantity,3)-15*Math.pow(quantity,2)+23000*quantity+2500;
					break;
			} 

			totalCost = parseInt(totalCost.toFixed(2));

			// price calculations
			var prevPrice = priceHistory[23+year];
			if ('<?=$gameInfo['macro_econ']?>' == 'stable') { // regular economy/price growth
				if (year-shockYear >= 5 && getRandomArbitrary()==3) {
					price = 0.5+0.999*prevPrice+random(1,2)+random(0,10);
					shockYear=i;
				}
				else
					price = 0.5+0.999*prevPrice+random(1,2);
			} else { // high inflation economy
				if (year-shockYear >= 5 && getRandomArbitrary()==3) {
					price = 0.125*year+0.999*prevPrice+random(1,2)+random(0,10);
					shockYear=i;
				}
				else
					price = 0.125*year+0.999*prevPrice+random(1,2);
			}
			price = parseInt(price.toFixed(2));

			var profit = parseInt((price*quantity-totalCost).toFixed(2));
			var revenue = parseInt((price*quantity).toFixed(2));
			var percentReturn = (profit/revenue)*100;
			var averageTotalCost = parseInt((totalCost/quantity).toFixed(2));
			var marginalCost = (year>=2&&quantity-quantityHistory[year-2]!=0)?parseInt(((totalCost-ttlCostHist[year-2])/(quantity-quantityHistory[year-2])).toFixed(2)):0;
			// -----------------------

			$('.preStartPrompt').css('display','none');
			// Enable/update summary display content
			if (year != numRounds) {
			  	document.getElementById("summarySection").style.display = "";
			  	document.getElementById("summaryYear").innerHTML = "Summary for Year "+year;
			  	year+=1;
			  	$('.yearSpan').text(year-1);
			  	document.getElementById("year").innerHTML = "<b>Year:</b> "+year;
			  	gameOver = false;
			}

			// update values based on retrieved data
			cumulativeRevenue += revenue;
			cumulativeProfit += profit;
			cumulativeHistory.push(cumulativeRevenue);
			cumulativeProfHistory.push(cumulativeProfit);
			profitHistory.push(profit);
			revenueHistory.push(revenue);
			ttlCostHist.push(totalCost);
			avgTtlCostHist.push(averageTotalCost);
			quantityHistory.push(quantity);
			priceHistory.push(price);
			marginalCostHist.push(marginalCost);
			productionLevelHist.push(prodLevel);

	        // correctly format output with commas and negatives where neccissary
	        var marketPriceString, revenueString, profitString, cumulativeString;
	        marketPriceString = '$'+price.toLocaleString();
	        if (revenue < 0 ) revenueString = '-$'+(revenue*(-1)).toLocaleString();
	        else revenueString = '$'+revenue.toLocaleString();
	        if (profit < 0 ) profitString = '-$'+(profit*(-1)).toLocaleString();
	        else profitString = '$'+profit.toLocaleString();
	        if (cumulativeProfit < 0 ) cumulativeString = '-$'+(cumulativeProfit*(-1)).toLocaleString();
	        else cumulativeString = '$'+cumulativeProfit.toLocaleString();

			// Set text in summary section to represent retrieved data
			document.getElementById("marketPrice").innerHTML = marketPriceString;
			document.getElementById("prodQuantity").innerHTML = quantity;
			document.getElementById("revenue").innerHTML = revenueString;
			document.getElementById("unitCost").innerHTML = marginalCost;
			document.getElementById("ttlCost").innerHTML = totalCost.toLocaleString();
			document.getElementById("profit").innerHTML = profitString;
			document.getElementById("cumulative").innerHTML = cumulativeString;

			// set income screen stuff
			$('#liRevenue').text(revenueString);
			$('#liNet').text(profitString);
			$('#liPrice').text(marketPriceString);
			$('#liReturn').text(percentReturn.toPrecision(4)+'%');

			// set cost screen stuff
			$('#liSales').text(quantity+" Units");
			'<?=$gameInfo["difficulty"]?>'=='advanced'?($('#liProdLevel').text("Level "+prodLevel)):($('#liProdLevel').parent().css('display','none'));
			$('#liPrice2').text('$'+price.toLocaleString());
			$('#liMarginal').text('$'+marginalCost+"/Unit");
			$('#liProduction').text('$'+totalCost.toLocaleString());

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
	  			data: { action: 'update_gameSessionData', groupId: groupId, username: $('#usrname').val(), opponent: null, quantity: quantity, revenue: revenue,
	  				profit: profit, percentReturn: percentReturn.toPrecision(4), price: price, unitCost: marginalCost, totalCost: totalCost, complete: gameOver?1:0, gameId: <?= $gameInfo['id'] ?>  }
	  		});

	  		// send message to tell instructor results to update
			if (!broadcast_web_socket) {
		  		broadcast_web_socket = tsugiNotifySocket(); 
		  		broadcast_web_socket.onopen = function(evt) { 
					broadcast_web_socket.send($('#sessionId').val());
				}
			}
			else
				broadcast_web_socket.send($('#sessionId').val());

				
			// update industry data with averages of other students in same game
			getIndustryData();
		}

		const urlPrefix = window.location.href.substr(0, window.location.href.indexOf('src'));

		function leaveGame() { // fires when one player hits exit game button in side menu
			// remove student from gamesession table
			$.ajax({
		  		url: "utils/session.php", 
		  		method: 'POST',
	  			data: { action: 'remove_student', id: $('#sessionId').val(), player: $('#usrname').val() }
	  		});
			window.location = urlPrefix+'src/student.php?session=left';
		}

		window.onunload = function () {
		    leaveGame();
		};

		// ==================


    	// Scrolling animations
    	//---------------------------
    	var animated = [false,false,false];
    	var animatedB = [false,false,false,false];
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
		    if(window.pageYOffset>1125){
		    	if ($('#dynamicHeader').text() == 'Cost Data' && !animated[3]) {
		    		$('#animate5b').addClass('animated slideInLeft').one('webkitAnimationEnd mozAnimationEnd', function() {
		    			$(this).removeClass('animated slideInLeft');
		    		});
		    		animated[3]=true;
		    	}
		    }
		});
		// --------------------------
    	

		function getIndustryData() {
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
	  				} 

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

		        	if (industryChart) { // if industryChart exists reset data and update it
		        		industryChart.data.datasets[0].data = [];
		            	industryChart.data.datasets[0].data = averages;
		            	industryChart.update();
		        	}
		        	else // if industryChart doesnt exist yet, create it
		        		createIndustrySectionChart(averages, 'Quantity');

	  			}
	  		});
		}
		$("#valueDisplaySelector").find("button").first().addClass('selectedValue');
		function changeDisplayValue(element) {
			// change colors
			$("#valueDisplaySelector").find("button").removeClass('selectedValue');
			$(element).addClass('selectedValue');
			selectedValType = valTypes[$(element).text()];

			getIndustryData();
		}

		function createIndustrySectionChart(data, valType) { 

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
			var chartOptions = {
				maintainAspectRatio: false,
				scales: {
		            yAxes: [{
		                ticks: {
		                    beginAtZero:true
		                }
		            }]
		        },
		        animation: false
			};
			industryChart = new Chart($('#industryChart'), {
			    type: 'line',
			    data: fullDataObj,
			    options: chartOptions
			});
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
	    	if ($('#quantity').val()=='')
	    		$('#quantity').val(quantity);

	    	alertify.set('notifier','delay', 5);
			alertify.set('notifier','position', 'top-right');
			alertify.error('<i class="fas fa-exclamation-circle"></i><br><strong>Year: '+year+ ' - Time\'s Up!</strong><br>'+$("#quantity").val()+' was submitted.');
			
	    	clearInterval(intervalId);
	    	$('#price_submit_btn').prop('disabled', true);
	    	
	    	submitResponse();
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
	  document.getElementById('price_submit_btn').addEventListener('click', function() {
  		// check validity
  		if ($('#quantity').val() >= 1 && $('#quantity').val() <= 500) {
  			firstSubmit = true;
	  		$('#price_submit_btn').prop('disabled', true);
	  		if (year == numRounds) {
	  			submitResponse();
	  			$('#endModal').foundation('open');
	  			clearInterval(intervalId);
	  		}
	  		else
	  			submitResponse();
	  	} else { // If user hasn't entered quantity or entered invalid quantity, button will shake
	  		alertify.set('notifier','delay', 5);
			alertify.set('notifier','position', 'top-right');
			alertify.error('<p style="text-align: center; margin: 0;"><i class="fas fa-exclamation-triangle" style=""></i><br>Please  enter valid quantity!<br>(1-500 units)</p>');
	  		$('#price_submit_btn').addClass('animated shake').one('webkitAnimationEnd mozAnimationEnd', function() {
	    			$(this).removeClass('animated shake');
	    		});
	  	}
	  });


	function submitResponse() {
		quantity = $("#quantity").val();
		prodLevel = $("#productionLevelInput").val()?parseInt($("#productionLevelInput").val()):1;
	  	$('#progressContainer').attr('class', 'success progress');

	  	// starts timer
	  	if (intervalId) {
	  		clearInterval(intervalId);
	  		totalSeconds = $('#timer').attr('data-legnth')*60;
	  		document.getElementById("timer").innerHTML = $('#timer').attr('data-legnth')+":00";
	  	}

	  	getResults(quantity, prodLevel);
	}

	// Helper function for economic calculations. Accepts a mean and std deviation and returns rand num
	function random(m, s) {
	  return m + 2.0 * s * (Math.random() + Math.random() + Math.random() - 1.5);
	}
	// Returns random number between 1 and 5, determening if shock will occur
	function getRandomArbitrary() {
	    return (Math.random() * (6 - 1) + 1).toPrecision(1);
	}

	// =================
	// //CHARTS SET UP\\
	// =================
	let historicalLabels = histLabels.concat(graphLabels.slice(0, $('#numRounds').val()));
	graphLabels = graphLabels.slice(0, $('#numRounds').val());
	var selectedValType = "player_quantity";

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
		    var usrQuantityData = {
				label: 'Your Sales (Units)',
				data: quantityHistory,
				backgroundColor: 'rgba(255, 165, 0, 0.2)',
				borderColor: 'rgba(255,165,0,1)',
				borderWidth: 1
			};

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
			    type: "line",
			    data: {
			        labels: historicalLabels,
			        datasets: [{
			            label: 'Price ($)',
			            data: priceHistory.slice(20),
			            backgroundColor: [
			                'rgba(0, 99, 0, 0.2)'
			            ],
			            borderColor: [
			                'rgba(0,99,0,1)'
			            ],
			            borderWidth: 1
			        }]
			    },
			    // Show annotation to seperate previously generated price history
			    options: {
			      maintainAspectRatio: false,
			      responsive: true,
			      annotation: {
			        events: ["click"],
			        annotations: [
			          {
			            type: "line",
			            mode: "vertical",
			            scaleID: "y-axis-0",
			            value: 0,
			            endValue: 0,
			            borderColor: "rgba(0,0,0,0.0)",
			            borderWidth: 0,
			            label: {
			              backgroundColor: "red",
			              content: "You Entered Market in Year 1",
			              enabled: true,
			              xAdjust: -80,
			            }
			          }
			        ]
			      }
			    }
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
			var displayData = {
				labels: graphLabels,
				datasets: [usrData]
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
			new Chart(document.getElementById("prodLevelChart"), {
			    type: 'line',
			    data: {
			        labels: graphLabels,
			        datasets: [{
			            label: 'Level',
			            data: productionLevelHist,
			            backgroundColor: [
			                'rgba(68, 81, 102, 0.2)'
			            ],
			            borderColor: [
			                'rgba(68, 81, 102,1)'
			            ],
			            borderWidth: 1
			        }]
			    },
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
			var displayData = {
				labels: graphLabels,
				datasets: [usrData]
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
	.initMarkReportStuff {
		display: none;
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
	.industrySectionCell {
		filter: drop-shadow(3px 3px 5px black);
		border-radius: 5px;
		width: 80%; 
		height: 600px;
		background-color: #fcfcfc;
		margin: 0 auto 0 auto;
	}
	.industrySectionCell > div > h4 {
		text-align: center;
		font-weight: 450;
		padding-top: 10px;
	}
	.industrySectionCell > hr {
		margin-bottom: 0.8rem;
		width: 80%;
	}
	#valueDisplaySelector {
		width: 50%;
		margin: auto;
	}
	#valueDisplaySelector > canvas {
		height: 520px !important;
	}
	#valueDisplaySelector > .cell > button {
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
	canvas {
	  -moz-user-select: none;
	  -webkit-user-select: none;
	  -ms-user-select: none;
	}
  </style>
</html>

<?php
$OUTPUT->footerEnd();
