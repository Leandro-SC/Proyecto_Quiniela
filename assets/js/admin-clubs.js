(function () {
    'use strict';

    function initClubAutocomplete() {
        var forms = document.querySelectorAll('[data-form="match-clubs"]');
        if (!forms.length) return;

        forms.forEach(function (form) {
            ['home', 'away'].forEach(function (side) {
                var input = form.querySelector('input[data-club-input="' + side + '"]');
                if (!input) return;

                var dropdown = document.createElement('div');
                dropdown.className = 'position-absolute bg-white border rounded shadow-sm w-100';
                dropdown.style.zIndex = '1050';
                dropdown.style.display = 'none';
                dropdown.style.maxHeight = '200px';
                dropdown.style.overflowY = 'auto';

                input.parentNode.style.position = 'relative';
                input.parentNode.appendChild(dropdown);

                var timeoutId = null;

                input.addEventListener('input', function () {
                    var term = input.value.trim();
                    if (timeoutId) clearTimeout(timeoutId);
                    if (term.length < 2) {
                        dropdown.style.display = 'none';
                        return;
                    }

                    timeoutId = setTimeout(function () {
                        // El endpoint debe coincidir con routes.php
                        fetch('/admin/clubs/search?term=' + encodeURIComponent(term))
                            .then(res => res.json())
                            .then(json => {
                                if (json.success) renderOptions(dropdown, json.clubs, input, side);
                            });
                    }, 250);
                });
            });
        });
    }
    
    
    document.addEventListener('DOMContentLoaded', function() {
    const autocompleteInputs = document.querySelectorAll('.club-autocomplete');
    const datalist = document.getElementById('clubsDatalist');
    let timeout = null;

    autocompleteInputs.forEach(input => {
        input.addEventListener('input', function() {
            const term = this.value.trim();
            const side = this.dataset.side;

            if (timeout) clearTimeout(timeout);
            if (term.length < 1) return;

            timeout = setTimeout(() => {
                fetch(`/admin/clubs/search?term=${encodeURIComponent(term)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            // 1. Actualizar las opciones del datalist
                            datalist.innerHTML = '';
                            data.clubs.forEach(club => {
                                const option = document.createElement('option');
                                option.value = club.name;
                                option.dataset.badge = club.badge || '';
                                // Guardamos info extra para el JS
                                option.textContent = `${club.name} (${club.country})`;
                                datalist.appendChild(option);

                                // 2. Si el nombre coincide exactamente, actualizamos el escudo
                                if (club.name.toLowerCase() === term.toLowerCase()) {
                                    updateUI(side, club.badge);
                                }
                            });
                        }
                    });
            }, 300);
        });
    });

    function updateUI(side, badgeUrl) {
        const hiddenInput = document.getElementById(`${side}_team_logo`);
        const previewImg = document.getElementById(`preview_${side}`);
        const previewText = document.getElementById(`text_${side}`);

        if (hiddenInput) hiddenInput.value = badgeUrl || '';
        if (previewImg && badgeUrl) {
            previewImg.src = badgeUrl;
            previewImg.style.display = 'block';
            if (previewText) previewText.style.display = 'none';
        }
    }
});
    
    document.addEventListener('DOMContentLoaded', function() {
    const searchInputs = document.querySelectorAll('.club-search-input');

    searchInputs.forEach(input => {
        const side = input.dataset.side;
        const container = input.closest('[data-autocomplete-container]');
        const resultsDiv = container.querySelector('.autocomplete-results');
        let timeout = null;

        input.addEventListener('input', function() {
            clearTimeout(timeout);
            const term = this.value.trim();

            if (term.length < 2) {
                resultsDiv.style.display = 'none';
                return;
            }

            timeout = setTimeout(() => {
                fetch(`/admin/clubs/search?term=${encodeURIComponent(term)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.clubs.length > 0) {
                            renderResults(data.clubs, resultsDiv, input, side);
                        } else {
                            resultsDiv.style.display = 'none';
                        }
                    });
            }, 300);
        });
    });

    function renderResults(clubs, resultsDiv, input, side) {
        resultsDiv.innerHTML = '';
        clubs.forEach(club => {
            const item = document.createElement('div');
            item.className = 'p-2 border-bottom autocomplete-item';
            item.style.cursor = 'pointer';
            
            const badge = club.badge ? `<img src="${club.badge}" width="25" class="me-2">` : '';
            item.innerHTML = `${badge} <strong>${club.name}</strong> <small class="text-muted">(${club.country})</small>`;

            item.addEventListener('click', function() {
                // Insertar el nombre
                input.value = club.name;
                
                // Actualizar el input hidden del logo y la previsualización
                const logoInput = document.getElementById(`${side}_team_logo`);
                const previewImg = document.getElementById(`preview_${side}`);
                const previewText = document.getElementById(`text_${side}`);

                if (logoInput) logoInput.value = club.badge || '';
                if (previewImg && club.badge) {
                    previewImg.src = club.badge;
                    previewImg.style.display = 'block';
                    if (previewText) previewText.style.display = 'none';
                }

                resultsDiv.style.display = 'none';
            });
            resultsDiv.appendChild(item);
        });
        resultsDiv.style.display = 'block';
    }

    // Cerrar si se hace clic fuera
    document.addEventListener('click', function(e) {
        if (!e.target.classList.contains('club-search-input')) {
            document.querySelectorAll('.autocomplete-results').forEach(div => div.style.display = 'none');
        }
    });
});

    function renderOptions(dropdown, clubs, input, side) {
        dropdown.innerHTML = '';
        if (clubs.length === 0) {
            dropdown.style.display = 'none';
            return;
        }

        clubs.forEach(function (club) {
            var item = document.createElement('button');
            item.type = 'button';
            item.className = 'dropdown-item d-flex align-items-center gap-2 py-2 w-100 text-start border-0 bg-transparent';
            
            let badge = club.badge ? `<img src="${club.badge}" style="width:20px;height:20px;object-fit:contain;">` : '';
            item.innerHTML = `${badge} <span>${club.name} <small class="text-muted">(${club.country})</small></span>`;

            item.addEventListener('click', function () {
                input.value = club.name;
                
                // Actualizar logos si existen en tu vista de partidos
                var hiddenLogo = document.getElementById(side + '_team_logo');
                var previewImg = document.getElementById('preview_' + side);
                var previewText = document.getElementById('text_' + side);

                if (hiddenLogo && club.badge) hiddenLogo.value = club.badge;
                if (previewImg && club.badge) {
                    previewImg.src = club.badge;
                    previewImg.style.display = 'block';
                    if (previewText) previewText.style.display = 'none';
                }

                dropdown.style.display = 'none';
            });
            dropdown.appendChild(item);
        });
        dropdown.style.display = 'block';
    }

    document.addEventListener('DOMContentLoaded', initClubAutocomplete);
})();