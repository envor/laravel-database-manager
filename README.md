# A small library for managing databases

Create and delete `mysql` and `sqlite` databases. Soft deletes, or "recycles" databases by default. Also it can clean up old recycled databases.

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

### SQLite

```php
$databaseManager = new Envor\DatabaseManager
    ->manage('sqlite')
    ->createDatabase('my-new-database');
```

Creates an sqlite database at `storage/app/my-new-database.sqlite`

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

Soft deletes the database and moves it to `storage/app/.trash/2023/03/02/07_04_38_my-new-database.sqlite`

```php
// erase the database permanently from disk
$databaseManager->eraseDatabase('.trash/2023/03/02/07_04_38_my-new-database');
```

Erases the database permanently form disk

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
    ->createDatabase('my_new_database');
```

```php
echo now()->format('Y_m_d_h_i_s_');
// 2023/3/2/7_04_38_
$databaseManager->deleteDatabase(
    databaseName: 'my_new_database', 
    deletedAt: now(), // optional: defaults to now(). Uses Carbon. 
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

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [inmanturbo](https://github.com/envor)
- [All Contributors](../../contributors)

Parts of this package are inspired by the `DatabaseManager` classes in [Tenancy for Laravel](https://github.com/archtechx/tenancy)
[CONTRIBUTING](.github/CONTRIBUTING.md) was copied verbatim from [Spatie's CONTRIBUTING.md](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md)
This package was generated using [spatie/package-skeleton-laravel](https://github.com/spatie/package-skeleton-laravel)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
