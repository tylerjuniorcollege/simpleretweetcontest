<?php
	// This is the bot code.
	
	require_once('../vendor/autoload.php');

	$cli = new Cli(array(
		'tracktweet:' => 'This lets you add a tweet to the tracker.',
		'processtweets' => 'Processes all retweets of tracked tweets',
		'processfollowers' => 'Checks to see if processed users are followers',
		'findwinner' => 'This will help specify a winner that is following the main account. (Will not choose any excluded users)',
		'excludewinners' => 'This switch will allow you to exclude all winnners from the picker.'
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
	$time = time();

	if($cli->opt('tracktweet')) {
		$tweetcount = \ORM::for_table('tracktweet')->where('tweetid', $cli->opt('tracktweet', TRUE))->count();

		if($tweetcount === 0) {
			// Add it to the database.
			$track = \ORM::for_table('tracktweet')->create();
			$track->tweetid = $cli->opt('tracktweet', TRUE);
			$track->lasttracked = $time;
			$track->save();
			$cli->print_line('Added tweet to tracker.');
		} else {
			$cli->print_line('Tweet Already Being Tracked');
		}
	}

	if($cli->opt('processtweets')) {
		$retweetcount = 0;
		// Grabbing all of the retweets - First we need to get all of the tracking tweets.
		$tweets = \ORM::for_table('tracktweet')->find_many();

		//var_dump($tweets);
		foreach($tweets as $tweet) {
			/* $retweets = $twitter->statuses_retweets_ID(sprintf('id=%s&limit=100', $tweet->tweetid), true);
			$cli->print_line(sprintf('Processing Retweets for %s Total# %s', $tweet->tweetid, count((array) $retweets))); */
			// Create a loop to check if there are more than 100 retweets on a single status.
			$retweeters = array();
			$req_str = sprintf('id=%s', $tweet->tweetid);
			$cursor = NULL;
			do {
				if(!is_null($cursor)) {
					$req_str .= sprintf('&cursor=%s', $cursor);
				}
				$retweet_gather = $twitter->statuses_retweeters_ids($req_str, true);
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
				$user_search = \ORM::for_table('user')->where('twitterid', $rtid)->find_one();

				if($user_search === false) {
					// Grab the information from the API.
					$user_info = $twitter->users_show(sprintf('user_id=%s', $rtid), true);
					// Now we need to create the user object.
					$user_db = \ORM::for_table('user')->create();
					$user_db->twitterid = $rtid;
					$user_db->username = $user_info->screen_name;
					$user_db->user_object = serialize($user_info);
					$user_db->added = $time;
					$user_db->save();
					$user = $user_db;
					unset($user_db);
					$cli->print_line(sprintf('Added User (%s) to the database', $user->username));
					sleep(5);
				} else {
					$user = $user_search;
					unset($user_search);
				}

				// See if the user is already entered for this tweet.
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
					$cli->print_line(sprintf('Added Entry for User (%s) for Tweet ID#%s', $user->username, $tweet->tweetid));
					unset($entry);
					$retweetcount++;
				}

				// Skip already entered user.
				unset($entry_search);
			}

			// Update time stamp on Tweet tracker.
			$tweet->lasttracked = $time;
			$tweet->save();
		}
	}

	if($cli->opt('processfollowers')) {
		$usercount = 0;
		// Grab all of the retweeters and pass them in to an array.
		$users = \ORM::for_table('user')->select('username')->where('follower', 0)->find_many();
		$user_arr = array();
		foreach($users as $user) {
			$user_arr[] = $user->username;
		}

		// Split array in to 100s.
		foreach(array_chunk($user_arr, 100) as $user_chunk) {
			$following = $twitter->friendships_lookup(sprintf('screen_name=%s', implode(',', $user_chunk)));
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

	$lastrun->value = $time;
	$lastrun->save();