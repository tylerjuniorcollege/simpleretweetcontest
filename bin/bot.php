<?php
	// This is the bot code.
	
	require_once('../vendor/autoload.php');

	function wait_until($timestamp, $skip_sleep = FALSE) {
		$now = new DateTime();
		$wait = new DateTime();
		$wait->setTimestamp($timestamp);

		$interval = $wait->diff($now);

		if($interval->i > 1 && $skip_sleep == TRUE) { // This is if the wait is too long.
			return TRUE; // Continue past the loop.
		}

		sleep(($wait->getTimestamp() - $now->getTimestamp()));

		return FALSE;
	}

	function print_rate_limit($opt, $limit, $remaining, $reset) {
		global $cli;

		$now = new DateTime();
		$future = new DateTime();
		$future->setTimestamp($reset);

		$interval = $future->diff($now);

		if($cli->opt($opt)) {
			$cli->print_line(sprintf('Rate Limit Remaning (%s/%s) Time Remaning Til Reset: %s', $remaining, $limit, $interval->format("%i minutes, %s seconds")));
		}
	}

	$cli = new Cli(array(
		'tracktweet:' => 'This lets you add a tweet to the tracker.',
		'processtweets' => 'Processes all retweets of tracked tweets',
		'processfollowers' => 'Checks to see if processed users are followers',
		'processstats:' => 'Processes stats for the specified Campaign ID',
		'showratelimit' => 'Show the current ratelimit',
		'showratelimits' => 'Application Rate Limit'

	));

	\ORM::configure('sqlite:../data/database.db');

	// Setup Codebird Twitter stuff.
	$consumer_key = \ORM::for_table('settings')->where('name', 'twitter_consumer_key')->find_one();
	$consumer_secret = \ORM::for_table('settings')->where('name', 'twitter_consumer_secret')->find_one();

	\Codebird\Codebird::setConsumerKey($consumer_key->value, $consumer_secret->value);	

	$twitter = \Codebird\Codebird::getInstance();

	$user_token = \ORM::for_table('settings')->where('name', 'twitter_access_token')->find_one();
	$user_secret = \ORM::for_table('settings')->where('name', 'twitter_access_token_secret')->find_one();

	$twitter->setToken($user_token->value, $user_secret->value);

	// Adding in time for timestamps.
	if($cli->opt('showratelimits')) {
		$rts = $twitter->application_rateLimitStatus();
		$cli->print_dump($rts);
		die();
	}

	if($cli->opt('tracktweet')) {
		$tweetcount = \ORM::for_table('tracktweet')->where('tweetid', $cli->opt('tracktweet', TRUE))->count();

		if($tweetcount === 0) {
			// Add it to the database.
			$track = \ORM::for_table('tracktweet')->create();
			$track->tweetid = $cli->opt('tracktweet', TRUE);
			$track->lasttracked = time();
			$track->save();
			$cli->print_line('Added tweet to tracker.');
		} else {
			$cli->print_line('Tweet Already Being Tracked');
		}
	}

	if($cli->opt('processtweets')) {
		$retweetcount = 0;
		// Grabbing all of the retweets - First we need to get all of the tracking tweets.
		$tweets = \ORM::for_table('tracktweet')->table_alias('tt')->select_many('tt.id', 'tt.tweetid', 'tt.lasttracked')
											   ->left_outer_join('campaigns', array('tt.campaignid', '=', 'c.id'), 'c')
											   ->where('c.active', 1)
											   ->find_many();

		//var_dump($tweets);
		foreach($tweets as $tweet) {
			$retweeters = array();
			$req_str = array('id' => $tweet->tweetid);
			$cursor = NULL;
			do {
				if(!is_null($cursor)) {
					$req_str['cursor'] = $cursor;
				}
				do {
					$retweet_gather = $twitter->statuses_retweeters_ids($req_str);

					if(!is_array($retweet_gather->rate)) {
						// Pause and try again
						$retry = TRUE;
						continue;
					} else {
						$retry = FALSE;
					}
					print_rate_limit('showratelimit', $retweet_gather->rate['limit'], $retweet_gather->rate['remaining'], $retweet_gather->rate['reset']);

					if($retweet_gather->rate['remaining'] < 2) {
						// Wait 
						$continue = wait_until($retweet_gather->rate['reset'], TRUE);
						if($continue == TRUE) {
							$cli->print_line('Rate Limit Reached Please Restart later.');
							continue 4;
						}
					}
				} while((!is_object($retweet_gather)) || ($retry == TRUE));

				$retweeters = array_merge($retweeters, $retweet_gather->ids);

				if($retweet_gather->next_cursor !== 0) {
					$cursor = $retweet_gather->next_cursor;
				} else {
					$cursor = NULL;
				}
			} while(!is_null($cursor));
			$cli->print_line(sprintf('Number of Retweeters for ID#(%s): %s', $tweet->tweetid, count($retweeters)));

			foreach($retweeters as $rtid) {
				// First, search for the user in the user table. If they don't exist, then we need to create a row for them.
				$user = \ORM::for_table('user')->where('twitterid', $rtid)->find_one();

				if($user === false) {
					// Grab the information from the API.
					do {
						$user_info = $twitter->users_show(http_build_query(array('user_id' => $rtid)));
					if(!is_array($user_info->rate)) {
						// Pause and try again
						$retry = TRUE;
					}
					print_rate_limit('showratelimit', $user_info->rate['limit'], $user_info->rate['remaining'], $user_info->rate['reset']);

					if($user_info->rate['remaining'] > 2) {
						wait_until($user_info->rate['reset']);
					}
				} while((!is_object($user_info)) || ($retry == TRUE));
					// Now we need to create the user object.

					if(!isset($user_info->screen_name)) {
						$cli->print_dump($user_info);
						die();
					}
					$user = \ORM::for_table('user')->create();
					$user->twitterid = $rtid;
					$user->username = (isset($user_info->screen_name) ? $user_info->screen_name : '');
					$user->user_object = serialize($user_info);
					$user->added = time();
					$user->save();
					$cli->print_line(sprintf('Added User (%s) to the database', $user->username));
					sleep(5);
				}

				// See if the user is already entered for this tweet.
				$entry = \ORM::for_table('entries')->where(array(
					'userid' => $user->id,
					'tweetid' => $tweet->id 
				))->find_one();

				if($entry === false) {
					$entry = \ORM::for_table('entries')->create();
					$entry->userid = $user->id;
					$entry->tweetid = $tweet->id;
					$entry->added = time();
					$entry->save();
					$cli->print_line(sprintf('Added Entry for User (%s) for Tweet ID#%s', $user->username, $tweet->tweetid));
					$retweetcount++;
				}
			}

			// Update time stamp on Tweet tracker.
			$tweet->lasttracked = time();
			$tweet->save();
		}
	}

	if($cli->opt('processfollowers')) {
		$usercount = 0;
		// Grab all of the retweeters and pass them in to an array.
		$users = \ORM::for_table('user')->select('username')->where('follower', 0)->find_array();
		$user_arr = array_column($users, 'username');

		// Split array in to 100s.
		foreach(array_chunk($user_arr, 100) as $user_chunk) {
			do {
				$following = $twitter->friendships_lookup(http_build_query(array('screen_name' => implode(',', $user_chunk))));
					if(!is_array($following->rate)) {
						// Pause and try again
						$retry = TRUE;
					} else {
						// Skip
						continue 2;
					}
					print_rate_limit('showratelimit', $following->rate['limit'], $following->rate['remaining'], $following->rate['reset']);

					if($following->rate['remaining'] > 2) {
						wait_until($following->rate['reset']);
					}
			} while((!is_object($following)) || ($retry == TRUE));
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
					$cli->print_line(sprintf('Added Follower %s', $user->username));
				} else {
					$user->follower = 0;
				}
				$user->save();
				$usercount++;
			}
		}
	}

	if($cli->opt('findwinner')) {

	}

	$lastrun = \ORM::for_table('settings')->where('name', 'last_run')->find_one();

	$lastrun->value = time();
	$lastrun->save();