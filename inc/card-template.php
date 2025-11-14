<?php

if (!defined('ABSPATH')) exit;

function ev_render_event_item($post_id) {
    $place = get_post_meta($post_id, 'event_place', true);
    $date  = get_post_meta($post_id, 'event_date', true); // формат Y-m-d
    $time  = get_post_meta($post_id, 'event_time', true); // формат H:i
    $lat   = get_post_meta($post_id, 'event_lat', true);
    $lng   = get_post_meta($post_id, 'event_lng', true);

    $date_formatted = '';
    $time_formatted = '';

    if ($date) {
        // Формируем строку даты и времени в формате Y-m-d H:i
        $datetime_str = $date . ' ' . ($time ?: '00:00');

        // Конвертируем в timestamp
        $timestamp = strtotime($datetime_str);

        if ($timestamp) {
            // Форматируем дату и время с учетом таймзоны WP
            $date_formatted = wp_date('d.m.Y', $timestamp);
            if ($time) {
                $time_formatted = wp_date('H:i', $timestamp);
            }
        }
    }

    ob_start();
    ?>
    <div class="event-item">
        <div>
            <h3><?php echo esc_html(get_the_title($post_id)); ?></h3>
            <p>
                <?php if ($date_formatted): ?>
                    <strong>Дата:</strong> <?php echo esc_html($date_formatted); ?>
                <?php endif; ?>
                <?php if ($time_formatted): ?>
                    <strong>Время:</strong> <?php echo esc_html($time_formatted); ?>
                <?php endif; ?>
            </p>
            <?php if ($place): ?>
                <p><strong>Место:</strong> <?php echo esc_html($place); ?></p>
            <?php endif; ?>
        </div>
        <?php if ($lat && $lng): ?>
            <div>
                <div class="event-map" data-lat="<?php echo esc_attr($lat); ?>" data-lng="<?php echo esc_attr($lng); ?>" data-place="<?php echo esc_attr($place); ?>"></div>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
