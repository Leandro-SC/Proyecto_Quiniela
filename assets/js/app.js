/**
 * app.js - FUSIÓN MAESTRA vFinal
 * 1. Validación Matemática (de quiniela_core.js)
 * 2. Cronómetro y API (de app.js)
 * 3. Limpieza de eventos (Anti-duplicados)
 */

(function () {
    'use strict';

    // --- 1. CANDADO ANTI-DOBLE CARGA ---
    if (window.QuinielaAppLoaded) {
        console.warn("⚠️ app.js ya estaba cargado. Omitiendo.");
        return;
    }
    window.QuinielaAppLoaded = true;
    console.log("✅ app.js Fusión Maestra cargada.");

    var state = {
        baseAmount: 10,
        currency: 'USD',
        leagueSlug: 'liga-mx',
        leagueLabel: 'Liga MX',
        matchdayLabel: 'Jornada',
        matchesLoaded: true,
        roundId: 0
    };

    // --- CONFIGURACIÓN INICIAL ---
    function getRootConfig() {
        var root = document.getElementById('quiniela-root');
        if (!root) return;
        state.baseAmount = parseFloat(root.getAttribute('data-ticket-amount') || '10');
        state.currency = root.getAttribute('data-currency') || 'USD';
        state.leagueLabel = root.getAttribute('data-league') || 'Liga MX';
        state.matchdayLabel = root.getAttribute('data-matchday') || 'Jornada';
        state.roundId = parseInt(root.getAttribute('data-round-id') || '0');
    }

    function initLeagueButtons() {
        document.querySelectorAll('.btn-league').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var slug = btn.getAttribute('data-league') || 'liga-mx';
                window.location.href = '/quiniela?league=' + encodeURIComponent(slug);
            });
        });
    }

    function initNavbarBehavior() {
        var navbar = document.querySelector('.navbar');
        var collapse = document.getElementById('mainNavbar');

        if (!navbar || !collapse) return;

        // Al empezar a abrir el menú
        collapse.addEventListener('show.bs.collapse', function () {
            navbar.classList.add('menu-open');
        });

        // Al empezar a cerrar el menú
        collapse.addEventListener('hide.bs.collapse', function () {
            navbar.classList.remove('menu-open');
        });
    }

    // --- HERRAMIENTAS ---
    function showAlert(title, message, icon) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ title: title, text: message, icon: icon || 'info', confirmButtonColor: '#3085d6' });
        } else {
            alert(message);
        }
    }

    // Esta función elimina los "fantasmas" (eventos viejos) reemplazando el botón
    function cleanReplaceElement(id) {
        var el = document.getElementById(id);
        if (!el) return null;
        var newEl = el.cloneNode(true);
        el.parentNode.replaceChild(newEl, el);
        return newEl;
    }

    // --- LÓGICA PRINCIPAL ---
    function initTickets() {
        var matchesTableBody = document.getElementById('matches-table-body');

        // REEMPLAZO DE BOTONES (CRUCIAL para evitar envíos dobles)
        var btnAddTicket = cleanReplaceElement('btn-add-ticket');
        var btnSendWhatsapp = cleanReplaceElement('btn-send-whatsapp');

        var ticketsSummaryBody = document.getElementById('tickets-summary-body');
        var ticketsCountBadge = document.getElementById('tickets-count-badge');
        var ticketsTotalAmount = document.getElementById('tickets-total-amount');
        var inputName = document.getElementById('player-name');
        var inputPhone = document.getElementById('player-phone');

        if (!matchesTableBody || !btnAddTicket) return;

        // Detectar si hay partidos
        state.matchesLoaded = matchesTableBody.querySelector('button.btn-choice') !== null;
        var tickets = [];

        // A. CLIC EN OPCIONES (L/E/V)
        matchesTableBody.onclick = function (event) {
            var btn = event.target.closest('button.btn-choice');
            if (!btn || btn.disabled) return;

            var tr = btn.closest('tr');
            if (!tr) return;

            // Limpiar fila
            tr.querySelectorAll('button.btn-choice').forEach(function (b) {
                b.classList.remove('active', 'btn-primary', 'text-white');
            });
            // Activar botón
            btn.classList.add('active', 'btn-primary', 'text-white');
            tr.classList.remove('table-danger');
        };

        // --- 1. LÓGICA BOTÓN ALEATORIO ---
        // --- 1. LÓGICA BOTÓN ALEATORIO (CORREGIDO) ---
        // Aceptamos ambos IDs por si acaso ('btn-random-fill' o 'btn-random-pick')
        var btnRandom = document.getElementById('btn-random-fill') || document.getElementById('btn-random-pick');

        if (btnRandom) {
            btnRandom.addEventListener('click', function (e) {
                e.preventDefault(); // Evitar saltos de página

                // Efecto visual de carga
                var originalContent = btnRandom.innerHTML;
                btnRandom.disabled = true;
                btnRandom.innerHTML = '<span class="spinner-border spinner-border-sm"></span> ...';

                // Pequeño retardo para que se note la acción
                setTimeout(function () {
                    var rows = matchesTableBody.querySelectorAll('tr'); // Seleccionamos todas las filas

                    rows.forEach(function (row) {
                        // Buscar los 3 botones de opción (L, E, V) en esta fila
                        var buttons = row.querySelectorAll('.btn-choice');

                        // Solo actuamos si encontramos los 3 botones (para evitar filas de cabecera vacías)
                        if (buttons.length >= 3) {
                            // Limpiar selección previa en esta fila visualmente
                            buttons.forEach(function (b) {
                                b.classList.remove('active', 'btn-primary', 'text-white');
                            });

                            // Elegir índice al azar: 0, 1 o 2
                            var randomPick = Math.floor(Math.random() * 3);
                            var selectedBtn = buttons[randomPick];

                            // Simular clic o activar manualmente
                            if (selectedBtn) {
                                // Opción A: Simular clic (activa la lógica de validación visual existente)
                                selectedBtn.click();

                                // Opción B: Forzar estilos (si el clic falla por alguna razón)
                                // selectedBtn.classList.add('active', 'btn-primary', 'text-white');
                            }
                        }
                    });

                    // Restaurar botón
                    btnRandom.disabled = false;
                    btnRandom.innerHTML = originalContent;

                    // Mensaje de éxito
                    if (typeof Swal !== 'undefined') {
                        const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 1000 });
                        Toast.fire({ icon: 'success', title: '¡Azar completado!' });
                    }
                }, 300);
            });
        } else {
            console.warn("⚠️ Botón aleatorio no encontrado en el HTML (ID: btn-random-pick)");
        }

        // --- 2. LÓGICA ELIMINAR TICKET (Delegación) ---
        ticketsSummaryBody.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-delete-row');

            if (!btn) {
                return;
            }

            var index = parseInt(btn.getAttribute('data-index'), 10);
            var row = btn.closest('tr');

            if (!Number.isInteger(index) || index < 0) {
                return;
            }

            if (row) {
                row.classList.add('ticket-summary-row--removing');

                window.setTimeout(function () {
                    tickets.splice(index, 1);
                    renderTickets(tickets, ticketsSummaryBody, ticketsCountBadge, ticketsTotalAmount, btnSendWhatsapp);
                }, 220);
            } else {
                tickets.splice(index, 1);
                renderTickets(tickets, ticketsSummaryBody, ticketsCountBadge, ticketsTotalAmount, btnSendWhatsapp);
            }

            if (typeof Swal !== 'undefined') {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 1300
                });

                Toast.fire({
                    icon: 'info',
                    title: 'Quiniela eliminada'
                });
            }
        });
        // B. AGREGAR TICKET (Con la lógica matemática de quiniela_core.js)
        btnAddTicket.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var name = inputName.value.trim();
            var phone = inputPhone.value.trim();

            if (!name || !phone) return showAlert('Faltan datos', 'Completa nombre y celular.', 'warning');
            if (!state.matchesLoaded) return showAlert('Error', 'No hay partidos disponibles.', 'error');

            // --- VALIDACIÓN MATEMÁTICA ---

            // 1. Contar filas de partidos (buscamos las que tienen atributo data-match-id)
            var allRows = matchesTableBody.querySelectorAll('tr[data-match-id]');
            // Fallback: si no tienen ID, contamos todas las que tengan botones
            if (allRows.length === 0) {
                allRows = Array.from(matchesTableBody.querySelectorAll('tr')).filter(function (r) {
                    return r.querySelector('button.btn-choice');
                });
            }

            var totalMatches = allRows.length;

            // 2. Contar selecciones activas
            var activeButtons = matchesTableBody.querySelectorAll('button.btn-choice.active');
            var totalSelected = activeButtons.length;

            // 3. COMPARACIÓN DIRECTA
            if (totalSelected < totalMatches) {
                // Pintar de rojo lo que falta
                allRows.forEach(function (row) {
                    if (!row.querySelector('button.btn-choice.active')) {
                        row.classList.add('table-danger');
                    }
                });

                var missing = totalMatches - totalSelected;
                return showAlert('Quiniela Incompleta', 'Te faltan seleccionar ' + missing + ' partido(s).', 'error');
            }

            // --- FIN VALIDACIÓN ---

            // Construir datos del ticket
            var sequence = [];
            var selections = [];
            var selectionDetails = [];

            allRows.forEach(function (row) {
                var btn = row.querySelector('button.btn-choice.active');
                var choice = btn ? (btn.getAttribute('data-choice') || '') : '';
                var matchId = parseInt(row.getAttribute('data-match-id') || '0', 10);

                sequence.push(choice);
                selections.push({
                    match_id: matchId,
                    pick: choice
                });

                selectionDetails.push(extractTicketMatchDetail(row, choice));
            });

            tickets.push({
                name: name,
                phone: phone,
                sequence: sequence.join('-'),
                amount: state.baseAmount,
                selections: selections,
                details: selectionDetails
            });

            renderTickets(tickets, ticketsSummaryBody, ticketsCountBadge, ticketsTotalAmount, btnSendWhatsapp);
            cleanSelections(matchesTableBody);

            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'success', title: 'Ticket Agregado', timer: 1000, showConfirmButton: false });
            }
        });

        // C. ENVIAR A BASE DE DATOS (Recuperado de app.js)
        if (btnSendWhatsapp) {
            btnSendWhatsapp.addEventListener('click', function (e) {
                e.preventDefault();
                if (tickets.length === 0) return;

                if (typeof Swal !== 'undefined') {
                    Swal.fire({ title: 'Procesando...', text: 'Guardando en base de datos...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                }

                var payload = {
                    name: inputName.value.trim(),
                    phone: inputPhone.value.trim(),
                    tickets: tickets.map(function (t) {
                        return { sequence: t.sequence, amount: t.amount, selections: t.selections };
                    }),
                    league: state.leagueLabel,
                    matchday: state.matchdayLabel,
                    round_id: state.roundId
                };

                fetch('/api/tickets/create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (typeof Swal !== 'undefined') Swal.close();

                        if (data.success && data.whatsAppUrl) {
                            // Éxito: abrir WhatsApp
                            window.location.href = data.whatsAppUrl;
                            // Opcional: window.location.reload(); 
                        } else {
                            showAlert('Error', data.message || 'No se pudo guardar.', 'error');
                        }
                    })
                    .catch(function (err) {
                        if (typeof Swal !== 'undefined') Swal.close();
                        console.error(err);
                        showAlert('Error', 'Fallo de conexión.', 'error');
                    });
            });
        }
    }

    // --- UI HELPERS ---

    function getSafeText(element) {
        return element ? String(element.textContent || '').trim() : '';
    }

    function escapeAttr(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function resolveChoiceLabel(choice) {
        if (choice === 'L') return 'Gana local';
        if (choice === 'E') return 'Empate';
        if (choice === 'V') return 'Gana visita';
        return 'Sin selección';
    }

    function resolveChoiceClass(choice) {
        if (choice === 'L') return 'local';
        if (choice === 'E') return 'empate';
        if (choice === 'V') return 'visita';
        return 'default';
    }

    function extractTicketMatchDetail(row, choice) {
        var teamBoxes = row.querySelectorAll('.ch-team-box');
        var homeBox = teamBoxes[0] || null;
        var awayBox = teamBoxes[1] || null;

        var homeName = getSafeText(homeBox ? homeBox.querySelector('span') : null) || 'Local';
        var awayName = getSafeText(awayBox ? awayBox.querySelector('span') : null) || 'Visita';

        var homeLogo = homeBox && homeBox.querySelector('img')
            ? homeBox.querySelector('img').getAttribute('src') || ''
            : '';

        var awayLogo = awayBox && awayBox.querySelector('img')
            ? awayBox.querySelector('img').getAttribute('src') || ''
            : '';

        var matchDate = getSafeText(row.querySelector('.match-date'));
        var matchTime = getSafeText(row.querySelector('.match-time'));
        var leagueAbbr = getSafeText(row.querySelector('.match-league-badge'));

        return {
            home_name: homeName,
            away_name: awayName,
            home_logo: homeLogo,
            away_logo: awayLogo,
            pick: choice || '',
            date: matchDate,
            time: matchTime,
            league: leagueAbbr
        };
    }

    function renderTicketSelectionsDetail(details, fallbackSequence) {
        if (!Array.isArray(details) || details.length === 0) {
            return `
                <div class="ticket-picks-fallback">
                    ${escapeHtml(fallbackSequence || 'Sin detalle disponible')}
                </div>
            `;
        }

        return `
            <div class="ticket-picks-list">
                ${details.map(function (item) {
            var choiceClass = resolveChoiceClass(item.pick);
            var choiceLabel = resolveChoiceLabel(item.pick);
            var metaParts = [];

            if (item.league) metaParts.push(escapeHtml(item.league));
            if (item.date) metaParts.push(escapeHtml(item.date));
            if (item.time) metaParts.push(escapeHtml(item.time));

            return `
                        <div class="ticket-pick-item">
                            <div class="ticket-pick-teams">
                                <div class="ticket-pick-team">
                                    ${item.home_logo ? `<img src="${escapeAttr(item.home_logo)}" alt="${escapeAttr(item.home_name)}">` : ''}
                                    <span>${escapeHtml(item.home_name)}</span>
                                </div>

                                <div class="ticket-pick-vs">vs</div>

                                <div class="ticket-pick-team ticket-pick-team--away">
                                    ${item.away_logo ? `<img src="${escapeAttr(item.away_logo)}" alt="${escapeAttr(item.away_name)}">` : ''}
                                    <span>${escapeHtml(item.away_name)}</span>
                                </div>
                            </div>

                            <div class="ticket-pick-footer">
                                <span class="ticket-pick-badge ticket-pick-badge--${choiceClass}">
                                    ${escapeHtml(choiceLabel)}
                                </span>

                                <span class="ticket-pick-match-meta">
                                    ${metaParts.join(' · ')}
                                </span>
                            </div>
                        </div>
                    `;
        }).join('')}
            </div>
        `;
    }

    function renderTickets(tickets, tbody, badge, totalEl, btnSend) {
        tbody.innerHTML = '';
        var total = 0;

        tickets.forEach(function (t, i) {
            var tr = document.createElement('tr');
            tr.className = 'ticket-summary-row ticket-summary-row--enter';

            tr.innerHTML = `
                <td>${i + 1}</td>
                <td>${escapeHtml(t.name)}</td>
                <td>${escapeHtml(t.phone)}</td>
                <td class="ticket-picks-cell">
                    ${renderTicketSelectionsDetail(t.details || [], t.sequence || '')}
                </td>
                <td class="text-end fw-bold">${formatAmount(t.amount, state.currency)}</td>
                <td class="text-center">
                    <button
                        type="button"
                        class="btn btn-delete-row"
                        data-index="${i}"
                        title="Eliminar este ticket"
                        aria-label="Eliminar quiniela ${i + 1}">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </td>
            `;

            tbody.appendChild(tr);

            window.requestAnimationFrame(function () {
                tr.classList.add('is-visible');
            });

            total += Number(t.amount || 0);
        });

        badge.textContent = tickets.length;
        totalEl.textContent = formatAmount(total, state.currency);

        badge.classList.remove('qv-total-pop');
        totalEl.classList.remove('qv-total-pop');

        void badge.offsetWidth;
        void totalEl.offsetWidth;

        badge.classList.add('qv-total-pop');
        totalEl.classList.add('qv-total-pop');

        if (btnSend) {
            btnSend.disabled = tickets.length === 0;
        }
    }

    function cleanSelections(tbody) {
        tbody.querySelectorAll('button.btn-choice').forEach(function (btn) {
            btn.classList.remove('active', 'btn-primary', 'text-white');
        });
    }

    function initCountdown() {
        var c = document.getElementById('countdown'); if (!c) return;
        function upd() {
            var deadlineStr = c.getAttribute('data-deadline');
            if (!deadlineStr) return;
            var diff = new Date(deadlineStr) - new Date();
            if (diff <= 0) return;
            var s = Math.floor(diff / 1000);
            var d = Math.floor(s / 86400); s -= d * 86400;
            var h = Math.floor(s / 3600); s -= h * 3600; var m = Math.floor(s / 60); s -= m * 60;
            [['days', d], ['hours', h], ['minutes', m], ['seconds', s]].forEach(function (p) {
                var el = c.querySelector('.countdown-value[data-unit="' + p[0] + '"]');
                if (el) el.textContent = String(p[1]).padStart(2, '0');
            });
        }
        setInterval(upd, 1000); upd();
    }

    function escapeHtml(s) { return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
    function formatAmount(n, c) { return '$' + (Number(n) || 0).toFixed(2) + ' ' + c; }

    // --- INICIO ---
    document.addEventListener('DOMContentLoaded', function () {
        getRootConfig();
        initLeagueButtons();
        initCountdown();
        initTickets();
        initNavbarBehavior();
    });

})();



/* =========================================
   RELOJ DINÁMICO (Fecha y Hora)
   ========================================= */
function initDynamicClock() {
    const dateElement = document.getElementById('dynamic-date');

    if (!dateElement) return;

    function updateClock() {
        const now = new Date();

        // Configuración para formato: "miércoles 17 de diciembre del 2025 14:51"
        const options = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false // Formato 24h (pon true si prefieres AM/PM)
        };

        // Generar texto en español
        let dateString = now.toLocaleDateString('es-ES', options);

        // Pequeño truco: toLocaleDateString a veces separa hora con coma, la quitamos si quieres
        dateString = dateString.replace(',', '');

        // Insertar en el HTML (Capitalizamos la primera letra)
        dateElement.textContent = dateString.charAt(0).toUpperCase() + dateString.slice(1);
    }

    // Actualizar inmediatamente y luego cada segundo
    updateClock();
    setInterval(updateClock, 1000);
}

