FROM php:8.4-fpm-alpine3.22 AS builder

ENV TZ="UTC"
# Composer no longer allows plugins to run as a superuser as of release 2.7 which prevents us from patching upstream libraries
ENV COMPOSER_ALLOW_SUPERUSER=1

# Alpine no longer ships with a real ICU library but with a cut-down shim, we need the real stuff for templating dates
# Alpine also does not install timezone data by default which we need for Date/Time calculation
# Alpine does not have a patch command by default, we need it when we patch composer dependencies
# See https://github.com/cweagans/composer-patches/issues/27
RUN apk add --no-cache patch && \
    apk add --no-cache icu-dev icu-libs icu-data-full && \
    apk add --no-cache tzdata && \
    apk add --no-cache git && \
    ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# use install-php-extensions to install required php extensions and composer
RUN curl https://github.com/mlocati/docker-php-extension-installer/releases/download/2.7.1/install-php-extensions \
    --location --output /usr/local/bin/install-php-extensions && \
    chmod +x /usr/local/bin/install-php-extensions && \
    /usr/local/bin/install-php-extensions opcache mbstring intl curl dom @composer

RUN mkdir -p /app/public/assets && mkdir -p /app/public/style && mkdir -p /app/patches/
COPY patches/* /app/patches/
COPY composer* /app/
WORKDIR /app
RUN composer install --no-dev --no-interaction --optimize-autoloader --no-cache

# get the deployed sha1 from git but don't keep the git repo in the image
COPY .git  /app/.git
WORKDIR /app
RUN git config --global --add safe.directory /app && git rev-parse HEAD > /app/public/deployed-version.txt && rm -rf .git

RUN apk del git && \
    apk del patch && \
    apk cache clean  && \
    rm /usr/local/bin/install-php-extensions

###

FROM php:8.4-fpm-alpine3.22 AS runner

ENV TZ="UTC"
RUN apk add --no-cache nginx supervisor && \
    apk add --no-cache icu-dev icu-libs icu-data-full && \
    apk add --no-cache tzdata

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN addgroup -g 10001 app && \
    adduser -D -u 10001 -G app app -h /app

# copy compiled libraries and php extensions from builder
COPY --from=builder /usr/lib /usr/lib
COPY --from=builder /usr/local/php /usr/local/php
COPY --from=builder /usr/local/lib/php /usr/local/lib/php
COPY --from=builder /usr/local/etc/php /usr/local/etc/php
COPY --from=builder /usr/local/include/php /usr/local/include/php

# deploy php, nginx, and supervisord configurations
RUN rm /usr/local/etc/php-fpm.d/*
COPY docker/php-fpm.ini /usr/local/etc/php-fpm.d/www.conf
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# prepare for running as the app user
RUN chown -R app:app /app /run /var/lib/nginx /var/log/nginx

# copy source from local, and composer-built assets from builder
COPY --chown=app:app . /app
COPY --from=builder --chown=app:app /app/public/assets/bootstrap /app/public/assets/bootstrap
COPY --from=builder --chown=app:app /app/public/assets/jquery /app/public/assets/jquery
COPY --from=builder --chown=app:app /app/vendor /app/vendor
COPY --from=builder --chown=app:app /app/vendor/benhall14/php-calendar/html/css/calendar.css /app/public/style/
COPY --from=builder --chown=app:app /app/public/deployed-version.txt /app/public/

# configure container
STOPSIGNAL SIGINT
EXPOSE 8000
USER app
WORKDIR /app
HEALTHCHECK --timeout=10s CMD curl --silent --fail http://127.0.0.1:8080/fpm-ping

# dockerflow support
ARG source
ARG version
ARG commit
ARG build
RUN php docker/version.php "$source" "$version" "$commit" "$build" > public/version.json
COPY docker/heartbeat.php /app/public

# run supervisord, which will start nginx and php-fpm
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

RUN apk cache clean