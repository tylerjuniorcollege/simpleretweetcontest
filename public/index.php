<?php
	/**
	 * Simple Contest Bot for Twitter.
	 * This bot needs to make sure that the user has followed the twitter account and that they have re-tweeted the contest message.
	 **/
	session_cache_limiter(false);
	session_start();

	define('DATE_FMT', 'l, F j, Y h:i:s a');

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

	/* $app->add(new \Slim\Middleware\SessionCookie(array(
		'expires' => '20 minutes',
		'path' => '/',
		'domain' => null,
		'secure' => false,
		'httponly' => false
	))); */
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

	// Add a Campaign drop down to the layout.
	$campaigns = \ORM::for_table('campaigns')->select_many('id', 'name')->order_by_asc('id')->order_by_desc('active')->find_array();
	$app->view->setLayoutData('campaigns', array_column($campaigns, 'name', 'id'));

	/* if(isset($_SESSION['current_campaign'])) {
		$app->view->setLayoutData('current_campaign', $_SESSION['current_campaign']['id']);
	} else {
		$app->view->setLayoutData('current_campaign', 'none');
	}

		$check_campaign = function() use($app) {
		if(!isset($_SESSION['current_campaign']) || empty($_SESSION['current_campaign'])) {
			$app->flash('warning', 'You need to specify a campaign to proceed.');
			$app->redirect('/campaign');
		}
	}; */

	// Campaign Stats.
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

		// Most Tweeters who Retweeted the status.
		$most_tweeters = \ORM::for_table('user')->table_alias('u')
												->select_many('u.id', 'u.username')
												->select_expr('COUNT(e.userid)', 'rt_count')
												->left_outer_join('entries', array('u.id', '=', 'e.userid'), 'e')
												->group_by('e.userid')
												->order_by_desc('rt_count')
												->limit(10)
												->find_many();

		$top_tweeters = array();
		foreach($most_tweeters as $tweeter) {
			$top_tweeters[] = sprintf('<tr><td><a href="%s">%s</td><td>%s</td></tr>', $app->urlFor('user', array('id' => $tweeter->id)), $tweeter->username, $tweeter->rt_count);
		}

		$app->render('index.php', array(
			//'campaign_name' => $_SESSION['current_campaign']['name'],
			'last_run' => $last_run_date,
			'total_entries' => $total_entries,
			'total_users' => $total_users,
			'total_followers' => $total_followers,
			'users_unfollowing' => implode($users_unfollowing),
			'unfollowing_count' => $not_following_count,
			'tweets_tracking' => $total_track,
			'tweet_stats' => implode($tweet_stats),
			'average_retweets' => floor($avg_retweet / $total_track),
			'top_tweeters' => implode($top_tweeters)
		));
	});

	$app->group('/campaign', function() use($app) {
		$app->get('/', function() use($app) {
			$campaigns = \ORM::for_table('campaigns')->table_alias('c')
													 ->select_many('c.id', 'c.name', 'c.description', 'c.start_time', 'c.end_time', 'c.active', 'c.created')
													 ->select_expr('COUNT(e.tweetid)', 'rt_count')
													 ->left_outer_join('tracktweet', array('c.id', '=', 'tt.campaignid'), 'tt')
													 ->left_outer_join('entries', array('e.tweetid', '=', 'tt.id'), 'e')
													 ->group_by('c.id')
													 ->find_many();

			$campaign_out = array();
			foreach($campaigns as $campaign) {
				$campaign_out[] = sprintf('<tr class="%s"><td><a href="%s">%s</a></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td><a href="%s" class="btn btn-danger">Edit</a></td><td><a href="%s" class="btn btn-primary">Select</a></td></tr>',
					($campaign->active == 1 ? 'success' : 'danger'),
					$app->urlFor('campaign-edit', array('id' => $campaign->id)),
					$campaign->name,
					$campaign->description,
					date(DATE_FMT, $campaign->created),
					date(DATE_FMT, $campaign->start_time),
					date(DATE_FMT, $campaign->end_time),
					$campaign->rt_count,
					$app->urlFor('campaign-edit', array('id' => $campaign->id)),
					$app->urlFor('campaign-select', array('id' => $campaign->id))
				);
			}
			$app->render('campaign/index.php', array('campaigns' => implode($campaign_out)));
		});

		$app->map('/edit/:id', function($id) use($app) {

		})->via('GET', 'POST')->name('campaign-edit');

		$app->get('/select/:id', function($id) use($app) {
			$campaign = \ORM::for_table('campaigns')->find_one($id);
			//$_SESSION['current_campaign']->as_array();

			//$app->flash('success', sprintf('Campaign Selected: <strong>%s</strong>', $_SESSION['current_campaign']['name']));
			$app->redirect('/');
		})->name('campaign-select');
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

		$app->post('/add',  function() use($app) {
			$url = $app->request->post('twitterurl');
			$pieces = parse_url($url);
			$id = array_pop(explode('/', $pieces['path']));

			$tweetcount = \ORM::for_table('tracktweet')->where('tweetid', $id)->count();
			if($tweetcount === 0) {
				// Add it to the database.
				$track = \ORM::for_table('tracktweet')->create();
				$track->tweetid = $id;
				$track->campaignid = $_SESSION['current_campaign']['id']; // Putting this in here for right now.
				$track->lasttracked = time();
					$track->save();
				$app->flash('success', 'Added Status to Tracker.');
			} else {
				$app->flash('danger', 'Status is already being tracked.');
			}

			$app->redirect('/track');
		});

		$app->get('/view/:id', function($id) use($app) {
			// Grab the oEmbeded code and display that with a list of retweeters.

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
		$app->render('user.php', array('username' => $user->username, 'user_object' => unserialize($user->user_object)));
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

	$app->group('/settings', function() use($app, $settings) {
		$app->get('/', function() use($app, $settings) {
			$app->render('settings/index.php', array('settings' => $settings));
		});

		$app->map('/edit/:id', function($id) use($app) {

		})->via('GET', 'POST')->name('settings-edit');
	});

	$app->group('/cron', function() use($app) {
		$app->get('/', function() use($app) {
			// This is the main action
			$stats = array('msgs' => array(), 'retweet_count' => 0, 'follower_count' => 0, 'user_count' => 0);
			$time = time();
			// First things first: we need to gather all of the tweets.

			$tracked_tweets = \ORM::for_table('tracktweet')->table_alias("tt")
														   ->select_many(array('id' => 'tt.id', 'tweetid' => 'tt.tweetid'))
														   ->left_outer_join("campaigns", array("tt.campaignid", '=', 'c.id'), 'c')
														   ->where('c.active', 1)
														   ->find_many();
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
				$stats['msgs'][] = sprintf('Number of Retweeters for ID#(%s): %s', $tweet->tweetid, count($retweeters));

				foreach($retweeters as $rtid) {
					// First, search for the user in the user table. If they don't exist, then we need to create a row for them.
					$user = \ORM::for_table('user')->where('twitterid', $rtid)->find_one();
					if($user === false) {
						// Grab the information from the API.
						$user_info = $app->twitter->users_show(sprintf('user_id=%s', $rtid), true);
						$user = \ORM::for_table('user')->create();
						$user->twitterid = $rtid;
						$user->username = $user_info->screen_name;
						$user->user_object = serialize($user_info);
						$user->added = $time;
						$user->save();
						$stats['msgs'][] = sprintf('Added User (%s) to the database', $user->username);
						$stats['user_count']++;
					}

					$entry_search = \ORM::for_table('entries')->where(array(
						'userid' => $user->id,
						'tweetid' => $tweet->id 
					))->find_one();

					if($entry_search === false) {
						$entry = \ORM::for_table('entries')->create();
						$entry->userid = $user->id;
						$entry->tweetid = $tweet->id;
						$entry->added = $time;
						$entry->save();
						$stats['msgs'][] = sprintf('Added Entry for User (%s) for Tweet ID#%s', $user->username, $tweet->tweetid);
						$stats['retweet_count']++;
					}
				}

				// Update time stamp on Tweet tracker.
				$tweet->lasttracked = $time;
				$tweet->save();
			}


			// Now we need to process followers ...
			$users = \ORM::for_table('user')->select('username')->where('follower', 0)->find_array();
			$user_arr = array_column($users, 'username');
			foreach(array_chunk($user_arr, 100) as $user_chunk) {
				$following = $app->twitter->friendships_lookup(http_build_query(array('screen_name' => implode(',', $user_chunk))));
				foreach((array) $following as $id => $relationship) {
					if(!is_object($relationship)) {
						continue; // Skip the processing of these items.
					}

					$user = \ORM::for_table('user')->where('username', $relationship->screen_name)->find_one();
					
					if($user == false) {
						continue;
					}
					if(in_array('followed_by', $relationship->connections)) {
						$user->follower = 1;
						$stats['msgs'][] = sprintf('Added Follower %s', $user->username);
						$stats['follower_count']++;
					} else {
						$user->follower = 0;
					}
					$user->save();
				}
			}
			$stats_json = json_encode($stats);
			if($app->request->isAjax()) {
				echo $stats_json;
			} else {
				// Add this to the database.
				$cron_message = \ORM::for_table('cron_messages')->create();
				$cron_message->timestamp = $time;
				$cron_message->json_dump = $stats_json;
				$cron_message->save();
			}
		});
		$app->group('/messages', function() use($app) {
			$app->get('/', function() use($app) {

			});

			$app->get('/view/:id', function($id) use($app) {

			})->name('cron-messages-view');


		});
	});

	$app->run();