document.addEventListener('DOMContentLoaded', () => {
    // debounce delay (ms) before the filter is actually applied once the user stops moving a handle
    var FILTER_DEBOUNCE_DELAY = 500;

    document.querySelectorAll('.range-slider').forEach(function (sliderElement) {
        var inputSelector = sliderElement.getAttribute('data-input-id');
        var hiddenInput = document.querySelector(inputSelector);
        if (!hiddenInput) {
            return;
        }

        var min = parseInt(sliderElement.getAttribute('data-min'));
        var max = parseInt(sliderElement.getAttribute('data-max'));
        var valuesForSlider = [];
        for (var i = min; i <= max; i++) {
            valuesForSlider.push(i);
        }

        var start = [valuesForSlider[0], valuesForSlider[valuesForSlider.length - 1]];
        var initialValue = sliderElement.getAttribute('data-value');
        if (initialValue !== null && initialValue !== '') {
            var initialParts = initialValue.split("|");
            if (initialParts.length === 2) {
                start = initialParts;
            }
        }

        var slider = noUiSlider.create(sliderElement, {
            start: start,
            step: 1,
            tooltips: true,
            connect: true,
            range: {
                'min': 0,
                'max': valuesForSlider.length - 1,
            },
            format: {
                to: function (value) {
                    return valuesForSlider[Math.round(value)];
                },
                from: function (value) {
                    return valuesForSlider.indexOf(Number(value));
                }
            }
        });

        // Only treat the slider as "touched" after a real pointer/keyboard interaction. noUiSlider's
        // own events ('update', 'end', ...) can also fire from creation, resize or other passive
        // re-renders, which previously caused the hidden input to be filled/submitted on its own.
        var userInteracted = false;
        sliderElement.addEventListener('pointerdown', function () {
            userInteracted = true;
        });
        sliderElement.addEventListener('keydown', function () {
            userInteracted = true;
        });

        var debounceTimer = null;
        slider.on('end', function (values) {
            if (!userInteracted) {
                return;
            }

            hiddenInput.value = values[0] + "|" + values[1];

            if (debounceTimer) {
                clearTimeout(debounceTimer);
            }
            debounceTimer = setTimeout(function () {
                hiddenInput.dispatchEvent(new Event('change', {bubbles: true}));
            }, FILTER_DEBOUNCE_DELAY);
        });
    });
});
