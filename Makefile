#!/usr/bin/make

user_id := $(shell id -u)
docker_compose_bin := $(shell command -v docker-compose 2> /dev/null) --file "docker/docker-compose.yml"
php_container_bin := $(docker_compose_bin) run --rm -u "$(user_id)" "php"
composer_bin := $(php_container_bin) composer run-script

.DEFAULT_GOAL := build

# --- [ Development tasks ] -------------------------------------------------------------------------------------------

build: ## Build container and install composer libs
	$(docker_compose_bin) build --force-rm

install: ## Install all data
	$(php_container_bin) composer update

shell: ## Runs shell in container
	$(php_container_bin) bash

fixer: ## Run fixer to fix code style
	$(composer_bin) fixer

linter: ## Run linter to check project
	$(composer_bin) linter

test: ## Run tests
	$(composer_bin) test

coverage: ## Run tests with coverage
	$(composer_bin) coverage

infection: ## Run infection testing
	$(composer_bin) infection

release: ## Run all preparations before release
	$(composer_bin) fixer
	$(composer_bin) linter
	$(composer_bin) test
	$(composer_bin) infection