document.addEventListener('DOMContentLoaded', () => {
    var sliderElement = document.querySelector('.range-slider');
    if (sliderElement) {
        var inputSelector = sliderElement.getAttribute('data-input-id');
        var hiddenInput = document.querySelector(inputSelector);
        if (hiddenInput) {
            var min = parseInt(sliderElement.getAttribute('data-min'));
            var max = parseInt(sliderElement.getAttribute('data-max'));
            var valuesForSlider = [];
            for (var i = min; i <= max; i++) {
                valuesForSlider.push(i);
            }

            var start = [valuesForSlider[0], valuesForSlider[valuesForSlider.length - 1]];
            var value = sliderElement.getAttribute('data-value');
            if (value !== null) {
                value = value.split("|");
                if (value.length === 2) {
                    start = value;
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
            slider.on('end', function(values) {
                hiddenInput.value = values[0] + "|" + values[1];
            });
        }
    }
});
