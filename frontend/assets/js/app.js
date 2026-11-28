// AMOURÉ SALON: small page helpers.

// Ask before submitting forms marked with data-confirm="Question?"
document.addEventListener('submit', function (event) {
    var form = event.target;
    var question = form.getAttribute('data-confirm');
    if (question && !window.confirm(question)) {
        event.preventDefault();
    }
});

// Reports: show the From/To dates only for "Custom range"; editing a date switches to custom.
(function () {
    var form = document.getElementById('report-filter');
    if (!form) {
        return;
    }
    var preset = form.querySelector('#preset');
    var ranges = form.querySelectorAll('.custom-range');

    function update() {
        ranges.forEach(function (field) {
            field.hidden = preset.value !== 'custom';
        });
    }
    form.querySelectorAll('input[type="date"]').forEach(function (input) {
        input.addEventListener('change', function () {
            preset.value = 'custom';
        });
    });
    preset.addEventListener('change', update);
    update();
})();

// New appointment: only show stylists who perform the chosen service.
// <select data-filters="stylist"> controls <select id="stylist"> whose options have data-services="1,3".
document.querySelectorAll('select[data-filters]').forEach(function (serviceSelect) {
    var stylistSelect = document.getElementById(serviceSelect.getAttribute('data-filters'));
    if (!stylistSelect) {
        return;
    }

    function update() {
        var serviceId = serviceSelect.value;
        Array.prototype.forEach.call(stylistSelect.options, function (option) {
            if (!option.value) {
                return; // keep the "Choose a stylist" option
            }
            var ids = (option.getAttribute('data-services') || '').split(',');
            var ok = !serviceId || ids.indexOf(serviceId) !== -1;
            option.hidden = !ok;
            option.disabled = !ok;
            if (!ok && option.selected) {
                stylistSelect.value = '';
            }
        });
    }

    serviceSelect.addEventListener('change', update);
    update();
});

// Dialogs: <button data-dialog="add-service"> opens <dialog id="add-service">.
document.addEventListener('click', function (event) {
    var opener = event.target.closest('[data-dialog]');
    if (opener) {
        var dlg = document.getElementById(opener.getAttribute('data-dialog'));
        if (dlg && dlg.showModal) {
            event.preventDefault();
            dlg.showModal();
        }
        return;
    }
    var closer = event.target.closest('[data-close]');
    if (closer) {
        var parent = closer.closest('dialog');
        if (parent) {
            parent.close();
        }
    }
});
// Re-open a dialog after a failed save (it has data-open set by the server).
document.querySelectorAll('dialog[data-open]').forEach(function (dlg) {
    if (dlg.showModal) {
        dlg.showModal();
    }
});
// Click on the dark backdrop closes the dialog.
document.querySelectorAll('dialog.modal').forEach(function (dlg) {
    dlg.addEventListener('click', function (event) {
        if (event.target === dlg) {
            dlg.close();
        }
    });
});

// "⋯" menus and the user menu: only one open at a time; click outside closes.
document.addEventListener('click', function (event) {
    document.querySelectorAll('details.dots[open], details.app-user[open]').forEach(function (d) {
        if (!d.contains(event.target)) {
            d.removeAttribute('open');
        }
    });
});

// Filters: change a select/date inside form[data-autosubmit] => submit.
document.querySelectorAll('form[data-autosubmit]').forEach(function (form) {
    form.querySelectorAll('select, input[type="date"]').forEach(function (el) {
        el.addEventListener('change', function () {
            form.submit();
        });
    });
});

// Mobile: hamburger opens the sidebar.
document.querySelectorAll('[data-toggle-side]').forEach(function (btn) {
    btn.addEventListener('click', function (event) {
        event.stopPropagation();
        document.body.classList.toggle('side-open');
    });
});
document.addEventListener('click', function (event) {
    if (document.body.classList.contains('side-open') && !event.target.closest('.app-side')) {
        document.body.classList.remove('side-open');
    }
});

// Notifications: opening the bell marks everything as read (badge disappears; items stay highlighted until next page).
document.querySelectorAll('details[data-notif]').forEach(function (bell) {
    var sent = false;
    bell.addEventListener('toggle', function () {
        if (!bell.open || sent) {
            return;
        }
        // Close other open menus.
        document.querySelectorAll('details.dots[open], details.app-user[open]').forEach(function (d) { d.removeAttribute('open'); });
        var badge = bell.querySelector('.app-bell-count');
        if (!badge) {
            return;
        }
        sent = true;
        var body = new FormData();
        body.append('csrf_token', bell.getAttribute('data-csrf'));
        fetch(bell.getAttribute('data-read-url'), { method: 'POST', body: body, headers: { 'X-Requested-With': 'fetch' }, credentials: 'same-origin' })
            .then(function (res) {
                if (res.ok) {
                    badge.remove();
                }
            })
            .catch(function () { sent = false; });
    });
});
// Click outside closes the bell too.
document.addEventListener('click', function (event) {
    document.querySelectorAll('details[data-notif][open]').forEach(function (d) {
        if (!d.contains(event.target)) {
            d.removeAttribute('open');
        }
    });
});

