// AMOURÉ SALON: small page helpers.

// Ask before submitting forms marked with data-confirm="Question?"
document.addEventListener('submit', function (event) {
    var form = event.target;
    var question = form.getAttribute('data-confirm');
    if (question && !window.confirm(question)) {
        event.preventDefault();
    }
});
