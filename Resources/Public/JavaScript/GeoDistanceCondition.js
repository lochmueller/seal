document.querySelectorAll('.seal-geo-locate-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var latField = document.getElementById(btn.dataset.latField);
        var lngField = document.getElementById(btn.dataset.lngField);
        var status = btn.nextElementSibling;

        if (!navigator.geolocation) {
            status.textContent = status.dataset.msgUnavailable || 'Geolocation not available';
            status.style.display = 'inline';
            return;
        }

        navigator.geolocation.getCurrentPosition(
            function (position) {
                latField.value = position.coords.latitude;
                lngField.value = position.coords.longitude;
                status.textContent = status.dataset.msgLocated || 'Located';
                status.style.display = 'inline';
            },
            function () {
                status.textContent = status.dataset.msgDenied || 'Permission denied';
                status.style.display = 'inline';
            }
        );
    });
});
