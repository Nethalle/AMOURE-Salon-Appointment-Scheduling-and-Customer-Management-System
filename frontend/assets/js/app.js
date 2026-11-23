// AMOURÉ SALON: small page helpers.

// Ask before submitting forms marked with data-confirm="Question?"
document.addEventListener('submit', function (event) {
    var form = event.target;
    var question = form.getAttribute('data-confirm');
    if (question && !window.confirm(question)) {
        event.preventDefault();
    }
});

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
