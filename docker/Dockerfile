FROM alpine:3.21

LABEL Maintainer="david <367013672@qq.com>"
LABEL Description="IYUUPlus-dev container with PHP ^8.3 + nginx + mysql based on Alpine ^3.21 Linux."
LABEL Version="8.3.2"
LABEL Release="20250101"

# 使用国内镜像（mirrors.aliyun.com ｜ mirrors.ustc.edu.cn）
ARG package_url=mirrors.ustc.edu.cn
RUN if [ $package_url ] ; then sed -i "s/dl-cdn.alpinelinux.org/${package_url}/g" /etc/apk/repositories ; fi

ENV PS1="\[\e[32m\][\[\e[m\]\[\e[36m\]\u \[\e[m\]\[\e[37m\]@ \[\e[m\]\[\e[34m\]\h\[\e[m\]\[\e[32m\]]\[\e[m\] \[\e[37;35m\]in\[\e[m\] \[\e[33m\]\w\[\e[m\] \[\e[32m\][\[\e[m\]\[\e[37m\]\d\[\e[m\] \[\e[m\]\[\e[37m\]\t\[\e[m\]\[\e[32m\]]\[\e[m\] \n\[\e[1;31m\]$ \[\e[0m\]" \
    LANG="C.UTF-8" \
    TZ="Asia/Shanghai" \
    S6_SERVICES_GRACETIME=30000 \
    S6_KILL_GRACETIME=60000 \
    S6_CMD_WAIT_FOR_SERVICES_MAXTIME=0 \
    S6_SYNC_DISKS=1 \
    COMPOSER_ALLOW_SUPERUSER=1 \
    APP_ENV=prod \
    IYUU_REPO_URL="https://gitee.com/ledc/iyuuplus-dev.git"

# 安装PHP环境
RUN set -ex && \
    apk add --no-cache \
        ca-certificates \
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
        php83-bz2 \
        php83-bcmath \
        php83-curl \
        php83-dom \
        php83-fileinfo \
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
        php83-sodium \
        php83-sqlite3 \
        php83-zip \
        php83-gd \
        php83-mysqli \
        php83-pdo_mysql \
        php83-pecl-event \
        php83-xml \
        nginx \
        nginx-mod-http-headers-more && \
    ln -sf /usr/bin/php83 /usr/bin/php && \
    curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php && \
    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer && \
    echo -e "upload_max_filesize=100M\npost_max_size=108M\nmemory_limit=1024M\ndate.timezone=${TZ}\n" > /etc/php83/conf.d/99-overrides.ini && \
    echo -e "[opcache]\nopcache.enable=1\nopcache.enable_cli=1" >> /etc/php83/conf.d/99-overrides.ini && \
    rm -rf \
        /var/cache/apk/* \
        /tmp/* \
        /etc/nginx/http.d/*

# 安装数据库 mariadb
RUN set -ex && \
    apk add --no-cache \
        mariadb \
        mariadb-client && \
    rm -rf \
        /var/cache/apk/* \
        /tmp/* \
        /etc/my.cnf.d/mariadb-server.cnf

# 创建目录
RUN set -ex && \
    mkdir /iyuu && \
    git config --global --add pull.rebase false && \
    git config --global --add safe.directory /iyuu

# s6配置
COPY --chmod=755 ./rootfs /

# 暴露端口
EXPOSE 8780

# 文件系统
VOLUME [ "/iyuu" ]
VOLUME [ "/data" ]

ENTRYPOINT ["/init"]
