FROM wordpress:5.6.1

RUN apt update && apt install -y jq curl zip gzip tar

RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x wp-cli.phar \
    && mv wp-cli.phar /usr/local/bin/wp

RUN mkdir /usr/src/wordpress/wp-content/plugins/woocommerce \
    && curl --output woocommerce.zip  https://downloads.wordpress.org/plugin/woocommerce.4.4.1.zip \
    && unzip woocommerce.zip -d /usr/src/wordpress/wp-content/plugins


RUN mkdir /usr/src/wordpress/wp-content/plugins/billage \
    && curl --output billage.zip   https://www.getbillage.com/descargas/woocommerce/billage.zip \
    && unzip billage.zip -d /usr/src/wordpress/wp-content/plugins/billage



