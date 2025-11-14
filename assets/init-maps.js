function initEventMaps() {
    document.querySelectorAll('.event-map:not([data-map-initialized])').forEach(box => {
        const lat = parseFloat(box.dataset.lat);
        const lng = parseFloat(box.dataset.lng);
        const placeName = box.dataset.place || '';

        const map = new ymaps.Map(box, {
            center: [lat, lng],
            zoom: 12
        });

        map.geoObjects.add(new ymaps.Placemark(
            [lat, lng],
            {
                hintContent: placeName,
                balloonContent: placeName
            },
        ));

        box.dataset.mapInitialized = "1";
    });
}

// ждём загрузку API Яндекс.Карт
ymaps.ready(() => {
    initEventMaps();
});
