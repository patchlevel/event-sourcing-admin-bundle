help:                                                                           ## shows this help
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_\-\.]+:.*?## / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

vendor: composer.lock
	composer install

.PHONY: phpcs-check
phpcs-check: vendor                                                             ## run phpcs
	vendor/bin/phpcs

.PHONY: phpcs-fix
cs: vendor                                                                      ## run phpcs fixer
	vendor/bin/phpcbf

.PHONY: phpstan
phpstan: vendor                                                                 ## run phpstan static code analyser
	php -d memory_limit=312M vendor/bin/phpstan analyse

.PHONY: phpstan-baseline
phpstan-baseline: vendor                                                        ## run phpstan static code analyser
	php -d memory_limit=312M vendor/bin/phpstan analyse --generate-baseline

.PHONY: infection
infection: vendor                                                               ## run infection
	php -d memory_limit=312M vendor/bin/infection --threads=5

.PHONY: infection-diff
infection-diff: vendor                                                          ## run infection on differences
	php -d memory_limit=312M vendor/bin/infection --threads=max --git-diff-lines --git-diff-base=origin/HEAD --ignore-msi-with-no-mutations --only-covered --min-msi=80 --min-covered-msi=95

.PHONY: phpunit
phpunit: vendor                                                                 ## run phpunit tests
	XDEBUG_MODE=coverage vendor/bin/phpunit

.PHONY: static
static: phpstan phpcs-check                                               ## run static analyser

test: phpunit                                                                   ## run tests

.PHONY: dev
dev: static test                                                                ## run dev tools
