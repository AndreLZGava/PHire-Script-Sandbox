CONTAINER_NAME=phirescript_app
PHP_BIN=docker exec -it $(CONTAINER_NAME) php

RUN_ARGS := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
$(eval $(RUN_ARGS):;@:)

SUBTREE_PREFIX ?= phirescript
SUBTREE_REMOTE ?= origin
SUBTREE_BRANCH ?= main

.PHONY: up down ssh init build debug snapshot watch subtree-push subtree-pull

up:
	docker-compose up -d

down:
	docker-compose down

ssh:
	docker exec -it $(CONTAINER_NAME) /bin/bash

init:
	$(PHP_BIN) phirescript/bin/init

build:
	$(PHP_BIN) phirescript/bin/build

validate:
	$(PHP_BIN) phirescript/bin/validate

debug:
	$(PHP_BIN) phirescript/bin/debug $(RUN_ARGS)

snapshot:
	$(PHP_BIN) phirescript/bin/snapshot

watch:
	$(PHP_BIN) phirescript/bin/watch

subtree-push:
	@echo "Sending subtree $(SUBTREE_PREFIX)..."
	git subtree push --prefix $(SUBTREE_PREFIX) $(SUBTREE_REMOTE) $(SUBTREE_BRANCH)

subtree-pull:
	@echo "Geting subtree $(SUBTREE_PREFIX)..."
	git subtree pull --prefix $(SUBTREE_PREFIX) $(SUBTREE_REMOTE) $(SUBTREE_BRANCH) --squash
