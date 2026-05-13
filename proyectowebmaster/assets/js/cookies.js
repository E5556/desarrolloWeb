/**
 * Cookie Consent Banner
 * Compatible: jQuery 1.11.1, Bootstrap 3, IE9+
 */
(function ($) {
    'use strict';

    var CookieConsent = {

        COOKIE_NAME: 'cookie_consent',
        DAYS: 365,

        // ── Leer cookie ──────────────────────────────────────────
        getCookie: function (name) {
            var pairs = document.cookie.split(';');
            for (var i = 0; i < pairs.length; i++) {
                var pair = pairs[i].replace(/^\s+/, '');
                if (pair.indexOf(name + '=') === 0) {
                    try {
                        return JSON.parse(decodeURIComponent(pair.substring(name.length + 1)));
                    } catch (e) {
                        return null;
                    }
                }
            }
            return null;
        },

        // ── Escribir cookie ──────────────────────────────────────
        setCookie: function (name, value, days) {
            var expires = '';
            if (days) {
                var d = new Date();
                d.setTime(d.getTime() + days * 24 * 60 * 60 * 1000);
                expires = '; expires=' + d.toUTCString();
            }
            document.cookie = name + '=' + encodeURIComponent(JSON.stringify(value))
                + expires + '; path=/; SameSite=Lax';
        },

        // ── Obtener consentimiento actual ────────────────────────
        getConsent: function () {
            return this.getCookie(this.COOKIE_NAME);
        },

        // ── Guardar consentimiento ───────────────────────────────
        saveConsent: function (analytics, preferences) {
            var obj = { decided: true, analytics: !!analytics, preferences: !!preferences };
            this.setCookie(this.COOKIE_NAME, obj, this.DAYS);
            $(document).trigger('cookieConsentSaved', [obj]);
        },

        // ── Mostrar banner (slide-up) ────────────────────────────
        showBanner: function () {
            var $b = $('#cookie-banner');
            $b.css('display', 'block');
            setTimeout(function () {
                $b.addClass('cookie-visible');
            }, 20);
        },

        // ── Ocultar banner (slide-down) ──────────────────────────
        hideBanner: function () {
            var $b = $('#cookie-banner');
            $b.removeClass('cookie-visible');
            setTimeout(function () {
                $b.css('display', 'none');
            }, 420);
        },

        // ── Pre-rellenar checkboxes con valores guardados ────────
        populateToggles: function () {
            var consent = this.getConsent();
            if (consent) {
                $('#toggle-analytics').prop('checked', !!consent.analytics);
                $('#toggle-preferences').prop('checked', !!consent.preferences);
            }
        },

        // ── Resetear a panel principal ───────────────────────────
        showMainPanel: function () {
            $('#cookie-custom-panel').hide();
            $('#cookie-main-panel').show();
        },

        // ── Inicializar ──────────────────────────────────────────
        init: function () {
            var self = this;
            var consent = this.getConsent();

            // Si ya decidió → no mostrar
            if (consent && consent.decided) return;

            // Mostrar con pequeño retraso
            setTimeout(function () {
                self.showBanner();
            }, 700);

            // ── Aceptar todas ────────────────────────────────────
            $(document).on('click', '#cookie-accept-all', function () {
                self.saveConsent(true, true);
                self.hideBanner();
            });

            // ── Rechazar ─────────────────────────────────────────
            $(document).on('click', '#cookie-reject', function () {
                self.saveConsent(false, false);
                self.hideBanner();
            });

            // ── Personalizar ─────────────────────────────────────
            $(document).on('click', '#cookie-customize', function () {
                self.populateToggles();
                $('#cookie-main-panel').hide();
                $('#cookie-custom-panel').show();
            });

            // ── Volver ───────────────────────────────────────────
            $(document).on('click', '#cookie-back', function () {
                self.showMainPanel();
            });

            // ── Guardar preferencias ──────────────────────────────
            $(document).on('click', '#cookie-save-custom', function () {
                var analytics    = $('#toggle-analytics').is(':checked');
                var preferences  = $('#toggle-preferences').is(':checked');
                self.saveConsent(analytics, preferences);
                self.hideBanner();
            });

            // ── Reabrir desde pie de página ───────────────────────
            $(document).on('click', '#cookie-reopen-link', function (e) {
                e.preventDefault();
                self.showMainPanel();
                self.showBanner();
            });
        }
    };

    // ── Arranque ─────────────────────────────────────────────────
    $(document).ready(function () {
        if ($('#cookie-banner').length) {
            CookieConsent.init();
        }
    });

})(jQuery);
