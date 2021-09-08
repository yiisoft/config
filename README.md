<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
        <img src="logo.png" height="100px">
    </a>
    <h1 align="center">Yii Config</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/config/v/stable)](https://packagist.org/packages/yiisoft/config)
[![Total Downloads](https://poser.pugx.org/yiisoft/config/downloads)](https://packagist.org/packages/yiisoft/config)
[![Build status](https://github.com/yiisoft/config/workflows/build/badge.svg)](https://github.com/yiisoft/config/actions)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/config/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/config/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/config/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/config/?branch=master)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fconfig%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/config/master)
[![static analysis](https://github.com/yiisoft/config/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/config/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/config/coverage.svg)](https://shepherd.dev/github/yiisoft/config)

This [Composer](https://getcomposer.org/) plugin provides assembling of configurations distributed with composer
packages. It allows putting configuration needed to use a package right inside thus implementing
a plugin system. The package becomes a plugin holding both the code and its configuration.

## Requirements

- PHP 7.4 or higher.
- Composer 2.0 or higher.

## Installation

```shell
composer require yiisoft/config --prefer-dist
```

## How it works?

The package consist of two parts: Composer plugin and config loader.

After composer updates its autoload file, and that happens after `dump-autoload`, `require`, `update` or `remove`,
Composer plugin:

- Scans installed packages for `config-plugin` extra option in their `composer.json`.
- Writes a merge plan into `config/MERGE_PLAN.php`. It includes configuration from each package `composer.json`.
  
In the application entry point, usually `index.php`, we create an instance of config loader and require a configuration
we need:

```php
use Yiisoft\Config\Config;
use Yiisoft\Config\ConfigPaths;

$config = new Config(
    new ConfigPaths(dirname(__DIR__)),
);

$webConfig = $config->get('web');
```

The `web` in the above is a config group. The config loader obtains it runtime according to the merge plan.
The configurations are loaded as follows:

- First, the default package configurations are loaded from the vendor.
- Then the root package configurations are loaded.
- If there is a key match, the configuration of the root package will overwrite
  the configuration of the package from the vendor.

> Please note that there is no full-fledged merge. The configuration of the root package will replace
> the configuration of the package from the vendor only once. If there are more than one matches,
> an exception about duplicate keys will be thrown.

## Config groups

Each config group represents a set of configs that is merged into a single array. It is defined per package in
each package `composer.json`:

```json
"extra": {
    "config-plugin": {
        "params": [
            "params.php",
            "?params-local.php"
        ],
        "common": "config/common.php",
        "web": [
            "$common",
            "web.php",
            "../src/Modules/*/config/web.php"
        ],
        "other": "other.php"
    }
},
```

### Markers 

- `?` - marks optional files. Absence of files not marked with it will cause exception.
    ```
    "params": [
       "params.php",
       "?params-local.php"
    ]
    ```
  It's okay if `params-local.php` will not found, but it's not okay if `params.php` will be absent.
  
- `*` - marks wildcard path. It means zero or more matches by wildcard mask.
  ```
  "web": [
     "../src/Modules/*/config/web.php"
  ]
  ```
  It will collect all `web.php` in any sub-folders of `src/Modules/` in `config` folder.

- `$` - reference to another config.
  ```
  "params": [
     "params.php",
     "?params-local.php"
  ],
  "params-console": [
     "$params",
     "params-console.php"
  ],
  "params-web": [
     "$params",
     "params-web.php"
  ]
  ```
  Output files `params-console.php` and `params-web.php` will contain `params.php` and `params-local.php`.

***

Define your configs like the following:

```php
<?php

return [
    'components' => [
        'db' => [
            'class' => \my\Db::class,
            'name' => $params['db.name'],
            'password' => $params['db.password'],
        ],
    ],
];
```

A special variable `$params` is read from `params` config.

### Using sub-configs

In order to access a sub-config, use the following in your config:

```php
'routes' => $config->get('routes');
```

## Options

A number of options is available both for Composer plugin and a config loader. Composer options are specified in
`composer.json`:

```json
"extra": {
    "config-plugin-options": {
      "source-directory": "config",
    },
    "config-plugin": {
        // ...
    },

},
```

the `source-directory` specifies where to read the configs for the package for which the option is specified.
The option is available for reading for all packages, including the root package, which is typically an application.
The value is a path relative to where the `composer.json` file is located. The default value is `config`.

If you are changing the source directory for the root package, don't forget to adjust configs path when creating
an instance of `Config`. Usually that is `index.php`:

```php
use Yiisoft\Config\Config;
use Yiisoft\Config\ConfigPaths;

$config = new Config(
    new ConfigPaths(dirname(__DIR__), 'path/to/config/directory'),
);

$webConfig = $config->get('web');
```

## Environments

The plugin supports creating additional environments added to the base configuration. This allows you to create
multiple configurations for the application such as `production` and `development`.

> The environment configuration options are added to the main configuration options, but do not replace them.

The environments are specified in the `composer.json` file of your application:

```json
"extra": {
    "config-plugin-options": {
        "source-directory": "config",
    },
    "config-plugin": {
        "params": "params.php",
        "web": "web.php",
    },
    "config-plugin-environments": {
        "dev": {
            "params": "dev/params.php",
            "app": [
                "$web",
                "dev/app.php"
            ]
        },
        "prod": {
            "app": "prod/app.php"
        }
    }
},
```

Configuration defines the merge process. One of the environments from `config-plugin-environments`
is merged with the main configuration defined by `config-plugin`. In given example, in the `dev` environment
we use `$web` configuration from the main environment.

This configuration has the following structure:

```
config/             Configuration root directory.
    dev/            Development environment files.
        app.php     Development environment app group configuration.
        params.php  Development environment parameters.
    prod/           Production environment files.
        app.php     Production environment app group configuration.
    params.php      Main configuration parameters.
    web.php         Мain configuration web group configuration.
```

To choose an environment to be used you must specify its name when creating an instance of `Config`:

```php
use Yiisoft\Config\Config;
use Yiisoft\Config\ConfigPaths;

$config = new Config(
    new ConfigPaths(dirname(__DIR__)),
    'dev',
);

$appConfig = $config->get('app');
```

If defined in an environment, `params` will be merged with `params` from the main configuration,
and could be used as `$params` in all configurations.

## Recursive merge of arrays

By default, recursive merging of arrays in configuration files is not performed. If you want to recursively merge
arrays in a certain group of configs, such as params, you must pass group names to the `Config` constructor:

```php
use Yiisoft\Config\Config;
use Yiisoft\Config\ConfigPaths;

$config = new Config(
    new ConfigPaths(dirname(__DIR__)),
    'dev',
    [
        'params',
        'events',
        'events-web',
        'events-console',
    ],
);

$appConfig = $config->get('events-web'); // merged recursively
```

## Commands

The plugin adds extra `config-copy` command to composer. It copies the package config files from the vendor
to the config directory of the root package:

```shell
composer config-copy <package-name> [target-path] [files]
```

Copies all config files of the `yiisoft/view` package:

```shell
# To the `config` directory
composer config-copy yiisoft/view

# To the `config/my/path` directory
composer config-copy yiisoft/view my/path
```

Copies the specified config files of the `yiisoft/view` package:

```shell
# To the `config` directory
composer config-copy yiisoft/view / params.php web.php

# To the `config/my/path` directory and without the file extension
composer config-copy yiisoft/view my/path params web
```

In order to avoid conflicts with file names, a prefix is added to the names of the copied files:
`yiisoft-view-params.php`, `yiisoft-view-web.php`.

## Testing

### Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```shell
./vendor/bin/phpunit --testdox --no-interaction
```

### Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework with
[Infection Static Analysis Plugin](https://github.com/Roave/infection-static-analysis-plugin). To run it:

```shell
./vendor/bin/roave-infection-static-analysis-plugin
```

### Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```shell
./vendor/bin/psalm
```

## License

The config package is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).

## Credits

The plugin is heavily inspired by [Composer config plugin](https://github.com/yiisoft/composer-config-plugin)
originally created by HiQDev (http://hiqdev.com/) in 2016 and then adopted by Yii.

## Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

## Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)
