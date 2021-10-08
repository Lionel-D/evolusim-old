# Evolusim

*Natural selection simulator*

---

### Requirements

- **[PHP](https://www.php.net/)** `7.4 or greater`
- **[MySQL](https://www.mysql.com/)** `8.0 or greater`
- **[Composer](https://getcomposer.org/)** `2.1 or greater`
- **[Yarn](https://yarnpkg.com)** `1.22 or greater`
- **[Symfony client](https://symfony.com/download)** `4.26 or greater`

---

### Installation

- create `.env.local` based on `.env` and set values for your local environment.
- `composer install` to get the framework dependencies.
- `yarn install` to get the assets pipeline dependencies.
- `php bin/console doctrine:database:create` to create database.
- `php bin/console doctrine:migrations:migrate` to setup database structure.
- `php bin/console doctrine:fixtures:load` to load data.

---

### Local environment

- `yarn encore dev --watch` to launch webpack.
- `symfony server:start` to launch local server.
