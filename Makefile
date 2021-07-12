COMPOSE=docker-compose
PHP=$(COMPOSE) exec php
CONSOLE=$(PHP) bin/console
COMPOSER=$(PHP) composer

up:
	@${COMPOSE} up -d

down:
	@${COMPOSE} down

clear:
	@${CONSOLE} cache:clear
phpunit:
	@${PHP} bin/phpunit

migration:
	@${CONSOLE} make:migration

migrate:
	@${CONSOLE} doctrine:migrations:migrate

fixtload:
	@${CONSOLE} doctrine:fixtures:load
