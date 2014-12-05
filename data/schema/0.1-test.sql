-- Adminer 4.1.0 SQLite 3 dump

DROP TABLE IF EXISTS "campaigns";
CREATE TABLE "campaigns" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "name" text NOT NULL,
  "description" text NOT NULL,
  "cost" integer NOT NULL,
  "start_time" integer NOT NULL,
  "end_time" integer NOT NULL,
  "active" integer(1) NOT NULL DEFAULT '0',
  "created" integer NOT NULL
);

INSERT INTO "campaigns" ("id", "name", "description", "cost", "start_time", "end_time", "active", "created") VALUES (1,	'Coffee for Finals',	'Giveaway of Starbucks for finals week.',	7000,	1417456800,	1417802400,	1,	1417456800);

DROP TABLE IF EXISTS "cron_messages";
CREATE TABLE "cron_messages" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "timestamp" integer NOT NULL,
  "json_dump" text NOT NULL
);


DROP TABLE IF EXISTS "entries";
CREATE TABLE "entries" (
  "userid" integer NOT NULL,
  "tweetid" integer NOT NULL,
  "added" integer NOT NULL,
  FOREIGN KEY ("userid") REFERENCES "user" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY ("tweetid") REFERENCES "tracktweet" ("id") ON DELETE CASCADE ON UPDATE CASCADE
);


DROP TABLE IF EXISTS "settings";
CREATE TABLE "settings" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "name" text NOT NULL,
  "value" text NOT NULL
);

INSERT INTO "settings" ("id", "name", "value") VALUES (1,	'twitter_consumer_key',	'jJSggcSAErrHsMQiK51e2aeB1');
INSERT INTO "settings" ("id", "name", "value") VALUES (2,	'twitter_consumer_secret',	'OVOQeQUCvQX78sa37vpzVDuUbPeeCeGBg3HBMQJDdeQqAF5SlQ');
INSERT INTO "settings" ("id", "name", "value") VALUES (3,	'twitter_access_token',	'148108766-0BMYjFoHbUpyuonzhJu20ovdtHTquTduCmk4CMoF');
INSERT INTO "settings" ("id", "name", "value") VALUES (4,	'twitter_access_token_secret',	'gih8MX3c1uF5VltJk9l2yLhccKCvL3VbQ6x5JdcsjS2bW');
INSERT INTO "settings" ("id", "name", "value") VALUES (5,	'last_run',	'1417819424');
INSERT INTO "settings" ("id", "name", "value") VALUES (6,	'twitter_username',	'TylerJrCollege');
INSERT INTO "settings" ("id", "name", "value") VALUES (7,	'winner_default_limit',	'10');
INSERT INTO "settings" ("id", "name", "value") VALUES (8,	'copyright',	'&copy; 2014, Tyler Junior College');

DROP TABLE IF EXISTS "tracktweet";
CREATE TABLE "tracktweet" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "tweetid" integer NOT NULL,
  "campaignid" integer NOT NULL,
  "lasttracked" integer NOT NULL,
  FOREIGN KEY ("campaignid") REFERENCES "campaigns" ("id") ON DELETE CASCADE ON UPDATE CASCADE
);

INSERT INTO "tracktweet" ("id", "tweetid", "campaignid", "lasttracked") VALUES (1,	538397177144283136,	1,	1417819424);
INSERT INTO "tracktweet" ("id", "tweetid", "campaignid", "lasttracked") VALUES (2,	539175792958525443,	1,	1417819424);
INSERT INTO "tracktweet" ("id", "tweetid", "campaignid", "lasttracked") VALUES (3,	539469647553130496,	1,	1417819424);
INSERT INTO "tracktweet" ("id", "tweetid", "campaignid", "lasttracked") VALUES (4,	538395545262641152,	1,	1417819424);
INSERT INTO "tracktweet" ("id", "tweetid", "campaignid", "lasttracked") VALUES (5,	539824202375647234,	1,	1417819424);
INSERT INTO "tracktweet" ("id", "tweetid", "campaignid", "lasttracked") VALUES (6,	540169771296116737,	1,	1417819424);
INSERT INTO "tracktweet" ("id", "tweetid", "campaignid", "lasttracked") VALUES (7,	540549191773061120,	1,	1417819424);
INSERT INTO "tracktweet" ("id", "tweetid", "campaignid", "lasttracked") VALUES (8,	540913813621854208,	1,	1417819424);
INSERT INTO "tracktweet" ("id", "tweetid", "campaignid", "lasttracked") VALUES (9,	540943073954394112,	1,	1417819424);
INSERT INTO "tracktweet" ("id", "tweetid", "campaignid", "lasttracked") VALUES (10,	540957523017093120,	1,	1417819424);

DROP TABLE IF EXISTS "user";
CREATE TABLE "user" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "twitterid" integer NOT NULL,
  "username" text NOT NULL,
  "user_object" text NOT NULL,
  "follower" integer NOT NULL DEFAULT '0',
  "winner" integer NOT NULL DEFAULT '0',
  "exclude" integer NOT NULL DEFAULT '0',
  "added" integer NOT NULL
);


-- 