// --- IMPORTANTE: LLAMAR A LA FUNCIÓN AL CARGAR ---
document.addEventListener('DOMContentLoaded', function () {
    // ... tus otros inits ...
    initDynamicClock();
});



/**
 * Feedback táctil para botones de pronóstico L/E/V.
 * No cambia la lógica del sistema: solo añade microinteracción visual.
 */
document.addEventListener('click', function (event) {
    var boton = event.target.closest('.btn-choice, .btn-ch-pick, .pick-option, .btn-pick, button[data-pick], .quiniela-pick');

    if (!boton || boton.disabled) {
        return;
    }

    var contenedor = boton.closest('tr, [data-match-id], .match-card, .quiniela-match');

    if (contenedor) {
        contenedor
            .querySelectorAll('.btn-choice, .btn-ch-pick, .pick-option, .btn-pick, button[data-pick], .quiniela-pick')
            .forEach(function (item) {
                item.classList.remove('is-selected');
            });
    }

    boton.classList.add('is-selected');
});
/**
 * Crea y envía formularios POST administrativos con CSRF.
 *
 * Uso:
 * window.enviarFormularioAdmin('/admin/leagues/delete', { id: 10 });
 */
window.enviarFormularioAdmin = function (action, fields) {
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

    var form = document.createElement('form');
    form.method = 'POST';
    form.action = action;

    if (csrfToken !== '') {
        var csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_csrf_token';
        csrfInput.value = csrfToken;
        form.appendChild(csrfInput);
    }

    Object.keys(fields || {}).forEach(function (name) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = fields[name];
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
};