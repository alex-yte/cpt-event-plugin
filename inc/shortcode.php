<?php

if (!defined('ABSPATH')) exit;

// вывод списка
add_shortcode('events_list', function() {

    $nonce = wp_create_nonce('load_more_events_nonce');
    $today = current_time('Y-m-d');

    // один запрос для первой страницы
    $per_page = 3;
    $page = 1;

    $args = [
        'post_type'      => 'event',
        'posts_per_page' => $per_page,
        'paged'          => $page,
        'post_status'    => 'publish',
        'meta_key'       => 'event_date',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'meta_query'     => [[
            'key'     => 'event_date',
            'value'   => $today,
            'compare' => '>=',
            'type'    => 'DATE'
        ]]
    ];

    $q = new WP_Query($args);

    ob_start();
    ?>

    <div id="events-list" data-nonce="<?php echo esc_attr($nonce); ?>">
        <?php
        if ($q->have_posts()) {
            while ($q->have_posts()) {
                $q->the_post();
                echo ev_render_event_item(get_the_ID());
            }
        } else {
            echo '<p>Нет ближайших событий.</p>';
        }
        wp_reset_postdata();
        ?>
    </div>

    <?php if ($q->max_num_pages > 1): ?>
        <button id="load-more-events" data-page="2" data-per-page="<?php echo $per_page; ?>" data-max-pages="<?php echo $q->max_num_pages; ?>">Показать больше</button>
    <?php endif;

    return ob_get_clean();
});

// получение HTML пачки событий для AJAX
function ev_get_events_html($page = 1, $per_page = 3) {
    $today = current_time('Y-m-d');

    $q = new WP_Query([
        'post_type'      => 'event',
        'posts_per_page' => $per_page,
        'paged'          => $page,
        'post_status'    => 'publish',
        'meta_key'       => 'event_date',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'meta_query'     => [[
            'key'     => 'event_date',
            'value'   => $today,
            'compare' => '>=',
            'type'    => 'DATE'
        ]]
    ]);

    if (!$q->have_posts()) return '<p>Нет ближайших событий.</p>';

    $html = '';
    while ($q->have_posts()) {
        $q->the_post();
        $html .= ev_render_event_item(get_the_ID());
    }
    wp_reset_postdata();

    return $html;
}

// AJAX
add_action('wp_ajax_load_more_events', 'ev_ajax_load_more');
add_action('wp_ajax_nopriv_load_more_events', 'ev_ajax_load_more');

function ev_ajax_load_more() {
    check_ajax_referer('load_more_events_nonce', 'nonce');

    $page = intval($_POST['page'] ?? 1);
    $per_page = intval($_POST['per_page'] ?? 3);

    $html = ev_get_events_html($page, $per_page);

    wp_send_json_success([
        'html' => $html
    ]);
}
