Pre-requisites:
* PHP >= 7.1
* Composer

Run application: 
```
composer install
php index.php
```

Testing:
```
phpunit --bootstrap vendor/autoload.php tests/InputParserTest.php
```