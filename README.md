# gravityforms-lianamailer

> GravityForms integration with LianaMailer

## Features

_A list of features_.

## Development

Install dependencies

    composer install

Bump versions

    # Bump patch release
    robo version:bump

    # Bump minor release
    robo version:bump --stage=minor

    # Bump major release
    robo version:bump --stage=major

Setup new plugin

    robo rename

### Translations

Rebuild POT files (after this, copy to each language as `languages/gravityforms-lianamailer-<langcode>.po` and translate it)

    npm run lang:pot

Compile MO files (requires msgfmt which is available with `brew install gettext && brew link gettext --force`)

    npm run lang:mo

Or run all of these with:

    npm run lang
