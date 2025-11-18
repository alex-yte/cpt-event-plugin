document.addEventListener('click', function (e) {
    const btn = e.target.closest('#load-more-events');
    if (!btn) return;

    const page = parseInt(btn.dataset.page) || 1;
    const perPage = parseInt(btn.getAttribute('data-per-page')) || 4;
    const nonce = document.getElementById('events-list').dataset.nonce;

    fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'load_more_events',
            page: page,
            per_page: perPage,
            nonce: nonce
        })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('events-list');
                container.insertAdjacentHTML('beforeend', data.data.html);

                if (typeof initEventMaps === 'function') {
                    initEventMaps();
                }

                btn.dataset.page = page + 1;

                const maxPages = parseInt(btn.getAttribute('data-max-pages')) || 1;
                if (page >= maxPages) {
                    btn.remove();
                }
            }
        });
});
