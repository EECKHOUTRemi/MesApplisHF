import { Controller } from '@hotwired/stimulus';

const SCRIPT_URL = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
let scriptPromise = null;

/** Charge une seule fois le script Turnstile de Cloudflare. */
function loadTurnstile() {
    if (window.turnstile) {
        return Promise.resolve(window.turnstile);
    }
    scriptPromise ??= new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = SCRIPT_URL;
        script.async = true;
        script.onload = () => resolve(window.turnstile);
        script.onerror = () => {
            scriptPromise = null;
            reject(new Error('Impossible de charger Cloudflare Turnstile'));
        };
        document.head.appendChild(script);
    });

    return scriptPromise;
}

/*
 * Affiche le widget Cloudflare Turnstile et recopie le jeton obtenu dans le
 * champ caché du formulaire Symfony (vérifié côté serveur par TurnstileValidator).
 */
export default class extends Controller {
    static targets = ['widget', 'input'];
    static values = { siteKey: String };

    async connect() {
        const turnstile = await loadTurnstile();
        if (!this.element.isConnected) {
            return;
        }
        this.widgetId = turnstile.render(this.widgetTarget, {
            sitekey: this.siteKeyValue,
            language: 'fr',
            'response-field': false,
            callback: (token) => { this.inputTarget.value = token; },
            'expired-callback': () => { this.inputTarget.value = ''; },
            'error-callback': () => { this.inputTarget.value = ''; },
        });
    }

    disconnect() {
        if (this.widgetId !== undefined && window.turnstile) {
            window.turnstile.remove(this.widgetId);
            this.widgetId = undefined;
        }
    }
}
