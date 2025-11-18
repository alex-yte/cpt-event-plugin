<?php

if (!defined('ABSPATH')) exit;

// Add settings page to admin menu
add_action('admin_menu', 'ev_add_settings_page');

function ev_add_settings_page() {
    add_submenu_page(
        'edit.php?post_type=event',
        'Настройки отображения',
        'Настройки',
        'manage_options',
        'event-settings',
        'ev_render_settings_page'
    );
}

// Register settings
add_action('admin_init', 'ev_register_settings');

function ev_register_settings() {
    register_setting('event_cpt_settings_group', 'event_cpt_settings', 'ev_sanitize_settings');

    add_settings_section(
        'ev_appearance_section',
        'Настройки внешнего вида карточек',
        'ev_appearance_section_callback',
        'event-settings'
    );

    add_settings_field(
        'card_bg',
        'Фон карточки',
        'ev_card_bg_callback',
        'event-settings',
        'ev_appearance_section'
    );

    add_settings_field(
        'border_width',
        'Толщина рамки',
        'ev_border_width_callback',
        'event-settings',
        'ev_appearance_section'
    );

    add_settings_field(
        'border_style',
        'Стиль рамки',
        'ev_border_style_callback',
        'event-settings',
        'ev_appearance_section'
    );

    add_settings_field(
        'border_color',
        'Цвет рамки',
        'ev_border_color_callback',
        'event-settings',
        'ev_appearance_section'
    );

    add_settings_field(
        'cards_per_row',
        'Карточек в ряд',
        'ev_cards_per_row_callback',
        'event-settings',
        'ev_appearance_section'
    );
}

function ev_sanitize_settings($input) {
    $sanitized = array();

    if (isset($input['card_bg'])) {
        $sanitized['card_bg'] = sanitize_hex_color($input['card_bg']);
    }

    if (isset($input['border_width'])) {
        $sanitized['border_width'] = absint($input['border_width']);
    }

    if (isset($input['border_style'])) {
        $allowed_styles = array('solid', 'dashed', 'dotted', 'double', 'none');
        $sanitized['border_style'] = in_array($input['border_style'], $allowed_styles) ? $input['border_style'] : 'solid';
    }

    if (isset($input['border_color'])) {
        $sanitized['border_color'] = sanitize_hex_color($input['border_color']);
    }

    if (isset($input['cards_per_row'])) {
        $sanitized['cards_per_row'] = absint($input['cards_per_row']);
        if ($sanitized['cards_per_row'] < 1 || $sanitized['cards_per_row'] > 4) {
            $sanitized['cards_per_row'] = 1;
        }
    }

    return $sanitized;
}

function ev_appearance_section_callback() {
    echo '<p>Настройте внешний вид карточек событий.</p>';
}

function ev_card_bg_callback() {
    $options = get_option('event_cpt_settings');
    $value = isset($options['card_bg']) ? $options['card_bg'] : '#fafafa';
    echo '<input type="color" name="event_cpt_settings[card_bg]" value="' . esc_attr($value) . '" />';
}

function ev_border_width_callback() {
    $options = get_option('event_cpt_settings');
    $value = isset($options['border_width']) ? $options['border_width'] : 1;
    echo '<input type="number" name="event_cpt_settings[border_width]" value="' . esc_attr($value) . '" min="0" max="10" /> px';
}

