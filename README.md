# wp-infomaniak-ticketing

[![php >= 8.0](http://shields.infomaniak.ch/badge/php-8.0-blue.svg)](https://gitlab.infomaniak.ch/infomaniak/wp-infomaniak-ticketing)
[![wordpress 6.7+](http://shields.infomaniak.ch/badge/wordpress-6.7+-blue.svg)](https://gitlab.infomaniak.ch/infomaniak/wp-infomaniak-ticketing)
[![react 19.*](http://shields.infomaniak.ch/badge/react-19.*-green.svg)](https://gitlab.infomaniak.ch/infomaniak/wp-infomaniak-ticketing)
[![node 24.*](http://shields.infomaniak.ch/badge/node-24.*-green.svg)](https://gitlab.infomaniak.ch/infomaniak/wp-infomaniak-ticketing)

WordPress plugin to sell and manage Infomaniak event tickets (billetterie) from any WordPress site.

> **Status:** empty scaffold. The plugin builds, installs and exposes a working shortcode + React mount point; the ticketing features are built on top of it.

## Required

- `PHP >= 8.0`
- `Composer ^2`
- `Node ^24`
- `Yarn ^4`

## Getting started

1. **Install the required Node version**: navigate to the project root and run:

    ```bash
    nvm install
    nvm use
    ```

    These commands read the `.nvmrc` file to install and use the Node version specified for this project.

2. **Install the frontend dependencies**:

    ```bash
    yarn install
    ```

3. **Install the PHP dependencies**:

    ```bash
    composer install
    ```

4. **Build the assets** (required before the plugin enqueues anything):

    ```bash
    yarn build
    ```

## Install in WordPress

### Development

Symlink this folder into a WordPress install (or a `dev-local`/wp-env site):

```bash
ln -s "$(pwd)" /path/to/wordpress/wp-content/plugins/wp-infomaniak-ticketing
```

Then activate **Infomaniak Ticketing** in the WordPress admin.

## Usage

Add the shortcode to any page or post:

```
[infomaniak_ticketing]
```

It renders the React mount point (`#itkt-app`) and enqueues the built assets.

## Scripts

### Frontend (Yarn)

| Script                 | Description                                              |
| ---------------------- | -------------------------------------------------------- |
| `yarn dev`             | Build with watch mode (assets rebuild on every change)   |
| `yarn build`           | Production build (writes `build/.vite/manifest.json`)    |
| `yarn test`            | Vitest in watch mode                                     |
| `yarn test:run`        | Vitest single run                                        |
| `yarn test:coverage`   | Vitest with coverage                                     |
| `yarn types`           | TypeScript type-checking (`tsc`)                         |
| `yarn lint:check`      | ESLint (CI gate)                                         |
| `yarn format:check`    | Prettier check                                           |

> **Note:** the plugin is not served by a Vite dev server: WordPress needs real files from `build/`. Use `yarn dev` (watch build) during development.

### PHP (Composer)

| Script                | Description                                                      |
| --------------------- | ---------------------------------------------------------------- |
| `composer test`       | PHPUnit (Brain Monkey, no WordPress install needed)              |
| `composer lint`       | Pint, fix dirty files                                            |
| `composer lint:check` | Pint, check only (CI gate)                                       |

## Project structure

```text
wp-infomaniak-ticketing/
├── wp-infomaniak-ticketing.php  # Plugin bootstrap (header, constants, boot)
├── composer.json                # PSR-4 autoload, Pint, PHPUnit
├── package.json                 # Vite, Vitest, React, TypeScript tooling
├── vite.config.ts               # Build config (entries, manifest, vitest)
├── src/                         # PHP classes (namespace Infomaniak\Ticketing)
│   ├── Plugin.php               # Boots the plugin and registers hooks
│   ├── AdminPage.php            # "Ticketing" admin menu: onboarding + account connection
│   ├── OAuthConfig.php          # OAuth application settings (client ID, scopes, redirect URI)
│   ├── OAuthService.php         # OAuth2 PKCE flow against Infomaniak Connect
│   ├── Connection.php           # Stores the API credentials (options table)
│   ├── Credentials.php          # Manager API token + ticketing API key value object
│   ├── TicketingApi.php         # Ticketing REST API client (Bearer + key headers)
│   ├── VerificationResult.php   # Credential verification outcome
│   ├── Assets.php               # Resolves the Vite manifest and registers assets
│   ├── I18n.php                 # Loads the plugin text domain
│   └── Shortcode.php            # [infomaniak_ticketing] shortcode
├── resources/
│   ├── css/                     # Styles sources (CSS/SCSS)
│   └── js/                      # React/TypeScript sources (alias "@/...")
├── tests/php/                   # PHPUnit tests (Brain Monkey)
├── languages/                   # Translations: POT source + PO/MO (text domain: wp-infomaniak-ticketing)
└── build/                       # Built assets — gitignored, never edit
```

## Tools

### Laravel Pint (PHP)

Pint formats PHP code with the house `laravel` preset:

```shell
composer lint          # fix dirty files
composer lint:check    # check only
```

### Translations (wp-cli)

The plugin header (Name, Description) is written in English and is translatable: the French card text lives in `languages/wp-infomaniak-ticketing-fr_FR.po` (msgid = English header string).

```bash
wp i18n make-pot . languages/wp-infomaniak-ticketing.pot --domain=wp-infomaniak-ticketing --exclude=build,vendor,node_modules,dist,coverage,tests
wp i18n make-mo languages/wp-infomaniak-ticketing-fr_FR.po
```

### Husky + commitlint

Git hooks enforce linting and conventional commit messages (`feat:`, `fix:`, …) on every commit.
