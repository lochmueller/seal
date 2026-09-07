document.querySelectorAll('.seal-geo-locate-btn').forEach(function (btn) {
    var statusElement = btn.dataset.statusField
        ? document.getElementById(btn.dataset.statusField)
        : btn.nextElementSibling;

    var setStatus = function (message, busy) {
        if (!statusElement) {
            return;
        }
        statusElement.textContent = message;
        statusElement.style.display = message ? 'inline' : '';
        btn.setAttribute('aria-busy', busy ? 'true' : 'false');
        btn.disabled = !!busy;
    };

    btn.addEventListener('click', function () {
        var latField = document.getElementById(btn.dataset.latField);
        var lngField = document.getElementById(btn.dataset.lngField);
        var messages = statusElement ? statusElement.dataset : {};

        if (!navigator.geolocation) {
            setStatus(messages.msgUnavailable || 'Geolocation not available', false);
            return;
        }

        setStatus(messages.msgLocating || 'Determining location…', true);

        navigator.geolocation.getCurrentPosition(
            function (position) {
                latField.value = position.coords.latitude;
                lngField.value = position.coords.longitude;
                setStatus(messages.msgLocated || 'Located', false);
            },
            function () {
                setStatus(messages.msgDenied || 'Permission denied', false);
            }
        );
    });
});
