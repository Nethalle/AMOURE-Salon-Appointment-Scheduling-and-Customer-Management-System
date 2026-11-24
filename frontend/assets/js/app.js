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
