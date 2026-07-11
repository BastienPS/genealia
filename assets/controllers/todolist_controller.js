import { Controller } from '@hotwired/stimulus';

/*
 * Configurateur de todolist (admin) :
 *  - ajout d'une tâche en AJAX (POST → fragment HTML de la ligne) ;
 *  - bouton « Fait » : toggle du raturage en AJAX (POST → JSON {done}) ;
 *  - bouton « Suppr » : suppression en AJAX (POST → 204, retire la ligne) ;
 *  - glisser-déposer HTML5 natif pour réordonner ; l'ordre est persisté
 *    (POST tasks[]=id) à la fin du glisser.
 *
 * CSRF : le jeton est lu dans le dataset de chaque ligne / du conteneur et
 * envoyé en champ `_token` (double-submit). Turbo n'interfère pas car les
 * appels se font via fetch() brut (le formulaire d'ajout porte data-turbo="false").
 */
export default class extends Controller {
    static targets = ['task', 'list', 'empty', 'addInput'];
    static values = {
        reorderUrl: String,
        reorderCsrf: String,
        newUrl: String,
        newCsrf: String,
    };

    connect() {
        this.draggedRow = null;
    }

    // ---- Ajout de tâche (AJAX → fragment HTML) ----
    async addTask(event) {
        event.preventDefault();
        const input = this.addInputTarget;
        const label = input.value.trim();
        if (label === '') {
            return;
        }
        try {
            const res = await this.postForm(this.newUrlValue, { label: label, _token: this.newCsrfValue });
            if (res.status === 200) {
                const html = await res.text();
                const tpl = document.createElement('template');
                tpl.innerHTML = html.trim();
                const row = tpl.content.firstElementChild;
                this.hideEmpty();
                this.listTarget.appendChild(row);
                input.value = '';
                input.focus();
            } else {
                console.warn('Ajout de tâche échoué : HTTP ' + res.status);
            }
        } catch (e) {
            console.error(e);
        }
    }

    // ---- Toggle « Fait » (AJAX → JSON {done}) ----
    async toggleDone(event) {
        const row = event.currentTarget.closest('[data-todolist-target="task"]');
        if (!row) {
            return;
        }
        try {
            const res = await this.postForm(row.dataset.toggleUrl, { _token: row.dataset.toggleCsrf });
            if (res.status !== 200) {
                return;
            }
            const data = await res.json();
            const done = data.done === true;
            const label = row.querySelector('.js-task-label');
            const btn = row.querySelector('.js-toggle-btn');
            if (done) {
                label.classList.add('line-through', 'text-brand-body');
                row.classList.add('opacity-60');
                btn.textContent = 'Annuler';
                btn.classList.add('text-brand-mint');
                btn.classList.remove('text-brand-ink');
            } else {
                label.classList.remove('line-through', 'text-brand-body');
                row.classList.remove('opacity-60');
                btn.textContent = 'Fait';
                btn.classList.remove('text-brand-mint');
                btn.classList.add('text-brand-ink');
            }
        } catch (e) {
            console.error(e);
        }
    }

    // ---- Suppression (AJAX → 204) ----
    async deleteTask(event) {
        const row = event.currentTarget.closest('[data-todolist-target="task"]');
        if (!row) {
            return;
        }
        try {
            const res = await this.postForm(row.dataset.deleteUrl, { _token: row.dataset.deleteCsrf });
            if (res.status === 204) {
                row.remove();
                if (this.taskTargets.length === 0) {
                    this.showEmpty();
                }
            }
        } catch (e) {
            console.error(e);
        }
    }

    // ---- Glisser-déposer HTML5 natif ----
    dragstart(event) {
        this.draggedRow = event.currentTarget;
        event.dataTransfer.effectAllowed = 'move';
        this.draggedRow.classList.add('opacity-50');
    }

    dragover(event) {
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
        const row = event.currentTarget;
        if (row === this.draggedRow) {
            return;
        }
        const rect = row.getBoundingClientRect();
        const after = (event.clientY - rect.top) > (rect.height / 2);
        if (after) {
            row.parentNode.insertBefore(this.draggedRow, row.nextSibling);
        } else {
            row.parentNode.insertBefore(this.draggedRow, row);
        }
    }

    drop(event) {
        event.preventDefault();
    }

    dragend() {
        if (this.draggedRow) {
            this.draggedRow.classList.remove('opacity-50');
            this.draggedRow = null;
        }
        this.persistOrder();
    }

    async persistOrder() {
        const ids = this.taskTargets.map((row) => row.dataset.id).filter((v) => v);
        if (ids.length === 0) {
            return;
        }
        try {
            const body = new URLSearchParams();
            body.append('_token', this.reorderCsrfValue);
            ids.forEach((id) => body.append('tasks[]', id));
            await fetch(this.reorderUrlValue, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                body,
            });
        } catch (e) {
            console.error(e);
        }
    }

    // ---- États vides ----
    hideEmpty() {
        if (this.hasEmptyTarget) {
            this.emptyTarget.remove();
        }
    }

    showEmpty() {
        if (this.hasEmptyTarget) {
            return;
        }
        const tr = document.createElement('tr');
        tr.setAttribute('data-todolist-target', 'empty');
        tr.className = 'bg-brand-canvas';
        const td = document.createElement('td');
        td.setAttribute('colspan', '3');
        td.className = 'px-6 py-12 text-center text-brand-body italic text-sm';
        td.textContent = 'Aucune tâche pour le moment. Ajoutez-en une ci-dessus.';
        tr.appendChild(td);
        this.listTarget.appendChild(tr);
    }

    // ---- Helper POST formulaire ----
    async postForm(url, data) {
        const body = new URLSearchParams();
        Object.entries(data).forEach(([k, v]) => body.append(k, String(v)));
        return fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            body,
        });
    }
}