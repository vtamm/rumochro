<?php

function theme_enqueue_assets() {
    wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', [], wp_get_theme()->Version);
    wp_enqueue_script( 'child-scripts', get_stylesheet_directory_uri() . '/scripts.js', ['jquery'], wp_get_theme()->Version, true );
}
add_action( 'wp_enqueue_scripts', 'theme_enqueue_assets', 20 );

//function avada_lang_setup() {
//    $lang = get_stylesheet_directory() . '/languages';
//    load_child_theme_textdomain( 'Avada', $lang );
//}
//add_action( 'after_setup_theme', 'avada_lang_setup' );
//
//add_filter('body_class', 'append_language_class');
//function append_language_class($classes){
//    $classes[] = ICL_LANGUAGE_CODE;
//    return $classes;
//}
