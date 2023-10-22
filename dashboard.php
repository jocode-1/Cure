<?php 
	include("header.php");
?>
		
			<!-- /Top Navigation -->
			<!-- Breadcrumb -->
			<!-- Page Title -->
			<div class="row no-margin-padding">
				<div class="col-md-6">
					<h3 class="block-title">Quick Statistics</h3>
				</div>
				<div class="col-md-6">
					<ol class="breadcrumb">
						<li class="breadcrumb-item">
							<a href="index.html">
								<span class="ti-home"></span>
							</a>
						</li>
						<li class="breadcrumb-item active">Dashboard</li>
					</ol>
				</div>
			</div>
			<!-- /Page Title -->

			<!-- /Breadcrumb -->
			<!-- Main Content -->
			<div class="container-fluid home">


				<div class="row">
					<!-- Widget Item -->
					<div class="col-md-4">
						<div class="widget-area proclinic-box-shadow color-red">
							<div class="widget-left">
								<span class="ti-user"></span>
							</div>
							<div class="widget-right">
								<h4 class="wiget-title">Patients</h4>
								<span class="numeric color-red">348</span>
								<p class="inc-dec mb-0"><span class="ti-angle-up"></span> +20% Increased</p>
							</div>
						</div>
					</div>
					<!-- /Widget Item -->
					<!-- Widget Item -->
					<div class="col-md-4">
						<div class="widget-area proclinic-box-shadow color-green">
							<div class="widget-left">
								<span class="ti-bar-chart"></span>
							</div>
							<div class="widget-right">
								<h4 class="wiget-title">Appointments</h4>
								<span class="numeric color-green">1585</span>
								<p class="inc-dec mb-0"><span class="ti-angle-down"></span> -15% Decreased</p>
							</div>
						</div>
					</div>
					<!-- /Widget Item -->
					<!-- Widget Item -->
					<div class="col-md-4">
						<div class="widget-area proclinic-box-shadow color-yellow">
							<div class="widget-left">
								<span class="ti-money"></span>
							</div>
							<div class="widget-right">
								<h4 class="wiget-title">Total Revenue</h4>
								<span class="numeric color-yellow">$7300</span>
								<p class="inc-dec mb-0"><span class="ti-angle-up"></span> +10% Increased</p>
							</div>
						</div>
					</div>
					<!-- /Widget Item -->
				</div>

				<div class="row">
					<!-- Widget Item -->
					<div class="col-md-6">
						<div class="widget-area-2 proclinic-box-shadow">
							<h3 class="widget-title">Appointments Year by Year</h3>
							<div id="lineMorris" class="chart-home"></div>
						</div>
					</div>
					<!-- /Widget Item -->
					<!-- Widget Item -->
					<div class="col-md-6">
						<div class="widget-area-2 proclinic-box-shadow">
							<h3 class="widget-title"> Patients Year by Year</h3>
							<div id="barMorris" class="chart-home"></div>
						</div>
					</div>
					<!-- /Widget Item -->
				</div>

				<div class="row">
					<!-- Widget Item -->
					<div class="col-md-12">
						<div class="widget-area-2 proclinic-box-shadow">
							<h3 class="widget-title">Appointments</h3>
							<div class="table-responsive">
								<table class="table table-bordered table-striped">
									<thead>
										<tr>
											<th>Patient Name</th>
											<th>Doctor</th>
											<th>Check-Up</th>
											<th>Date</th>
											<th>Time</th>
											<th>Status</th>
										</tr>
									</thead>
									<tbody>
										<tr>
											<td>Rajesh</td>
											<td>Manoj Kumar</td>
											<td>Dental</td>
											<td>12-10-2018</td>
											<td>12:10PM</td>
											<td>
												<span class="badge badge-success">Completed</span>
											</td>
										</tr>
										<tr>
											<td>Riya</td>
											<td>Daniel </td>
											<td>Ortho</td>
											<td>12-10-2018</td>
											<td>1:10PM</td>
											<td>
												<span class="badge badge-warning">Pending</span>
											</td>
										</tr>
										<tr>
											<td>Siri</td>
											<td>Daniel </td>
											<td>Ortho</td>
											<td>12-10-2018</td>
											<td>1:30PM</td>
											<td>
												<span class="badge badge-danger">Cancelled</span>
											</td>
										</tr>
										<tr>
											<td>Rajesh</td>
											<td>Manoj Kumar</td>
											<td>Dental</td>
											<td>12-10-2018</td>
											<td>12:10PM</td>
											<td>
												<span class="badge badge-success">Completed</span>
											</td>
										</tr>
										<tr>
											<td>Riya</td>
											<td>Daniel </td>
											<td>Ortho</td>
											<td>12-10-2018</td>
											<td>1:10PM</td>
											<td>
												<span class="badge badge-warning">Pending</span>
											</td>
										</tr>
										<tr>
											<td>Siri</td>
											<td>Daniel </td>
											<td>Ortho</td>
											<td>12-10-2018</td>
											<td>1:30PM</td>
											<td>
												<span class="badge badge-danger">Cancelled</span>
											</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
					</div>
					<!-- /Widget Item -->
				</div>

				<div class="row">
					<!-- Widget Item -->
					<div class="col-sm-6">
						<div class="widget-area-2 proclinic-box-shadow">
							<h3 class="widget-title">Appointments Status</h3>
							<div id="donutMorris" class="chart-home"></div>
						</div>
					</div>
					<!-- /Widget Item -->
					<!-- Widget Item -->
					<div class="col-md-6">
						<div class="widget-area-2 progress-status proclinic-box-shadow">
							<h3 class="widget-title">Doctors Availability</h3>
							<div class="table-responsive">
								<table class="table table-bordered">
									<thead>
										<tr>
											<th>Doctor</th>
											<th>Speciality</th>
											<th>Availability</th>
										</tr>
									</thead>
									<tbody>
										<tr>
											<td>Rajesh</td>
											<td>Dental</td>
											<td>
												<span class="badge badge-success">Available</span>
											</td>
										</tr>
										<tr>
											<td>Riya</td>
											<td>Ortho</td>
											<td>
												<span class="badge badge-warning">On Leave</span>
											</td>
										</tr>
										<tr>
											<td>Siri</td>
											<td>Skin</td>
											<td>
												<span class="badge badge-danger">Not Available</span>
											</td>
										</tr>
										<tr>
											<td>Rajesh</td>
											<td>Dental</td>
											<td>
												<span class="badge badge-success">Available</span>
											</td>
										</tr>
									</tbody>
								</table>
							</div>

						</div>
					</div>
					<!-- /Widget Item -->

				</div>

			</div>
			<!-- /Main Content -->
		</div>
		<!-- /Page Content -->
	</div>
	<!-- Back to Top -->
	<a id="back-to-top" href="#" class="back-to-top">
		<span class="ti-angle-up"></span>
	</a>
	<!-- /Back to Top -->
	
	<!-- Jquery Library-->
	<script src="js/jquery-3.2.1.min.js"></script>
	<!-- Popper Library-->
	<script src="js/popper.min.js"></script>
	<!-- Bootstrap Library-->
	<script src="js/bootstrap.min.js"></script>
	<!-- morris charts -->
	<script src="charts/js/raphael-min.js"></script>
	<script src="charts/js/morris.min.js"></script>
	<script src="js/custom-morris.js"></script>

	<!-- Custom Script-->
	<script src="js/custom.js"></script>
	<script src="js/dashboard.js"></script>
	<script src="js/sweet.js"></script>
</body>


<!-- Mirrored from www.konnectplugins.com/proclinic/Vertical/index.html by HTTrack Website Copier/3.x [XR&CO'2014], Wed, 18 Oct 2023 11:21:13 GMT -->
</html>
