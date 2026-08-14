# Sound Choice Artist Video Player

## Overview

Sound Choice Artist pages use a single custom-themed Plyr player with a
horizontal thumbnail carousel. Promotional videos remain normal Drupal
Remote Video Media entities, so Drupal manages the content while the
theme controls presentation.

Architecture:

``` text
Artist
└── field_promotional_videos
    └── Media: Remote video
        └── field_media_oembed_video
              ↓
       Single Plyr player
              ↓
       Thumbnail carousel
```

The implementation uses:

-   Drupal 11 core Media
-   Remote video Media entities
-   `field_promotional_videos` on the Artist content type
-   `field_media_oembed_video` on the Remote video Media type
-   Drupal's built-in Media thumbnail
-   Plyr
-   A custom `artist-video.js` Drupal behaviour
-   A `Video thumbnail` Media view mode

## 1. Install Plyr

Plyr is installed with npm at the Drupal project root:

``` bash
npm install plyr
```

This creates the package under:

``` text
node_modules/plyr/
```

The Drupal project root contains directories such as `core`,
`libraries`, `modules`, `node_modules`, `profiles`, `recipes`, `sites`
and `themes`.

Do not serve Plyr directly from `node_modules`. Copy its compiled assets
into the custom theme:

``` bash
mkdir -p themes/custom/soundchoice11/css/vendor
mkdir -p themes/custom/soundchoice11/js/vendor

cp node_modules/plyr/dist/plyr.css themes/custom/soundchoice11/css/vendor/plyr.css
cp node_modules/plyr/dist/plyr.js themes/custom/soundchoice11/js/vendor/plyr.js
```

The resulting files are:

``` text
themes/custom/soundchoice11/css/vendor/plyr.css
themes/custom/soundchoice11/js/vendor/plyr.js
```

## 2. Define the Drupal library

Add the following to `soundchoice11.libraries.yml`:

``` yaml
artist-video:
  css:
    theme:
      css/vendor/plyr.css: {}
  js:
    js/vendor/plyr.js: {}
    js/artist-video.js: {}
  dependencies:
    - core/drupal
    - core/once
```

Attach it from `node--artist--full.html.twig`:

``` twig
{{ attach_library('soundchoice11/artist-video') }}
```

The theme machine name is `soundchoice11`, so the library prefix must be
`soundchoice11`, not `soundchoice`.

After changing libraries or Twig templates:

``` bash
drush cr
```

To verify Plyr is loaded, run this in the browser console on an Artist
page:

``` javascript
typeof Plyr
```

The expected result is:

``` text
"function"
```

## 3. Drupal Media configuration

### Artist field

The Artist content type uses:

``` text
field_promotional_videos
```

This is an unlimited Media entity reference field accepting Remote video
Media entities.

### Remote video source field

The Remote video Media type uses Drupal's standard field:

``` text
field_media_oembed_video
```

This stores the YouTube/remote video URL.

## 4. Create the Video thumbnail Media view mode

Go to:

``` text
Structure → Display modes → View modes → Media
```

Create:

``` text
Video thumbnail
```

Then go to:

``` text
Structure → Media types → Remote video → Manage display
```

Enable `Video thumbnail` under Custom display settings if necessary,
then configure that view mode.

Use:

``` text
Thumbnail   → Image
Video URL   → Disabled
```

For Thumbnail:

-   Label: Hidden
-   Formatter: Image
-   Link image to: Nothing
-   Use a suitable 16:9 image style if required, e.g. 320 × 180 Scale
    and Crop.

The important point is that Drupal core already obtains and stores the
oEmbed thumbnail for Remote Video Media. There is no need to construct
YouTube thumbnail URLs manually.

## 5. Prepare video data in the theme

In:

``` text
themes/custom/soundchoice11/soundchoice11.theme
```

add the Artist video data to `hook_preprocess_node()`:

