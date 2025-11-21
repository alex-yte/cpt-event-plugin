<?php

if (!defined('ABSPATH')) exit;

// Cron hook names
define('EV_CRON_DELETE_WEEK', 'ev_delete_events_week');
define('EV_CRON_DELETE_MONTH', 'ev_delete_events_month');
define('EV_CRON_DELETE_6MONTHS', 'ev_delete_events_6months');
define('EV_CRON_DELETE_YEAR', 'ev_delete_events_year');

// Удаление событий старше указанного периода. На вход int.
function ev_delete_old_events($days) {
    $cutoff_date = date('Y-m-d', strtotime("-{$days} days"));

    $args = array(
        'post_type'      => 'event',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => 'event_date',
                'value'   => $cutoff_date,
                'compare' => '<',
                'type'    => 'DATE'
            )
        ),
        'fields' => 'ids'
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        foreach ($query->posts as $post_id) {
            wp_delete_post($post_id, true);
        }
    }

    wp_reset_postdata();
}

// Обработчики cron задач.
add_action(EV_CRON_DELETE_WEEK, function() {
    ev_delete_old_events(7);
});

add_action(EV_CRON_DELETE_MONTH, function() {
    ev_delete_old_events(30);
});

add_action(EV_CRON_DELETE_6MONTHS, function() {
    ev_delete_old_events(180);
});

add_action(EV_CRON_DELETE_YEAR, function() {
    ev_delete_old_events(365);
});

// Планирование или отмена cron задач на основе настроек.
function ev_schedule_cron_jobs() {
    $options = get_option('event_cpt_settings', array());

    // Получение выбранного периода удаления (значение радио кнопки).
    $selected_period = isset($options['cron_delete_period']) ? $options['cron_delete_period'] : 'off';

    // Маппинг периодов на cron хуки.
    $period_to_hook = array(
        'week'     => EV_CRON_DELETE_WEEK,
        'month'    => EV_CRON_DELETE_MONTH,
        '6months'  => EV_CRON_DELETE_6MONTHS,
        'year'     => EV_CRON_DELETE_YEAR,
    );

    // Сначала отменяем все существующие cron задачи.
    foreach ($period_to_hook as $hook) {
        $timestamp = wp_next_scheduled($hook);
        if ($timestamp) {
            wp_clear_scheduled_hook($hook);
        }
    }

    // Планируем выбранную cron задачу (если не 'off').
    if ($selected_period !== 'off' && isset($period_to_hook[$selected_period])) {
        $hook = $period_to_hook[$selected_period];

        // Планируем только если не запланирована.
        if (!wp_next_scheduled($hook)) {
            wp_schedule_event(time(), 'daily', $hook);
        }
    }
}

// Планирование cron задач при обновлении настроек.
add_action('update_option_event_cpt_settings', 'ev_schedule_cron_jobs', 10, 2);

// Планирование cron задач при активации плагина (если настройки существуют).
add_action('init', function() {
    if (get_option('event_cpt_settings')) {
        ev_schedule_cron_jobs();
    }
});

