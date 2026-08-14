# Sound Choice Artist Gallery

## Overview

Sound Choice Artist pages use a fixed 16:9 image mosaic alongside the
promotional video player. The gallery occupies the same proportional
footprint as the Plyr player so the two-column multimedia section
remains visually balanced.

The Artist page displays up to four gallery images:

-   Image 1 is the dominant large image.
-   Images 2--4 are stacked vertically on the right.
-   If more than four images exist, the fourth tile displays a
    `+N photos` overlay.
-   Clicking any visible image opens the complete gallery in GLightbox.
-   Images beyond the first four are included as hidden GLightbox links.

``` text
Artist
└── field_image_gallery
    └── Media: Image
        └── field_media_image
              ↓
       Artist preprocess
              ↓
       artist_gallery array
              ↓
       16:9 mosaic preview
              ↓
       GLightbox full gallery
```

## Drupal field configuration

Artist field:

``` text
field_image_gallery
Field type: Media entity reference
Allowed Media type: Image
Cardinality: Unlimited
```

Image Media field:

``` text
field_media_image
```

## Artist preprocessing

Gallery data is prepared alongside promotional video data in:

``` text
themes/custom/soundchoice11/soundchoice11.theme
```

The gallery section inside `soundchoice11_preprocess_node()` is:

``` php
$variables['artist_gallery'] = [];

if (
  $node->hasField('field_image_gallery') &&
  !$node->get('field_image_gallery')->isEmpty()
) {
  foreach ($node->get('field_image_gallery')->referencedEntities() as $media) {
    if (
      !$media->hasField('field_media_image') ||
      $media->get('field_media_image')->isEmpty()
    ) {
      continue;
    }

    $image = $media->get('field_media_image');
    $file = $image->entity;

    if (!$file) {
      continue;
    }

    $variables['artist_gallery'][] = [
      'url' => \Drupal::service('file_url_generator')
        ->generateAbsoluteString($file->getFileUri()),
      'alt' => $image->alt ?: $media->label(),
      'title' => $media->label(),
    ];
  }
}
```

This belongs inside the existing `soundchoice11_preprocess_node()`
function. Do not create a second node preprocess function and do not
place this code at file scope.

## GLightbox

The gallery uses the Drupal GLightbox module and GLightbox Inline
integration.

``` bash
composer require drupal/glightbox
drush en glightbox -y
drush en glightbox_inline -y
drush cr
```

Verify the JavaScript library on an Artist page with:

``` javascript
typeof GLightbox
```

Expected result:

``` text
"function"
```

## Artist gallery Twig

In `node--artist--full.html.twig`:

``` twig
{# Custom gallery STARTS #}
{% if artist_gallery %}

  {% set gallery_count = artist_gallery|length %}
  {% set preview_images = artist_gallery|slice(0, 4) %}

  <div class="artist-gallery">

    {% for image in preview_images %}

      <a
        class="artist-gallery__item artist-gallery__item--{{ loop.index }} glightbox"
        href="{{ image.url }}"
        data-gallery="artist-gallery-{{ node.id }}"
        data-title="{{ image.title }}"
        aria-label="View {{ image.alt }}">

        <img
          src="{{ image.url }}"
          alt="{{ image.alt }}"
          loading="lazy">

        {% if loop.last and gallery_count > 4 %}
          <span class="artist-gallery__more">
            +{{ gallery_count - 4 }} photos
          </span>
        {% endif %}

      </a>

    {% endfor %}

  </div>

  {% if gallery_count > 4 %}
    <div class="artist-gallery__hidden">

      {% for image in artist_gallery|slice(4) %}
        <a
          class="glightbox"
          href="{{ image.url }}"
          data-gallery="artist-gallery-{{ node.id }}"
          data-title="{{ image.title }}"
          aria-label="View {{ image.alt }}">
        </a>
      {% endfor %}

    </div>
  {% endif %}

{% endif %}
{# Custom gallery ENDS #}
```

The first four images form the visible mosaic. The remainder are hidden
links belonging to the same GLightbox group.

## Gallery grouping

All links use:

``` twig
data-gallery="artist-gallery-{{ node.id }}"
```

This creates a gallery unique to the current Artist and allows GLightbox
to navigate through both visible and hidden images.

For 12 images:

``` text
Visible: 1, 2, 3, 4
Hidden:  5, 6, 7, 8, 9, 10, 11, 12
Overlay: +8 PHOTOS
```

## Gallery SCSS

