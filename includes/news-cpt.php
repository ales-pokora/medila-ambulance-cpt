<?php
/**
 * Medila Care - Ambulance News CPT
 * Per-ambulance news/announcements (uzavírky, nová vyšetření, atd.)
 * with archive support and per-ambulance filtering.
 *
 * @since 1.6.0
 */

if (!defined('ABSPATH')) exit;

// Register CPT
add_action('init', 'medila_register_ambulance_news_cpt');
function medila_register_ambulance_news_cpt() {
    $labels = [
        'name'               => 'Aktuality',
        'singular_name'      => 'Aktualita',
        'menu_name'          => 'Aktuality (ambulance)',
        'add_new'            => 'Přidat aktualitu',
        'add_new_item'       => 'Přidat novou aktualitu',
        'edit_item'          => 'Upravit aktualitu',
        'new_item'           => 'Nová aktualita',
        'view_item'          => 'Zobrazit aktualitu',
        'search_items'       => 'Hledat aktuality',
        'not_found'          => 'Žádné aktuality nenalezeny',
        'not_found_in_trash' => 'Žádné aktuality v koši',
        'all_items'          => 'Všechny aktuality',
    ];

    register_post_type('ambulance_news', [
        'labels'        => $labels,
        'public'        => true,
        'has_archive'   => 'aktuality',
        'rewrite'       => ['slug' => 'aktuality', 'with_front' => false],
        'menu_icon'     => 'dashicons-megaphone',
        'supports'      => ['title', 'editor', 'thumbnail', 'excerpt'],
        'show_in_rest'  => true,
        'menu_position' => 7,
    ]);
}

// Meta box: which ambulance does this news belong to
add_action('add_meta_boxes', 'medila_add_news_meta_box');
function medila_add_news_meta_box() {
    add_meta_box(
        'ambulance_news_details',
        'Souvislost s ambulancí',
        'medila_news_meta_box_callback',
        'ambulance_news',
        'side',
        'high'
    );
}

function medila_news_meta_box_callback($post) {
    wp_nonce_field('medila_news_meta', 'medila_news_nonce');
    $amb_id = (int) get_post_meta($post->ID, '_news_ambulance', true);

    $ambulances = get_posts([
        'post_type'      => 'ambulance',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'post_status'    => ['publish', 'draft', 'private'],
    ]);
    ?>
    <p style="margin-top:0;">Vyberte ordinaci, ke které tato aktualita patří:</p>
    <select name="news_ambulance" style="width:100%;">
        <option value="">— vyberte ambulanci —</option>
        <?php foreach ($ambulances as $amb) : ?>
            <option value="<?php echo esc_attr($amb->ID); ?>" <?php selected($amb_id, $amb->ID); ?>>
                <?php echo esc_html($amb->post_title); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p class="description" style="margin-top:10px;">Aktualita se zobrazí v gridu na stránce vybrané ambulance.</p>
    <?php
}

// Save handler
add_action('save_post_ambulance_news', 'medila_save_news_meta');
function medila_save_news_meta($post_id) {
    if (!isset($_POST['medila_news_nonce']) || !wp_verify_nonce($_POST['medila_news_nonce'], 'medila_news_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['news_ambulance'])) {
        update_post_meta($post_id, '_news_ambulance', (int) $_POST['news_ambulance']);
    }
}

// Filter the news archive by ?amb={ambulance_id}
add_action('pre_get_posts', 'medila_filter_news_archive');
function medila_filter_news_archive($query) {
    if (is_admin() || !$query->is_main_query()) return;
    if (!$query->is_post_type_archive('ambulance_news')) return;

    if (!empty($_GET['amb']) && is_numeric($_GET['amb'])) {
        $meta_query = (array) $query->get('meta_query');
        $meta_query[] = [
            'key'   => '_news_ambulance',
            'value' => (int) $_GET['amb'],
        ];
        $query->set('meta_query', $meta_query);
    }
}

// Make news CPT visible to Divi
add_filter('et_builder_post_types', 'medila_add_news_to_divi');
function medila_add_news_to_divi($post_types) {
    $post_types[] = 'ambulance_news';
    return $post_types;
}
add_filter('et_builder_get_builder_post_types', 'medila_add_news_to_divi_builder');
function medila_add_news_to_divi_builder($post_types) {
    $post_types[] = 'ambulance_news';
    return $post_types;
}

// Helper: get N most recent news for a specific ambulance
function medila_get_ambulance_news($ambulance_id, $limit = 4) {
    if (!$ambulance_id) return [];
    return get_posts([
        'post_type'      => 'ambulance_news',
        'posts_per_page' => (int) $limit,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => [[
            'key'   => '_news_ambulance',
            'value' => (int) $ambulance_id,
        ]],
    ]);
}

// Helper: get the parent ambulance for a news post
function medila_get_news_ambulance_id($news_id) {
    return (int) get_post_meta($news_id, '_news_ambulance', true);
}

// Flush rewrites on activation (registers /aktuality/ permalinks)
register_activation_hook(MEDILA_PLUGIN_FILE, 'medila_news_activate');
function medila_news_activate() {
    medila_register_ambulance_news_cpt();
    flush_rewrite_rules();
}