function ev_border_style_callback() {
    $options = get_option('event_cpt_settings');
    $value = isset($options['border_style']) ? $options['border_style'] : 'solid';
    $styles = array('solid' => 'Сплошная', 'dashed' => 'Пунктирная', 'dotted' => 'Точечная', 'double' => 'Двойная', 'none' => 'Без рамки');

    echo '<select name="event_cpt_settings[border_style]">';
    foreach ($styles as $key => $label) {
        echo '<option value="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
}

function ev_border_color_callback() {
    $options = get_option('event_cpt_settings');
    $value = isset($options['border_color']) ? $options['border_color'] : '#e3e3e3';
    echo '<input type="color" name="event_cpt_settings[border_color]" value="' . esc_attr($value) . '" />';
}

function ev_cards_per_row_callback() {
    $options = get_option('event_cpt_settings');
    $value = isset($options['cards_per_row']) ? $options['cards_per_row'] : 1;

    echo '<select name="event_cpt_settings[cards_per_row]">';
    for ($i = 1; $i <= 4; $i++) {
        echo '<option value="' . $i . '" ' . selected($value, $i, false) . '>' . $i . '</option>';
    }
    echo '</select>';
}

// Render settings page
function ev_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Enqueue color picker
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');

    // Get current settings
    $options = get_option('event_cpt_settings');
    $card_bg = isset($options['card_bg']) ? $options['card_bg'] : '#fafafa';
    $border_width = isset($options['border_width']) ? $options['border_width'] : 1;
    $border_style = isset($options['border_style']) ? $options['border_style'] : 'solid';
    $border_color = isset($options['border_color']) ? $options['border_color'] : '#e3e3e3';
    $cards_per_row = isset($options['cards_per_row']) ? $options['cards_per_row'] : 1;

    ?>
    <div class="wrap event-settings-page">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <!-- <style id="event-preview-styles">
            :root {
                --event-card-bg: <?php echo esc_attr($card_bg); ?>;
                --event-card-border-width: <?php echo esc_attr($border_width); ?>px;
                --event-card-border-style: <?php echo esc_attr($border_style); ?>;
                --event-card-border-color: <?php echo esc_attr($border_color); ?>;
                --cards-per-row: <?php echo esc_attr($cards_per_row); ?>;
            }
        </style> -->

        <div class="event-settings-container">
            <div class="event-settings-preview">
                <h2>Предварительный просмотр</h2>
                <div class="preview-content">
                    <?php echo do_shortcode('[events_list]'); ?>
                </div>
            </div>

            <div class="event-settings-sidebar">
                <h2>Настройки</h2>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('event_cpt_settings_group');
                    do_settings_sections('event-settings');
                    submit_button('Сохранить настройки');
                    ?>
                </form>
            </div>
        </div>
    </div>

    <style>
        .event-settings-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
            margin-top: 20px;
        }

        .event-settings-preview {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }

        .event-settings-sidebar {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }

        .event-settings-sidebar h2 {
            margin-top: 0;
        }

        .event-settings-sidebar .form-table th {
            padding-left: 0;
        }

        @media (max-width: 1200px) {
            .event-settings-container {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
    jQuery(document).ready(function($) {
        // Initialize color pickers
        $('input[type="color"]').wpColorPicker({
            change: function(event, ui) {
                updatePreview();
            }
        });

        // Add listeners to all form inputs
        $('input[name="event_cpt_settings[card_bg]"]').on('input', updatePreview);
        $('input[name="event_cpt_settings[border_width]"]').on('input', updatePreview);
        $('select[name="event_cpt_settings[border_style]"]').on('input', updatePreview);
        $('input[name="event_cpt_settings[border_color]"]').on('input', updatePreview);
        $('select[name="event_cpt_settings[cards_per_row]"]').on('input', function() {
            updatePreview();
            reinitializeMaps();
        });

        function updatePreview() {
            console.log('updatePreview called');

            var cardBg = $('input[name="event_cpt_settings[card_bg]"]').val();
            var borderWidth = $('input[name="event_cpt_settings[border_width]"]').val();
            var borderStyle = $('select[name="event_cpt_settings[border_style]"]').val();
            var borderColor = $('input[name="event_cpt_settings[border_color]"]').val();
            var cardsPerRow = $('select[name="event_cpt_settings[cards_per_row]"]').val();

            var styleContent = ':root { ' +
                '--event-card-bg: ' + cardBg + '; ' +
                '--event-card-border-width: ' + borderWidth + 'px; ' +
                '--event-card-border-style: ' + borderStyle + '; ' +
                '--event-card-border-color: ' + borderColor + '; ' +
                '--cards-per-row: ' + cardsPerRow + '; ' +
            '}';

            $('#event-vars').html(styleContent);
        }

        function reinitializeMaps() {
            // Re-initialize all Yandex maps
            if (typeof ymaps !== 'undefined' && typeof initEventMaps === 'function') {
                // Remove map initialization markers and clear map containers
                $('.event-map').each(function() {
                    $(this).removeAttr('data-map-initialized');
                    $(this).html(''); // Clear the map container
                });

                // Re-initialize maps
                ymaps.ready(function() {
                    initEventMaps();
                });
            }
        }
    });
    </script>
    <?php
}

// Enqueue admin assets for settings page
add_action('admin_enqueue_scripts', 'ev_enqueue_settings_assets');

function ev_enqueue_settings_assets($hook) {
    // Only load on our settings page
    if ($hook !== 'event_page_event-settings') {
        return;
    }

    // Enqueue frontend styles and scripts for preview
    wp_enqueue_style(
        'events-list-css',
        plugin_dir_url(dirname(__FILE__)) . 'assets/events-list.css'
    );

    wp_enqueue_script(
        'events-list-js',
        plugin_dir_url(dirname(__FILE__)) . 'assets/events-list.js',
        array('jquery'),
        false,
        true
    );

    // Яндекс.Карты для предпросмотра
    wp_enqueue_script(
        'yamaps',
        'https://api-maps.yandex.ru/2.1/?lang=ru_RU',
        array(),
        null,
        true
    );

    wp_enqueue_script(
        'events-maps-init',
        plugin_dir_url(dirname(__FILE__)) . 'assets/init-maps.js',
        array('yamaps'),
        false,
        true
    );
}

