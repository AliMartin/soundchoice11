# Sound Choice Search Log

Drupal 11 module for anonymously logging searches performed through the
Sound Choice `artist_search` View.

## Logged data

- Search phrase
- Result count
- Timestamp

No IP address, user ID, user agent or other visitor identifier is stored.

## Administrator/test searches

Searches made by users with Drupal's `access site reports` permission are
not logged. This keeps administrator testing out of the visitor-search data.

## Assumptions

- View machine name: `artist_search`
- Exposed search filter identifier: `search`

## Installation / update

Place the module in:

`modules/custom/soundchoice_search_log`

Enable for a new installation:

```bash
drush en soundchoice_search_log -y
drush cr
```

If replacing an existing copy of the module with this updated version, overwrite
the module files and run:

```bash
drush cr
```

No database update is required for this version because the schema is unchanged.

## Report

`/admin/reports/soundchoice-searches`

The report includes:

- total searches;
- zero-result search count;
- most common searches;
- most common zero-result searches;
- recent searches;
- a Clear search log button.

Clearing the log requires confirmation and permanently deletes all stored search
records.
