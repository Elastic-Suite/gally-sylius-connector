import { Controller } from '@hotwired/stimulus';
import noUiSlider from '../../../public/nouislider.min.js';
import '../../../public/nouislider.min.css';
import '../../../public/slider.css';

export default class extends Controller {
    static targets = ['slider', 'input'];
    static values = {
        min: Number,
        max: Number,
        value: String,
    };

    connect() {
        const valuesForSlider = [];
        for (let i = this.minValue; i <= this.maxValue; i++) {
            valuesForSlider.push(i);
        }

        let start = [valuesForSlider[0], valuesForSlider[valuesForSlider.length - 1]];
        if (this.hasValueValue) {
            const parts = this.valueValue.split('|');
            if (parts.length === 2) {
                start = parts;
            }
        }

        this.slider = noUiSlider.create(this.sliderTarget, {
            start,
            step: 1,
            tooltips: true,
            connect: true,
            range: {
                min: 0,
                max: valuesForSlider.length - 1,
            },
            format: {
                to: (value) => valuesForSlider[Math.round(value)],
                from: (value) => valuesForSlider.indexOf(Number(value)),
            },
        });

        this.slider.on('end', (values) => {
            this.inputTarget.value = `${values[0]}|${values[1]}`;
        });
    }

    disconnect() {
        if (this.slider) {
            this.slider.destroy();
            this.slider = null;
        }
    }
}
