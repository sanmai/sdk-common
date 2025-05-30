.PHONY: ci test prerequisites

# Use any most recent PHP version
PHP=$(shell which php)
PHPDBG=$(shell which phpdbg && echo -qrr || echo php)

# Default parallelism
JOBS=$(shell nproc)

# Default silencer if installed
SILENT=$(shell which chronic)

# PHP CS Fixer
PHP_CS_FIXER=vendor/bin/php-cs-fixer
PHP_CS_FIXER_ARGS=--cache-file=build/cache/.php_cs.cache --verbose
export PHP_CS_FIXER_IGNORE_ENV=1

# PHPUnit
PHPUNIT=vendor/bin/phpunit
PHPUNIT_COVERAGE_CLOVER=--coverage-clover=build/logs/clover.xml
PHPUNIT_GROUP=default
PHPUNIT_ARGS=--coverage-xml=build/logs/coverage-xml --log-junit=build/logs/junit.xml $(PHPUNIT_COVERAGE_CLOVER)
export XDEBUG_MODE=coverage

# Phan
PHAN=vendor/bin/phan
PHAN_ARGS=-j $(JOBS) --allow-polyfill-parser
PHAN_PHP_VERSION=7.3
export PHAN_DISABLE_XDEBUG_WARN=1

# PHPStan
PHPSTAN=vendor/bin/phpstan
PHPSTAN_ARGS=analyse src tests --level=2 -c .phpstan.neon

# Psalm
PSALM=vendor/bin/psalm
PSALM_ARGS=--show-info=false
PSALM_PHP_VERSION="PHP 7.3"

# Composer
COMPOSER=$(PHP) $(shell which composer)

# Infection
INFECTION=vendor/bin/infection
MIN_MSI=100
MIN_COVERED_MSI=100
INFECTION_ARGS=--min-msi=$(MIN_MSI) --min-covered-msi=$(MIN_COVERED_MSI) --threads=$(JOBS) --coverage=build/logs --log-verbosity=default --show-mutations --no-interaction
INFECTION_PHP_VERSION="PHP 7.3"

all: test

##############################################################
# Continuous Integration                                     #
##############################################################

ci-test: SILENT=
ci-test: prerequisites $(PHPUNIT)
	$(SILENT) $(PHP) $(PHPUNIT) $(PHPUNIT_COVERAGE_CLOVER) --verbose --group=$(PHPUNIT_GROUP)

ci-analyze: SILENT=
ci-analyze: prerequisites ci-phpunit ci-infection ci-phan ci-phpstan ci-psalm ci-git-archive

ci-phpunit: ci-cs $(PHPUNIT)
	$(SILENT) $(PHPDBG) $(PHPUNIT) $(PHPUNIT_ARGS)

ci-infection: ci-phpunit
	$(SILENT) $(PHP) $(INFECTION) $(INFECTION_ARGS)

ci-phan: ci-cs
	$(SILENT) $(PHP) $(PHAN) $(PHAN_ARGS)

ci-phpstan: ci-cs
	$(SILENT) $(PHP) $(PHPSTAN) $(PHPSTAN_ARGS) --no-progress || true

ci-psalm: ci-cs
	$(SILENT) $(PHP) $(PSALM) $(PSALM_ARGS) --no-cache

ci-cs: prerequisites
	$(SILENT) $(PHP) $(PHP_CS_FIXER) $(PHP_CS_FIXER_ARGS) --dry-run --stop-on-violation fix

ci-git-archive:
	@if ! git archive --format=tar HEAD | tar t | grep -q tests/Common; then echo "tests/Common not found in the release archive"; exit 1; fi
	@if git archive --format=tar HEAD | tar t | grep tests | tail -n +2 | grep -qv Common; then echo "Found files that should not belong to the release archive"; exit 1; fi

##############################################################
# Development Workflow                                       #
##############################################################

.PHONY: test
test: analyze phpunit composer-validate

.PHONY: composer-validate
composer-validate: test-prerequisites
	$(SILENT) $(COMPOSER) validate --strict

.PHONY: test-prerequisites
test-prerequisites: prerequisites composer.lock

.PHONY: phpunit
phpunit: cs
	$(SILENT) $(PHP) $(PHPUNIT) $(PHPUNIT_ARGS) --verbose
	CI=true $(SILENT) $(PHP) $(INFECTION) $(INFECTION_ARGS)

.PHONY: analyze
analyze: phan phpstan psalm

.PHONY: phan
phan: cs
	$(SILENT) $(PHP) $(PHAN) $(PHAN_ARGS) --color

.PHONY: phpstan
phpstan: cs
	$(SILENT) $(PHP) $(PHPSTAN) $(PHPSTAN_ARGS)

.PHONY: psalm
psalm: cs
	$(SILENT) $(PHP) $(PSALM) $(PSALM_ARGS)

.PHONY: cs
cs: test-prerequisites
	$(SILENT) $(PHP) $(PHP_CS_FIXER) $(PHP_CS_FIXER_ARGS) --diff fix

##############################################################
# Prerequisites Setup                                        #
##############################################################

# We need both vendor/autoload.php and composer.lock being up to date
.PHONY: prerequisites
prerequisites: report-php-version build/cache vendor/autoload.php .phan composer.lock

$(PHPUNIT): prerequisites

# Do install if there's no 'vendor'
vendor/autoload.php:
	$(SILENT) $(COMPOSER) install --prefer-dist

# If composer.lock is older than `composer.json`, do update,
# and touch composer.lock because composer not always does that
composer.lock: composer.json
	$(SILENT) $(COMPOSER) update --no-interaction && touch composer.lock

.phan:
	$(PHP) $(PHAN) --init --init-level=1 --init-overwrite --target-php-version=$(PHAN_PHP_VERSION) > /dev/null

build/cache:
	mkdir -p build/cache

.PHONY: report-php-version
report-php-version:
	# Using $(PHP)

##############################################################
# Quick development testing procedure                        #
##############################################################

PHP_VERSIONS=php7.3 php7.4

.PHONY: quick
quick:
	make --no-print-directory -j test-all

.PHONY: test-all
test-all: $(PHP_VERSIONS)

.PHONY: $(PHP_VERSIONS)
$(PHP_VERSIONS): cs
	@make --no-print-directory PHP=$@ PHP_CS_FIXER=/bin/true

.PHONY: docs
docs:
	mkdocs serve
