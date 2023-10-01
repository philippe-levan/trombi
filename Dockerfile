FROM kibatic/symfony:8.2-fpm-alpine AS base

RUN echo "Europe/Paris" > /etc/timezone
ENV TZ="Europe/Paris"

RUN apk add --no-cache \
		libpq-dev \
    	libcurl \
    	curl-dev \
    	git \
    	bash \
		make
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql
RUN docker-php-ext-install \
    	curl \
		pdo_pgsql

# Symfony CLI
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.alpine.sh' | bash
RUN apk add symfony-cli

RUN git config --global user.email "dev@localhost"
RUN git config --global user.name "dev"

ADD docker/web/rootfs /


FROM base as web-dev

ENV PERFORMANCE_OPTIM false
ENV APP_ENV dev

RUN apk add --no-cache \
    git \
    openssh > /dev/null \
    # XDebug
    && apk add --no-cache --virtual .build-deps ${PHPIZE_DEPS} \
    && apk add --no-cache linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps


FROM base AS web-prod

ADD . /var/www

ENV PERFORMANCE_OPTIM true
