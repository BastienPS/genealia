import { Controller } from '@hotwired/stimulus';

// Autocomplétion des communes françaises via l'API GéoAPI (geo.api.gouv.fr).
//
// Poser `data-controller="commune-autocomplete"` sur un <div> wrapper englobant
// le champ de lieu (target "input") et la liste <ul> des suggestions (target
// "results"). Au fil de la frappe on interroge GéoAPI et on propose les communes
// ; la sélection remplit le champ avec « Nom, Département » et sélectionne
// automatiquement le pays « France » dans le <select> dont le sélecteur CSS est
// donné par la valeur `country-target`.
//
// La saisie libre reste possible : l'autocomplétion est purement suggestive,
// rien n'oblige à choisir une commune (utile pour les villes étrangères).
//
//   <div data-controller="commune-autocomplete"
//        data-commune-autocomplete-country-target-value="#ancestor_birthCountry">
//     <input data-commune-autocomplete-target="input"
//            data-action="input->commune-autocomplete#search keydown->commune-autocomplete#navigate">
//     <ul data-commune-autocomplete-target="results" class="hidden ..."></ul>
//   </div>
export default class extends Controller {
    static targets = ['input', 'results'];
    static values = { countryTarget: String };

    connect() {
        this.timer = null;
        this.abort = null;
        this.activeIndex = -1;
        this.communes = [];

        // Ferme le dropdown si l'on clique en dehors du wrapper.
        this.onOutsideClick = (event) => {
            if (!this.element.contains(event.target)) {
                this.hide();
            }
        };
        document.addEventListener('click', this.onOutsideClick);
    }

    disconnect() {
        document.removeEventListener('click', this.onOutsideClick);
        if (this.timer) clearTimeout(this.timer);
        if (this.abort) this.abort.abort();
    }

    search(event) {
        const query = event.target.value.trim();

        if (query.length < 2) {
            this.hide();
            return;
        }

        clearTimeout(this.timer);
        this.timer = setTimeout(() => this.fetchCommunes(query), 250);
    }

    async fetchCommunes(query) {
        if (this.abort) this.abort.abort();
        this.abort = new AbortController();

        const url = `https://geo.api.gouv.fr/communes?nom=${encodeURIComponent(query)}`
            + `&fields=nom,codesPostaux,departement,region&limit=8`;

        try {
            const response = await fetch(url, { signal: this.abort.signal });
            if (!response.ok) {
                return;
            }
            const data = await response.json();
            this.communes = Array.isArray(data) ? data : [];
            this.render();
        } catch (error) {
            if (error.name !== 'AbortError') {
                this.hide();
            }
        } finally {
            this.abort = null;
        }
    }

    render() {
        if (this.communes.length === 0) {
            this.hide();
            return;
        }

        this.activeIndex = -1;
        this.resultsTarget.innerHTML = this.communes.map((commune, index) => {
            const postal = commune.codesPostaux && commune.codesPostaux.length ? commune.codesPostaux[0] : '';
            const department = commune.departement ? commune.departement.nom : '';
            const hint = postal ? `(${postal})` : '';
            const safe = commune.nom.replace(/</g, '&lt;').replace(/>/g, '&gt;');

            return `<li class="px-3 py-2 cursor-pointer hover:bg-brand-canvas-dark text-sm text-brand-ink/80"
                        data-action="click->commune-autocomplete#select"
                        data-commune-autocomplete-index="${index}">
                        <span class="font-medium text-brand-ink">${safe}</span>
                        ${department ? `<span class="text-brand-ink/50"> — ${department}</span>` : ''}
                        ${hint ? `<span class="text-brand-ink/40"> ${hint}</span>` : ''}
                    </li>`;
        }).join('');

        this.resultsTarget.classList.remove('hidden');
    }

    select(event) {
        const item = event.target.closest('li');
        if (!item) {
            return;
        }
        const index = parseInt(item.dataset.communeAutocompleteIndex, 10);
        const commune = this.communes[index];
        if (!commune) {
            return;
        }

        const department = commune.departement ? commune.departement.nom : '';
        this.inputTarget.value = [commune.nom, department].filter(Boolean).join(', ');
        this.inputTarget.dispatchEvent(new Event('input', { bubbles: true }));

        // Sélection automatique du pays « France ».
        if (this.countryTargetValue) {
            const select = document.querySelector(this.countryTargetValue);
            if (select) {
                select.value = 'FR';
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        this.hide();
        this.inputTarget.focus();
    }

    navigate(event) {
        if (this.resultsTarget.classList.contains('hidden')) {
            return;
        }

        const items = this.resultsTarget.querySelectorAll('li');
        if (items.length === 0) {
            return;
        }

        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                this.activeIndex = (this.activeIndex + 1) % items.length;
                this.highlight();
                break;
            case 'ArrowUp':
                event.preventDefault();
                this.activeIndex = (this.activeIndex - 1 + items.length) % items.length;
                this.highlight();
                break;
            case 'Enter':
                if (this.activeIndex >= 0) {
                    event.preventDefault();
                    items[this.activeIndex].click();
                }
                break;
            case 'Escape':
                this.hide();
                break;
        }
    }

    highlight() {
        const items = this.resultsTarget.querySelectorAll('li');
        items.forEach((item, index) => {
            item.classList.toggle('bg-brand-canvas-dark', index === this.activeIndex);
        });

        const active = items[this.activeIndex];
        if (active) {
            active.scrollIntoView({ block: 'nearest' });
        }
    }

    hide() {
        this.resultsTarget.classList.add('hidden');
        this.resultsTarget.innerHTML = '';
        this.activeIndex = -1;
    }
}