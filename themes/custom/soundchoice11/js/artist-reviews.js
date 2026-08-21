(function (Drupal, once) {

  Drupal.behaviors.artistReviews = {
    attach(context) {

      once('artist-reviews', '.artist-reviews', context).forEach((reviews) => {

        const track = reviews.querySelector('.view-content');
        const previous = reviews.querySelector('.artist-reviews__nav--previous');
        const next = reviews.querySelector('.artist-reviews__nav--next');

        if (!track || !previous || !next) {
          return;
        }

        const updateNavigation = () => {
          const maxScroll = track.scrollWidth - track.clientWidth;

          previous.disabled = track.scrollLeft <= 1;
          next.disabled = track.scrollLeft >= maxScroll - 1;
        };

        const scrollReviews = (direction) => {
          const row = track.querySelector('.views-row');

          if (!row) {
            return;
          }

          const gap = parseFloat(getComputedStyle(track).columnGap) || 0;
          const distance = row.getBoundingClientRect().width + gap;

          track.scrollBy({
            left: distance * direction,
            behavior: 'smooth',
          });
        };

        previous.addEventListener('click', () => {
          scrollReviews(-1);
        });

        next.addEventListener('click', () => {
          scrollReviews(1);
        });

        track.addEventListener('scroll', updateNavigation, {
          passive: true,
        });

        window.addEventListener('resize', updateNavigation);

        updateNavigation();
      });
    },
  };

})(Drupal, once);