import { Controller } from '@hotwired/stimulus';
import noUiSlider from '../../../public/nouislider.min.js';
import '../../../public/nouislider.min.css';
import '../../../public/slider.css';

// debounce delay (ms) before the filter is actually applied once the user stops moving a handle
const FILTER_DEBOUNCE_DELAY = 500;

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

        // Only treat the slider as "touched" after a real pointer/keyboard interaction. noUiSlider's
        // own events ('update', 'end', ...) can also fire from creation, resize or other passive
        // re-renders, which would otherwise fill/submit the hidden input on its own.
        this.userInteracted = false;
        this.sliderTarget.addEventListener('pointerdown', () => {
            this.userInteracted = true;
        });
        this.sliderTarget.addEventListener('keydown', () => {
            this.userInteracted = true;
        });

        this.debounceTimer = null;
        this.slider.on('end', (values) => {
            if (!this.userInteracted) {
                return;
            }

            this.inputTarget.value = `${values[0]}|${values[1]}`;

            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this.inputTarget.dispatchEvent(new Event('change', { bubbles: true }));
            }, FILTER_DEBOUNCE_DELAY);
        });
    }

    disconnect() {
        clearTimeout(this.debounceTimer);
        if (this.slider) {
            this.slider.destroy();
            this.slider = null;
        }
    }
}
