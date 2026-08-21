# Sound Choice Reviews

Drupal 11 custom module that prevents standalone Review node pages from being
publicly browsable while leaving Review entities renderable in Views and other
contexts.

## Behaviour

Review nodes can still be:

- created and edited normally;
- referenced from Artist nodes;
- rendered through Views;
- rendered in the custom Review card view mode.

But a direct canonical request such as `/node/123` for a Review node returns a
404.

## Why this approach

The module deliberately does not use node access to block reviews because that
could also prevent anonymous Views from rendering them. It only intercepts the
canonical node route (`entity.node.canonical`).

## Installation

Copy to:

`modules/custom/soundchoice_reviews`

Then run:

```bash
drush en soundchoice_reviews -y
drush cr
```

## Recommended related configuration

Also exclude Review nodes from:

- XML sitemap;
- Pathauto aliases;
- public Search API indexes;
- other public listings unless intentionally used.

If Review nodes have Metatag defaults, consider setting them to `noindex`.
