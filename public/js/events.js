(function () {
    const state = {
        source: '', status: '', date_from: '', date_to: '',
        sort: 'received_at', direction: 'desc', page: 1,
    };

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const tbody = document.getElementById('events-body');
    const pagination = document.getElementById('pagination');
    const modalBackdrop = document.getElementById('modal-backdrop');
    const modalBody = document.getElementById('modal-body');

    // payload и normalized-форма приходят от внешних систем (телефония, мессенджер, почта)
    // и могут содержать что угодно, включая "</pre><script>...". Вставлять такие строки
    // через innerHTML без экранирования - готовый XSS, поэтому весь текст, полученный
    // от сервера, всегда проходит через escapeHtml перед вставкой в разметку.
    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function buildQuery() {
        const params = new URLSearchParams();
        Object.entries(state).forEach(([key, value]) => {
            if (value !== '' && value !== null) params.set(key, value);
        });
        return params.toString();
    }

    function statusBadge(status) {
        const safe = escapeHtml(status);
        return `<span class="badge badge-${safe}">${safe}</span>`;
    }

    function formatDate(value) {
        return value ? new Date(value).toLocaleString('ru-RU') : '—';
    }

    async function loadEvents() {
        const response = await fetch(`/events/data?${buildQuery()}`, { headers: { Accept: 'application/json' } });
        if (!response.ok) return;
        const data = await response.json();
        renderTable(data.data);
        renderPagination(data);
    }

    function renderTable(events) {
        tbody.innerHTML = events.map((event) => `
            <tr data-id="${escapeHtml(event.id)}">
                <td>${escapeHtml(event.source)}</td>
                <td>${statusBadge(event.status)}</td>
                <td>${escapeHtml(event.external_id)}</td>
                <td>${escapeHtml(formatDate(event.received_at))}</td>
                <td>${escapeHtml(formatDate(event.processed_at))}</td>
            </tr>
        `).join('');

        tbody.querySelectorAll('tr').forEach((row) => {
            row.addEventListener('click', () => openEvent(row.dataset.id));
        });
    }

    function renderPagination(data) {
        pagination.innerHTML = '';
        if (data.last_page <= 1) return;

        for (let page = 1; page <= data.last_page; page += 1) {
            const button = document.createElement('button');
            button.textContent = page;
            if (page === data.current_page) button.disabled = true;
            button.addEventListener('click', () => { state.page = page; loadEvents(); });
            pagination.appendChild(button);
        }
    }

    async function openEvent(id) {
        const response = await fetch(`/events/${id}`, { headers: { Accept: 'application/json' } });
        if (!response.ok) return;
        const event = await response.json();
        const normalized = event.normalized_event;

        modalBody.innerHTML = `
            <h2>Событие #${escapeHtml(event.id)}</h2>
            <p>Источник: ${escapeHtml(event.source)} · Статус: ${statusBadge(event.status)}</p>
            <h3>Payload</h3>
            <pre>${escapeHtml(JSON.stringify(event.payload, null, 2))}</pre>
            <h3>Нормализованная форма</h3>
            <pre>${normalized ? escapeHtml(JSON.stringify(normalized, null, 2)) : 'нет'}</pre>
            <div class="modal-actions">
                <button id="replay-btn" ${!event.signature_valid ? 'disabled' : ''}>Переобработать</button>
                <span class="modal-error" id="replay-error"></span>
            </div>
        `;

        document.getElementById('replay-btn').addEventListener('click', () => replayEvent(id));
        modalBackdrop.style.display = 'flex';
    }

    async function replayEvent(id) {
        const errorEl = document.getElementById('replay-error');
        const response = await fetch(`/events/${id}/replay`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
        });

        if (!response.ok) {
            const data = await response.json().catch(() => ({}));
            if (errorEl) errorEl.textContent = data.error || 'Не удалось переобработать событие';
            return;
        }

        modalBackdrop.style.display = 'none';
        loadEvents();
    }

    document.getElementById('modal-close').addEventListener('click', () => {
        modalBackdrop.style.display = 'none';
    });

    document.getElementById('apply-filters').addEventListener('click', () => {
        state.source = document.getElementById('filter-source').value;
        state.status = document.getElementById('filter-status').value;
        state.date_from = document.getElementById('filter-date-from').value;
        state.date_to = document.getElementById('filter-date-to').value;
        state.page = 1;
        loadEvents();
    });

    document.querySelectorAll('th[data-sort]').forEach((th) => {
        th.addEventListener('click', () => {
            const field = th.dataset.sort;
            if (state.sort === field) {
                state.direction = state.direction === 'asc' ? 'desc' : 'asc';
            } else {
                state.sort = field;
                state.direction = 'asc';
            }
            loadEvents();
        });
    });

    loadEvents();
    setInterval(loadEvents, 5000);
})();
