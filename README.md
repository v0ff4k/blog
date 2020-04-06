Demo Blog code testing
========================


TODO
------------
 * test admin(lost translate)
 * test accesses, check allow/forbid
 * test all API via postman(60%).
 * add js link to load more comments if length>10 
 * write some test for "proff"


Requirements
------------

  * PHP 5.6 or higher;
  * mySQL PHP extension enabled;
  * correct php.ini config
  * leftHand + rightHand + brain + lucky


#vars(used locally)
* site: blog-demo.loc
* db: test_demo_code_blog
* cache: prefix_seed: blog-code-demo

commandline CLi
------------
```bash
$ clone with dot .
$ if no dot after  `cd` in to your dir
$ chown/chmod correct (below full info)
$ composer install (with or without dev)
$ composer update

### db manage ###

--создание датабейс
$ php bin/console doctrine:database:create

--создаем записи в базе данных
$ php bin/console doctrine:schema:create

--Если были какие-то изменения, в entity или вобще в структуре, обновить базу:
$ php bin/console doctrine:schema:update --force --dump-sql

--   VALIDATE ENTITIES
$ php bin/console doctrine:schema:validate

#Если были какие-то изменения, в entity или вобще в структуре, обновить базу:
$ php bin/console doctrine:schema:update --force --dump-sql

#Если менялись assets/(js|css)/*.(js|css) то обновляем web/build/(js|css)/app.(js|css) 
$ php bin/console assetic:dump --env=prod --no-debug

# обновляем manifest.json (обычно после добавления js/css)
$ yarn encore dev

# устанавливаем/обновляем ссылки на необходимые img/css/js
$ bin/console asset:install

# импорт последнего дампа микро содержимогоб для демонстрации данных
$ php bin/console doctrine:migrations:migrate

# или генерим новые фикстуры
$ php bin/console doctrine:fixtures:load
 
$ php bin/console cache:clear; sudo chown -R www-data:www-data var/
$ php bin/console cache:clear -e prod; sudo chown -R www-data:www-data var/

# меняем права чтобы все было от www-data  или подходящего веб юзера
$ sudo chown -R www-data:www-data ./

# chmod correctly: set permission
$ HTTPDUSER=$(ps axo user,comm | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\  -f1)
$ sudo setfacl -dR -m u:"$HTTPDUSER":rwX -m u:$(whoami):rwX var
$ sudo setfacl -R -m u:"$HTTPDUSER":rwX -m u:$(whoami):rwX var

#for *nix,  dev and prod environment 
$ php bin/console cache:clear ; sudo chown -R www-data:www-data ./
$ php bin/console cache:clear -e prod ; sudo chown -R www-data:www-data ./

# OPTIONAL если необхоимо  посмотреть действующие конфигурации(н-р по swiftmailer):
$ php bin/console debug:config swiftmailer | grep loca

# TESTING  require for dev,  test DIR Utils  OR  specific file  ValidatorTest.php
$ composer require --dev symfony/phpunit-bridge
$ ./vendor/bin/simple-phpunit tests/AppBundle/Utils 
$ ./vendor/bin/simple-phpunit tests/AppBundle/Utils/ValidatorTest.php 

# OPTIONAL install intl lib  for support   see https://symfony.com/doc/3.4/components/intl.html
$ composer require symfony/intl

# portion send emails
$ php bin/console swiftmailer:spool:send --env=dev --message-limit=10
$ php bin/console swiftmailer:spool:send --env=prod --message-limit=10

# test send 1 email.
$ php bin/console swiftmailer:email:send
```

> **NOTE**
>
> any notes goes here )
>
> doctrine2 migrations
> https://symfony.com/doc/master/bundles/DoctrineMigrationsBundle/index.html
>
> /API/ is with FOSRESTBundle
> https://symfony.com/doc/master/bundles/FOSRestBundle/index.html
> nice example, not 100% !
> https://www.cloudways.com/blog/rest-api-in-symfony-3-1/
>
> posts/comments/users generated from fixtures !!!!
>
>
> for new sql versions (//github.com/doctrine/orm/issues/5622)
> /etc/mysql/my.cnf
> add: 
>  
> [mysqld]
> sql-mode=""
> 



MustHave:
---------

Реализовать Блог

 * Должна быть реализована авторизация и регистрация
 
Посты:
   * тайтл
   * слаг
   * контент
   * автор
   * дата публикации
   * комментарии
   * рейтинг
   * метаданные для поисковика(title, keywords, description)
   * is_active
   * ссылка работает как по id/слаг/id-слаг

Пользователи:
   * имя
   * фамилия
   * email
   * пароль
   * комментарии
   * посты
   * token
   * is_active

Комментарии:
   * контент
   * автор
   * пост
   * дата создания
   * is_active

Должна быть админка на сонате, только для супер администратора

Все посты должны быть доступны по человеко-понятному урл


Роли:
админ(и супер админ для админки), 
пользователь. 

Прочее:
    * Пользователи с ролью “админ” могут заходить в админ часть. 
    * Авторизация происходит по email и пароль. 
    * Пользователи не могут входить в админ панель, но могу авторизовываться на сайте. 
    * После авторизации им доступна страница, где они могут добавить или удалить свои посты, а так же комментарии у других постов. 
    * Удалять, редактировать чужие посты они не могут. 
    * При удалении поста, пост должен быть просто не доступным ни для кого, кроме админа, пост должен попадать в “корзину”. 
    * Удаление из базы данных постов, комментариев и пользователей доступно только админу, но сначала они должны попадать в “корзину” перед удалением. 
    * Если пользователь находится в “корзине” он не может быть авторизован.

    * Реализовать API для всего блога. 
    * Должен быть реализован идентичный функционал как на сайте. 
    * Кроме получения постов, метаданные для поисковиков не должны быть в апи
    * Использовать symfony3.4

---------------
