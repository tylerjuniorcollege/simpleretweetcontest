
DROP TABLE IF EXISTS "entries";
CREATE TABLE "entries" (
  "userid" integer NOT NULL,
  "tweetid" integer NOT NULL,
  "added" integer NOT NULL,
  FOREIGN KEY ("userid") REFERENCES "user" ("userid"),
  FOREIGN KEY ("tweetid") REFERENCES "tracktweet" ("id")
);


DROP TABLE IF EXISTS "settings";
CREATE TABLE "settings" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "name" text NOT NULL,
  "value" text NOT NULL
);


DROP TABLE IF EXISTS "tracktweet";
CREATE TABLE "tracktweet" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "tweetid" integer NOT NULL,
  "lasttracked" integer NOT NULL
);


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

INSERT INTO "settings" ("name", "value") VALUES ('twitter_consumer_key', '');
INSERT INTO "settings" ("name", "value") VALUES ('twitter_consumer_secret', '');
INSERT INTO "settings" ("name", "value") VALUES ('twitter_access_token', '');
INSERT INTO "settings" ("name", "value") VALUES ('twitter_access_token_secret', '');
INSERT INTO "settings" ("name", "value") VALUES ('last_run', '');
INSERT INTO "settings" ("name", "value") VALUES ('twitter_username')