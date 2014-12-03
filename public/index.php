<?php
	/**
	 * Simple Contest Bot for Twitter.
	 * This bot needs to make sure that the user has followed the twitter account and that they have re-tweeted the contest message.
	 **/

	define('DATE_FMT', 'l, F j, Y h:i:s');

	require_once('../vendor/autoload.php');

	$app = new \Slim\Slim(array(
		'debug' => true,
		'view' => new \TJC\View\Layout(),
		'templates.path' => '../app/templates',
		'whoops.editor' => 'sublime'
	));

	\ORM::configure('sqlite:../data/database.db');
	\ORM::configure('logging', false);
	\ORM::configure('logger', function($log_str, $query_time) {
		printf("\t\t%s - %s<br />\n", $log_str, $query_time);
	});

	$app->add(new \Slim\Middleware\SessionCookie);
	$app->add(new \Zeuxisoo\Whoops\Provider\Slim\WhoopsMiddleware);

	$app->view->setLayout('layout/layout.php');

	// This is the default layout files.
 	$app->view->appendJavascriptFile('https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js')
			  ->appendJavascriptFile('//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js')
			  ->appendJavascriptFile('//cdnjs.cloudflare.com/ajax/libs/jasny-bootstrap/3.1.3/js/jasny-bootstrap.min.js')
			  //->appendJavascriptFile('//cdnjs.cloudflare.com/ajax/libs/typeahead.js/0.10.4/dist/typeahead.bundle.min.js')
			  ->appendJavascriptFile('/js/application.js');

	$app->view->appendStylesheet('//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css')
			  ->appendStyle("body { padding-top: 60px; } table.collapse.in { display: table; }")
			  ->appendStylesheet('//cdnjs.cloudflare.com/ajax/libs/jasny-bootstrap/3.1.3/css/jasny-bootstrap.min.css');

	$settings = \ORM::for_table('settings')->select_many('name', 'value')->find_array();

	$app->app_settings = (object) array_column($settings, 'value', 'name');

	$app->view->setLayoutData('copyright', $app->app_settings->copyright);

	// Setup Codebird Twitter stuff.
	\Codebird\Codebird::setConsumerKey($app->app_settings->twitter_consumer_key, $app->app_settings->twitter_consumer_secret);

	$app->container->singleton('twitter', function() {
		return \Codebird\Codebird::getInstance();
	});

	$app->twitter->setToken($app->app_settings->twitter_access_token, $app->app_settings->twitter_access_token_secret);

	$app->get('/', function() use($app) {
		$last_run_date = date(DATE_FMT, $app->app_settings->last_run);

		$total_entries = \ORM::for_table('entries')->count();
		$total_users = \ORM::for_table('user')->where('exclude', 0)->count();
		$total_followers = \ORM::for_table('user')->where('exclude', 0)->where('follower', 1)->count();

		$total_track = \ORM::for_table('tracktweet')->count();

		$not_following = \ORM::for_table('user')->where('exclude', 0)->where('follower', 0)->find_many();
		$not_following_count = \ORM::for_table('user')->where('exclude', 0)->where('follower', 0)->count();

		$users_unfollowing = array();
		foreach($not_following as $user) {
			$users_unfollowing[] = sprintf('<tr><td><a href="https://twitter.com/%s" target="_blank">%s</a></td><td>%s</td></tr>',
										   $user->username,
										   $user->username, 
										   \ORM::for_table('entries')->where('userid', $user->id)->count());
		}

		$stats = \ORM::for_table('tracktweet')->table_alias('tt')
											  ->select_many('tt.id', 'tt.tweetid')
											  ->select_expr('COUNT(e.tweetid)', 'rt_count')
											  ->left_outer_join('entries', array('tt.id', '=', 'e.tweetid'), 'e')
											  ->group_by("e.tweetid")
											  ->find_many();
		$tweet_stats = array();
		$avg_retweet = 0;
		foreach($stats as $stat) {
			$tweet_stats[] = sprintf('<tr><td><a href="%s">%s</a></td><td>%s</td><td><a href="http://twitter.com/%s/status/%s" target="_blank">View Tweet</a></td></tr>', $app->urlFor('view-track', array('id' => $stat->id)),
																													  													  $stat->tweetid,
																													  													  $stat->rt_count,
																													  													  $app->app_settings->twitter_username,
																													  													  $stat->tweetid);

			$avg_retweet += $stat->rt_count;
		}

		$app->render('index.php', array(
			'last_run' => $last_run_date,
			'total_entries' => $total_entries,
			'total_users' => $total_users,
			'total_followers' => $total_followers,
			'users_unfollowing' => implode($users_unfollowing),
			'unfollowing_count' => $not_following_count,
			'tweets_tracking' => $total_track,
			'tweet_stats' => implode($tweet_stats),
			'average_retweets' => floor($avg_retweet / $total_track)
		));
	});

	$app->group('/track', function() use($app) {
		$app->get('/', function() use($app) {
			// Grab Current Statuses
			$track = \ORM::for_table('tracktweet')->table_alias('tt')
												  ->select_many('tt.id', 'tt.tweetid', 'tt.lasttracked')
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

		$app->get('/view/:id', function($id) use($app) {

		})->name('view-track');

		$app->get('/delete/:id', function($id) use($app) {

		})->name('delete-track');
	});

	$app->map('/search', function() use($app) {
		$username = $app->request->params('username');
		$search_results = \ORM::for_table('user')->where_like('username', '%'.$username.'%')->find_many();
		$search_count = \ORM::for_table('user')->where_like('username', '%'.$username.'%')->count();

		if($app->request->isAjax()) {
			$app->view->disableLayout(); // Disable the layout for this ...

		} else {
			$results = array();
			foreach($search_results as $result) {
				$results[] = sprintf('<tr><td><a href="%s">%s</a></td><td>%s</td></tr>', $app->urlFor('user', array('id' => $result->id)),
																						 $result->username,
																						 \ORM::for_table('entries')->where('userid', $result->id)->count());
			}

			$app->render('search.php', array('search_results' => implode($results), 'search_count' => $search_count));
		}
	})->via('GET', 'POST');

	$app->get('/user/:id', function($id) use($app) {
		$user = \ORM::for_table('user')->find_one($id);
		var_dump(unserialize($user->user_object));
		$app->render('user.php', array('username' => $user->username));
	})->name('user');

	$app->map('/winner', function() use($app) {
		$data_arr = array('follower_default' => 1, 'winner_default' => 1, 'exclude_default' => 1, 'number_default' => $app->app_settings->winner_default_limit);
		if($app->request->isPost()) {
			if($app->request->post('winnernumber') > 0) {
				$data_arr['number_default'] = $limit = $app->request->post('winnernumber');
			} else {
				$limit = $app->app_settings->winner_default_limit;
			}

			$where = array();

			if($app->request->post('follower') == 1) {
				$where['u.follower'] = 1; 
			} else {
				$data_arr['follower_default'] = 0;
			}

			if($app->request->post('previouswinner') == 1) {
				$where['u.winner'] = 0;
			} else {
				$data_arr['winner_default'] = 0;
			}

			if($app->request->post('exclude') == 1) {
				$where['u.exclude'] = 0;
			} else {
				$data_arr['exclude_default'] = 0;
			}

			// We process the "winners" ..
			$winners = \ORM::for_table('entries')->table_alias('e')
												 ->select_many(array("userid" => "u.id", "u.twitterid", "u.username", "tt.tweetid", "trackid" => "tt.id"))
												 ->left_outer_join('user', array('e.userid', '=', 'u.id'), 'u')
												 ->left_outer_join('tracktweet', array('e.tweetid', '=', 'tt.id'), 'tt')
												 ->where($where)
												 ->order_by_expr('RANDOM()')
												 ->limit($limit)
												 ->find_array();

			shuffle($winners);
			shuffle($winners); // Double Shuffle to have more of a random pick

			$results = array();
			foreach($winners as $winner) {
				$results[] = sprintf('<tr><td><a href="%s">%s</a></td><td><a href="https://twitter.com/%s" target="_blank">%s</a></td><td><a href="%s">%s</a></td></tr>', $app->urlFor('user', array('id' => $winner['userid'])),
																																										  $winner['username'],
																																										  $winner['username'],
																																										  $winner['twitterid'],
																																										  $app->urlFor('view-track', array('id' => $winner['trackid'])),
																																										  $winner['tweetid']);
			}
			$data_arr['results'] = implode($results);

		}
		$app->render('winner.php', $data_arr);
	})->via('GET', 'POST');

	$app->group('/settings', function() use($app) {

	});

	$app->get('/cron', function() use($app) {
		// This is the main action
		$stats = array('msgs' => array());
		$time = time();
		// First things first: we need to gather all of the tweets.

		$tracked_tweets = \ORM::for_table('tracktweet')->find_many();
		foreach($tracked_tweets as $tweet) {
			$retweeters = array();
			$req_str = sprintf('id=%s', $tweet->tweetid);
			$cursor = NULL;
			do {
				if(!is_null($cursor)) {
					$req_str .= sprintf('&cursor=%s', $cursor);
				}
				$retweet_gather = $app->twitter->statuses_retweeters_ids($req_str, true);
				$retweeters = array_merge($retweeters, $retweet_gather->ids);

				if($retweet_gather->next_cursor !== 0) {
					$cursor = $retweet_gather->next_cursor;
				} else {
					$cursor = NULL;
				}
			} while(!is_null($cursor));
			if($app->request->isAjax()) {
				printf('Number of Retweeters for ID#(%s): %s <br />', $tweet->tweetid, count($retweeters));
			}


		}
	});

	$app->run();









