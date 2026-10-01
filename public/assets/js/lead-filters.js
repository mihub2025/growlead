(function () {
    const bar = document.querySelector('[data-lead-filters]');
    if (!bar) return;

    const preserveKeys = ['tab', 'search', 'sort', 'dir', 'per_page'];
    const uiOnlyKeys = ['campaign_scope', 'agent_scope', 'applied'];

    function filterByOwner(owner) {
        return bar.querySelector('.lf-filter[data-filter="' + owner + '"]');
    }

    function dropEl(filter) {
        const key = filter.getAttribute('data-filter');
        return document.querySelector('.lf-drop[data-lf-owner="' + key + '"]') || filter.querySelector('.lf-drop');
    }

    function closeFilter(filter) {
        const drop = dropEl(filter);
        filter.classList.remove('open');
        if (drop) {
            drop.classList.remove('lf-drop-open');
            drop.style.top = '';
            drop.style.left = '';
            drop.style.position = '';
            drop.style.display = '';
            if (drop.parentElement !== filter) {
                filter.appendChild(drop);
            }
        }
    }

    function closeAll(except) {
        bar.querySelectorAll('.lf-filter.open').forEach(function (el) {
            if (el !== except) closeFilter(el);
        });
    }

    function positionDrop(filter, drop) {
        const pill = filter.querySelector('.lf-pill');
        if (!drop || !pill) return;
        const rect = pill.getBoundingClientRect();
        const width = Math.max(drop.offsetWidth || 280, 260);
        let left = rect.left;
        if (left + width > window.innerWidth - 12) {
            left = Math.max(12, rect.right - width);
        }
        let top = rect.bottom + 6;
        const maxH = Math.min(420, window.innerHeight - 24);
        if (top + 240 > window.innerHeight) {
            top = Math.max(12, rect.top - Math.min(drop.offsetHeight || 280, maxH) - 6);
        }
        drop.style.position = 'fixed';
        drop.style.top = top + 'px';
        drop.style.left = left + 'px';
        drop.style.zIndex = '4000';
        drop.style.display = 'flex';
        drop.style.flexDirection = 'column';
        drop.style.maxHeight = maxH + 'px';
    }

    function openFilter(filter) {
        closeAll(filter);
        const drop = filter.querySelector('.lf-drop') || dropEl(filter);
        if (!drop) return;
        drop.setAttribute('data-lf-owner', filter.getAttribute('data-filter') || '');
        document.body.appendChild(drop);
        filter.classList.add('open');
        drop.classList.add('lf-drop-open');
        positionDrop(filter, drop);
    }

    bar.querySelectorAll('.lf-pill').forEach(function (pill) {
        pill.addEventListener('click', function (e) {
            if (e.target.closest('[data-clear-filter]')) return;
            e.preventDefault();
            e.stopPropagation();
            const filter = pill.closest('.lf-filter');
            if (filter.classList.contains('open')) {
                closeFilter(filter);
            } else {
                openFilter(filter);
            }
        });
    });

    document.addEventListener('click', function (e) {
        if (e.target.closest('.lf-filter') || e.target.closest('.lf-drop')) return;
        closeAll();
    });

    window.addEventListener('resize', function () {
        bar.querySelectorAll('.lf-filter.open').forEach(function (filter) {
            positionDrop(filter, dropEl(filter));
        });
    });
    window.addEventListener('scroll', function () {
        bar.querySelectorAll('.lf-filter.open').forEach(function (filter) {
            positionDrop(filter, dropEl(filter));
        });
    }, true);

    function visibleOptions(root, filter) {
        const tab = filter.getAttribute('data-current-tab');
        return Array.prototype.slice.call(root.querySelectorAll('[data-option]')).filter(function (opt) {
            if (tab && opt.getAttribute('data-scope') && opt.getAttribute('data-scope') !== tab) return false;
            if (opt.classList.contains('lf-hidden') || opt.classList.contains('d-none')) return false;
            return true;
        });
    }

    function syncSelectAll(root, filter) {
        const master = root.querySelector('[data-select-all]');
        if (!master) return;
        const boxes = visibleOptions(root, filter).map(function (opt) {
            return opt.querySelector('input[type="checkbox"]');
        }).filter(Boolean);
        master.checked = boxes.length > 0 && boxes.every(function (b) { return b.checked; });
        master.indeterminate = boxes.some(function (b) { return b.checked; }) && !master.checked;
    }

    document.addEventListener('input', function (e) {
        if (!e.target.classList.contains('lf-search')) return;
        const drop = e.target.closest('.lf-drop');
        const owner = drop && drop.getAttribute('data-lf-owner');
        const filter = owner ? filterByOwner(owner) : e.target.closest('.lf-filter');
        if (!filter || !drop) return;
        const q = e.target.value.trim().toLowerCase();
        visibleOptions(drop, filter).forEach(function (opt) {
            const hay = (opt.getAttribute('data-search') || opt.textContent).toLowerCase();
            opt.classList.toggle('lf-hidden', !!(q && hay.indexOf(q) === -1));
        });
        syncSelectAll(drop, filter);
    });

    document.addEventListener('change', function (e) {
        const drop = e.target.closest('.lf-drop');
        if (!drop) return;
        const owner = drop.getAttribute('data-lf-owner');
        const filter = owner ? filterByOwner(owner) : drop.closest('.lf-filter');
        if (!filter) return;

        if (e.target.matches('[data-select-all]')) {
            visibleOptions(drop, filter).forEach(function (opt) {
                const box = opt.querySelector('input[type="checkbox"]');
                if (box) box.checked = e.target.checked;
            });
            return;
        }

        if (e.target.matches('[data-date-preset]') && e.target.checked) {
            drop.querySelectorAll('[data-date-preset]').forEach(function (other) {
                if (other !== e.target) other.checked = false;
            });
            const custom = drop.querySelector('[data-custom]');
            const flag = drop.querySelector('[data-custom-flag]');
            if (custom) custom.classList.add('d-none');
            if (flag) flag.value = '';
            const toggle = drop.querySelector('.lf-custom-toggle');
            if (toggle) toggle.classList.remove('is-on');
        }

        if (!e.target.matches('[data-select-all], [data-special]')) {
            syncSelectAll(drop, filter);
        }
    });

    document.addEventListener('click', function (e) {
        const tabBtn = e.target.closest('[data-tab]');
        if (tabBtn) {
            e.preventDefault();
            const drop = tabBtn.closest('.lf-drop');
            const owner = drop && drop.getAttribute('data-lf-owner');
            const filter = owner ? filterByOwner(owner) : tabBtn.closest('.lf-filter');
            if (!filter || !drop) return;
            const tab = tabBtn.getAttribute('data-tab');
            filter.setAttribute('data-current-tab', tab);
            drop.querySelectorAll('[data-tab]').forEach(function (b) { b.classList.toggle('active', b === tabBtn); });
            const scopeInput = drop.querySelector('[data-scope-input]');
            if (scopeInput) scopeInput.value = tab;
            drop.querySelectorAll('[data-option]').forEach(function (opt) {
                const scope = opt.getAttribute('data-scope');
                opt.classList.toggle('d-none', !!(scope && scope !== tab));
            });
            syncSelectAll(drop, filter);
            return;
        }

        const customBtn = e.target.closest('.lf-custom-toggle');
        if (customBtn) {
            e.preventDefault();
            const drop = customBtn.closest('.lf-drop');
            const custom = drop.querySelector('[data-custom]');
            const flag = drop.querySelector('[data-custom-flag]');
            const show = custom.classList.contains('d-none');
            custom.classList.toggle('d-none', !show);
            customBtn.classList.toggle('is-on', show);
            if (flag) flag.value = show ? 'custom' : '';
            if (show) {
                drop.querySelectorAll('[data-date-preset]').forEach(function (box) { box.checked = false; });
            }
            return;
        }

        const advancedBtn = e.target.closest('.lf-advanced-toggle');
        if (advancedBtn) {
            e.preventDefault();
            const panel = advancedBtn.closest('.lf-drop').querySelector('[data-advanced]');
            if (panel) panel.classList.toggle('d-none');
            return;
        }

        const apply = e.target.closest('.lf-apply');
        if (apply) {
            e.preventDefault();
            e.stopPropagation();
            const drop = apply.closest('.lf-drop');
            const owner = drop && drop.getAttribute('data-lf-owner');
            const filter = owner ? filterByOwner(owner) : apply.closest('.lf-filter');
            if (filter) {
                const flag = (drop && drop.querySelector('[data-applied-flag]')) || filter.querySelector('[data-applied-flag]');
                if (flag) flag.value = '1';
                filter.setAttribute('data-applied', '1');
            }
            navigate(collect());
            return;
        }
    });

    function collect() {
        const current = new URL(window.location.href);
        const next = new URLSearchParams();
        preserveKeys.forEach(function (key) {
            const val = current.searchParams.get(key);
            if (val) next.set(key, val);
        });

        bar.querySelectorAll('.lf-filter').forEach(function (filter) {
            const drop = dropEl(filter);
            const root = drop || filter;
            const type = filter.getAttribute('data-type');

            if (type === 'multi') {
                root.querySelectorAll('input[type="checkbox"]').forEach(function (box) {
                    if (!box.getAttribute('name') || box.hasAttribute('data-select-all') || !box.checked) return;
                    next.append(box.getAttribute('name'), box.value);
                });
                const scope = root.querySelector('[data-scope-input]');
                if (scope && scope.value) next.set(scope.name, scope.value);
                root.querySelectorAll('[data-advanced] input').forEach(function (input) {
                    if (input.name && input.value) next.set(input.name, input.value);
                });
            } else if (type === 'relative' || type === 'range') {
                const flag = root.querySelector('[data-applied-flag]');
                const applied = filter.getAttribute('data-applied') === '1' || (flag && flag.value === '1');
                if (!applied) return;
                root.querySelectorAll('select[name], input[name]').forEach(function (input) {
                    if (input.hasAttribute('data-applied-flag')) {
                        next.set(input.name, '1');
                        return;
                    }
                    if (input.value !== '') next.set(input.name, input.value);
                });
            } else if (type === 'date') {
                const custom = root.querySelector('[data-custom]');
                const customOn = custom && !custom.classList.contains('d-none');
                const preset = root.querySelector('[data-date-preset]:checked');
                if (customOn) {
                    const from = root.querySelector('input[type="date"][name$="_from"]');
                    const to = root.querySelector('input[type="date"][name$="_to"]');
                    const paramBox = root.querySelector('[data-date-preset]');
                    if (paramBox && paramBox.name) next.set(paramBox.name, 'custom');
                    if (from && from.value) next.set(from.name, from.value);
                    if (to && to.value) next.set(to.name, to.value);
                } else if (preset) {
                    next.set(preset.name, preset.value);
                }
            }
        });

        var hasReal = false;
        next.forEach(function (_value, key) {
            var base = key.replace(/\[\]$/, '');
            if (preserveKeys.indexOf(base) === -1 && uiOnlyKeys.indexOf(base) === -1) {
                hasReal = true;
            }
        });
        if (hasReal) next.set('applied', '1');
        return next;
    }

    function navigate(params) {
        const url = new URL(window.location.href);
        url.search = params.toString();
        window.location.assign(url.toString());
    }

    bar.querySelectorAll('.lf-pill.is-active').forEach(function (pill) {
        if (pill.querySelector('[data-clear-filter]')) return;
        const x = document.createElement('span');
        x.className = 'lf-x';
        x.setAttribute('data-clear-filter', '');
        x.setAttribute('title', 'Clear');
        x.innerHTML = '&times;';
        pill.appendChild(x);
    });

    bar.addEventListener('click', function (e) {
        const x = e.target.closest('[data-clear-filter]');
        if (!x) return;
        e.preventDefault();
        e.stopPropagation();
        const filter = x.closest('.lf-filter');
        const drop = dropEl(filter);
        const root = drop || filter;
        root.querySelectorAll('input[type="checkbox"]').forEach(function (box) { box.checked = false; });
        root.querySelectorAll('[data-applied-flag]').forEach(function (flag) { flag.value = ''; });
        filter.setAttribute('data-applied', '0');
        root.querySelectorAll('[data-date-preset]').forEach(function (box) { box.checked = false; });
        const custom = root.querySelector('[data-custom]');
        if (custom) custom.classList.add('d-none');
        root.querySelectorAll('input[type="date"]').forEach(function (input) { input.value = ''; });
        root.querySelectorAll('[data-advanced] input').forEach(function (input) { input.value = ''; });
        navigate(collect());
    });
})();
