<p align="center">    
    <img src="logo.png" height="126px">
    <h1 align="center">Config</h1>
    <br>
</p>

Composer plugin for config assembling.

[![Latest Stable Version](https://poser.pugx.org/yiisoft/config/v/stable)](https://packagist.org/packages/yiisoft/config)
[![Total Downloads](https://poser.pugx.org/yiisoft/config/downloads)](https://packagist.org/packages/yiisoft/config)
[![Build status](https://github.com/yiisoft/config/workflows/build/badge.svg)](https://github.com/yiisoft/config/actions)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/config/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/config/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/config/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/config/?branch=master)

This [Composer] plugin provides assembling
of configurations distributed with composer packages.
It allows putting configuration needed to use a package right inside of
the package thus implementing a plugin system. The package becomes a plugin
holding both the code and its configuration.

## Installation

```sh
composer require "yiisoft/config"
```

## How it works?

The package consist of two parts: Composer plugin and config loader.

After composer updates its autoload file, and that happens after `dump-autoload`, `require`, `update` or `remove`,
Composer plugin:

- Scans installed packages for `config-plugin` extra option in their `composer.json`.
- Copies missing config files into the project `configs`.
- Writes a merge plan into `config/packages/merge_plan.php`. It includes configuration from each package `composer.json`.
- Tracks change to configuration files from the vendor by storing metadata in the `config/packages/dist.lock` file.
- In the interactive console mode it asks what to do with modified configs after updating packages in the vendor.
  
In the application entry point, usually `index.php`, we create an instance of config loader and require a configuration
we need:

```php
$config = new \Yiisoft\Config\Config(dirname(__DIR__));
$webConfig = $config->get('web');
```

The `web` in the above is a config group. The config loader obtains it runtime according to the merge plan.

## Config groups

Each config group represents a set of configs that is merged into a single array. It is defined per package in
each package `composer.json`:

```json
"extra": {
    "config-plugin": {
        "params": [
            "config/params.php",
            "?config/params-local.php"
        ],
        "common": "config/common.php",
        "web": [
            "$common",
            "config/web.php",
            "../src/Modules/*/config/web.php"
        ],
        "other": "config/other.php"
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
      "output-directory": "/config/packages",
      "source-directory": "/config",
    },
    "config-plugin": {
        // ...
    },

},
```

In the above `output-directory` points to where configs will be copied to. The path is relative to where
`composer.json` is. The option is read for the root package, which is typically an application.
Default is "/config/packages".

If you change output directory, don't forget to adjust configs path when creating an instance of `Config`. Usually
that is `index.php`:

```php
$config = new \Yiisoft\Config\Config(
    dirname(__DIR__),
    '/config/packages', // Configs path.
);
$webConfig = $config->get('web');
```

`source-directory` points to where to read configs from for the package the option is specified for. The option
is read for all packages. The value is a path relative to where package `composer.json` is. Default value is `/config`.

## Commands

The plugin adds extra `config-diff`command to composer. It displays the difference between the vendor and application
configuration files in the console.

```shell
# For all config files
composer config-diff

# For the config files of the specified packages
composer config-diff yiisoft/aliases yiisoft/view
```

## License

The config package is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).

## Credits

The plugin is heavily inspired by [Composer config plugin](https://github.com/yiisoft/composer-config-plugin)
originally created by HiQDev (http://hiqdev.com/) in 2016 and then adopted by Yii.
