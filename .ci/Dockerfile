# Première étape, on construit les fichiers nécessaires au client dans une étape
# particulière.
FROM node:12.19 as assets

# On définit que notre projet sera situé dans ce dossier. Par défaut, toutes les
# commandes qui seront exécutées seront effectuées dans ce dossier.
WORKDIR /var/www/html

# On ajoute seulement les fichiers utiles au client, sinon, nous ne pourrons pas
# bénéficier du cache proposé entre chaque commande.
ADD package.json yarn.lock postcss.config.js /var/www/html/

# On installe les dépendences client.
RUN yarn install

# On peut maintenant ajouter les fichiers à construire. S'ils n'ont pas été
# modifiés, le cache fera son job et aucun build ne sera lancé. Nous n'avons que
# deux ressources à déplacer : `resources`, qui contient le code source du
# client et `index.html` qui est l'entrypoint de l'application.
ADD resources /var/www/html/resources
ADD index.html /var/www/html/index.html

# On lance le build.
RUN yarn build

################################################################################

# Maintenant, nous pouvons construire le code PHP et injecter le code construit
# précédemment dans le dossier `/public/dist`.
FROM php:rc-fpm

# On installe NGINX ainsi que Supervisor.
RUN set -xe \
	&& apt update \
	&& apt install -y --no-install-recommends \
		apt-transport-https \
		lsb-release \
		ca-certificates \
		software-properties-common \
		gnupg2 \
		unzip \
		zip \
		git \
	\
	&& sed -i '$adeb http://nginx.org/packages/debian/ buster nginx' /etc/apt/sources.list \
	&& sed -i '$adeb-src http://nginx.org/packages/debian/ buster nginx' /etc/apt/sources.list \
	&& curl http://nginx.org/keys/nginx_signing.key | apt-key add - \
	&& apt update \
	&& apt install nginx supervisor -y \
	&& rm -rf /var/lib/apt/lists/*

# Installation des extensions PHP. Nous n'avons besoin que de PDO ici.
RUN docker-php-ext-install pdo pdo_mysql

# Installation de Composer. Attention à bien faire ça avant de déplacer les
# fichiers, sinon, à chaque build, vous allez re-télécharger Composer (pareil
# pour les extensions, il faut profiter au maximum du cache entre chaque
# exécution).
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --2

# On copie le code source dans /var/www/html. Puis, on récupère les fichiers
# générés dans la première étape du build afin de l'injecter dans `public/dist`.
COPY . /var/www/html
COPY --from=assets /var/www/html/public/dist /var/www/html/public/dist

# Installation de Composer et installation des dépendances. Nous pourrions faire
# une étape intermédiaire permettant que les dépendances soient installées
# seulement en cas de modification (comme pour `assets`).
RUN composer install --prefer-dist --optimize-autoloader --no-dev

# Copy NGINX and Supervisor configuration files.
ADD .ci/supervisord.d/ /etc/supervisor/conf.d
ADD .ci/nginx/conf.d/ /etc/nginx/conf.d
ADD .ci/nginx/nginx.conf /etc/nginx/nginx.conf

# On redirige toutes les sorties de NGINX vers la sortie standard (accessible
# via `docker logs ...`).
RUN set -xe \
	&& ln -sf /dev/stdout /var/log/nginx/access.log \
	&& ln -sf /dev/stderr /var/log/nginx/error.log

# Le conteneur écoute sur le port 80. L'information est purement dans le cadre
# de l'affichage.
EXPOSE 80

# On démare Supervisor (comprenant PHP-FPM et NGINX).
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]

# vim: ft=dockerfile
