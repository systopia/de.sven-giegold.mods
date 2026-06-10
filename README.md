# Sven Giegold Modifications

CiviCRM customisations for sven-giegold.de.

The extension adds a custom contact search ("Suche nach Interessen") that
filters contacts by the custom fields stored in the `zusatzinformationen`
custom group — media types, topics, regions and other interest criteria.
Each criterion can be used as an include or exclude filter; the search also
supports a postal code range and excludes contacts that received a mailing
within the past twelve months.

## Requirements

- CiviCRM >= 5.45
- PHP >= 8.1
- A `zusatzinformationen` custom group with the interest-related custom fields
  the search expects
- The `legacycustomsearches` core extension (CiviCRM ships the custom search
  framework as a separate extension since 5.x)

## Installation

Install via the CiviCRM Extension browser, or from the command line:

```bash
cv en mods
```

## Configuration

No configuration screen. The custom search is registered automatically and
appears under Search » Custom Searches » Suche nach Interessen.

## License

This extension is licensed under [AGPL-3.0](LICENSE.txt).
