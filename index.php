<?php
/**
 * Plugin Name: Uncategorized Posts List
 * Plugin URI: https://yonet.org/
 * Description: WordPress yönetici panelinde kategorize edilmemiş gönderileri listeleyen bir eklenti.
 * Version: 1.0.0
 * Author: harew1
 * Author URI: hhttps://www.r10.net/profil/32747-harew1.html
 * License: GPL2
 */

// Gerekli dosyaları dahil edin
require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

// Tablo sınıfını oluşturun
class Uncategorized_Posts_Table extends WP_List_Table {

    function __construct() {
        parent::__construct(array(
            'singular' => 'uncategorized_post',
            'plural'   => 'uncategorized_posts',
            'ajax'     => false
        ));
    }

    function get_columns() {
        return array(
            'title'     => 'Başlık',
            'author'    => 'Yazar',
            'categories'=> 'Kategoriler',
            'tags'      => 'Etiketler',
            'date'      => 'Tarih'
        );
    }

    function prepare_items() {
        global $wpdb;

        $query = "SELECT wp_posts.ID, wp_posts.post_title, wp_posts.post_date, wp_users.display_name AS author,
                  GROUP_CONCAT(wp_terms.name SEPARATOR ', ') AS categories,
                  GROUP_CONCAT(wp_tags.name SEPARATOR ', ') AS tags
                  FROM {$wpdb->posts} AS wp_posts
                  LEFT JOIN {$wpdb->term_relationships} AS wp_term_relationships 
                  ON (wp_posts.ID = wp_term_relationships.object_id)
                  LEFT JOIN {$wpdb->terms} AS wp_terms ON (wp_term_relationships.term_taxonomy_id = wp_terms.term_id)
                  LEFT JOIN {$wpdb->term_taxonomy} AS wp_term_taxonomy ON (wp_terms.term_id = wp_term_taxonomy.term_id)
                  LEFT JOIN {$wpdb->term_relationships} AS wp_tag_relationships 
                  ON (wp_posts.ID = wp_tag_relationships.object_id)
                  LEFT JOIN {$wpdb->terms} AS wp_tags ON (wp_tag_relationships.term_taxonomy_id = wp_tags.term_id)
                  LEFT JOIN {$wpdb->users} AS wp_users ON (wp_posts.post_author = wp_users.ID)
                  WHERE wp_term_relationships.term_taxonomy_id IS NULL
                  AND wp_posts.post_type = 'post'
                  AND wp_posts.post_status = 'publish'
                  GROUP BY wp_posts.ID";

        $this->set_pagination_args(array(
            'total_items' => $wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->posts}"),
            'per_page'    => 20
        ));

        $this->_column_headers = array($this->get_columns(), array(), array());

        $this->items = $wpdb->get_results($query);
    }

    function column_default($item, $column_name) {
        switch ($column_name) {
            case 'title':
                return $item->post_title;
            case 'author':
                return $item->author;
            case 'categories':
                return $item->categories;
            case 'tags':
                return $item->tags;
            case 'date':
                return $item->post_date;
            default:
                return '';
        }
    }
}

function display_uncategorized_posts() {
    $table = new Uncategorized_Posts_Table();
    $table->prepare_items();
    $table->display();
}

// Admin paneline sayfa ekleme
function register_uncategorized_posts_page() {
    add_menu_page(
        'Kategorisi Olmayan Yazılar',
        'Kategorisi Olmayan Yazılar',
        'manage_options',
        'uncategorized_posts',
        'display_uncategorized_posts',
        'dashicons-category',
        25
    );
}
add_action('admin_menu', 'register_uncategorized_posts_page');
