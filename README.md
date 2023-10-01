Trombi
======

Opensource trombinoscope

Quick start
-----------

```bash
cp docker-compose.override.sample.yml docker-compose.override.yml
docker-compose up -d
docker-compose exec php composer install
docker-compose exec php bin/console doctrine:migrations:migrate
```

