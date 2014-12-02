<div class="container">
	<div class="row">
		<div class="col-md-12 page-header">
			<h3>As of <small><?= $data['last_run']; ?></small></h3>
		</div>
	</div>
	<div class="row">
		<div class="col-md-4"> 
			<div class="panel panel-success">
				<div class="panel-heading">
					<h4 class="panel-title">Total Number of Retweets</h4>
				</div>
				<div class="panel-body">
					<h1 id="totalentries"><?= $data['total_entries']; ?></h1>
				</div>
			</div>
		</div>
		<div class="col-md-4"> 
			<div class="panel panel-info">
				<div class="panel-heading">
					<h4 class="panel-title">Total Number of Users</h4>
				</div>
				<div class="panel-body">
					<h1 id="totalusers"><?= $data['total_users']; ?></h1>
				</div>
			</div>
		</div>
		<div class="col-md-4"> 
			<div class="panel panel-warning">
				<div class="panel-heading">
					<h4 class="panel-title">Total Number of Followers</h4>
				</div>
				<div class="panel-body">
					<h1 id="totalfollowers"><?= $data['total_followers']; ?></h1>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<h3 class="page-header">Users not following ...</h3>
			<table class="table table-condensed table-hover">
				<thead>
					<tr>
						<th>Username</th>
						<th># of Retweets</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach($data['users_unfollowing'] as $row): ?>
					<?= sprintf('<tr><td>%s</td><td>%s</td></tr>', $row['username'], $row['entry_count']); ?>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>