document.addEventListener('DOMContentLoaded', function () {

    // ===== INPUT FILE =====
    document.querySelectorAll('.input-file-wrapper input[type="file"]').forEach(function(input) {
        input.addEventListener('change', function() {
            const wrapper = this.closest('.input-file-wrapper');
            if (!wrapper) return;
            const nameEl = wrapper.parentElement.querySelector('.input-file-name');
            if (nameEl) nameEl.textContent = this.files[0] ? this.files[0].name : '';
        });
    });

    // ===== SELECT RÔLE =====
    document.querySelectorAll('.select-role-wrapper select').forEach(function(select) {
        function updateColor() {
            if (select.value === 'technicien')    select.style.color = '#A78BFA';
            else if (select.value === 'client')   select.style.color = '#3FB950';
            else                                  select.style.color = '';
        }
        select.addEventListener('change', updateColor);
        updateColor();
    });

    // ===== CLIC LIGNE → PLEIN ÉCRAN =====
    document.querySelectorAll('tbody').forEach(function(tbody) {
        tbody.addEventListener('click', function(e) {
            if (e.target.closest('button') ||
                e.target.closest('a') ||
                e.target.closest('form') ||
                e.target.closest('select')) return;

            const row = e.target.closest('tr');
            if (!row) return;
            if (row.closest('.modale-overlay')) return;

            const table = row.closest('table');
            if (!table) return;

            const existing = document.getElementById('tableFullscreenAuto');
            if (existing) existing.remove();

            const overlay = document.createElement('div');
            overlay.id = 'tableFullscreenAuto';
            overlay.style.cssText = `
                position:fixed; inset:0;
                background:rgba(0,0,0,0.88);
                z-index:998;
                display:flex;
                align-items:center;
                justify-content:center;
                padding:40px;
                backdrop-filter:blur(6px);
            `;

            const inner = document.createElement('div');
            inner.style.cssText = `
                background:#0C1830;
                border:1px solid rgba(212,175,55,0.2);
                border-radius:12px;
                width:100%;
                max-width:1300px;
                max-height:85vh;
                overflow-y:auto;
                box-shadow:0 4px 40px rgba(0,0,0,0.6);
            `;

            const header = document.createElement('div');
            header.style.cssText = `
                display:flex;
                justify-content:space-between;
                align-items:center;
                padding:16px 24px;
                border-bottom:1px solid rgba(212,175,55,0.2);
                position:sticky;
                top:0;
                background:#0C1830;
                z-index:1;
            `;
            header.innerHTML = `
                <span style="font-size:1rem;font-weight:600;color:#E6EDF3;">Vue détaillée</span>
                <button id="closeFullscreenAuto" style="
                    background:transparent;
                    border:1px solid rgba(212,175,55,0.2);
                    color:#8BA3C0;
                    padding:6px 14px;
                    border-radius:6px;
                    cursor:pointer;
                    font-size:0.85rem;
                    font-family:inherit;
                ">✕ Fermer</button>
            `;

            const tableClone = table.cloneNode(true);
            tableClone.style.cssText = 'border-radius:0;border:none;margin:0;font-size:0.95rem;';
            tableClone.querySelectorAll('button, a.btn').forEach(function(el) {
                el.style.pointerEvents = 'none';
                el.style.opacity = '0.4';
            });

            inner.appendChild(header);
            inner.appendChild(tableClone);
            overlay.appendChild(inner);
            document.body.appendChild(overlay);

            document.getElementById('closeFullscreenAuto').addEventListener('click', function() {
                overlay.remove();
            });

            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) overlay.remove();
            });

            document.addEventListener('keydown', function escHandler(e) {
                if (e.key === 'Escape') {
                    overlay.remove();
                    document.removeEventListener('keydown', escHandler);
                }
            });
        });
    });

    // ===== MODALE ASSIGNATION =====
    const modale = document.getElementById('modaleAssigner');

    document.querySelectorAll('.btn-assigner').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const idIncident = this.getAttribute('data-id');
            const titre      = this.getAttribute('data-titre');

            const label = document.getElementById('modaleIncidentTitre');
            if (label) label.textContent = 'Incident : ' + titre;

            document.querySelectorAll('.hidden-incident-id').forEach(function(input) {
                input.value = idIncident;
            });

            if (modale) modale.classList.add('active');
        });
    });

    const fermer = document.getElementById('fermerModale');
    if (fermer) {
        fermer.addEventListener('click', function() {
            if (modale) modale.classList.remove('active');
        });
    }

    // ===== TRI TECHNICIENS =====
    const triBtnNbTaches = document.getElementById('triBtnNbTaches');
    if (triBtnNbTaches) {
        triBtnNbTaches.addEventListener('change', function() {
            const tbody = document.getElementById('techniciensTbody');
            if (!tbody) return;
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const val  = this.value;
            rows.sort(function(a, b) {
                const ta = parseInt(a.getAttribute('data-taches') || 0);
                const tb = parseInt(b.getAttribute('data-taches') || 0);
                return val === 'asc' ? ta - tb : tb - ta;
            });
            rows.forEach(function(row) { tbody.appendChild(row); });
        });
    }

    // ===== TRI INCIDENTS PAR PRIORITÉ =====
    const triIncidentPriorite = document.getElementById('triIncidentPriorite');
    if (triIncidentPriorite) {
        triIncidentPriorite.addEventListener('change', function() {
            const ordre = this.value;
            const tbody = document.getElementById('incidentsTbody');
            if (!tbody || !ordre) return;
            const prioriteOrdre = { critique: 3, moyen: 2, faible: 1 };
            const rows = Array.from(tbody.querySelectorAll('tr'));
            rows.sort(function(a, b) {
                const pa = prioriteOrdre[a.getAttribute('data-priorite')] || 0;
                const pb = prioriteOrdre[b.getAttribute('data-priorite')] || 0;
                return ordre === 'desc' ? pb - pa : pa - pb;
            });
            rows.forEach(function(row) { tbody.appendChild(row); });
        });
    }

    // ===== TRI INCIDENTS PAR STATUT =====
    const triIncidentStatut = document.getElementById('triIncidentStatut');
    if (triIncidentStatut) {
        triIncidentStatut.addEventListener('change', function() {
            const statutCible = this.value;
            const tbody = document.getElementById('incidentsTbody');
            if (!tbody || !statutCible) return;

            const statutOrdre = { resolu: 3, en_cours: 2, ouvert: 1 };
            const rows = Array.from(tbody.querySelectorAll('tr'));

            rows.sort(function(a, b) {
                const sa = a.getAttribute('data-statut') === statutCible ? 1 : 0;
                const sb = b.getAttribute('data-statut') === statutCible ? 1 : 0;
                return sb - sa;
            });

            rows.forEach(function(row) { tbody.appendChild(row); });
        });
    }

    // ===== TRI PRIORITÉ TECHNICIEN =====
    const triPriorite = document.getElementById('triPriorite');
    if (triPriorite) {
        triPriorite.addEventListener('change', function() {
            const ordre = this.value;
            const tbody = document.getElementById('techIncidentsTbody');
            if (!tbody || !ordre) return;
            const prioriteOrdre = { critique: 3, moyen: 2, faible: 1 };
            const rows = Array.from(tbody.querySelectorAll('tr'));
            rows.sort(function(a, b) {
                const pa = prioriteOrdre[a.getAttribute('data-priorite')] || 0;
                const pb = prioriteOrdre[b.getAttribute('data-priorite')] || 0;
                return ordre === 'desc' ? pb - pa : pa - pb;
            });
            rows.forEach(function(row) { tbody.appendChild(row); });
        });
    }

    // ===== FILTRE STATUT UTILISATEURS =====
    const filtreStatutUser = document.getElementById('filtreStatutUser');
    if (filtreStatutUser) {
        filtreStatutUser.addEventListener('change', function() {
            const val  = this.value;
            const rows = document.querySelectorAll('#usersTbody tr');
            rows.forEach(function(row) {
                const statut = row.getAttribute('data-statut');
                row.style.display = (val === 'tous' || statut === val) ? '' : 'none';
            });
        });
    }

    // ===== CONFIRMATION ARCHIVAGE =====
    document.querySelectorAll('.btn-archiver').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (!confirm('Confirmer l\'archivage de cet incident ?')) e.preventDefault();
        });
    });

    // ===== THÈME CLAIR/SOMBRE =====
    const toggleTheme = document.getElementById('toggleTheme');
    const themeIcon   = document.getElementById('themeIcon');
    const themeLabel  = document.getElementById('themeLabel');

    const savedTheme = localStorage.getItem('trackInsiTheme');
    if (savedTheme === 'light') {
        document.body.classList.add('theme-light');
        if (themeIcon)  themeIcon.textContent  = '🌙';
        if (themeLabel) themeLabel.textContent = 'Thème sombre';
    }

    if (toggleTheme) {
        toggleTheme.addEventListener('click', function() {
            document.body.classList.toggle('theme-light');
            const isLight = document.body.classList.contains('theme-light');
            localStorage.setItem('trackInsiTheme', isLight ? 'light' : 'dark');
            if (themeIcon)  themeIcon.textContent  = isLight ? '🌙' : '☀';
            if (themeLabel) themeLabel.textContent = isLight ? 'Thème sombre' : 'Thème clair';
        });
    }

});