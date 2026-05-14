import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'icon', 'button'];

    toggle() {
        const show = this.inputTarget.type === 'password';
        this.inputTarget.type = show ? 'text' : 'password';
        this.iconTarget.classList.toggle('bi-eye', !show);
        this.iconTarget.classList.toggle('bi-eye-slash', show);
        this.buttonTarget.setAttribute(
            'aria-label',
            show ? 'Masquer le mot de passe' : 'Afficher le mot de passe',
        );
    }
}
