<div class="row">
	<div class="col-md-12">
		<h2 class="page-header">Current Campaign:</h2>
		<h3 class="page-header">As of <small><?= $data['last_run']; ?></small></h3>
	</div>
</div>
<div class="row">
	<div class="col-md-3"> 
		<div class="panel panel-success">
			<div class="panel-heading">
				<h4 class="panel-title">Total Number of Retweets</h4>
			</div>
			<div class="panel-body">
				<h1 id="totalentries"><?= $data['total_entries']; ?></h1>
			</div>
		</div>
	</div>
	<div class="col-md-3"> 
		<div class="panel panel-info">
			<div class="panel-heading">
				<h4 class="panel-title">Total Number of Users</h4>
			</div>
			<div class="panel-body">
				<h1 id="totalusers"><?= $data['total_users']; ?></h1>
			</div>
		</div>
	</div>
	<div class="col-md-3"> 
		<div class="panel panel-warning">
			<div class="panel-heading">
				<h4 class="panel-title">Total Number of Followers</h4>
			</div>
			<div class="panel-body">
				<h1 id="totalfollowers"><?= $data['total_followers']; ?></h1>
			</div>
		</div>
	</div>
	<div class="col-md-3"> 
		<div class="panel panel-primary">
			<div class="panel-heading">
				<h4 class="panel-title">Average (Mean) Retweets</h4>
			</div>
			<div class="panel-body">
				<h1 id="totalfollowers"><?= $data['average_retweets']; ?></h1>
			</div>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<h3 class="page-header"><a data-toggle="collapse" data-target="#tweetstats" href="#tweetstats" aria-expanded="false" aria-controls="tweetstats">Tweet Stats</a> <small><?= $data['tweets_tracking']; ?> Total Tweets being Tracked</small> 
								<a data-toggle="collapse" data-target="#tweetstats" href="#tweetstats" aria-expanded="false" aria-controls="tweetstats" class="pull-right"><span class="glyphicon glyphicon-chevron-down"></span></a></h3>
		<div class="clearfix"></div>
		<table class="table table-condensed table-hover collapse in" id="tweetstats">
			<colgroup>
				<col class="col-md-4">
				<col class="col-md-4">
				<col class="col-md-4">
			</colgroup>
			<thead>
				<tr>
					<th>Tweet ID</th>
					<th># of Retweets</th>
					<th>View</th>
				</tr>
			</thead>
			<tbody>
			<?=$data['tweet_stats'] ?>
			</tbody>
		</table>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<h3 class="page-header"><a data-toggle="collapse" data-target="#toptweeters" href="#toptweeters" aria-expanded="false" aria-controls="toptweeters">Top Retweeters</a>
								<a data-toggle="collapse" data-target="#toptweeters" href="#toptweeters" aria-expanded="false" aria-controls="toptweeters" class="pull-right"><span class="glyphicon glyphicon-chevron-down"></span></a></h3>
		<div class="clearfix"></div>
		<table class="table table-condensed table-hover collapse in" id="toptweeters">
			<colgroup>
				<col class="col-md-6">
				<col class="col-md-6">
			</colgroup>
			<thead>
				<tr>
					<th>Username</th>
					<th># of Retweets</th>
				</tr>
			</thead>
			<tbody>
			<?=$data['top_tweeters'] ?>
			</tbody>
		</table>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<h3 class="page-header"><a data-toggle="collapse" data-target="#notfollowing" href="#notfollowing" aria-expanded="true" aria-controls="notfollowing"><?= $data['unfollowing_count'] ?> Users not following ...</a>
								<a data-toggle="collapse" data-target="#notfollowing" href="#notfollowing" aria-expanded="true" aria-controls="notfollowing" class="pull-right"><span class="glyphicon glyphicon-chevron-down"></span></a></h3>
		<table class="table table-condensed table-hover collapse" id="notfollowing">
			<colgroup>
				<col class="col-md-6">
				<col class="col-md-6">
			</colgroup>
			<thead>
				<tr>
					<th>Username</th>
					<th># of Retweets</th>
				</tr>
			</thead>
			<tbody>
			<?=($data['users_unfollowing']) ?>
			</tbody>
		</table>
	</div>
</div>