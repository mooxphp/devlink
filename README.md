# Moox Devlink

This package is only for internal use.

It is used to link the packages from the `moox` monorepo into a project. It runs on MacOS, Linux and Windows.

## Installation

```bash
cp composer.json.example composer.json
cp .env.example .env
composer require moox/devlink
php artisan vendor:publish --tag="devlink-config"
```

## Usage

```bash
php artisan moox:devlink
```

## Screenshot

![Moox Devlink](./devlink.jpg)

## Preparation

Before you can use this package, you need to prepare your project's `.gitignore` file.

```bash
# Ignore all files in packages/ (including symlinks)
packages/*
# Allow tracking of real directories inside packages/
!packages/**/
# Ensure empty directories can be committed
!packages/*/.gitkeep
# Ignore all files in packages-linked/ (for Windows)
packages-linked/*
```

## Configuration

The configuration is done in the `config/devlink.php` file.

```php

    'packages_path' => 'packages',

    'base_paths' => [
        base_path('../moox/packages'),
    ],

    'packages' => [
        'moox/tag',
    ],

```

## Command

The devlink command will create a `packages` directory in the root of the project and symlink the packages from the configured base paths.

```bash

    php artisan moox:devlink

```

It will also update the `composer.json` file to include the packages in the `require` section and the `repositories` section.

Finally, it will run `composer update`.

### Changing branches

If you need to change the branches for ANY of the involved repositories, you just need to run the command again, it will automatically update the symlinks for the current branch.

```bash

    php artisan moox:devlink

```

> ⚠️ **Important**  
> If you forget to run the command, when CHANGING BRANCHES ON ANY OF THE REPOS, you will surely run into a 500 error, that drives you nuts.

## Mac

Mac works out of the box. You can have local packages mixed with the symlinked packages in your `/packages` folder.

![Moox Devlink](./devlink-mix.jpg)

## Windows

On Windows there are most probably some issues with the symlinks. If you run into issues, you can either globally or project-wise disable the symlinks or do the following:

```php

    'packages_path' => 'packages-linked',

```

Devlink will then link the packages into the `packages-linked` folder.

## Security Vulnerabilities

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
