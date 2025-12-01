{
    "name": "test/yiisoft-config",
    "version": "1.0.0",
    "minimum-stability": "dev",
    "require": {
        %REQUIRE%
    },
    "extra": %EXTRA%,
    "config": {
        "allow-plugins": {
            "yiisoft/config": true
        }
    },
    "repositories": [
        {
            "type": "path",
            "url": "../../.."
        },
        %REPOSITORIES%
        {
            "type": "path",
            "url": "../../../vendor/yiisoft/arrays",
            "options": {
                "versions": {
                    "yiisoft/arrays": "3.0"
                }
            }
        },
        {
            "type": "path",
            "url": "../../../vendor/yiisoft/var-dumper",
            "options": {
                "versions": {
                    "yiisoft/var-dumper": "1.1"
                }
            }
        },
        {
            "type": "path",
            "url": "../../../vendor/yiisoft/strings",
            "options": {
                "versions": {
                    "yiisoft/strings": "2.6"
                }
            }
        }
    ]
}
