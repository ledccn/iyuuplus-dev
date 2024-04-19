FROM alpine:3.19

LABEL Maintainer="david <367013672@qq.com>"
LABEL Description="IYUUPlus-dev container with PHP ^8.3 based on Alpine Linux."
LABEL Version="8.3"

# 使用国内镜像（mirrors.aliyun.com ｜ mirrors.ustc.edu.cn）
ARG package_url=mirrors.ustc.edu.cn
RUN if [ $package_url ] ; then sed -i "s/dl-cdn.alpinelinux.org/${package_url}/g" /etc/apk/repositories ; fi

ENV PS1="\[\e[32m\][\[\e[m\]\[\e[36m\]\u \[\e[m\]\[\e[37m\]@ \[\e[m\]\[\e[34m\]\h\[\e[m\]\[\e[32m\]]\[\e[m\] \[\e[37;35m\]in\[\e[m\] \[\e[33m\]\w\[\e[m\] \[\e[32m\][\[\e[m\]\[\e[37m\]\d\[\e[m\] \[\e[m\]\[\e[37m\]\t\[\e[m\]\[\e[32m\]]\[\e[m\] \n\[\e[1;31m\]$ \[\e[0m\]" \
    LANG="C.UTF-8" \
    TZ="Asia/Shanghai" \
    APP_ENV=prod \
    IYUU_REPO_URL="https://gitee.com/ledc/iyuuplus-dev.git"

RUN set -ex && \
    #sed -i 's/dl-cdn.alpinelinux.org/mirrors.aliyun.com/' /etc/apk/repositories && \
    #sed -i 's/dl-cdn.alpinelinux.org/mirrors.ustc.edu.cn/' /etc/apk/repositories && \
    apk add --no-cache \
        curl \
        bash \
        openssl \
        wget \
        zip \
        unzip \
        tzdata \
        git \
        libressl \
        tar \
        s6-overlay \
        php83 \
        php83-cli \
        php83-bcmath \
        php83-curl \
        php83-dom \
        php83-mbstring \
        php83-openssl \
        php83-opcache \
        php83-pcntl \
        php83-pdo \
        php83-pdo_sqlite \
        php83-phar \
        php83-posix \
        php83-simplexml \
        php83-sockets \
        php83-session \
        php83-zip \
        php83-gd \
        php83-mysqli \
        php83-pdo_mysql \
        php83-pecl-event \
        php83-xml && \
    ln -sf /usr/bin/php83 /usr/bin/php && \
    curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php && \
    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer && \
    echo -e "upload_max_filesize=100M\npost_max_size=108M\nmemory_limit=1024M\ndate.timezone=${TZ}\n" > /etc/php83/conf.d/99-overrides.ini && \
    echo -e "[opcache]\nopcache.enable=1\nopcache.enable_cli=1" >> /etc/php83/conf.d/99-overrides.ini && \
    git config --global pull.ff only && \
    git config --global --add safe.directory /iyuu && \
    git clone --depth 1 ${IYUU_REPO_URL} /iyuu && \
    rm -rf /var/cache/apk/* /tmp/*

COPY --chmod=755 ./docker/rootfs /

VOLUME [ "/iyuu" ]

ENTRYPOINT ["/init"]
