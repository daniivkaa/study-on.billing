COMPOSE=docker-compose
PHP=$(COMPOSE) exec php
CONSOLE=$(PHP) bin/console
COMPOSER=$(PHP) composer
TESTURL = "UserTest.php"

up:
	@${COMPOSE} up -d

down:
	@${COMPOSE} down

clear:
	@${CONSOLE} cache:clear
phpunit:
	@${PHP} bin/phpunit

up-test:
	@${PHP} bin/phpunit -d memory_limit=2G tests/$(TESTURL)

migration:
	@${CONSOLE} make:migration

migrate:
	@${CONSOLE} doctrine:migrations:migrate

fixtload:
	@${CONSOLE} doctrine:fixtures:load

start:
	@${COMPOSE} start

stop:
	@${COMPOSE} stop