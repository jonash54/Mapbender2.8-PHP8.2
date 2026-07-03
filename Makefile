# Mapbender 2.8 PHP-8 migration — dev workflow.
#
# Quick reference:
#   make up           bring up postgis + both mapbender containers
#   make down         stop everything (keeps DB volume)
#   make clean        stop everything AND drop DB volume (re-seeds on next up)
#   make seed         reset and re-seed the DB
#   make logs         tail logs from all services
#   make shell-legacy bash into the PHP 7.4 container
#   make shell-php8   bash into the PHP 8.2 container
#   make shell-db     psql into the mapbender database
#   make smoke        curl-based sanity check that both versions respond
#
# Migration tooling (runs in the mig-tools container):
#   make phpcs        PHPCompatibility report (saves to migration-tools/reports/)
#   make rector       rector dry-run (saves to migration-tools/reports/)
#   make rector-apply rector apply (modifies sources!)
#   make phpstan      phpstan analysis
#
# Test orchestration (Phase 4 onward):
#   make record       record characterization baseline against mapbender-legacy
#   make test         replay characterization tests against mapbender-php8
#   make test-live    extra suite that hits real external WMS endpoints

# Detect whether the current shell can talk to dockerd directly,
# otherwise fall back to sudo. (Happens when the user's docker-group
# membership was added after the current login session started.)
DOCKER := $(shell docker info >/dev/null 2>&1 && echo "docker" || echo "sudo docker")
DC := $(DOCKER) compose
REPORTS := migration-tools/reports

.PHONY: help up down clean seed logs build rebuild status \
        shell-legacy shell-php8 shell-db smoke \
        phpcs rector rector-apply phpstan \
        record test test-live \
        squash-preview

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'

# -------- lifecycle --------

build: ## (re)build all images
	$(DC) build

up: ## start postgis + php8 + wms-mock (the integration set)
	$(DC) up -d postgis wms-mock mapbender-php8
	@echo
	@echo "  PHP 8.2:          http://127.0.0.1:8091/mapbender/"
	@echo "  WMS mock:         http://127.0.0.1:8092/"
	@echo "  Default login:    root / root"
	@echo
	@echo "  (Migration-only) start the legacy comparison container with:"
	@echo "      make up-with-legacy"
	@echo

up-with-legacy: ## migration developer mode — also start the PHP 7.4 baseline
	$(DC) --profile legacy up -d
	@echo
	@echo "  Legacy (PHP 7.4): http://127.0.0.1:8090/mapbender/"
	@echo "  PHP 8.2:          http://127.0.0.1:8091/mapbender/"
	@echo

down: ## stop everything (keep DB volume)
	$(DC) down

clean: ## stop and drop the DB volume too (forces re-seed)
	$(DC) down -v

rebuild: ## rebuild and restart (no DB reset)
	$(DC) build
	$(DC) up -d --force-recreate mapbender-legacy mapbender-php8

seed: ## drop DB and re-seed from scratch
	$(DC) down
	docker volume rm -f $$($(DC) config --volumes | grep postgis) || true
	$(DC) up -d postgis
	@echo "Waiting for postgis init scripts to finish..."
	@sleep 8
	$(DC) up -d

status: ## list services
	$(DC) ps

logs: ## tail all logs
	$(DC) logs -f --tail=100

# -------- shells / DB --------

shell-legacy: ## bash inside the PHP 7.4 container
	$(DC) exec mapbender-legacy bash

shell-php8: ## bash inside the PHP 8.2 container
	$(DC) exec mapbender-php8 bash

shell-db: ## psql into the mapbender database
	$(DC) exec postgis psql -U mapbenderdbuser -d mapbender

# -------- smoke tests --------

smoke: ## curl both versions for a 200 on a basic page
	@echo "--- legacy ---"
	@curl -sS -o /dev/null -w "  HTTP %{http_code}  size=%{size_download}  time=%{time_total}s\n" http://127.0.0.1:8090/mapbender/ || true
	@echo "--- php8 ---"
	@curl -sS -o /dev/null -w "  HTTP %{http_code}  size=%{size_download}  time=%{time_total}s\n" http://127.0.0.1:8091/mapbender/ || true

# -------- migration tooling (in mig-tools container) --------

$(REPORTS):
	@mkdir -p $(REPORTS)

phpcs: $(REPORTS) ## PHPCompatibility scan -> reports/phpcs.txt
	$(DC) --profile tools run --rm mig-tools \
		phpcs -p \
		--standard=PHPCompatibility \
		--runtime-set testVersion 8.2 \
		--extensions=php \
		--ignore=*/vendor/*,*/node_modules/*,*/migration-tools/*,*/tests/*,*/docker/*,*/.git/* \
		--report-full=migration-tools/reports/phpcs.txt \
		--report-summary \
		. || true
	@echo "Report: $(REPORTS)/phpcs.txt"

rector: $(REPORTS) ## rector --dry-run (no changes)
	$(DC) --profile tools run --rm mig-tools \
		rector process --dry-run --config=migration-tools/configs/rector.php > $(REPORTS)/rector-dry.txt || true
	@echo "Dry-run report: $(REPORTS)/rector-dry.txt"

rector-apply: ## rector apply -- modifies sources!
	$(DC) --profile tools run --rm mig-tools \
		rector process --config=migration-tools/configs/rector.php

phpstan: $(REPORTS) ## phpstan analysis
	$(DC) --profile tools run --rm mig-tools \
		phpstan analyse --memory-limit=2G -c migration-tools/configs/phpstan.neon > $(REPORTS)/phpstan.txt || true
	@echo "Report: $(REPORTS)/phpstan.txt"

# -------- characterization tests (Phase 4 onward) --------

record: ## record baseline against mapbender-legacy
	cd tests/characterization && pnpm exec playwright test --update-snapshots

test: ## replay characterization tests against mapbender-php8
	cd tests/characterization && pnpm exec playwright test

test-live: ## extra suite hitting real WMS endpoints (no network = skip)
	cd tests/characterization && pnpm exec playwright test --grep @live

# -------- finalization --------

squash-preview: ## show what the final squashed diff vs master would look like
	@git --no-pager diff --stat master..HEAD
	@echo "Commits to be squashed:"
	@git --no-pager log --oneline master..HEAD
