# Matomo on Heroku with AWS RDS

Optimised for multiple workers and auto archiving.

Based on [creativecoder/piwik-heroku](https://github.com/creativecoder/piwik-heroku) and [«Setting up Piwik on Heroku»](https://medium.com/@joshuaestes/setting-up-piwik-on-heroku-5438e36dc4ca) by Joshua Estes.

We check in the whole Matomo code base since the composer package is broken—[at least for 3.8.1 to 3.9.1](https://github.com/creativecoder/piwik-heroku/issues/6). If you want to use this and install new plugins you should fork it.

## Setup a Deploy

Prerequisite: [Heroku CLI](https://devcenter.heroku.com/articles/heroku-cli)

1. `heroku apps:create my-matomo --region eu`
    - `heroku buildpacks:add --index 1 https://github.com/danstiner/heroku-buildpack-geoip-geolite2`
    - `heroku buildpacks:add --index 2 heroku/php`
2. Add a `MYSQL_URL`, e.g. AWS RDS
3. Add a `REDIS_URL`, e.g. `heroku addons:create heroku-redis:premium-1`
4. `heroku config:set SALT=XXXXXXX TRUSTED_HOST=my-matomo.herokuapp.com MAXMIND_LICENSE_KEY=XXXXXXX`
5. `git push heroku`

You'll need to [obtain a free MaxMind key](https://www.maxmind.com/en/accounts/current/license-key) for GeoIP.

## Config

`generate.config.ini.php` is always run before starting the app on Heroku. Ensuring the environment changes are always reflected.

## Running Locally

Prerequisite: PHP, phpredis

```
brew install php
pecl install redis
```

### Local Config

Run `generate.config.ini.php` with inline envs:

```bash
REDIS_URL=redis://127.0.0.1:6379 \
MYSQL_URL=mysql://root:@localhost:3306/piwik \
TRUSTED_HOST=localhost:8000 \
SALT=XXXXXXX \
php ./generate.config.ini.php
```

### Start a Server

```bash
php -S 0.0.0.0:8000 -t matomo/
```

## Queued Tracking

`Process during tracking request` should be disabled in `System -> General Settings # QueuedTracking`. And a scheduler or dedicated worker should be used to process the queue.

This is because Heroku generally has a 30s timeout for requests and by default also for php-fpm script execution time—the processing will be aborted if it exceeds 30s.

### Scheduler

Use the «Heroku Scheduler» addon and setup a job to run the following command every 10 minutes with an performance-l dyno:

```bash
php ./generate.config.ini.php && php -d memory_limit=14G ./matomo/console queuedtracking:process
```

### Monitor

```bash
heroku run "php ./generate.config.ini.php && ./matomo/console queuedtracking:monitor"
```

### Alternative: Raise Timeout

One could also raise the php-fpm execution limit in `fpm_custom.conf`:

```
request_slowlog_timeout = 25s
request_terminate_timeout = 5m
```

However this will still produce warnings in the logs, high response times and possibly timeouts in the Heroku metrics.

## Archiving

### Rebuild all reports

```bash
# invalidate all reports via Settings -> System -> Invalidate reports
# run detached to avoid timeout
heroku run:detached --size=performance-l "php ./generate.config.ini.php && php -d memory_limit=14G ./matomo/console core:archive --force-all-websites --php-cli-options=\"-d memory_limit=14G\" --concurrent-requests-per-website=8"
heroku ps # get run number, e.g. 1
# follow logs
heroku logs --dyno run.1 -t
# stop if needed
heroku ps:stop run.1
```

See [Matomo docs](https://matomo.org/docs/setup-auto-archiving/) for more options.

### Scheduler

Use the «Heroku Scheduler» addon and setup a job to run the following command every hour with an performance-l dyno:

```bash
php ./generate.config.ini.php && php -d memory_limit=14G ./matomo/console core:archive --force-periods="day,week" --force-date-last-n=2 --php-cli-options="-d memory_limit=14G"
```

And following command every night at e.g. 00:30 UTC with an performance-l dyno:

```bash
php ./generate.config.ini.php && php -d memory_limit=14G ./matomo/console core:archive --php-cli-options="-d memory_limit=14G"
```

## Plugins

Run it locally and install via the interface.

Afterwards synch the newly added or removed plugins manually to `Plugins[]` and `PluginsInstalled[]` in `generate.config.ini.php`. Commit the file system changes and deploy.

## GeoIP

This setup is configured to use the GeoIp2 plugin included in the core Matomo package. The GeoLite databases are downloaded on every deploy using [danstiner/heroku-buildpack-geoip-geolite2](https://github.com/danstiner/heroku-buildpack-geoip-geolite2).

You can turn on this geolocation method on in Settings > System > Geolocation.

## Updating

Download zip from matomo and merge extract it:

```bash
wget https://builds.matomo.org/matomo.zip
unzip -o matomo.zip
rm matomo.zip "How to install Matomo.html"
```

For plugins you can merge with e.g. `ditto`:

```bash
ditto ~/Downloads/CustomDimensions ~/Code/matomo/matomo/plugins/CustomDimensions
```

Run locally after update and do a system check:

```bash
php -S 0.0.0.0:8000 -t matomo/
open http://localhost:8000/index.php?module=Installation&action=systemCheckPage
```

It will often report files that can be removed after an update. PHP config issues and archiving completion can be ignored locally.

### Migrating Production

Watch out: big migration may take longer in production than locally.

Before deploying you can put the admin interface into maintenance mode by setting `MAINTENANCE_MODE=1`:

```
# faster env switching
heroku features:disable preboot
heroku config:set MAINTENANCE_MODE=1
git push production
```

Once the code is deployed you should disable tracking by setting the following env:

```
heroku config:set DISABLE_TRACKING=1
```

And then start the migration with a detached one-off dyno:

```bash
# run detached to avoid timeout
heroku run:detached --size=performance-l "php ./generate.config.ini.php && php -d memory_limit=14G /app/matomo/console core:update --yes"
heroku ps # get run number, e.g. 1
# follow logs
heroku logs --dyno run.1 -t
# stop if needed
heroku ps:stop run.1
```

After the migration remove the `MAINTENANCE_MODE` and `DISABLE_TRACKING` env. For minor migrations you may be able to skip `MAINTENANCE_MODE` and `DISABLE_TRACKING`.

```
heroku config:unset MAINTENANCE_MODE DISABLE_TRACKING
# re-enable for seemless deploys without or small migrations
heroku features:enable preboot
```
