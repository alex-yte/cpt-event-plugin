<?php

if (!defined('ABSPATH')) exit;

add_action('init', function() {

    register_post_type('event', array(
        'labels' => array(
            'name'          => 'События',
            'singular_name' => 'Событие',
            'menu_name'     => 'События'
        ),
        'public'       => true,
        'has_archive'  => true,
        'rewrite'      => array('slug' => 'events'),
        'supports'     => array('title', 'editor', 'thumbnail'),
        'show_in_rest' => false
    ));
});
