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

	// Setup Codebird Twitter stuff.
	$consumer_key = \ORM::for_table('settings')->where('name', 'twitter_consumer_key')->find_one();
	$consumer_secret = \ORM::for_table('settings')->where('name', 'twitter_consumer_secret')->find_one();

	\Codebird\Codebird::setConsumerKey($consumer_key->value, $consumer_secret->value);

	$app->container->singleton('twitter', function() {
		return \Codebird\Codebird::getInstance();
	});

	$user_token = \ORM::for_table('settings')->where('name', 'twitter_access_token')->find_one();
	$user_secret = \ORM::for_table('settings')->where('name', 'twitter_access_token_secret')->find_one();

	$app->twitter->setToken($user_token->value, $user_secret->value);

	$app->get('/', function() use($app) {
		$last_run = \ORM::for_table('settings')->select('value')->where('name', 'last_run')->find_one();
		$last_run_date = date('l, F j, Y h:i:s', $last_run->value);

		$total_entries = \ORM::for_table('entries')->count();

		$total_users = \ORM::for_table('user')->where('exclude', 0)->count();

		$total_followers = \ORM::for_table('user')->where('exclude', 0)->where('follower', 1)->count();

		$not_following = \ORM::for_table('user')->where('exclude', 0)->where('follower', 0)->find_many();

		$users_unfollowing = array();
		foreach($not_following as $user) {
			$users_unfollowing[] = array(
				'username' => $user->username,
				'entry_count' => \ORM::for_table('entries')->where('userid', $user->id)->count()
			);
		}

		$app->render('index.php', array(
			'last_run' => $last_run_date,
			'total_entries' => $total_entries,
			'total_users' => $total_users,
			'total_followers' => $total_followers,
			'users_unfollowing' => $users_unfollowing
		));
	});

	$app->map('/track', function() use($app) {
		
	})->via('GET', 'POST')->name('track');

	$app->run();