<?php

if (!defined('ABSPATH')) exit;

// Общая проверка перед сохранением
function ev_check_save($post_id, $nonce, $action)
{
    if (!isset($_POST[$nonce]) || !wp_verify_nonce($_POST[$nonce], $action)) return false;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return false;
    if (!current_user_can('edit_post', $post_id)) return false;
    return true;
}

// метабокс с датой, временем и местом
add_action('add_meta_boxes', function () {
    add_meta_box(
        'event_details',
        'Детали события',
        'ev_meta_details_html',
        'event',
        'normal',
        'high'
    );
});

function ev_meta_details_html($post)
{
    $date  = get_post_meta($post->ID, 'event_date', true);
    $time  = get_post_meta($post->ID, 'event_time', true);
    $place = get_post_meta($post->ID, 'event_place', true);

    wp_nonce_field('save_event_meta', 'event_meta_nonce');
    ?>
    <p>
        <label>Дата события:</label><br>
        <input type="date" name="event_date" value="<?php echo esc_attr($date); ?>">
    </p>
    <p>
        <label>Время события:</label><br>
        <input type="time" name="event_time" value="<?php echo esc_attr($time); ?>">
    </p>
    <p>
        <label>Место проведения:</label><br>
        <input type="text" name="event_place" value="<?php echo esc_attr($place); ?>" size="50">
    </p>
    <?php
}

// Метабокс с картой
add_action('add_meta_boxes', function () {
    add_meta_box(
        'event_map',
        'Местоположение события',
        'ev_meta_map_html',
        'event',
        'normal',
        'high'
    );
});

function ev_meta_map_html($post)
{
    $lat = get_post_meta($post->ID, 'event_lat', true) ?: 55.7569;
    $lng = get_post_meta($post->ID, 'event_lng', true) ?: 37.6151;

    wp_nonce_field('save_event_map', 'event_map_nonce');
    ?>
    <div id="event-map" style="width:100%;height:400px"></div>

    <input type="hidden" id="event_lat" name="event_lat" value="<?php echo esc_attr($lat); ?>">
    <input type="hidden" id="event_lng" name="event_lng" value="<?php echo esc_attr($lng); ?>">

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            ymaps.ready(function() {
                let lat = parseFloat(document.getElementById('event_lat').value);
                let lng = parseFloat(document.getElementById('event_lng').value);

                let map = new ymaps.Map("event-map", {
                    center: [lat, lng],
                    zoom: 10
                });

                let mark = new ymaps.Placemark([lat, lng], {}, {
                    draggable: true
                });
                map.geoObjects.add(mark);

                let searchControl = new ymaps.control.SearchControl({
                    options: {
                        float: 'right',
                        provider: 'yandex#search'
                    }
                });

                map.controls.add(searchControl);

                searchControl.events.add('resultselect', function(e) {
                    let index = e.get('index');
                    searchControl.getResult(index).then(function(res) {
                        let coords = res.geometry.getCoordinates();
                        mark.geometry.setCoordinates(coords);
                        document.getElementById('event_lat').value = coords[0];
                        document.getElementById('event_lng').value = coords[1];
                        map.setCenter(coords, 14);
                    });
                });

                mark.events.add('dragend', function(e) {
                    let c = mark.geometry.getCoordinates();
                    document.getElementById('event_lat').value = c[0];
                    document.getElementById('event_lng').value = c[1];
                });

                map.events.add('click', function(e) {
                    let c = e.get('coords');
                    mark.geometry.setCoordinates(c);
                    document.getElementById('event_lat').value = c[0];
                    document.getElementById('event_lng').value = c[1];
                });
            });
        });
    </script>
    <?php
}

// API Яндекс.Карт
add_action('admin_enqueue_scripts', function ($hook) {
    global $post;
    if ($hook === 'post.php' || $hook === 'post-new.php') {
        if ($post && $post->post_type === 'event') {
            wp_enqueue_script('yamaps-admin', 'https://api-maps.yandex.ru/2.1/?lang=ru_RU', [], null, true); // тут можно добавить ключ API и тогда заработает поиск по карте (в админке)
        }
    }
});

// Сохранение метаданных
add_action('save_post', function ($post_id) {

    // дата + время + место
    if (ev_check_save($post_id, 'event_meta_nonce', 'save_event_meta')) {
        update_post_meta($post_id, 'event_date', sanitize_text_field($_POST['event_date'] ?? ''));
        update_post_meta($post_id, 'event_time', sanitize_text_field($_POST['event_time'] ?? ''));
        update_post_meta($post_id, 'event_place', sanitize_text_field($_POST['event_place'] ?? ''));
    }

    // координаты
    if (ev_check_save($post_id, 'event_map_nonce', 'save_event_map')) {
        update_post_meta($post_id, 'event_lat', sanitize_text_field($_POST['event_lat'] ?? ''));
        update_post_meta($post_id, 'event_lng', sanitize_text_field($_POST['event_lng'] ?? ''));
    }
});
