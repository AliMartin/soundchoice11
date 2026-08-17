# Sound Choice Enquiry

Drupal 11 custom module for the shared Sound Choice Artist enquiry form.

## Current behaviour

Artist pages link to:

`/enquire?artist=NODE_ID`

The module:

1. validates the node ID against a viewable `artist` node;
2. displays the Artist profile image and `Enquire about [Artist]`;
3. resolves `field_membership` and `field_contact_email` server-side;
4. sends the Webform submission according to membership.

## Routing

- `pioneer` or `partner` + valid `field_contact_email`
  - To: Artist contact email
  - CC: `hello@soundchoice.co.uk`
- `pioneer` or `partner` with no valid contact email
  - To: `hello@soundchoice.co.uk`
- `premium`
  - To: `hello@soundchoice.co.uk`
- missing/unrecognised membership
  - To: `hello@soundchoice.co.uk`

The visitor email is used as Reply-To when valid.

## Webform assumptions

Webform machine name:

`artist_enquiry`

Expected element keys:

- `artist_id`
- `name`
- `email`
- `telephone`
- `event_date`
- `location`
- `message`

The module sends the email itself. **Do not add a separate Webform email
handler to `artist_enquiry`**, otherwise enquiries can be sent twice.

## Updating an existing installation

Overwrite the existing module files and run:

```bash
drush cr
```

There is no database schema change.

## Testing

Submit one test for each route:

1. Pioneer with contact email
2. Partner with contact email
3. Pioneer/Partner without contact email
4. Premium

Check Drupal logs at:

`/admin/reports/dblog`

Messages are logged under the `soundchoice_enquiry` channel.

Local DDEV mail delivery can be tested separately; routing can also be verified
on production once the site's mail transport is configured.
