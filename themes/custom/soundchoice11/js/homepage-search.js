(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.soundchoiceHomepageSearch = {
    attach(context) {

      once(
        'soundchoice-homepage-search',
        '.js-homepage-search-examples',
        context
      ).forEach((wrapper) => {

        const section = wrapper.closest('.homepage-search');
        const input = section.querySelector('.js-homepage-search-input');
        const form = section.querySelector('.js-homepage-search-form');
        const example = wrapper.querySelector('.js-homepage-search-example');
        const output = wrapper.querySelector('.js-homepage-search-example-text');

        if (!input || !form || !example || !output) {
          return;
        }

        let examples;

        try {
          examples = JSON.parse(wrapper.dataset.searchExamples);
        }
        catch (error) {
          return;
        }

        if (!examples.length) {
          return;
        }

        const reducedMotion = window.matchMedia(
          '(prefers-reduced-motion: reduce)'
        ).matches;

        let phraseIndex = 0;
        let characterIndex = 0;
        let deleting = false;

        const typeSpeed = 35;
        const deleteSpeed = 18;
        const pauseAfterTyping = 2200;
        const pauseBeforeTyping = 350;

        if (reducedMotion) {
          output.textContent = examples[0];
          return;
        }

        const type = () => {
          const phrase = examples[phraseIndex];

          if (!deleting) {
            characterIndex += 1;
            output.textContent = phrase.substring(0, characterIndex);

            if (characterIndex === phrase.length) {
              deleting = true;
              window.setTimeout(type, pauseAfterTyping);
              return;
            }

            window.setTimeout(type, typeSpeed);
          }
          else {
            characterIndex -= 1;
            output.textContent = phrase.substring(0, characterIndex);

            if (characterIndex === 0) {
              deleting = false;
              phraseIndex = (phraseIndex + 1) % examples.length;
              window.setTimeout(type, pauseBeforeTyping);
              return;
            }

            window.setTimeout(type, deleteSpeed);
          }
        };

        type();

        example.addEventListener('click', () => {
          const phrase = output.textContent.trim();

          if (!phrase) {
            return;
          }

          input.value = phrase;
          form.requestSubmit();
        });

      });
    }
  };

})(Drupal, once);