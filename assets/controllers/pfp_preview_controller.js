import { Controller } from '@hotwired/stimulus';

/** Aperçu instantané de la photo de profil sélectionnée, affiché à côté de l'actuelle. */
export default class extends Controller {
    static targets = ['input', 'preview', 'arrow'];

    show() {
        const file = this.inputTarget.files[0];

        if (!file) {
            return;
        }

        const reader = new FileReader();
        reader.onload = () => {
            this.previewTarget.src = reader.result;
            this.previewTarget.classList.remove('d-none');
            this.arrowTarget.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    }
}
