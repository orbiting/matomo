# Matomo

Optimised for multiple workers and auto archiving.

## Setup a Deploy

[Install the heroku cli](https://devcenter.heroku.com/articles/heroku-cli).

1. `heroku apps:create my-matomo --region eu`
  - `heroku buildpacks:add --index 1 https://github.com/danstiner/heroku-buildpack-geoip-geolite2`
  - `heroku buildpacks:add --index 2 heroku/php`
2. Add a `MYSQL_URL`, e.g. Amazon RDS
3. Add a `REDIS_URL`, e.g. `heroku addons:create heroku-redis:premium-1`
4. `heroku config:set SALT=XXXXXXX TRUSTED_HOST=my-matomo.herokuapp.com`
5. `git push heroku`

## Config

`generate.config.ini.php` is run before starting the app.

### Local Config

```bash
REDIS_URL=redis://127.0.0.1:6379 \
MYSQL_URL=mysql://root:@localhost:3306/piwik \
TRUSTED_HOST=localhost:8000 \
SALT=XXXXXXX \
php ./generate.config.ini.php
```

### Running Locally

```bash
php -S 0.0.0.0:8000 -t matomo/
```

## GeoIP

This setup is configured to use the GeoIp2 plugin included in the core Matomo package. The GeoLite databases are downloaded using a custom buildpack https://github.com/danstiner/heroku-buildpack-geoip-geolite2 defined in `.buildpacks`.

You can turn on this geolocation method on in Settings > System > Geolocation. Rebuilding the app will get a fresh copy of the GeoLite databases. You can also configure the plugin to download an updated database periodically.