``` scss
.artist-gallery {
  display: grid;
  grid-template-columns: 2fr 1fr;
  grid-template-rows: repeat(3, 1fr);
  gap: 6px;

  width: 100%;
  aspect-ratio: 16 / 9;
  overflow: hidden;
  border-radius: $border-radius-lg;
  background: $dark;

  &__item {
    position: relative;
    display: block;
    min-width: 0;
    min-height: 0;
    overflow: hidden;

    border: 0;
    background: $dark;
    text-decoration: none;

    &:first-child {
      grid-row: 1 / 4;
    }

    img {
      display: block;
      width: 100%;
      height: 100%;
      object-fit: cover;

      transition:
        transform .3s ease,
        filter .3s ease;
    }

    &::after {
      content: "";
      position: absolute;
      inset: 0;
      background: rgba($dark, 0);
      transition: background .3s ease;
      pointer-events: none;
    }

    &:hover,
    &:focus-visible {
      img {
        transform: scale(1.04);
      }

      &::after {
        background: rgba($dark, .18);
      }
    }

    &:focus-visible {
      outline: 2px solid $gold;
      outline-offset: -2px;
    }
  }

  &__more {
    position: absolute;
    inset: 0;
    z-index: 2;

    display: flex;
    align-items: center;
    justify-content: center;

    padding: 15px;

    background: rgba($dark, .65);
    color: $light;

    font-family: $headings-font-family;
    font-size: 1rem;
    font-weight: 600;
    line-height: 1.2;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: .06em;

    transition: background .3s ease;
  }

  &__item:hover &__more,
  &__item:focus-visible &__more {
    background: rgba($dark, .78);
  }

  &__hidden {
    display: none;
  }
}
```

## Mosaic layout

``` text
┌────────────────────────┬──────────────┐
│                        │   IMAGE 2    │
│                        ├──────────────┤
│        IMAGE 1         │   IMAGE 3    │
│                        ├──────────────┤
│                        │ +N PHOTOS    │
└────────────────────────┴──────────────┘
```

The first image spans all three rows. The `2fr 1fr` ratio gives it twice
the width of the supporting images.

## Matching the video player

Both components use a 16:9 aspect ratio:

``` scss
.artist-gallery {
  aspect-ratio: 16 / 9;
}

.artist-video {
  .plyr {
    border-radius: $border-radius-lg;
    overflow: hidden;
  }
}
```

Placed in equal Bootstrap columns, the primary gallery and video areas
therefore have matching proportions.

## Accessibility

Visible and hidden links have descriptive labels:

``` twig
aria-label="View {{ image.alt }}"
```

Alt text comes from the Media image field with the Media label as
fallback:

``` php
'alt' => $image->alt ?: $media->label(),
```

Keyboard focus uses the Sound Choice gold:

``` scss
&:focus-visible {
  outline: 2px solid $gold;
  outline-offset: -2px;
}
```

## Excluding normal Drupal field output

If the Artist template later renders `{{ content }}`, exclude the custom
gallery field to prevent duplicate standard output:

``` twig
{{ content|without(
  'field_promotional_videos',
  'field_image_gallery'
) }}
```

Merge this with the other fields already excluded by the Artist
template.

## Performance improvement to implement later

The current implementation uses the original image URL for both the
mosaic and GLightbox. The eventual optimisation should provide two URLs:

``` text
Media image
├── Mosaic URL   → optimised/cropped Drupal image style
└── Lightbox URL → original or large image style
```

This will prevent full-resolution originals being downloaded merely to
display small mosaic tiles.

## Cache clearing

After changing theme PHP, Twig, GLightbox configuration or Media
configuration:

``` bash
drush cr
```

If PHP appears to execute an old version of the theme file:

``` bash
ddev restart
```

## Key configuration

``` text
Artist:
field_image_gallery
  → Media entity reference
  → Image
  → Unlimited

Image Media:
field_media_image

Modules:
GLightbox
GLightbox Inline
```

## Final behaviour

The gallery provides:

-   Unlimited Drupal Media images per Artist.
-   Four-image editorial mosaic preview.
-   Fixed 16:9 proportions matching the video player.
-   Large dominant first image.
-   Three supporting images.
-   `+N photos` overlay for additional images.
-   Full gallery navigation through GLightbox.
-   Hidden additional links that do not affect layout.
-   Keyboard-accessible image links.
-   Subtle hover zoom and dark overlay.
-   Sound Choice gold focus treatment.
-   Responsive Bootstrap-compatible presentation.
