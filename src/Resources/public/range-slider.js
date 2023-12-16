$(document).ready(function() {
    var sliderElement = $('.range-slider');
    var hiddenInput = $(sliderElement.attr('data-input-id'));

    var min = parseInt(sliderElement.attr('data-min'));
    var max = parseInt(sliderElement.attr('data-max'));
    var valuesForSlider = [];
    for (var i = min; i <= max; i++) {
        valuesForSlider.push(i);
    }

    var start = [valuesForSlider[0], valuesForSlider[valuesForSlider.length - 1]];
    var value = sliderElement.attr('data-value').split(";");
    if (value.length === 2) {
        start = value;
    }

    var slider = noUiSlider.create(sliderElement[0], {
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
        hiddenInput.val(values[0] + ";" + values[1]);
    });
});
