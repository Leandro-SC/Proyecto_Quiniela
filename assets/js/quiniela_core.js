/**
 * quiniela_core.js
 * Lógica blindada: Compara el total de filas vs total de selecciones.
 */
(function () {
    'use strict';

    console.log("✅ Quiniela Core Cargado Exitosamente");

    // Configuración Inicial
    const state = {
        baseAmount: 10,
        currency: 'USD',
        matchesLoaded: false
    };

    function init() {
        // 1. Leer configuración del HTML
        const root = document.getElementById('quiniela-root');
        if (root) {
            state.baseAmount = parseFloat(root.getAttribute('data-ticket-amount') || '10');
            state.currency = root.getAttribute('data-currency') || 'USD';
        }

        // 2. Elementos DOM
        const tableBody = document.getElementById('matches-table-body');
        const btnAdd = document.getElementById('btn-add-ticket');
        const btnWhats = document.getElementById('btn-send-whatsapp');
        
        if (!tableBody || !btnAdd) return;

        // Verificar si hay partidos
        state.matchesLoaded = tableBody.querySelectorAll('tr[data-match-id]').length > 0;

        // --- EVENTO: CLIC EN BOTONES DE JUEGO ---
        tableBody.addEventListener('click', function (e) {
            const btn = e.target.closest('button.btn-choice');
            if (!btn || btn.disabled) return;

            const row = btn.closest('tr');
            
            // A. Limpiar otros botones de la fila
            row.querySelectorAll('.btn-choice').forEach(b => {
                b.classList.remove('active', 'btn-primary', 'text-white');
            });

            // B. Activar el seleccionado
            btn.classList.add('active', 'btn-primary', 'text-white');
            
            // C. Quitar alerta roja si la tenía
            row.classList.remove('table-danger');
        });

        // --- EVENTO: AGREGAR TICKET (VALIDACIÓN MATEMÁTICA) ---
        btnAdd.addEventListener('click', function () {
            const nameInput = document.getElementById('player-name');
            const phoneInput = document.getElementById('player-phone');

            if (!nameInput.value.trim() || !phoneInput.value.trim()) {
                return fireAlert('Faltan Datos', 'Escribe tu nombre y celular.', 'warning');
            }

            if (!state.matchesLoaded) {
                return fireAlert('Error', 'No hay partidos en esta jornada.', 'error');
            }

            // --- AQUÍ ESTÁ LA MAGIA ---
            // 1. Contar filas totales de partidos
            const allRows = tableBody.querySelectorAll('tr[data-match-id]');
            const totalMatches = allRows.length;

            // 2. Contar botones activos (seleccionados)
            const activeButtons = tableBody.querySelectorAll('tr[data-match-id] button.btn-choice.active');
            const totalSelected = activeButtons.length;

            // 3. Comparación Directa
            if (totalSelected < totalMatches) {
                // Faltan selecciones. Vamos a marcar cuáles faltan visualmente.
                allRows.forEach(row => {
                    if (!row.querySelector('button.btn-choice.active')) {
                        row.classList.add('table-danger'); // Pone la fila roja
                    }
                });

                const missing = totalMatches - totalSelected;
                return fireAlert('Quiniela Incompleta', `Te faltan seleccionar ${missing} partido(s).`, 'error');
            }

            // 4. Si llegamos aquí, está completo. Generar Ticket.
            addTicketToSummary(nameInput.value, phoneInput.value);
        });

        // --- EVENTO: ENVIAR WHATSAPP ---
        if (btnWhats) {
            btnWhats.addEventListener('click', sendWhatsApp);
        }
    }

    // Funciones Auxiliares
    const tickets = [];

    function addTicketToSummary(name, phone) {
        const tableBody = document.getElementById('matches-table-body');
        const rows = tableBody.querySelectorAll('tr[data-match-id]');
        
        let sequence = [];
        let selections = [];

        rows.forEach(row => {
            const btn = row.querySelector('button.btn-choice.active');
            const choice = btn.getAttribute('data-choice');
            const id = row.getAttribute('data-match-id');
            
            sequence.push(choice);
            selections.push({ match_id: parseInt(id), pick: choice });
        });

        tickets.push({
            name: name,
            phone: phone,
            sequence: sequence.join('-'),
            amount: state.baseAmount,
            selections: selections
        });

        renderTable();
        
        // Limpiar formulario visualmente
        tableBody.querySelectorAll('.btn-choice').forEach(b => {
            b.classList.remove('active', 'btn-primary', 'text-white');
        });
        
        fireAlert('¡Listo!', 'Ticket agregado correctamente.', 'success');
    }

    function renderTable() {
        const tbody = document.getElementById('tickets-summary-body');
        const badge = document.getElementById('tickets-count-badge');
        const total = document.getElementById('tickets-total-amount');
        const btnSend = document.getElementById('btn-send-whatsapp');

        tbody.innerHTML = '';
        let totalAmount = 0;

        tickets.forEach((t, i) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${i + 1}</td>
                <td>${t.name}</td>
                <td>${t.phone}</td>
                <td>${t.sequence}</td>
                <td class="text-end">$${t.amount.toFixed(2)} ${state.currency}</td>
            `;
            tbody.appendChild(tr);
            totalAmount += t.amount;
        });

        badge.textContent = tickets.length;
        total.textContent = `$${totalAmount.toFixed(2)} ${state.currency}`;
        btnSend.disabled = tickets.length === 0;
    }

    function sendWhatsApp() {
        if (tickets.length === 0) return;

        Swal.fire({ title: 'Generando pedido...', didOpen: () => Swal.showLoading() });

        const root = document.getElementById('quiniela-root');
        const payload = {
            name: tickets[0].name,
            phone: tickets[0].phone,
            tickets: tickets.map(t => ({ sequence: t.sequence, amount: t.amount, selections: t.selections })),
            league: root.getAttribute('data-league'),
            matchday: root.getAttribute('data-matchday')
        };

        fetch('/api/tickets/create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            Swal.close();
            if (data.success && data.whatsAppUrl) {
                window.open(data.whatsAppUrl, '_blank');
            } else {
                fireAlert('Error', 'No se pudo generar el enlace.', 'error');
            }
        })
        .catch(() => {
            Swal.close();
            fireAlert('Error', 'Fallo de conexión.', 'error');
        });
    }

    function fireAlert(title, text, icon) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ title, text, icon, confirmButtonColor: '#3085d6' });
        } else {
            alert(text);
        }
    }

    // Inicializar al cargar
    document.addEventListener('DOMContentLoaded', init);

})();