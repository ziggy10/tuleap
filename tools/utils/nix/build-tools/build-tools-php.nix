{ pkgs, phpBase }:

let
    php = phpBase.withExtensions ({ enabled, all }: with all; enabled ++ [
        bcmath
        curl
        ctype
        dom
        fileinfo
        filter
        gd
        gettext
        hash
        iconv
        intl
        json
        ldap
        mbstring
        mysqli
        mysqlnd
        opcache
        openssl
        pcntl
        pdo_mysql
        posix
        readline
        session
        simplexml
        soap
        sodium
        tokenizer
        xmlreader
        xmlwriter
        zip
        zlib
        mailparse
        imagick
        sysvsem
        redis
        xsl
    ]);
in
[
    php
    php.packages.composer2
]
