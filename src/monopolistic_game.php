<?php
/* monopolistic_game.php

- gameplay for monopolistic competion structure

User enters directly into game and can start playing. There are 2 to 5 descisions to be made, depending on the difficulty level set by instructor.
Principles:output=Q and prices
Intermediate: output=Q and prices, marketing, production facility development and product development
Advanced: output=Q and prices, marketing, production facility development, product development, human capital development, distribution development

 Once submission is made, an overview of the past year's data is shown in a table. The user can use slide over menue to get more specific info

Last Update:
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
    		var headers = {"dashboard_section": "Dashboard", "income_section": "Income Statement", "cost_section": "Production Cost Data", "expenditures_section": "Expenditures Data"};
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
	    	else {
	    		alertify.set('notifier','delay', 5);
				alertify.set('notifier','position', 'top-right');
				alertify.warning('<i class="fas fa-exclamation-triangle"></i><br><strong>Enter Valid Quantities for Output and Price!</strong>');
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
		  <li><a onclick="change_content('cost_section')">Production Cost Data</a></li>
		  <?=$gameInfo['difficulty']!='principles'?'<li><a onclick="change_content(\'expenditures_section\')">Expenditures Data</a></li>':''?>
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
							<div class="surplusTooltip">
								<span class="surplusTooltiptext">Surplus:</span>
								<input type="number" id="quantity" min="1" max="500" placeholder="1 - 500 Units" style="width: 120px;">
							</div>	
						<p style="float: left; padding: 0 10px 0 15px"><b>Price: </b></p>
							<input type="number" id="price" min="1" max="500" placeholder="$1 - $XX??" style="width: 120px; float: left;">
						<?php if ($gameInfo['difficulty']!='principles') { ?>
							<button class="secondary button" type="button" data-toggle="dropdown-1" style="float: left; margin-left: 15px; height: 39px;"><i class="fas fa-caret-down fa-2x" style="margin-top: -8px;"></i></button>
							<div class="dropdown-pane large" id="dropdown-1" data-dropdown data-hover="true" data-hover-pane="true">
								<div class="grid-x">
									<div class="cell"><h5><strong>Annual Budgets for Expenditures</strong></h5></div>
								</div>
								<div class="grid-x">
									<div class="cell small-6" style="font-weight: 450">Marketing:</b></div>
									<div class="cell small-6"> 
										<div class="input-group">
											<span class="input-group-label"><i class="fas fa-dollar-sign"></i></i></span><input class="input-group-field" min="0" type="number" id="marketingInput" value="0">
										</div>
									</div>
								</div>
								<div class="grid-x">
									<div class="cell small-6"><b style="font-weight: 450;">Production Facility Development:</b></div>
									<div class="cell small-6"> 
										<div class="input-group">
											<span class="input-group-label"><i class="fas fa-dollar-sign"></i></i></span><input class="input-group-field" min="0" type="number" id="facilityInput" value="0">
										</div>
									</div>
								</div>
								<div class="grid-x">
									<div class="cell small-6"><b style="font-weight: 450;">Product Development:</b></div>
									<div class="cell small-6"> 
										<div class="input-group">
											<span class="input-group-label"><i class="fas fa-dollar-sign"></i></i></span><input class="input-group-field" min="0" type="number" id="productInput" value="0">
										</div>
									</div>
								</div>
								<?php if ($gameInfo['difficulty']=='advanced') { ?>
									<div class="grid-x">
										<div class="cell small-6"><b style="font-weight: 450;">Human Capital Development:</b></div>
										<div class="cell small-6"> 
											<div class="input-group">
												<span class="input-group-label"><i class="fas fa-dollar-sign"></i></i></span><input class="input-group-field" min="0" type="number" id="humanInput" value="0">
											</div>
										</div>
									</div>
									<div class="grid-x">
										<div class="cell small-6"><b style="font-weight: 450;">Distribution Development:</b></div>
										<div class="cell small-6"> 
											<div class="input-group">
												<span class="input-group-label"><i class="fas fa-dollar-sign"></i></i></span><input class="input-group-field" min="0" type="number" id="distributionInput" value="0">
											</div>
										</div>
									</div>
								<?php } ?>
							</div>
						<?php } ?>
						<button class="button" type="button" id="price_submit_btn" style="margin-right: 20px; font-weight: 500; float: right;">Submit</i></button>
					</div>
				</div>
			</div>
		</div>
		<!--  End toolbar -->

		<input type="hidden" id="sessionId" value="<?=$_GET['session']?>">
		<input type="hidden" id="usrname" value="<?=$USER->email?>">
		<input type="hidden" id="mode" value="<?=$gameInfo['mode']?>">

		<div id="mainContent"> 

			<!-- before first submission prompt -->
			<div id="preStartPrompt" style="width: 500px; margin: 280px auto 30px auto;">
				<h3 style="text-align: center; color: #bdbebf">
					<i class="far fa-play-circle fa-5x"></i><br>
					<strong style="font-weight: 500;">Enter Values to Begin!</strong>
				</h3>
			</div>

			<!-- Dashboard -->
			<div class="display_sections" id="dashboard_section"> 
				<div class="section_content" id="summarySection" style="display: none;">
					<div class="section_cell" style="width:80%; margin: auto;">
						<h4 id="summaryYear" style="text-align: center; font-weight: 450">Summary for Year </h4>
						<hr style="margin-bottom: 30px">
						<table class="paleBlueRows">
							<tbody>
								<tr>
									<td>Market Price</td>
									<td><span id="marketPrice"></span></td>
								</tr>
								<tr>
									<td>Output</td>
									<td><span id="prodQuantity"></span></td>
								</tr>
								<tr>
									<td>Quantity of Demand</td>
									<td><span id="qDemand"></span></td>
								</tr>
								<tr>
									<td>Surplus</td>
									<td><span id="numSurplus"></span></td>
								</tr>
								<tr>
									<td>Surplus Storage Fee</td>
									<td><span id="surplueCost"></span></td>
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
									<tr>
										<td>Revenue</td>
										<td id="liRevenue"></td>
									</tr>
									<tr>
										<td>Net Profit</td>
										<td id="liNet"></td>
									</tr>
									<tr>
										<td>Return on Sales</td>
										<td id="liReturn"></td>
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

			<!-- expenditures Data -->
			<div class="display_sections" id="expenditures_section" style="display: none;">
				<div class="section_content">
					<div style="min-height: 550px">
						<div class="section_cell" style="width: 700px; margin: 0 auto 50px auto;">
							<h4 style="text-align: center; font-weight: 450">Year <span class="yearSpan">1</span> Expences</h4>
							<hr style="margin-bottom: 20px">
							<table class="paleBlueRows">
								<tbody>
									<tr>
										<td>Marketing</td>
										<td id="advertisingDisp"></td>
									</tr>
									<tr>
										<td>Production Facility Development</td>
										<td id="facilityDevDisp"></td>
									</tr>
									<tr>
										<td>Product Development</td>
										<td id="productDevDisp"></td>
									</tr>
									<?php if ($gameInfo['difficulty']=='advanced') { ?>
										<tr>
											<td>Human Capital Development</td>
											<td id="humanCapDisp"></td>
										</tr>
										<tr>
											<td>Distribution Development</td>
											<td id="distributionDisp"></td>
										</tr>
									<?php } ?>
									<tr>
										<td style="font-weight: 500">Total Expenditures</td>
										<td id="totalExpDisp" style="font-weight: 500"></td>
									</tr>
								</tbody>
							</table>
						</div>
						<div style="margin-bottom: 50px; margin-top: -20px; text-align: center;">
							<i class="fas fa-angle-down fa-4x animated bounce" id="bouncingArrow"></i>
						</div>
					</div>
					<div id="animate0c" class="section_cell" style="width: 500px; margin: 0 auto 50px auto;">
						<h4 style="text-align: center; font-weight: 450">Key Values</h4>
						<hr style="margin-bottom: 0.65rem">
						<div class="grid-x">
							<div class="cell small-10">
								Advertising Coefficient
							</div>
							<div class="cell small-2" id="B2"></div>
						</div>
						<div class="grid-x">
							<div class="cell small-10">
								Facility Coefficient
							</div>
							<div class="cell small-2" id="B3"></div>
						</div>
						<?php if ($gameInfo['difficulty']=='advanced') { ?>
							<div class="grid-x">
								<div class="cell small-10">
									Product Coefficient
								</div>
								<div class="cell small-2" id="B4"></div>
							</div>
						<?php } ?>
					</div>
					<div id="animate1c" class="section_cell cell_graph" style="float: left;">
						<h4 style="text-align: center; font-weight: 450">Spending Breakdown</h4>
						<hr style="margin-bottom: 0.65rem">
						<canvas id="expendituresPieChart"></canvas>
					</div>
					<div id="animate2c" class="section_cell cell_graph" style="float: right;">
						<h4 style="text-align: center; font-weight: 450">Expenditures History</h4>
						<hr style="margin-bottom: 0.65rem">
						<canvas id="expendituresChart"></canvas>
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

			<p>In this simulation you will be the owner of a Financial Analytics firm, selling your services in a monopolistically competitive environment. Your goal is to set prices and output levels in this environment in order to profit maximize.</p> 
			<p>For each of <?= $gameInfo['num_rounds'] ?> periods you will observe previous prices and choose both a price and a quantity to bring to the market in the next period.  Since you are one of many firms selling in this market, consulting the market research reports detailing the industry averages may help as you make the profit maximizing choices.</p> 
			<p>At the end of the simulation, cumulative profits will be measured and grading against a hypothetical firm acting optimally.</p>

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
	<script src="//cdn.jsdelivr.net/npm/alertifyjs@1.11.1/build/alertify.min.js"></script>
    <script type="text/javascript">
    	broadcast_web_socket = null;

    	// from submission
    	var quantity;

    	const numRounds = parseInt($('#numRounds').val(), 10);

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
    	var priceHistory = [50];
    	var marginalCostHist = [];
    	var advertisingHist = [];
    	var facilityDevHist = [];
    	var productDevHist = [];
    	var humanCapHist = [];
    	var distributionDevHist = [];
    	var pieData = [];
    	var expendituresTotal = 0;

    	var excessQuantity=0, prevExcess=0;
    	// -------------------

		var groupId = (Math.random()+1).toString(36).slice(2, 18);

		//  open instuction modal upon entering game
		$('#beginModal').foundation('open');


		// submission occured
		function getResults(quantity, price, marketingAmount, productionFacilAmount, productDevAmount, humanCapAmount, distributionAmount) {
			var gameOver=true;
			if (year != numRounds) intervalId = setInterval(startTimer, 1000);

			// constant values
			const B0=100, B1=3, B2=4, B3=5, B4=6;
			const a0=500, a1=0.000006, a2=-0.0147, a3=41, a4=1, a5=1;
			const surplusFee = 0.35;

			// variables
			var totalCost, profit, revenue, percentReturn, averageTotalCost, marginalCost;

			// demand function
			var qDemand = Math.floor(B0-B1*price+B2*Math.pow(marketingAmount,0.5)+B3*Math.pow(productDevAmount,0.5)+B4*Math.pow(distributionAmount,0.5));

			// total cost function
			switch ('<?=$gameInfo["difficulty"]?>') {
				case 'principles':
					var totalCost = a0+a1*Math.pow(quantity,3)-a2*Math.pow(quantity,2)+a3*quantity;
					break;
				case 'advanced':
					var totalCost = a0+a1*Math.pow(quantity,3)-a2*Math.pow(quantity,2)+a3*quantity+a4*price+humanCapAmount-a5*Math.pow(humanCapAmount,0.5)+productDevAmount+distributionAmount+marketingAmount;

					pieData=[marketingAmount,productionFacilAmount,productDevAmount,humanCapAmount,distributionAmount];					
					break;
				case 'intermediate':
					var totalCost = a0+a1*Math.pow(quantity,3)-a2*Math.pow(quantity,2)+a3*quantity+a4*price+productDevAmount+marketingAmount;

					pieData=[marketingAmount,productionFacilAmount,productDevAmount];
					break;
			}
			totalCost = parseInt(totalCost);

			// excess production
			// excess inventory can only be stored for 1 year, so subtract prevExcess from current excess
			// if excessQuanitiy becomes 0 or negative, there's no more surplus, so update prevQuantity accordingly for use the next year
			excessQuantity=quantity-qDemand;
			excessQuantity=excessQuantity-prevExcess;

			// if  surplus show tooltip above output input
			if (excessQuantity > 0) {
				prevExcess = excessQuantity;
				updateSurplusText();
				$(".surplusTooltip").first().addClass('makeVisible');
				$(".surplusTooltiptext").first().addClass('makeVisible');
			} else {
				prevExcess = 0;
				$(".surplusTooltip").first().removeClass('makeVisible');
				$(".surplusTooltiptext").first().removeClass('makeVisible');
			}

			// profit & revenue
			if (excessQuantity>0)
				revenue = price*qDemand;
			else 
				revenue = price*quantity;
			profit = revenue-totalCost;
			profit = parseInt(profit.toFixed(2));
			revenue = parseInt(revenue.toFixed(2));
			percentReturn = (profit/revenue)*100;

			// cost values
			averageTotalCost = parseInt((totalCost/quantity).toFixed(2));
			marginalCost = (year>=2&&quantity-quantityHistory[year-2]!=0)?parseInt(((totalCost-ttlCostHist[year-2])/(quantity-quantityHistory[year-2])).toFixed(2)):0;


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
			advertisingHist.push(marketingAmount);
    	    facilityDevHist.push(productionFacilAmount);
    		productDevHist.push(productDevAmount);
    		humanCapHist.push(humanCapAmount);
    		distributionDevHist.push(distributionAmount);

    		expendituresTotal = parseInt(marketingAmount)+parseInt(productionFacilAmount)+parseInt(productDevAmount)+parseInt(humanCapAmount)+parseInt(distributionAmount);

	        // correctly format output with commas and negatives where neccissary
	        var marketPriceString = '$'+price.toLocaleString(), revenueString, profitString, cumulativeString;
	        if (revenue < 0 ) revenueString = '-$'+(revenue*(-1)).toLocaleString();
	        else revenueString = '$'+revenue.toLocaleString();
	        if (profit < 0 ) profitString = '-$'+(profit*(-1)).toLocaleString();
	        else profitString = '$'+profit.toLocaleString();
	        if (cumulativeProfit < 0 ) cumulativeString = '-$'+(cumulativeProfit*(-1)).toLocaleString();
	        else cumulativeString = '$'+cumulativeProfit.toLocaleString();

			// Set text in summary section to represent retrieved data
			document.getElementById("marketPrice").innerHTML = marketPriceString;
			document.getElementById("prodQuantity").innerHTML = quantity + " Units";
			document.getElementById("revenue").innerHTML = revenueString;
			document.getElementById("unitCost").innerHTML = marginalCost;
			document.getElementById("ttlCost").innerHTML = totalCost.toLocaleString();
			document.getElementById("profit").innerHTML = profitString;
			document.getElementById("cumulative").innerHTML = cumulativeString;
			document.getElementById("qDemand").innerHTML = qDemand.toLocaleString() + " Units";
			document.getElementById("numSurplus").innerHTML = excessQuantity>=0?excessQuantity+" Units":"0 Units";
			document.getElementById("surplueCost").innerHTML = excessQuantity>=0?"$"+(excessQuantity*surplusFee).toFixed(2):"$0.00";

			// set income screen stuff
			$('#liRevenue').text(revenueString);
			$('#liNet').text(profitString);
			$('#liPrice').text(marketPriceString);
			$('#liReturn').text(percentReturn.toPrecision(4)+'%');

			// set cost screen stuff
			$('#liSales').text(quantity+" Units");
			$('#liPrice2').text(marketPriceString);
			$('#liMarginal').text('$'+marginalCost+"/Unit");
			$('#liProduction').text('$'+totalCost.toLocaleString());

			// set expenditures screen stuff
			$('#advertisingDisp').text('$'+marketingAmount);
			$('#facilityDevDisp').text('$'+productionFacilAmount);
			$('#productDevDisp').text('$'+productDevAmount);
			$('#humanCapDisp').text('$'+humanCapAmount);
			$('#distributionDisp').text('$'+distributionAmount);
			$('#totalExpDisp').text('$'+expendituresTotal);
			$('#B2').text(B2);
			$('#B3').text(B3);
			$('#B4').text(B4);

			// redraw graphs
			init('income_section');
			init('cost_section');
			init('expenditures_section');

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

		// Update surplus tooltip text
		$('#quantity').change(function() { console.log("inside on change: "+excessQuantity);
			updateSurplusText();
		});

		function updateSurplusText() {
			$(".surplusTooltiptext").first().html('<p>Surplus Inventory: '+excessQuantity+' Units</p><p><b>Total Output: '+(parseInt(excessQuantity,10)+parseInt($('#quantity').val(),10))+' Units</b></p>');
		}

    	// Scrolling animations
    	//---------------------------
    	var animated = [false,false,false];
    	var animatedB = [false,false,false];
    	var animatedC = [false,false,false];
    	$(window).scroll(function() { 
    	 	if(window.pageYOffset>55){
		    	if ($('#dynamicHeader').text() == 'Income Statement' && !animated[0]) {
		    		$('#animate0').addClass('animated flipInX').one('webkitAnimationEnd mozAnimationEnd', function() {
		    			$(this).removeClass('animated flipInX');
		    		});
		    		animated[0]=true;
		    	}
		    	else if ($('#dynamicHeader').text() == 'Production Cost Data' && !animatedB[0]) {
		    		$('#animate0b').addClass('animated flipInX').one('webkitAnimationEnd mozAnimationEnd', function() {
		    			$(this).removeClass('animated flipInX');
		    		});
		    		animatedB[0]=true;
		    	}
		    	else if ($('#dynamicHeader').text() == 'Expenditures Data' && !animatedC[0]) {
		    		$('#animate0c').addClass('animated flipInX').one('webkitAnimationEnd mozAnimationEnd', function() {
		    			$(this).removeClass('animated flipInX');
		    		});
		    		animatedC[0]=true;
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
		    	else if ($('#dynamicHeader').text() == 'Production Cost Data' && !animatedB[1]) {
		    		$('#animate1b').addClass('animated slideInLeft').one('webkitAnimationEnd mozAnimationEnd', function() {
		    			$(this).removeClass('animated slideInLeft');
		    		});
		    		$('#animate2b').addClass('animated slideInRight').one('webkitAnimationEnd mozAnimationEnd', function() {
		    			$(this).removeClass('animated slideInRight');
		    		});
		    		animatedB[1]=true;
		    	}
		    	else if ($('#dynamicHeader').text() == 'Expenditures Data' && !animatedC[1]) {
		    		$('#animate1c').addClass('animated slideInLeft').one('webkitAnimationEnd mozAnimationEnd', function() {
		    			$(this).removeClass('animated slideInLeft');
		    		});
		    		$('#animate2c').addClass('animated slideInRight').one('webkitAnimationEnd mozAnimationEnd', function() {
		    			$(this).removeClass('animated slideInRight');
		    		});
		    		animatedC[1]=true;
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
		    	else if ($('#dynamicHeader').text() == 'Production Cost Data' && !animatedB[2]) {
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
			alertify.warning('<i class="fas fa-exclamation-triangle"></i><br><strong>Enter Valid Quantities for Output and Price!</strong>');
	  		$('#price_submit_btn').addClass('animated shake').one('webkitAnimationEnd mozAnimationEnd', function() {
    			$(this).removeClass('animated shake');
    		});
	  	}
	  });


	function submitResponse() {
		quantity = $("#quantity").val();
		if (excessQuantity>0)
			quantity=parseInt(quantity,10)+parseInt(excessQuantity,10);

	  	$('#progressContainer').attr('class', 'success progress');

	  	// starts timer
	  	if (intervalId) {
	  		clearInterval(intervalId);
	  		totalSeconds = $('#timer').attr('data-legnth')*60;
	  		document.getElementById("timer").innerHTML = $('#timer').attr('data-legnth')+":00";
	  	}

	  	getResults(quantity, $("#price").val(), $("#marketingInput").val(), $("#facilityInput").val(), $("#productInput").val(), $("#humanInput").val(), $("#distributionInput").val());
	}

	// =================
    	// //CHARTS SET UP\\
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
				            data: priceHistory.slice(25),
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
			}
			else if (to_section == "expenditures_section") {
				var adData = {
					label: 'Marketing ($)',
					data: advertisingHist,
					backgroundColor: 'rgba(255, 165, 0, 0.2)',
					borderColor: 'rgba(255,165,0,1)',
					borderWidth: 1
				};
				var facilData = {
					label: 'Facility Dev. ($)',
					data: facilityDevHist,
					backgroundColor: 'rgba(255, 28, 28, 0.2)',
					borderColor: 'rgba(255, 28, 28,1)',
					borderWidth: 1
				};
				var prodData = {
					label: 'Product Dev. ($)',
					data: productDevHist,
					backgroundColor: 'rgba(46, 201, 4, 0.2)',
					borderColor: 'rgba(46, 201, 4,1)',
					borderWidth: 1
				};
				var humanData = {
					label: 'Human Cap. ($)',
					data: humanCapHist,
					backgroundColor: 'rgba(17, 21, 255, 0.2)',
					borderColor: 'rgba(17, 21, 255,1)',
					borderWidth: 1
				};
				var distrData = {
					label: 'Distribution Dev. ($)',
					data: distributionDevHist,
					backgroundColor: 'rgba(86, 35, 255, 0.2)',
					borderColor: 'rgba(86, 35, 255,1)',
					borderWidth: 1
				};
				
				if ('<?=$gameInfo["difficulty"]?>'=='advanced')
					var set = [adData,facilData,prodData,humanData,distrData];
				else 
					var set = [adData,facilData,prodData]
				
				var expData = {
					labels: graphLabels,
					datasets: set
				};

				var pieDataset = {
					datasets: [{
						data: pieData,
						backgroundColor: [
						'rgba(255,165,0,1)',
						'rgba(255, 28, 28,1)',
						'rgba(46, 201, 4,1)',
						'rgba(17, 21, 255,1)',
						'rgba(86, 35, 255,1)'
						]
					}],
					labels: [
						'Marketing',
						'Facility Dev.',
						'Product Dev.',
						'Human Cap.',
						'Distribution Dev.'
					]
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
				};

				new Chart(document.getElementById("expendituresChart"), {
				    type: 'line',
				    data: expData,
				    options: chartOptions
				});

				new Chart(document.getElementById("expendituresPieChart"), {
				    type: 'pie',
				    data: pieDataset,
				    options: { 
				    	maintainAspectRatio:false
				    }
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
	.cell > input {
		width: 80%;
		float: right;
	}
	.dropdown-pane > .grid-x {
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
	.surplusTooltip {
	    position: relative;
	    display: inline-block;
	    float: left;
	 }
	.surplusTooltip .surplusTooltiptext {
	    visibility: hidden;
	    width: 210px;
	    background-color: #d9edf7;
	    color: #31708f;
	    text-align: center;
	    border-radius: 6px;
	    padding: 5px 0;
	    position: absolute;
	    z-index: 1;
	    bottom: 115%;
	    margin-left: -60px;
	}
	.surplusTooltip .surplusTooltiptext::after {
	    content: "";
	    position: absolute;
	    top: 100%;
	    left: 85%;
	    margin-left: -5px;
	    border-width: 5px;
	    border-style: solid;
	    border-color: #d9edf7 transparent transparent transparent;
	}
	.surplusTooltiptext>p{
		margin-bottom: 0.5rem;
		line-height: 1.2;
		font-size: 1rem;
	}
	.makeVisible {
	    visibility: visible !important;
	}
  </style>
</html>

<?php
$OUTPUT->footerEnd();
