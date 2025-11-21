<?php

if (!defined('ABSPATH')) exit;

// =============================================================================
// НАСТРОЙКИ И ДЕФОЛТНЫЕ ЗНАЧЕНИЯ
// =============================================================================

// Дефолтные настройки внешнего вида карточек.
define('EV_DEFAULT_CARD_BG', '#fafafa');
define('EV_DEFAULT_BORDER_WIDTH', 1);
define('EV_DEFAULT_BORDER_STYLE', 'solid');
define('EV_DEFAULT_BORDER_COLOR', '#e3e3e3');
define('EV_DEFAULT_CARDS_PER_ROW', 1);
define('EV_DEFAULT_CRON_PERIOD', 'off');

// Доступные стили рамки.
$ev_border_styles = array(
    'solid'  => 'Сплошная',
    'dashed' => 'Пунктирная',
    'dotted' => 'Точечная',
    'double' => 'Двойная',
    'none'   => 'Без рамки'
);

// Доступные периоды удаления cron задач.
$ev_cron_periods = array(
    'off'      => 'Выключено',
    'week'     => 'Неделя (7 дней)',
    'month'    => 'Месяц (30 дней)',
    '6months'  => '6 месяцев (180 дней)',
    'year'     => 'Год (365 дней)'
);

// =============================================================================
// ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// =============================================================================

/**
 * Получение значения настройки с дефолтным fallback.
 */
function ev_get_setting($key, $default = '') {
    $options = get_option('event_cpt_settings', array());
    return isset($options[$key]) ? $options[$key] : $default;
}

// =============================================================================
// МЕНЮ АДМИНИСТРАТОРА
// =============================================================================

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

// =============================================================================
// РЕГИСТРАЦИЯ НАСТРОЕК
// =============================================================================

add_action('admin_init', 'ev_register_settings');

function ev_register_settings() {
    register_setting('event_cpt_settings_group', 'event_cpt_settings', 'ev_sanitize_settings');

    // Секция внешнего вида карточек.
    add_settings_section(
        'ev_appearance_section',
        'Настройки внешнего вида карточек',
        'ev_appearance_section_callback',
        'event-settings'
    );

    // Поля внешнего вида карточек.
    $appearance_fields = array(
        array('id' => 'card_bg', 'label' => 'Фон карточки', 'callback' => 'ev_card_bg_callback'),
        array('id' => 'border_width', 'label' => 'Толщина рамки', 'callback' => 'ev_border_width_callback'),
        array('id' => 'border_style', 'label' => 'Стиль рамки', 'callback' => 'ev_border_style_callback'),
        array('id' => 'border_color', 'label' => 'Цвет рамки', 'callback' => 'ev_border_color_callback'),
        array('id' => 'cards_per_row', 'label' => 'Карточек в ряд', 'callback' => 'ev_cards_per_row_callback'),
    );

    foreach ($appearance_fields as $field) {
        add_settings_field(
            $field['id'],
            $field['label'],
            $field['callback'],
            'event-settings',
            'ev_appearance_section'
        );
    }

    // Секция cron задач.
    add_settings_section(
        'ev_cron_section',
        'Автоматическое удаление событий',
        'ev_cron_section_callback',
        'event-settings'
    );

    // Поле выбора периода cron задачи (радио кнопки).
    add_settings_section(
        'cron_delete_period',
        'Удалять события старше',
        'ev_cron_delete_period_callback',
        'event-settings',
        'ev_cron_section'
    );
}

// =============================================================================
// ОЧИСТКА НАСТРОЕК
// =============================================================================

function ev_sanitize_settings($input) {
    $sanitized = array();

    // Очистка цвета фона карточки.
    if (isset($input['card_bg'])) {
        $sanitized['card_bg'] = sanitize_hex_color($input['card_bg']);
    }

    // Очистка толщины рамки.
    if (isset($input['border_width'])) {
        $sanitized['border_width'] = absint($input['border_width']);
    }

    // Очистка стиля рамки.
    if (isset($input['border_style'])) {
        $allowed_styles = array('solid', 'dashed', 'dotted', 'double', 'none');
        $sanitized['border_style'] = in_array($input['border_style'], $allowed_styles)
            ? $input['border_style']
            : EV_DEFAULT_BORDER_STYLE;
    }

    // Очистка цвета рамки.
    if (isset($input['border_color'])) {
        $sanitized['border_color'] = sanitize_hex_color($input['border_color']);
    }

    // Очистка количества карточек в ряду.
    if (isset($input['cards_per_row'])) {
        $sanitized['cards_per_row'] = absint($input['cards_per_row']);
        if ($sanitized['cards_per_row'] < 1 || $sanitized['cards_per_row'] > 4) {
            $sanitized['cards_per_row'] = EV_DEFAULT_CARDS_PER_ROW;
        }
    }

    // Очистка периода cron задачи (радио кнопки).
    $allowed_periods = array('off', 'week', 'month', '6months', 'year');
    $sanitized['cron_delete_period'] = isset($input['cron_delete_period'])
        && in_array($input['cron_delete_period'], $allowed_periods)
        ? $input['cron_delete_period']
        : EV_DEFAULT_CRON_PERIOD;

    return $sanitized;
}

