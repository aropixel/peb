import { Controller } from '@hotwired/stimulus';

/*
 * Formulaire de don : n'affiche le champ « montant libre » que si « Autre montant » est choisi.
 */
export default class extends Controller {
    static targets = ['choice', 'custom'];

    connect() {
        this.toggle();
    }

    toggle() {
        const selected = this.choiceTargets.find((input) => input.checked);
        const isOther = selected?.value === 'other';

        this.customTarget.hidden = !isOther;
        if (isOther) {
            this.customTarget.querySelector('input')?.focus();
        }
    }
}
