<div class="row">
	<div class="col-md-12">
		<h3 class="page-header">Active Tracking</h3>
		<table class="table table-condensed table-hover">
			<colgroup>
				<col class="col-md-3">
				<col class="col-md-3">
				<col class="col-md-3">
				<col class="col-md-3">
			</colgroup>
			<thead>
				<tr>
					<th>Twitter ID</th>
					<th>Number of Retweets</th>
					<th>Last Tracked</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
			<?= $data['active_track']; ?>
			</tbody>
		</table>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<h3 class="page-header">Track a Twitter Status Update</h3>
		<form method="POST" action="/track/add" class="form-horizontal" role="form">
			<div class="form-group">
				<label for="twitterurl" class="col-sm-2 control-label">Twitter URL</label>
				<div class="col-sm-10">
					<div class="input-group">
						<input type="text" name="twitterurl" id="twitterurl" placeholder="ex. https://twitter.com/TylerJrCollege/status/538395545262641152" class="form-control" aria-describedby="helptext">
						<span class="input-group-btn">
							<button type="submit" class="btn btn-default">Submit</button>
						</span>
					</div>
					<span id="helptext" class="help-block">Please provide the full Twitter URL that you want to track Retweets from.</span>
				</div>
			</div>
		</form>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<h3 class="page-header"><?= $data['username']; ?> <small>Last 50 Tweets</small></h3>
		<table class="table table-condensed table-hover" role="table">
			<colgroup>
				<col class="col-md-2">
				<col class="col-md-9">
				<col class="col-md-1">
			</colgroup>
			<thead>
				<tr>
					<th>Tweet ID</th>
					<th>Tweet</th>
					<th>Track Button</th>
				</tr>
			</thead>
			<tbody>
				<?= $data['timeline']; ?>
			</tbody>
		</table>
	</div>
</div>