// =============================================================================
// ОБРАБОТЧИКИ СЕКЦИЙ
// =============================================================================

function ev_appearance_section_callback() {
    echo '<p>Настройте внешний вид карточек событий.</p>';
}

function ev_cron_section_callback() {
    echo '<p>Выберите период, после которого события будут удаляться автоматически. Задача выполняется ежедневно.</p>';
}

// =============================================================================
// ОБРАБОТЧИКИ ПОЛЕЙ - ВНЕШНИЙ ВИД
// =============================================================================

function ev_card_bg_callback() {
    $value = ev_get_setting('card_bg', EV_DEFAULT_CARD_BG);
    echo '<input type="color" name="event_cpt_settings[card_bg]" value="' . esc_attr($value) . '" />';
}

function ev_border_width_callback() {
    $value = ev_get_setting('border_width', EV_DEFAULT_BORDER_WIDTH);
    echo '<input type="number" name="event_cpt_settings[border_width]" value="' . esc_attr($value) . '" min="0" max="10" /> px';
}

function ev_border_style_callback() {
    global $ev_border_styles;
    $value = ev_get_setting('border_style', EV_DEFAULT_BORDER_STYLE);

    echo '<select name="event_cpt_settings[border_style]">';
    foreach ($ev_border_styles as $key => $label) {
        echo '<option value="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
}

function ev_border_color_callback() {
    $value = ev_get_setting('border_color', EV_DEFAULT_BORDER_COLOR);
    echo '<input type="color" name="event_cpt_settings[border_color]" value="' . esc_attr($value) . '" />';
}

function ev_cards_per_row_callback() {
    $value = ev_get_setting('cards_per_row', EV_DEFAULT_CARDS_PER_ROW);

    echo '<select name="event_cpt_settings[cards_per_row]">';
    for ($i = 1; $i <= 4; $i++) {
        echo '<option value="' . $i . '" ' . selected($value, $i, false) . '>' . $i . '</option>';
    }
    echo '</select>';
}

// =============================================================================
// ОБРАБОТЧИКИ ПОЛЕЙ - CRON
// =============================================================================

function ev_cron_delete_period_callback() {
    global $ev_cron_periods;
    $value = ev_get_setting('cron_delete_period', EV_DEFAULT_CRON_PERIOD);

    foreach ($ev_cron_periods as $key => $label) {
        echo '<label style="display: block; margin-bottom: 8px;">';
        echo '<input type="radio" name="event_cpt_settings[cron_delete_period]" value="' . esc_attr($key) . '" ' . checked($value, $key, false) . ' /> ';
        echo esc_html($label);
        echo '</label>';
    }
}

// =============================================================================
// РЕНДЕР СТРАНИЦЫ НАСТРОЕК
// =============================================================================

function ev_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Подключение цветового пикера.
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');

    // Получение текущих настроек.
    $card_bg = ev_get_setting('card_bg', EV_DEFAULT_CARD_BG);
    $border_width = ev_get_setting('border_width', EV_DEFAULT_BORDER_WIDTH);
    $border_style = ev_get_setting('border_style', EV_DEFAULT_BORDER_STYLE);
    $border_color = ev_get_setting('border_color', EV_DEFAULT_BORDER_COLOR);
    $cards_per_row = ev_get_setting('cards_per_row', EV_DEFAULT_CARDS_PER_ROW);

    ?>
    <div class="wrap event-settings-page">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

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
        // Инициализация цветового пикера.
        $('input[type="color"]').wpColorPicker({
            change: function(event, ui) {
                updatePreview();
            }
        });

        // Добавление обработчиков к всем полям формы.
        $('input[name="event_cpt_settings[card_bg]"]').on('change', updatePreview);
        $('input[name="event_cpt_settings[border_width]"]').on('input', updatePreview);
        $('select[name="event_cpt_settings[border_style]"]').on('input', updatePreview);
        $('input[name="event_cpt_settings[border_color]"]').on('change', updatePreview);
        $('select[name="event_cpt_settings[cards_per_row]"]').on('input', function() {
            updatePreview();
            reinitializeMaps();
        });

        function updatePreview() {

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
            // Инициализация всех Яндекс карт.
            if (typeof ymaps !== 'undefined' && typeof initEventMaps === 'function') {
                // Удаление маркеров инициализации карт и очистка контейнеров карт.
                $('.event-map').each(function() {
                    $(this).removeAttr('data-map-initialized');
                    $(this).html(''); // Очистка контейнера карты.
                });

                // Инициализация карт.
                ymaps.ready(function() {
                    initEventMaps();
                });
            }
        }
    });
    </script>
    <?php
}

// =============================================================================
// АССЕТЫ АДМИНИСТРАТОРА
// =============================================================================

add_action('admin_enqueue_scripts', 'ev_enqueue_settings_assets');

function ev_enqueue_settings_assets($hook) {
    // Только загружаем на нашей странице настроек.
    if ($hook !== 'event_page_event-settings') {
        return;
    }

    // Подключение стилей и скриптов для предпросмотра.
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

    // Яндекс.Карты для предпросмотра.
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
