<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="description" content="">
		<meta name="author" content="Tyler Junior College">

		<title>Simple Retweet Contest App</title>

		<?= implode($data['css']['rendered'], "\n\t\t"); ?>

		<!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
		<!--[if lt IE 9]>
	  		<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
	  		<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
		<![endif]-->
	</head>
	<body>

		<div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
			<div class="container">
				<div class="navbar-header">
					<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
						<span class="sr-only">Toggle navigation</span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</button>
			  		<a class="navbar-brand" href="/">Retweet Contest App</a>
				</div>
				<form class="navbar-form navbar-left">
					<div class="btn-group">
						<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">Campaigns <span class="caret"></span></button>
						<ul class="dropdown-menu" role="menu">
							<?php foreach($data['campaigns'] as $id => $campaign) {
								printf('<li%s><a href="/campaign/select/%s">%s</a></li>', (''), $id, $campaign);
							} ?>
						</ul>
					</div>
				</form>
				<ul class="nav navbar-nav">
					<li><a href="/track">Track a Tweet</a></li>
			  	</ul>
				<form class="navbar-form navbar-right" method="POST" action="/search">
					<div class="input-group">
						<input type="text" name="username" placeholder="Username ..." id="searchusername" class="form-control">
						<span class="input-group-btn">
							<button class="btn btn-default" type="submit" id="search"><span class="glyphicon glyphicon-search"></span> Search</button>
						</span>
					</div>
					<div class="btn-group" role="group">
			  			<button type="button" id="grabretweets" class="btn btn-primary" data-toggle="popover" data-content="Process Retweets"><span class="glyphicon glyphicon-retweet"></span></button>
			  			<a href="/winner" id="findwinner" class="btn btn-danger" data-toggle="popover" data-content="Find a Winner"><span class="glyphicon glyphicon-user"></span></a>
			  		</div>
			  	</form>
		  	</div>
		</div>
		<div class="container">
			<div class="row">
				<div class="col-md-12">
					<?php foreach($flash as $type => $message) {
						printf('<div class="alert alert-%s alert-dismissible fade in" role="alert"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>%s</div>', $type, $message);
					} ?>
				</div>
			</div>
<?= $data['content']; ?>
			<div class="row">
				<div class="col-md-12">
					<p><?= $data['copyright'] ?></p>
				</div>
		  	</div>
		</div>
	<?= implode($data['js']['rendered'], "\n\t"); ?>
	</body>
</html>
