<?php
	/**
	 * Simple Contest Bot for Twitter.
	 * This bot needs to make sure that the user has followed the twitter account and that they have re-tweeted the contest message.
	 **/

	require_once('../vendor/autoload.php');

	$app = new \Slim\Slim(array(
		'debug' => true,
		'view' => new \TJC\View\Layout(),
		'templates.path' => '../app/templates',
		'whoops.editor' => 'sublime'
	));

	\ORM::configure('sqlite:../data/database.db');

	$app->add(new \Slim\Middleware\SessionCookie);
	$app->add(new \Zeuxisoo\Whoops\Provider\Slim\WhoopsMiddleware);

	$app->view->setLayout('layout/layout.php');

	// This is the default layout files.
 	$app->view->appendJavascriptFile('https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js')
			  ->appendJavascriptFile('//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js')
			  ->appendJavascriptFile('//cdnjs.cloudflare.com/ajax/libs/jasny-bootstrap/3.1.3/js/jasny-bootstrap.min.js')
			  ->appendJavascriptFile('/js/application.js');

	$app->view->appendStylesheet('//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css')
			  ->appendStyle("body { padding-top: 60px; }")
			  ->appendStylesheet('//cdnjs.cloudflare.com/ajax/libs/jasny-bootstrap/3.1.3/css/jasny-bootstrap.min.css');

	$settings = \ORM::for_table('settings')->select_many('name', 'value')->find_array();

	$app->app_settings = (object) array_column($settings, 'value', 'name');

	// Setup Codebird Twitter stuff.
	\Codebird\Codebird::setConsumerKey($app->app_settings->twitter_consumer_key, $app->app_settings->twitter_consumer_secret);

	$app->container->singleton('twitter', function() {
		return \Codebird\Codebird::getInstance();
	});

	$app->twitter->setToken($app->app_settings->twitter_access_token, $app->app_settings->twitter_access_token_secret);

	$app->get('/', function() use($app) {
		$last_run_date = date('l, F j, Y h:i:s', $app->app_settings->last_run);

		$total_entries = \ORM::for_table('entries')->count();
		$total_users = \ORM::for_table('user')->where('exclude', 0)->count();
		$total_followers = \ORM::for_table('user')->where('exclude', 0)->where('follower', 1)->count();

		$not_following = \ORM::for_table('user')->where('exclude', 0)->where('follower', 0)->find_many();
		$not_following_count = \ORM::for_table('user')->where('exclude', 0)->where('follower', 0)->count();

		$users_unfollowing = array();
		foreach($not_following as $user) {
			$users_unfollowing[] = sprintf('<tr><td>%s</td><td>%s</td></tr>', 
										   $user->username, 
										   \ORM::for_table('entries')->where('userid', $user->id)->count());
		}

		$stats = \ORM::for_table('tracktweet')->table_alias('tt')
											  ->select('tt.tweetid')
											  ->select_expr('COUNT(e.tweetid)', 'rt_count')
											  ->left_outer_join('entries', array('tt.id', '=', 'e.tweetid'), 'e')
											  ->group_by("e.tweetid")
											  ->find_many();
		$tweet_stats = array();
		foreach($stats as $stat) {
			$tweet_stats[] = sprintf('<tr><td><a href="http://twitter.com/%s/status/%s">%s</a></td><td>%s</td></tr>', $app->app_settings->twitter_username,
																													  $stat->tweetid,
																													  $stat->tweetid,
																													  $stat->rt_count);
		}

		$app->render('index.php', array(
			'last_run' => $last_run_date,
			'total_entries' => $total_entries,
			'total_users' => $total_users,
			'total_followers' => $total_followers,
			'users_unfollowing' => implode($users_unfollowing),
			'unfollowing_count' => $not_following_count,
			'tweet_stats' => implode($tweet_stats)
		));
	});

	$app->group('/track', function() use($app) {
		$app->get('/', function() use($app) {
			// Grab Current Statuses
			$track = \ORM::for_table('tracktweet')->table_alias('tt')
												  ->select_many('tt.tweetid', 'tt.lasttracked')
												  ->select_expr('COUNT(e.tweetid)', 'rt_count')
												  ->left_outer_join('entries', array('tt.id', '=', 'e.tweetid'), 'e')
												  ->group_by("e.tweetid")
												  ->find_many();

			$active_track = array();
			foreach($track as $t) {
				$active_track[] = sprintf('<tr><td><a href="%s">%s</a></td><td>%s</td><td>%s</td><td><a href="%s">Stop Tracking</a></td></tr>', $app->urlFor('view-track', array('id' => $t->id)),
																																	 			$t->tweetid,
																																	 			$t->rt_count,
																																	 			date(DATE_FMT, $t->lasttracked),
																																	 			$app->urlFor('delete-track', array('id' => $t->id)));	
			}

			$timeline = $app->twitter->statuses_userTimeline(sprintf('screen_name=%s&count=50&trim_user=true&exclude_replies=true', $app->app_settings->twitter_username), true);

			$track_timeline = array();
			foreach((array) $timeline as $tweet) {
				if(!is_object($tweet)) {
					continue;
				}
				$tweeturl = sprintf('https://twitter.com/%s/status/%s', $app->app_settings->twitter_username, $tweet->id);
				$tracked = \ORM::for_table('tracktweet')->where('tweetid', $tweet->id)->count();
				$track_timeline[] = sprintf('<tr><td>%s</td><td>%s</td><td><button class="tracktweet btn btn-default" value="%s"%s>Track</button></td></tr>', $tweet->id,
																																							  $tweet->text,
																																							  $tweeturl,
																																							  ($tracked > 0 ? ' disabled="disabled"' : ''));
			}
			$app->render('track.php', array('active_track' => implode($active_track), 'timeline' => implode($track_timeline), 'username' => $app->app_settings->twitter_username));
		});

		$app->post('/add', function() use($app) {
			$url = $app->request->post('twitterurl');
			$pieces = parse_url($url);
			$id = array_pop(explode('/', $pieces['path']));

			$tweetcount = \ORM::for_table('tracktweet')->where('tweetid', $id)->count();
			if($tweetcount === 0) {
				// Add it to the database.
				$track = \ORM::for_table('tracktweet')->create();
				$track->tweetid = $id;
				$track->lasttracked = time();
					$track->save();
				$app->flash('success', 'Added Status to Tracker.');
			} else {
				$app->flash('danger', 'Status is already being tracked.');
			}

			$app->redirect('/track');
		});
	});

	$app->post('/search', function() use($app) {
		$username = $app->request->post('username');
		$search_results = \ORM::for_table('user')->where_like('username', '%'.$username.'%')->find_many();
		$search_count = \ORM::for_table('user')->where_like('username', '%'.$username.'%')->count();

		$app->render('search.php', array('search_results' => $search_results, 'search_count' => $search_count));
	});

	$app->get('/user/:id', function($id) use($app) {

	})->name('user');

	$app->group('/winner', function() use($app) {
		$app->get('/', function() use($app) {
			$app->render('winner.php', array('follower_default' => 1, 'winner_default' => 0, 'exclude_default' => 1));
		});

		$app->post('/find', function() use($app) {

		});
	});

	$app->group('/settings', function() use($app) {

	});

	$app->run();










