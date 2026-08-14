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

        const player = new Plyr(playerElement, {
          captions: {
            active: false,
            language: 'auto',
            update: false
          }
        });

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