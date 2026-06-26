/**
 * coach.js - Sistema de Guía Contextual Dinámica
 * Orienta al usuario paso a paso sin bloquear la interacción.
 */

(function() {
    'use strict';

    // Configuración
    const CONFIG = {
        offsetY: 10, // Distancia vertical del elemento
        sessionKey: 'quiniela_guide_completed' // Para no mostrar si ya completó
    };

    // Elementos del DOM
    let overlay, hand, tooltip;

    // Estado interno
    let currentTarget = null;
    let isPaused = false;

    // 1. INICIALIZACIÓN
    function init() {
        // Si el usuario ya completó el flujo en esta sesión, no mostramos nada
        if (sessionStorage.getItem(CONFIG.sessionKey) === 'true') return;

        createDOM();
        attachEvents();
        updateState(); // Primera comprobación
        
        console.log("✅ Coach Mark iniciado");
    }

    // 2. CREACIÓN DEL HTML
    function createDOM() {
        overlay = document.createElement('div');
        overlay.id = 'coach-overlay';
        
        hand = document.createElement('div');
        hand.className = 'coach-hand';
        hand.innerHTML = '👆'; // Emoji de mano (ligero y universal)

        tooltip = document.createElement('div');
        tooltip.className = 'coach-tooltip';
        tooltip.innerText = 'Inicia aquí';

        overlay.appendChild(hand);
        overlay.appendChild(tooltip);
        document.body.appendChild(overlay);
    }

    // 3. LÓGICA DE ESTADO (El Cerebro)
    function updateState() {
        if (isPaused) return;

        // A. ¿Hay tickets agregados? -> Guiar a WhatsApp
        const hasTickets = document.querySelectorAll('#tickets-summary-body tr').length > 0;
        if (hasTickets) {
            pointTo('#btn-send-whatsapp', '¡Envía tu quiniela por WhatsApp!');
            return;
        }

        // B. ¿Faltan partidos por seleccionar? -> Guiar al primer partido vacío
        const matchesRows = document.querySelectorAll('#matches-table-body tr');
        let firstUnselectedRow = null;

        for (let row of matchesRows) {
            // Buscamos si la fila tiene algún botón activo
            if (!row.querySelector('.btn-choice.active')) {
                firstUnselectedRow = row;
                break; // Encontramos el primero, paramos
            }
        }

        if (firstUnselectedRow) {
            // Apuntamos al contenedor de botones de esa fila (o al botón del medio 'E')
            // En tu diseño móvil, los botones están en celdas, apuntamos a la celda del medio
            const targetBtn = firstUnselectedRow.querySelector('td:nth-child(3)'); 
            pointToElement(targetBtn || firstUnselectedRow, 'Selecciona tu pronóstico');
            return;
        }

        // C. ¿Faltan Datos Personales? -> Guiar a Inputs
        const nameInput = document.getElementById('player-name');
        const phoneInput = document.getElementById('player-phone');

        if (nameInput && nameInput.value.trim() === '') {
            pointTo('#player-name', 'Ingresa tu nombre');
            return;
        }
        if (phoneInput && phoneInput.value.trim() === '') {
            pointTo('#player-phone', 'Ingresa tu celular');
            return;
        }

        // D. ¿Todo listo pero no ha agregado? -> Guiar a Agregar Ticket
        pointTo('#btn-add-ticket', '¡Agrega tu ticket ahora!');
    }

    // 4. POSICIONAMIENTO
    function pointTo(selector, message) {
        const el = typeof selector === 'string' ? document.querySelector(selector) : selector;
        if (!el) {
            hide();
            return;
        }
        pointToElement(el, message);
    }

    function pointToElement(el, message) {
        // Si el elemento está oculto o deshabilitado, ocultamos la guía
        if (el.offsetParent === null || el.disabled) {
            hide();
            return;
        }

        const rect = el.getBoundingClientRect();
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;

        // Calculamos posición (Centrado debajo del elemento, o encima si está muy abajo)
        // Por defecto: Apuntando DENTRO del elemento (como tocándolo)
        
        // Ajuste fino: Queremos que el dedo apunte al centro del elemento
        const targetTop = rect.top + scrollTop + (rect.height / 2); 
        const targetLeft = rect.left + scrollLeft + (rect.width / 2);

        // Movemos el overlay
        overlay.style.transform = `translate(${targetLeft}px, ${targetTop}px)`;
        
        // Actualizamos texto
        tooltip.innerText = message;
        
        show();
    }

    function show() {
        if (overlay) overlay.classList.add('active');
    }

    function hide() {
        if (overlay) overlay.classList.remove('active');
    }

    // 5. EVENTOS
    function attachEvents() {
        // Reaccionar a cualquier clic en la página (delegación)
        document.addEventListener('click', () => {
            // Pequeño delay para dejar que el DOM se actualice (ej: clases .active)
            setTimeout(updateState, 100);
        });

        // Reaccionar a escritura en inputs
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('input', updateState);
        });

        // Reaccionar al scroll y resize para reposicionar
        window.addEventListener('scroll', () => {
             // Opcional: Ocultar mientras hace scroll para rendimiento
             // hide(); 
             // O recalcular (usar debounce para producción)
             updateState(); 
        }, { passive: true });
        
        window.addEventListener('resize', updateState);

        // DETECTAR FINAL DEL FLUJO
        const btnWhatsapp = document.getElementById('btn-send-whatsapp');
        if (btnWhatsapp) {
            btnWhatsapp.addEventListener('click', () => {
                // El usuario cumplió el objetivo
                sessionStorage.setItem(CONFIG.sessionKey, 'true');
                hide();
                isPaused = true; // Detener el script
            });
        }
    }

    // Arrancar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();