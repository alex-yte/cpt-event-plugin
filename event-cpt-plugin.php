<?php
/*
Plugin Name: Event Custom Post Type
Description: Кастомный CPT «События» с метабоксами, картами и AJAX-подгрузкой.
Version: 1.1
Author: Denis
*/

if (!defined('ABSPATH')) exit;

// подключаем модули
require_once __DIR__ . '/inc/cpt.php'; // регистрация CPT
require_once __DIR__ . '/inc/metaboxes.php'; // метабоксы
require_once __DIR__ . '/inc/card-template.php'; // шаблон карточки события
require_once __DIR__ . '/inc/shortcode.php'; // шорткод и AJAX

// стиль и скрипты
add_action('wp_enqueue_scripts', function() {

    wp_enqueue_style(
        'events-list-css',
        plugin_dir_url(__FILE__) . 'assets/events-list.css'
    );

    wp_enqueue_script(
        'events-list-js',
        plugin_dir_url(__FILE__) . 'assets/events-list.js',
        array('jquery'),
        false,
        true
    );

    // Яндекс.Карты только на фронте
    wp_enqueue_script(
        'yamaps',
        'https://api-maps.yandex.ru/2.1/?lang=ru_RU',
        array(),
        null,
        true
    );

    wp_enqueue_script(
        'events-maps-init',
        plugin_dir_url(__FILE__) . 'assets/init-maps.js',
        array('yamaps'),
        false,
        true
    );
});
