DELETE FROM "sqlite_sequence";

DROP TABLE IF EXISTS "entries";
CREATE TABLE "entries" (
  "userid" integer NOT NULL,
  "tweetid" integer NOT NULL,
  "added" integer NOT NULL,
  FOREIGN KEY ("userid") REFERENCES "user" ("id"),
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
  "campaignid" integer NOT NULL,
  "lasttracked" integer NOT NULL,
  FOREIGN KEY ("campaignid") REFERENCES "campaign" ("id")
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

DROP TABLE IF EXISTS "user_winner";
CREATE TABLE "user_winner" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "userid" integer NOT NULL,
  "campaignid" integer NOT NULL,
  FOREIGN KEY ("userid") REFERENCES "user" ("id"),
  FOREIGN KEY ("campaignid") REFERENCES "campaign" ("id")
);

INSERT INTO "settings" ("name", "value") VALUES ('twitter_consumer_key', '');
INSERT INTO "settings" ("name", "value") VALUES ('twitter_consumer_secret', '');
INSERT INTO "settings" ("name", "value") VALUES ('twitter_access_token', '');
INSERT INTO "settings" ("name", "value") VALUES ('twitter_access_token_secret', '');
INSERT INTO "settings" ("name", "value") VALUES ('last_run', '');
INSERT INTO "settings" ("name", "value") VALUES ('twitter_username', '');
INSERT INTO "settings" ("name", "value") VALUES ('winner_default_limit', '');
INSERT INTO "settings" ("name", "value") VALUES ('copyright', '');

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

CREATE TABLE "cron_messages" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "timestamp" integer NOT NULL,
  "json_dump" text NOT NULL
);