``` php
/**
 * Implements hook_preprocess_node().
 */
function soundchoice11_preprocess_node(array &$variables): void {
  $node = $variables['node'];

  if ($node->bundle() !== 'artist') {
    return;
  }

  $variables['artist_videos'] = [];

  if (
    !$node->hasField('field_promotional_videos') ||
    $node->get('field_promotional_videos')->isEmpty()
  ) {
    return;
  }

  $view_builder = \Drupal::entityTypeManager()->getViewBuilder('media');

  foreach ($node->get('field_promotional_videos')->referencedEntities() as $media) {
    if (
      !$media->hasField('field_media_oembed_video') ||
      $media->get('field_media_oembed_video')->isEmpty()
    ) {
      continue;
    }

    $variables['artist_videos'][] = [
      'url' => $media->get('field_media_oembed_video')->value,
      'title' => $media->label(),
      'thumbnail' => $view_builder->view($media, 'video_thumbnail'),
    ];
  }
}
```

This gives Twig a clean structure containing each video's URL, Media
title and Drupal-rendered thumbnail.

If `soundchoice11_preprocess_node()` already exists for other Artist
preprocessing, merge this logic into the existing function rather than
declaring the function twice.

## 6. Artist Twig markup

In `node--artist--full.html.twig`, output one active Plyr player
followed by the carousel:

``` twig
{% if artist_videos %}

  <div class="artist-video">

    <div class="artist-video__player">
      <div
        class="js-artist-video-player"
        data-plyr-provider="youtube"
        data-plyr-embed-id="{{ artist_videos.0.url }}">
      </div>
    </div>

    {% if artist_videos|length > 1 %}

      <div class="artist-video__carousel">

        <button
          class="artist-video__nav artist-video__nav--previous"
          type="button"
          aria-label="Previous videos">
          <i class="fa-solid fa-chevron-left"></i>
        </button>

        <div class="artist-video__thumbnails">

          {% for video in artist_videos %}

            <button
              class="artist-video__thumbnail{% if loop.first %} is-active{% endif %}"
              type="button"
              data-video-url="{{ video.url }}"
              aria-label="Play {{ video.title }}">

              {{ video.thumbnail }}

            </button>

          {% endfor %}

        </div>

        <button
          class="artist-video__nav artist-video__nav--next"
          type="button"
          aria-label="Next videos">
          <i class="fa-solid fa-chevron-right"></i>
        </button>

      </div>

    {% endif %}

  </div>

{% endif %}
```

The first promotional video becomes the initial player source.
Additional videos are not embedded as separate players.

If the normal Artist field output is rendered elsewhere with
`{{ content }}`, exclude `field_promotional_videos` there to prevent
Drupal from also outputting the standard embeds:

``` twig
{{ content|without(
  'field_promotional_videos'
) }}
```

Merge this with any other fields already excluded by the Artist
template.

## 7. JavaScript behaviour

Create:

``` text
themes/custom/soundchoice11/js/artist-video.js
```

with:

``` javascript
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.soundchoiceArtistVideo = {
    attach(context) {
      once('soundchoice-artist-video', '.artist-video', context).forEach((wrapper) => {
        const playerElement = wrapper.querySelector('.js-artist-video-player');
        const thumbnails = wrapper.querySelectorAll('.artist-video__thumbnail');
        const track = wrapper.querySelector('.artist-video__thumbnails');
        const previous = wrapper.querySelector('.artist-video__nav--previous');
        const next = wrapper.querySelector('.artist-video__nav--next');

        if (!playerElement) {
          return;
        }

        const player = new Plyr(playerElement);

        thumbnails.forEach((thumbnail) => {
          thumbnail.addEventListener('click', () => {
            const videoUrl = thumbnail.dataset.videoUrl;

            if (!videoUrl) {
              return;
            }

            player.source = {
              type: 'video',
              sources: [
                {
                  src: videoUrl,
                  provider: 'youtube',
                },
              ],
            };

            thumbnails.forEach((item) => {
              item.classList.remove('is-active');
            });

            thumbnail.classList.add('is-active');
          });
        });

        previous?.addEventListener('click', () => {
          track?.scrollBy({
            left: -300,
            behavior: 'smooth',
          });
        });

        next?.addEventListener('click', () => {
          track?.scrollBy({
            left: 300,
            behavior: 'smooth',
          });
        });
      });
    },
  };
})(Drupal, once);
```

