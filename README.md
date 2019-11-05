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
4. `heroku config:set SALT=XXXXXXX TRUSTED_HOST=my-matomo.herokuapp.com`
5. `git push heroku`

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

## Archiving

### Rebuild all reports

```bash
# invalidate all reports via Settings -> System -> Invalidate reports
# run detached to avoid timeout
heroku run:detached --size=performance-l "php ./generate.config.ini.php && php -d memory_limit=14G ./matomo/console core:archive --force-all-websites --force-all-periods=315576000 --force-date-last-n=1000 --php-cli-options=\"-d memory_limit=14G\" --concurrent-requests-per-website=8"
heroku ps # get run number, e.g. 1
# follow logs
heroku logs --dyno run.1 -t
# stop if needed
heroku ps:stop run.1
```

See [Matomo docs](https://matomo.org/docs/setup-auto-archiving/) for more options.

### Scheduler

Add the «Heroku Scheduler» addon and setup a job to run the following command every hour with an performance-l dyno:

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
rm matomo.zip
```

For plugins you can merge with e.g. `ditto`:

```bash
ditto ~/Downloads/CustomDimensions ~/Code/matomo/matomo/plugins/CustomDimensions
```
