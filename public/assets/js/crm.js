document.addEventListener('DOMContentLoaded', function () {
    if (window.Chart) {
        Chart.defaults.font.family = '"Plus Jakarta Sans", Inter, sans-serif';
        Chart.defaults.color = '#6f6b64';
        Chart.defaults.borderColor = '#efebe3';
    }
    const sidebar = document.querySelector('.crm-sidebar');
    document.querySelectorAll('[data-sidebar-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            sidebar && sidebar.classList.toggle('open');
        });
    });

    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!window.confirm(el.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    const toastEl = document.getElementById('crmToast');
    if (toastEl && window.bootstrap) {
        bootstrap.Toast.getOrCreateInstance(toastEl).show();
    }

    document.addEventListener('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
            const search = document.querySelector('.crm-search input');
            if (search) {
                e.preventDefault();
                search.focus();
            }
        }
    });

    const master = document.querySelector('[data-check-all]');
    if (master) {
        master.addEventListener('change', function () {
            document.querySelectorAll('[data-row-check]').forEach(function (box) {
                box.checked = master.checked;
            });
        });
    }

    document.querySelectorAll('[data-bulk-form]').forEach(function (form) {
        form.addEventListener('submit', function () {
            const ids = Array.from(document.querySelectorAll('[data-row-check]:checked')).map(function (el) {
                return el.value;
            });
            let input = form.querySelector('input[name="ids"]');
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids';
                form.appendChild(input);
            }
            input.value = ids.join(',');
        });
    });

    document.querySelectorAll('canvas.spark').forEach(function (c) {
        renderSpark(c, c.dataset.color || '#161513');
    });

    document.querySelectorAll('.cp-color').forEach(function (wrap) {
        const native = wrap.querySelector('.cp-color-native');
        const hex = wrap.querySelector('.cp-color-hex');
        if (!native || !hex) return;
        native.addEventListener('input', function () {
            hex.value = native.value.toUpperCase();
        });
        hex.addEventListener('input', function () {
            let v = hex.value.trim();
            if (v && v.charAt(0) !== '#') v = '#' + v;
            if (/^#[0-9A-Fa-f]{6}$/.test(v)) native.value = v;
        });
        hex.addEventListener('blur', function () {
            hex.value = native.value.toUpperCase();
        });
    });

    initAssignAgents();
});

function initAssignAgents() {
    const modalEl = document.getElementById('assignAgentsModal');
    const form = document.getElementById('assignAgentsForm');
    if (!modalEl || !form || !window.bootstrap) return;
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const subtitle = document.getElementById('assignAgentsSubtitle');
    const search = document.getElementById('assignAgentSearch');

    document.querySelectorAll('[data-assign-open]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            form.action = btn.getAttribute('data-assign-url') || '';
            const selected = (btn.getAttribute('data-assign-users') || '').split(',').filter(Boolean);
            form.querySelectorAll('input[name="user_ids[]"]').forEach(function (box) {
                box.checked = selected.indexOf(box.value) !== -1;
            });
            if (subtitle) {
                const name = btn.getAttribute('data-assign-name') || 'this campaign';
                subtitle.textContent = 'Assign ' + name + '. Agents will only see this campaign’s leads.';
            }
            if (search) {
                search.value = '';
                form.querySelectorAll('.assign-row').forEach(function (row) { row.style.display = ''; });
            }
            modal.show();
        });
    });

    if (search) {
        search.addEventListener('input', function () {
            const q = search.value.trim().toLowerCase();
            form.querySelectorAll('.assign-row').forEach(function (row) {
                const hay = row.getAttribute('data-search') || '';
                row.style.display = !q || hay.indexOf(q) !== -1 ? '' : 'none';
            });
        });
    }
}

function renderSpark(canvas, color) {
    if (!canvas || !window.Chart || canvas.dataset.chartReady) return;
    canvas.dataset.chartReady = '1';
    const wrap = canvas.parentElement;
    const width = Math.max(120, wrap ? wrap.clientWidth : canvas.offsetWidth || 160);
    canvas.width = width;
    canvas.height = 36;
    canvas.style.width = width + 'px';
    canvas.style.height = '36px';
    const values = JSON.parse(canvas.dataset.values || '[3,5,4,7,6,8,7]');
    const line = canvas.dataset.color || color || '#161513';
    new Chart(canvas, {
        type: 'line',
        data: { labels: values.map((_, i) => i), datasets: [{ data: values, borderColor: line, backgroundColor: 'transparent', borderWidth: 2, pointRadius: 0, tension: .4 }] },
        options: {
            responsive: false,
            maintainAspectRatio: false,
            animation: false,
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
            scales: { x: { display: false }, y: { display: false } }
        }
    });
}
