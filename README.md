# A small library for managing databases.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/envor/laravel-database-manager.svg?style=flat-square)](https://packagist.org/packages/envor/laravel-database-manager)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/envor/laravel-database-manager/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/envor/laravel-database-manager/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/envor/laravel-database-manager/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/envor/laravel-database-manager/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/envor/laravel-database-manager.svg?style=flat-square)](https://packagist.org/packages/envor/laravel-database-manager)

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/laravel-database-manager.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/laravel-database-manager)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

You can install the package via composer:

```bash
composer require envor/laravel-database-manager
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-database-manager-config"
```

This is the contents of the published config file:

```php
// config for Envor/DatabaseManager
return [
    /***
     * The disk where the sqlite database files will be stored.
     */
    'sqlite_disk' => 'local',

    /***
     * Available drivers that can be managed.
     */
    'managers' => [
        'sqlite' => \Envor\DatabaseManager\SQLiteDatabaseManager::class,
        'mysql' => \Envor\DatabaseManager\MySQLDatabaseManager::class,
    ]
];
```

## Usage

### Sqlite

```php
$databaseManager = new Envor\DatabaseManager
    ->manage('sqlite')
    ->createDatabase('my-new-database');
```

Creates an sqlite database at `storage/app/my-new-database.sqlite

> The package appends the .sqlite file extension on its own,
> and expects managed sqlite database files to have the extension

```php
echo now()->format('Y/m/d_h_i_s_');
// 2023/3/2/7_04_38_
$databaseManager->deleteDatabase(
    databaseName: 'my-new-database', 
    deletedAt: now(), // optional: defaults to now() (Carbon date)
);
```

Soft deletes the database and moves it to `storage/app/.trash/2023/3/2/7_04_38_my-new-database.sqlite`

```php
// erase the database permanently from disk
$databaseManager->eraseDatabase('.trash/2023/3/2/7_04_38_my-new-database');
```

```php
$databaseManager->cleanupOldDatabases(
    daysOld: 1, // optional, defaults to one
);
```

Erases all the database files in the .trash folder with mtime more than one day old

### MYQL

```php
$databaseManager = new Envor\DatabaseManager
    ->manage('mysql')
    ->setConnection('any-mysql-connection')
    ->createDatabase('my-new-database');
```

```php
echo now()->format('Y_m_d_h_i_s_');
// 2023/3/2/7_04_38_
$databaseManager->deleteDatabase(
    databaseName: 'my_new_database', 
    deletedAt: now(), // optional: defaults to now() (Carbon date)
);
```

Soft deletes the database and moves it to `deleted_2023_3_2_7_04_38_my_new_database`

```php
$databaseManager->cleanupOldDatabases(
    daysOld: 1, // optional, defaults to one
);
```

No mtime for mysql, simply compares `$daysOld` against the formated time in the deleted name (`2023_3_2_7_04_38_`).
This is done by using `Carbon::createFromFormat()`.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [inmanturbo](https://github.com/envor)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