Using a Drupal behaviour with `core/once` prevents duplicate
initialisation when Drupal attaches behaviours again, including after
AJAX operations.

## 8. Sound Choice Plyr theming

The Plyr accent colour is controlled with a CSS custom property.

In the Artist SCSS:

``` scss
.artist-video {
  --plyr-color-main: #{$gold};

  --plyr-video-background: #{$dark};
  --plyr-menu-background: #{$dark};
  --plyr-menu-color: #{$light};
  --plyr-tooltip-background: #{$dark};
  --plyr-tooltip-color: #{$light};

  .plyr {
    border-radius: $border-radius-lg;
    overflow: hidden;
  }
}
```

This replaces Plyr's default blue accent with the Sound Choice gold.

## 9. Basic carousel SCSS

A starting point for the thumbnail carousel:

``` scss
.artist-video {
  &__player {
    aspect-ratio: 16 / 9;
  }

  &__carousel {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 15px;
  }

  &__thumbnails {
    display: flex;
    flex: 1;
    gap: 10px;
    overflow: hidden;
    scroll-behavior: smooth;
  }

  &__thumbnail {
    flex: 0 0 140px;
    padding: 0;
    border: 1px solid transparent;
    background: $dark;
    opacity: .6;
    transition:
      opacity .2s ease,
      border-color .2s ease;

    &:hover,
    &.is-active {
      opacity: 1;
    }

    &.is-active {
      border-color: $gold;
    }

    img {
      display: block;
      width: 100%;
      aspect-ratio: 16 / 9;
      object-fit: cover;
    }
  }

  &__nav {
    border: 0;
    background: transparent;
    color: $gold;
    font-size: 1.5rem;
    padding: 10px;
  }
}
```

This is only the baseline styling and can be refined as the Artist page
design develops.

## 10. How it works

On an Artist page:

1.  Drupal loads all Media entities referenced by
    `field_promotional_videos`.
2.  `soundchoice11_preprocess_node()` extracts each Remote Video URL.
3.  Drupal renders each Media entity using the `video_thumbnail` view
    mode.
4.  Twig uses the first video as the initial Plyr source.
5.  Twig outputs the remaining videos as thumbnail buttons.
6.  `artist-video.js` initialises one Plyr instance.
7.  Clicking a thumbnail replaces `player.source` rather than creating
    another player.
8.  The active thumbnail receives `.is-active`.
9.  Previous/next buttons horizontally scroll the thumbnail track.

This keeps the page lightweight compared with rendering multiple YouTube
embeds simultaneously and keeps Drupal responsible for media content
while the Sound Choice theme owns presentation.

## 11. Maintenance notes

After changing `soundchoice11.libraries.yml`, Twig templates, preprocess
functions or Media display configuration, clear Drupal caches:

``` bash
drush cr
```

After updating Plyr with npm, refresh the copies stored in the theme:

``` bash
cp node_modules/plyr/dist/plyr.css themes/custom/soundchoice11/css/vendor/plyr.css
cp node_modules/plyr/dist/plyr.js themes/custom/soundchoice11/js/vendor/plyr.js
```

A future improvement would be to add these copy operations to the
project's npm build scripts so vendor assets are refreshed
automatically.

## Key files

``` text
themes/custom/soundchoice11/
├── soundchoice11.libraries.yml
├── soundchoice11.theme
├── css/
│   └── vendor/
│       └── plyr.css
├── js/
│   ├── vendor/
│   │   └── plyr.js
│   └── artist-video.js
└── templates/
    └── content/
        └── node--artist--full.html.twig
```

## Key Drupal configuration

``` text
Artist field:
field_promotional_videos
  → Media entity reference
  → Remote video
  → Unlimited

Remote video source:
field_media_oembed_video

Media view mode:
video_thumbnail

Video thumbnail display:
Thumbnail → Image
Video URL → Disabled
```
