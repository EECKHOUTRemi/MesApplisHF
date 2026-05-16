import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.element.addEventListener('chartjs:pre-connect', this._onPreConnect);
    }

    disconnect() {
        this.element.removeEventListener('chartjs:pre-connect', this._onPreConnect);
    }

    _onPreConnect = (event) => {
        const config = event.detail.config;
        config.options = config.options || {};
        config.options.plugins = config.options.plugins || {};
        config.options.plugins.legend = config.options.plugins.legend || {};
        config.options.plugins.legend.labels = config.options.plugins.legend.labels || {};
        config.options.plugins.legend.labels.filter = (item) => item.text !== '__hidden__';
    };
}
