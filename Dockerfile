FROM kibatic/symfony:8.3-fpm-debian AS base

RUN echo "Europe/Paris" > /etc/timezone
ENV TZ="Europe/Paris"

RUN apt-get -qq update > /dev/null && DEBIAN_FRONTEND=noninteractive apt-get -qq -y --no-install-recommends install \
		libpq-dev \
    	git \
    	bash \
    	libcurl4-openssl-dev \
		make > /dev/null && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

#RUN apk add --no-cache \
#		libpq-dev \
#    	libcurl \
#    	curl-dev \
#    	git \
#    	bash \
#		make
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql
RUN docker-php-ext-install \
    	curl \
		pdo_pgsql

# Symfony CLI
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash
RUN apt install symfony-cli


RUN git config --global user.email "dev@localhost"
RUN git config --global user.name "dev"

ADD docker/web/rootfs /


FROM base as web-dev

ENV PERFORMANCE_OPTIM false
ENV APP_ENV dev

RUN pecl install xdebug \
        && docker-php-ext-enable xdebug

#RUN apk add --no-cache \
#    git \
#    openssh > /dev/null \
#    # XDebug
#    && apk add --no-cache --virtual .build-deps ${PHPIZE_DEPS} \
#    && apk add --no-cache linux-headers \
#    && pecl install xdebug \
#    && docker-php-ext-enable xdebug \
#    && apk del .build-deps


FROM base AS web-prod

ADD . /var/www

ENV PERFORMANCE_OPTIM true