// Stylist forms: "Select all" ticks every service in that category (click again to untick).
document.addEventListener('click', function (event) {
    var btn = event.target.closest('[data-check-all]');
    if (!btn) {
        return;
    }
    var boxes = btn.closest('fieldset').querySelectorAll('input[type="checkbox"]');
    var allOn = Array.prototype.every.call(boxes, function (b) { return b.checked; });
    boxes.forEach(function (b) { b.checked = !allOn; });
});

// Booking step 1: search box filters services as you type; empty categories hide.
(function () {
    var input = document.getElementById('service-search');
    if (!input) {
        return;
    }
    var rows = document.querySelectorAll('.choice-row[data-search]');
    var sections = document.querySelectorAll('.cat-section');
    var chips = document.querySelectorAll('.cat-chips a');
    var none = document.querySelector('.svc-none');

    var onlyCat = '';   // '' = all categories, otherwise a section id like "cat-haircut"

    // Category chips filter the list ("All Services" shows everything).
    chips.forEach(function (chip) {
        chip.addEventListener('click', function (e) {
            e.preventDefault();
            var id = chip.getAttribute('href').slice(1);
            onlyCat = id === 'all' ? '' : id;
            chips.forEach(function (c) { c.removeAttribute('aria-current'); });
            chip.setAttribute('aria-current', 'true');
            input.dispatchEvent(new Event('input'));
        });
    });

    input.addEventListener('input', function () {
        var words = input.value.toLowerCase().trim().split(/\s+/).filter(Boolean);
        var shown = 0;
        rows.forEach(function (row) {
            var text = row.getAttribute('data-search');
            var ok = words.every(function (w) { return text.indexOf(w) !== -1; });
            row.hidden = !ok;
            shown += ok ? 1 : 0;
        });
        sections.forEach(function (sec) {
            var visible = sec.querySelectorAll('.choice-row:not([hidden])').length;
            sec.hidden = visible === 0 || (onlyCat !== '' && sec.id !== onlyCat);
            var chip = document.querySelector('.cat-chips a[href="#' + sec.id + '"]');
            if (chip) {
                chip.hidden = visible === 0;
                var count = chip.querySelector('span');
                if (count) {
                    count.textContent = visible;
                }
            }
        });
        var anyShown = document.querySelectorAll('.cat-section:not([hidden])').length > 0;
        none.hidden = anyShown || words.length === 0;
        none.querySelector('span').textContent = input.value.trim();
    });
    // Esc clears the search.
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            input.value = '';
            input.dispatchEvent(new Event('input'));
        }
    });
})();

// Light / dark mode: the moon/sun button in the top bar. Remembered in this browser.
document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var root = document.documentElement;
        var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-theme', next);
        try { localStorage.setItem('amoure-theme', next); } catch (e) { /* private mode: just this page */ }
    });
});

// Stylist photo: show the chosen picture right away, before saving.
document.querySelectorAll('[data-photo-input]').forEach(function (input) {
    input.addEventListener('change', function () {
        var box = input.closest('.photo-field').querySelector('[data-photo-preview]');
        if (!box || !input.files || !input.files[0]) {
            return;
        }
        var img = document.createElement('img');
        img.alt = '';
        img.src = URL.createObjectURL(input.files[0]);
        box.innerHTML = '';
        box.appendChild(img);
    });
});

// Status dropdown that filters table rows by their data-status (customer history).
document.querySelectorAll('select[data-status-filter]').forEach(function (sel) {
    var table = document.querySelector(sel.getAttribute('data-status-filter'));
    if (!table) {
        return;
    }
    sel.addEventListener('change', function () {
        var shown = 0;
        table.querySelectorAll('tbody tr[data-status]').forEach(function (tr) {
            tr.hidden = sel.value !== '' && tr.getAttribute('data-status') !== sel.value;
            shown += tr.hidden ? 0 : 1;
        });
        var none = table.querySelector('.filter-none');
        if (none) {
            none.hidden = shown > 0;
        }
    });
});

// Booking date strip: arrow buttons scroll the days; the chosen day is scrolled into view.
document.querySelectorAll('[data-day-strip]').forEach(function (strip) {
    var box = strip.closest('.day-scroller');
    box.querySelectorAll('[data-scroll-days]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            strip.scrollBy({ left: parseInt(btn.getAttribute('data-scroll-days'), 10) * strip.clientWidth * 0.8, behavior: 'smooth' });
        });
    });
    var sel = strip.querySelector('.day-selected');
    if (sel) {
        strip.scrollLeft = sel.offsetLeft - strip.offsetLeft - 40;
    }
});
