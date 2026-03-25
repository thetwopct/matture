# Plugin Development

To start our development environment, run `wp-env start`.

For additional environment setup instructions read https://thetwopercent.atlassian.net/wiki/x/AgA3Bw

## Install Composer

To install composer packages:

```
wp-env run cli --env-cwd=wp-content/plugins/plugin-name composer install
```

## Run Composer Commands

Once installed, you can then run mulitple commands.

To dump autoloader:

```
wp-env run cli --env-cwd=wp-content/plugins/plugin-name composer dump-autoload
```

To run sniffs:

```
wp-env run cli --env-cwd=wp-content/plugins/plugin-name composer run-script sniff
```

To fix sniffs:

```
wp-env run cli --env-cwd=wp-content/plugins/plugin-name composer run-script fix
```

To run static analysis:

```
wp-env run cli --env-cwd=wp-content/plugins/plugin-name composer run-script analyse
```

To run both sniffs and analyse:

```
wp-env run cli --env-cwd=wp-content/plugins/plugin-name composer run-script checks
```

To check PHP Compatability:

```
wp-env run cli --env-cwd=wp-content/plugins/plugin-name composer run-script version-checks
```

# PHP Unit Tests

## Running Tests

**⚠️ IMPORTANT**: Tests are specifically configured for wp-env context only.

```bash
# Run all tests (wp-env context only)
wp-env run tests-cli --env-cwd=wp-content/plugins/plugin-name vendor/bin/phpunit
```

This is the only way to successfully run tests on the tests-cli and using PHPUnit 9.6. The bootstrap file is designed specifically for the wp-env testing environment and will not work correctly outside of this context.

# Prepare for release

Run language POT file generation:

```
wp-env run cli --env-cwd=wp-content/plugins/plugin-name wp i18n make-pot . languages/plugin-name.pot --headers='{"Report-Msgid-Bugs-To":"https://www.ymmv.co"}'
```
