import { Controller } from '@hotwired/stimulus';

/*
 * Ouvre la popin (modal UIkit) si le visiteur ne l'a pas encore vue.
 *
 * Le cookie est lu côté navigateur : la page peut donc rester en cache (Varnish).
 * Sa valeur change quand la popin est modifiée, ce qui la réaffiche à tout le monde.
 * Avec « force », la popin s'ouvre à chaque chargement et aucun cookie n'est posé.
 */
export default class extends Controller {
    static values = {
        cookieName: String,
        cookieValue: String,
        force: Boolean,
    };

    connect() {
        if (!this.forceValue && this.readCookie() === this.cookieValueValue) {
            return;
        }

        UIkit.modal(this.element).show();

        if (!this.forceValue) {
            const maxAge = 60 * 60 * 24 * 365;
            document.cookie = `${this.cookieNameValue}=${this.cookieValueValue}; path=/; max-age=${maxAge}; SameSite=Lax`;
        }
    }

    readCookie() {
        const prefix = `${this.cookieNameValue}=`;
        const cookie = document.cookie.split('; ').find((c) => c.startsWith(prefix));

        return cookie ? cookie.slice(prefix.length) : null;
    }
}
