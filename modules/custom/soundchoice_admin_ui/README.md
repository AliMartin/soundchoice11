# Sound Choice Admin UI

Small Drupal 11 custom module providing editorial UI enhancements for Sound Choice.

## Current behaviour

On Artist node add/edit forms, checkbox-based option widgets are displayed as a responsive CSS grid rather than a long vertical list.

The styling is scoped to:

- the `artist` content type; and
- fields using Drupal's `options_buttons` widget.

Autocomplete fields such as "Tribute to" are unaffected.

## Installation

1. Copy the `soundchoice_admin_ui` directory to `web/modules/custom/` or `docroot/modules/custom/`.
2. Enable the module:

   `drush en soundchoice_admin_ui -y`

3. Rebuild caches:

   `drush cr`

If your Artist content type machine name is not `artist`, change the bundle check in `soundchoice_admin_ui.module`.
