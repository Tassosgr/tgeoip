A Joomla system plugin that provides geolocation services to [Tassos.gr](https://www.tassos.gr) Joomla extensions.

## Install
After you've cloned the repo you must install PHP dependencies in the `tgeoip` folder.

```
cd source/plugins/system/tgeoip
composer install
```

## Prefixing vendor namespaces
- Install [php-scoper](https://github.com/humbug/php-scoper)
- `cd` to `source/plugins/system/tgeoip`
- Generate prefixed vendor code (default prefix: `Tassos\Vendor\`): `php-scoper add-prefix`
- Replace the `vendor` folder with `build/vendor`, delete `build` folder
- Generate new autoloader: `composer dump-autoload`

php-scoper configuration file: `php-scoper.inc.php`

## Used by
The TGeoIP plugin is currently being used by the following extensions

[EngageBox](https://www.tassos.gr/joomla-extensions/engagebox), [Convert Forms](https://www.tassos.gr/joomla-extensions/convert-forms) and [Advanced Custom Fields](https://www.tassos.gr/joomla-extensions/advanced-custom-fields)