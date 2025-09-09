#!/usr/bin/env bash

MYSQL_ROOT_PASSWORD="SuperSecret123"
TODO_DB_NAME="tododb"
TODO_DB_USER="todouser"
TODO_DB_PASSWORD="TodoPassword1234"
TODO_TABLE_NAME="tasks"

TODO_APP_DIR="/var/www/todo"
DOMAIN_NAME="localhost"
REPO="https://github.com/AnastasiyaGapochkina01/simplest-todo.git"

log() {
    echo -e "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
}

error() {
    echo -e "[$(date '+%Y-%m-%d %H:%M:%S')] - ERROR - $1"
}

check_error() {
    if [ $? -ne 0 ]; then
      error "$1"
      exit 1
    fi
}

if [ "$(id -u)" -ne 0 ]; then
  error "Этот скрипт должен быть запущен с правами root. Используйте sudo $0"
  exit 1
fi

#if ! command -V debconf-set-selections 2>&1 > /dev/null; then
#  error "debconf-set-selections отсутствует. Установите его"
#  exit 1
#fi

log "Начало установки LAMP-стека"
log "Обновление списка пакетов"
apt-get update 2>&1 > /dev/null
check_error "Не удалось обновить список пакетов"

log "Устанавливаем apache2"
apt-get install -y apache2 2>&1 > /dev/null
check_error "Не удалось установить apache2"

log "Включение модуля rewrite"
a2enmod rewrite 2>&1 > /dev/null
check_error "Не удалось включить модуль rewrite"

set -x
debconf-set-selections <<< "mysql-server mysql-server/root_password password $MYSQL_ROOT_PASSWORD"
debconf-set-selections <<< "mysql-server mysql-server/root_password_again password $MYSQL_ROOT_PASSWORD"
set +x

log "Установка MySQL"
apt-get install -y mysql-server mysql-client 2>&1 > /dev/null
check_error "Не удалось установить mysql"

log "Установка PHP"
apt-get install -y php libapache2-mod-php php-mysql php-curl php-gd php-mbstring php-xml php-zip  2>&1 > /dev/null
check_error "Не удалось установить php и/или его модули"

log "Установка phpmyadmin"
DEBIAN_FRONTEND=noninteractive apt-get install -y phpmyadmin 2>&1 > /dev/null
check_error "Не удалось установить phpmyadmin"

log "Настройка phpmyadmin"
ln -sf /usr/share/phpmyadmin /var/www/html/phpmyadmin
cat > /etc/apache2/sites-available/phpmyadmin.conf <<EOF
Alias /phpmyadmin /usr/share/phpmyadmin

<Directory /usr/share/phpmyadmin>
  Options SymLinksIfOwnerMatch
  DirectoryIndex index.php
  AllowOverride All
  Require all granted

  <IfModule mod_php7.c>
    php_admin_value upload_max_filesize 128M
    php_admin_value post_max_size 128M
    php_admin_value memory_limit 256M
  </IfModule>
</Directory>
EOF
a2ensite phpmyadmin 2>&1 > /dev/null
check_error "Не удалось создать конфигурацию для phpmyadmin"

log "Создание БД для todo-app"
set -x
mysql -u root -p"$MYSQL_ROOT_PASSWORD" <<EOF
  create database if not exists $TODO_DB_NAME;
  create user if not exists '$TODO_DB_USER'@'localhost' identified by '$TODO_DB_PASSWORD';
  grant all privileges on $TODO_DB_NAME.* to '$TODO_DB_USER'@'localhost';
  flush privileges;
EOF
set +x
check_error "Не удалось инициализировать БД"

log "Создание таблиц в БД"
mysql -u root -p$MYSQL_ROOT_PASSWORD <<EOF
  use $TODO_DB_NAME;
  create table if not exists $TODO_TABLE_NAME (
    id int auto_increment primary key,
    title varchar(255) not null,
    description text,
    status enum('pending', 'in_progress', 'comleted') default 'pending',
    created_at timestamp default current_timestamp,
    updated_at timestamp default current_timestamp on update current_timestamp
  );
EOF
check_error "Не удалось создать таблицу"

log "Запуск todo-app"
log "Клонирование проекта"
git clone $REPO $TODO_APP_DIR
check_error "Не удалось склонировать репозиторий"

log "Создание vhost для приложения"
cat > /etc/apache2/sites-available/todo.conf <<EOF
<VirtualHost *:80>
  ServerName $DOMAIN_NAME
  DocumentRoot $TODO_APP_DIR

  <Directory $TODO_APP_DIR>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
  </Directory>

  ErrorLog /var/log/apache2/todo_error.log
</VirtualHost>
EOF
a2ensite todo 2>&1 > /dev/null
a2dissite 000-default.conf 2>&1 > /dev/null
check_error "Не удалось создать vhost"

log "Настройка прав доступа"
chown -R www-data:www-data $TODO_APP_DIR
chmod -R 755 $TODO_APP_DIR

log "Перезапуск apache2"
systemctl restart apache2 2>&1 > /dev/null
check_error "Не удалось перезапустить apache2"

log "Установка LAMP стека и todo завершена"
echo ""
echo "============================================================"
echo "URL for todo app: http://$DOMAIN_NAME"
echo "URL for phpmyadmin: http://$DOMAIN_NAME/phpmyadmin"
echo ""
echo "Доступы к БД"
echo "root:$MYSQL_ROOT_PASSWORD"
echo ""
echo "Доступы к приложению"
echo "${TODO_DB_USER}:$TODO_DB_PASSWORD"
echo "============================================================"
