/**
 * Lógica Frontend - Quiniela (Corregida y Conectada al Backend)
 */
document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById('quiniela-root');
    if (!root) return;

    // --- Referencias DOM ---
    const matchTableBody    = document.getElementById('matches-table-body');
    const btnAddTicket      = document.getElementById('btn-add-ticket');
    const ticketsSummary    = document.getElementById('tickets-summary-body');
    const ticketsCountBadge = document.getElementById('tickets-count-badge');
    const totalAmountSpan   = document.getElementById('tickets-total-amount');
    const btnSendWhatsapp   = document.getElementById('btn-send-whatsapp');
    const countdownEl       = document.getElementById('countdown');
    
    // Inputs
    const playerNameInput   = document.getElementById('player-name');
    const playerPhoneInput  = document.getElementById('player-phone');

    // Config
    const ticketAmount = parseFloat(root.dataset.ticketAmount || '0');
    const currency     = root.dataset.currency || 'USD';
    
    // Estado
    let matchSelections = {}; 
    const tickets = [];

    // ======================================================
    // 1. SELECCIÓN MANUAL
    // ======================================================
    if (matchTableBody) {
        matchTableBody.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-choice');
            if (!btn || btn.disabled) return;

            const tr = btn.closest('tr');
            const matchId = tr.getAttribute('data-match-id');
            const choice  = btn.getAttribute('data-choice');

            updateRowSelection(tr, btn, matchId, choice);
        });
    }

    function updateRowSelection(tr, activeBtn, matchId, choice) {
        tr.querySelectorAll('.btn-choice').forEach(b => {
            b.classList.remove('btn-primary', 'text-white');
        });

        if (activeBtn) {
            activeBtn.classList.add('btn-primary', 'text-white');
        }

        tr.classList.remove('table-danger');

        if (choice) {
            matchSelections[matchId] = choice;
        } else {
            delete matchSelections[matchId];
        }
    }

    // ======================================================
    // 3. AGREGAR QUINIELA (CORREGIDO: Guarda selecciones reales)
    // ======================================================
    if (btnAddTicket) {
        btnAddTicket.addEventListener('click', function() {
            const name = playerNameInput.value.trim();
            const phone = playerPhoneInput.value.trim();

            if (!name || !phone) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Faltan Datos',
                    text: 'Ingresa tu nombre y celular para guardar la quiniela.',
                    confirmButtonColor: '#001f3f'
                });
                return;
            }

            const rows = matchTableBody.querySelectorAll('tr[data-match-id]');
            const sequenceDisplay = [];
            const rawSelections = []; // IMPORTANTE: Datos para el backend
            let incomplete = false;

            // Validar filas
            rows.forEach(tr => {
                const mid = tr.getAttribute('data-match-id');
                const val = matchSelections[mid];
                
                if (!val) {
                    incomplete = true;
                    tr.classList.add('table-danger');
                } else {
                    sequenceDisplay.push(val);
                    // Guardamos el objeto exacto que pide el Controller
                    rawSelections.push({
                        match_id: parseInt(mid),
                        pick: val
                    });
                }
            });

            if (incomplete) {
                Swal.fire({
                    icon: 'error',
                    title: 'Incompleto',
                    text: 'Te faltan partidos por pronosticar (marcados en rojo).',
                    confirmButtonColor: '#d33'
                });
                return;
            }

            // Crear Ticket en memoria
            tickets.push({
                id: tickets.length + 1,
                name, 
                phone,
                sequence: sequenceDisplay.join('-'),
                selections: rawSelections, // <--- ESTO FALTABA
                amount: ticketAmount,
                currency
            });

            renderSummary();
            resetSelections();

            Swal.fire({
                icon: 'success',
                title: '¡Agregada!',
                text: 'Tu quiniela se añadió a la lista inferior. Recuerda enviar el pedido.',
                timer: 1500,
                showConfirmButton: false
            });
        });
    }

    function resetSelections() {
        matchSelections = {}; 
        const activeBtns = matchTableBody.querySelectorAll('.btn-choice.btn-primary');
        activeBtns.forEach(btn => {
            btn.classList.remove('btn-primary', 'text-white');
        });
    }

    // ======================================================
    // 4. CRONÓMETRO
    // ======================================================
    if (countdownEl) {
        const deadlineStr = countdownEl.getAttribute('data-deadline');
        if (deadlineStr) {
            const dest = new Date(deadlineStr).getTime();
            
            const timer = setInterval(() => {
                const now = new Date().getTime();
                const diff = dest - now;

                if (diff < 0) {
                    clearInterval(timer);
                    countdownEl.innerHTML = '<div class="badge bg-danger fs-5 px-4 py-2">TIEMPO AGOTADO</div>';
                    disableInputs();
                    return;
                }

                updateTime('days', Math.floor(diff / (1000 * 60 * 60 * 24)));
                updateTime('hours', Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)));
                updateTime('minutes', Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60)));
                updateTime('seconds', Math.floor((diff % (1000 * 60)) / 1000));
            }, 1000);
        }
    }

    function updateTime(unit, val) {
        const el = countdownEl.querySelector(`[data-unit="${unit}"]`);
        if (el) el.textContent = val < 10 ? '0' + val : val;
    }

    function disableInputs() {
        if (matchTableBody) matchTableBody.querySelectorAll('.btn-choice').forEach(b => b.disabled = true);
        if (btnAddTicket) btnAddTicket.disabled = true;
        if (btnRandomPick) btnRandomPick.disabled = true;
    }

    // ======================================================
    // 5. ENVIAR WHATSAPP (CORREGIDO: Conexión a Backend)
    // ======================================================
    if (btnSendWhatsapp) {
        btnSendWhatsapp.addEventListener('click', function() {
            if (tickets.length === 0) {
                Swal.fire('Atención', 'Agrega al menos una quiniela antes de enviar.', 'warning');
                return;
            }

            // Bloquear botón para evitar doble envío
            const originalText = btnSendWhatsapp.innerHTML;
            btnSendWhatsapp.disabled = true;
            btnSendWhatsapp.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Procesando...';

            // Datos generales
            const firstTicket = tickets[0];
            const leagueSlug = root.dataset.leagueSlug || 'liga-mx'; // Asegúrate de tener esto en tu HTML o usa default
            
            // Construir Payload para TicketController::create
            const payload = {
                name: firstTicket.name,
                phone: firstTicket.phone,
                league: leagueSlug,
                matchday: root.dataset.matchdayName || 'Jornada Actual',
                tickets: tickets.map(t => ({
                    sequence: t.sequence,
                    amount: t.amount,
                    selections: t.selections // Enviamos la estructura correcta
                }))
            };

            // Petición al Backend
            // NOTA: Ajusta la URL '/api/tickets/create' si tu ruta es diferente
            fetch('/api/tickets/create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.whatsAppUrl) {
                    // Éxito: Redirigir a WhatsApp
                    Swal.fire({
                        icon: 'success',
                        title: '¡Pedido Creado!',
                        text: 'Redirigiendo a WhatsApp para finalizar...',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = data.whatsAppUrl;
                    });
                } else {
                    throw new Error(data.message || 'Error desconocido del servidor');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Ocurrió un error',
                    text: error.message,
                    confirmButtonColor: '#d33'
                });
                // Restaurar botón
                btnSendWhatsapp.disabled = false;
                btnSendWhatsapp.innerHTML = originalText;
            });
        });
    }

    function renderSummary() {
        ticketsSummary.innerHTML = '';
        let total = 0;
        tickets.forEach(t => {
            total += t.amount;
            ticketsSummary.innerHTML += `
                <tr>
                    <td>${t.id}</td>
                    <td>${t.name}</td>
                    <td>${t.phone}</td>
                    <td class="font-monospace small">${t.sequence}</td>
                    <td class="text-end fw-bold">$${t.amount.toFixed(2)}</td>
                </tr>`;
        });
        ticketsCountBadge.textContent = tickets.length;
        totalAmountSpan.textContent = `$${total.toFixed(2)} ${currency}`;
        btnSendWhatsapp.disabled = false;
    }
});