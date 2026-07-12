import { Controller } from '@hotwired/stimulus';

/*
 * Menu déroulant simple (header) : ouvre/ferme au clic sur le bouton bascule,
 * se ferme au clic extérieur ou sur Échap. Pas deTurbo-frame : c'est juste un
 * toggle de la classe `hidden` sur la target `menu`.
 */
export default class extends Controller {
    static targets = ['menu'];

    connect() {
        this.boundHide = this.hide.bind(this);
        document.addEventListener('click', this.boundHide);
        document.addEventListener('keydown', this.boundHide);
    }

    disconnect() {
        document.removeEventListener('click', this.boundHide);
        document.removeEventListener('keydown', this.boundHide);
    }

    toggle(event) {
        event.stopPropagation();
        this.menuTarget.classList.toggle('hidden');
    }

    hide(event) {
        if (event.type === 'keydown' && event.key !== 'Escape') {
            return;
        }
        if (event.type === 'click' && this.element.contains(event.target)) {
            return;
        }
        this.menuTarget.classList.add('hidden');
    }
}