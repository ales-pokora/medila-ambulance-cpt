<?php
/**
 * Medila Care - Career Positions CPT
 * Registers job positions custom post type with custom fields.
 */

if (!defined('ABSPATH')) exit;

// Register Custom Post Type
add_action('init', 'medila_register_career_cpt');
function medila_register_career_cpt() {
    $labels = [
        'name'               => 'Kariéra',
        'singular_name'      => 'Pozice',
        'menu_name'          => 'Kariéra',
        'add_new'            => 'Přidat pozici',
        'add_new_item'       => 'Přidat novou pozici',
        'edit_item'          => 'Upravit pozici',
        'new_item'           => 'Nová pozice',
        'view_item'          => 'Zobrazit pozici',
        'search_items'       => 'Hledat pozice',
        'not_found'          => 'Žádné pozice nenalezeny',
        'not_found_in_trash' => 'Žádné pozice v koši',
        'all_items'          => 'Všechny pozice',
    ];

    register_post_type('career_position', [
        'labels'        => $labels,
        'public'        => true,
        'has_archive'   => false,
        'rewrite'       => ['slug' => 'pozice', 'with_front' => false],
        'menu_icon'     => 'dashicons-groups',
        'supports'      => ['title', 'editor', 'thumbnail', 'excerpt'],
        'show_in_rest'  => true,
        'menu_position' => 6,
    ]);
}

// Register Taxonomy - Department / Field
add_action('init', 'medila_register_department_taxonomy');
function medila_register_department_taxonomy() {
    register_taxonomy('department', 'career_position', [
        'labels' => [
            'name'          => 'Obor',
            'singular_name' => 'Obor',
            'search_items'  => 'Hledat obory',
            'all_items'     => 'Všechny obory',
            'edit_item'     => 'Upravit obor',
            'add_new_item'  => 'Přidat obor',
            'menu_name'     => 'Obory',
        ],
        'hierarchical' => true,
        'public'       => true,
        'rewrite'      => ['slug' => 'obor'],
        'show_in_rest' => true,
    ]);
}

// Add Meta Boxes
add_action('add_meta_boxes', 'medila_add_career_meta_boxes');
function medila_add_career_meta_boxes() {
    add_meta_box(
        'career_details',
        'Detaily pozice',
        'medila_career_meta_box_callback',
        'career_position',
        'normal',
        'high'
    );
}

function medila_career_meta_box_callback($post) {
    wp_nonce_field('medila_career_meta', 'medila_career_nonce');

    $location       = get_post_meta($post->ID, '_career_location', true);
    $employment_type = get_post_meta($post->ID, '_career_employment_type', true);
    $salary          = get_post_meta($post->ID, '_career_salary', true);
    $icon            = get_post_meta($post->ID, '_career_icon', true);
    ?>
    <style>
        .medila-career-field { margin-bottom: 15px; }
        .medila-career-field label { display: block; font-weight: 600; margin-bottom: 4px; }
        .medila-career-field input,
        .medila-career-field select,
        .medila-career-field textarea { width: 100%; }
    </style>

    <div class="medila-career-field">
        <label for="career_location">Lokalita</label>
        <input type="text" id="career_location" name="career_location" value="<?php echo esc_attr($location); ?>" placeholder="Pardubice, Louny, ...">
    </div>

    <div class="medila-career-field">
        <label for="career_employment_type">Typ úvazku</label>
        <select id="career_employment_type" name="career_employment_type">
            <option value="full-time" <?php selected($employment_type, 'full-time'); ?>>Plný úvazek</option>
            <option value="part-time" <?php selected($employment_type, 'part-time'); ?>>Částečný úvazek</option>
            <option value="contract" <?php selected($employment_type, 'contract'); ?>>DPP / DPČ</option>
            <option value="flexible" <?php selected($employment_type, 'flexible'); ?>>Flexibilní</option>
        </select>
    </div>

    <div class="medila-career-field">
        <label for="career_salary">Platové ohodnocení (nepovinné)</label>
        <input type="text" id="career_salary" name="career_salary" value="<?php echo esc_attr($salary); ?>" placeholder="od 80 000 Kč / měsíc">
    </div>

    <div class="medila-career-field">
        <label for="career_icon">URL ikony / obrázku (nepovinné)</label>
        <input type="url" id="career_icon" name="career_icon" value="<?php echo esc_url($icon); ?>" placeholder="https://medila.care/wp-content/uploads/...">
        <p class="description">Pokud nevyplníte, použije se výchozí ikona.</p>
    </div>
    <?php
}

// Save Meta Box Data
add_action('save_post_career_position', 'medila_save_career_meta');
function medila_save_career_meta($post_id) {
    if (!isset($_POST['medila_career_nonce']) || !wp_verify_nonce($_POST['medila_career_nonce'], 'medila_career_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $fields = ['location', 'employment_type', 'salary', 'icon'];
    foreach ($fields as $field) {
        if (isset($_POST['career_' . $field])) {
            update_post_meta($post_id, '_career_' . $field, sanitize_text_field($_POST['career_' . $field]));
        }
    }
}

// Make CPT available in Divi
add_filter('et_builder_post_types', 'medila_add_career_to_divi');
function medila_add_career_to_divi($post_types) {
    $post_types[] = 'career_position';
    return $post_types;
}

add_filter('et_builder_get_builder_post_types', 'medila_add_career_to_divi_builder');
function medila_add_career_to_divi_builder($post_types) {
    $post_types[] = 'career_position';
    return $post_types;
}

// Shortcode for career listing
add_shortcode('medila_career_list', 'medila_career_list_shortcode');
function medila_career_list_shortcode($atts) {
    $atts = shortcode_atts([
        'count'      => 12,
        'department' => '',
        'category'   => '',
        'columns'    => 2,
        'post_type'  => 'career_position',
    ], $atts);

    $args = [
        'post_type'      => sanitize_text_field($atts['post_type']),
        'posts_per_page' => intval($atts['count']),
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];

    if ($atts['department']) {
        $args['tax_query'][] = [
            'taxonomy' => 'department',
            'field'    => 'slug',
            'terms'    => explode(',', $atts['department']),
        ];
    }

    // Support filtering by regular post category (when using post_type="post")
    if ($atts['category']) {
        $args['category_name'] = sanitize_text_field($atts['category']);
    }

    $query = new WP_Query($args);

    if (!$query->have_posts()) {
        return '<p style="text-align:center;color:#666;padding:40px 0;">Momentálně nemáme žádné otevřené pozice.</p>';
    }

    $employment_labels = [
        'full-time' => 'Plný úvazek',
        'part-time' => 'Částečný úvazek',
        'contract'  => 'DPP / DPČ',
        'flexible'  => 'Flexibilní',
    ];

    $cols = intval($atts['columns']);
    $output = '<div class="medila-career-grid">';

    while ($query->have_posts()) {
        $query->the_post();
        $id              = get_the_ID();
        $location        = get_post_meta($id, '_career_location', true);
        $employment_type = get_post_meta($id, '_career_employment_type', true);
        $salary          = get_post_meta($id, '_career_salary', true);
        $icon            = get_post_meta($id, '_career_icon', true);
        $departments     = get_the_terms($id, 'department');
        $dept_name       = ($departments && !is_wp_error($departments)) ? $departments[0]->name : '';
        $employment_label = isset($employment_labels[$employment_type]) ? $employment_labels[$employment_type] : '';
        $excerpt         = get_the_excerpt();
        $permalink       = get_permalink();

        $output .= '<a href="' . esc_url($permalink) . '" class="medila-career-card">';

        // Top accent bar
        $output .= '<div class="medila-career-card__accent"></div>';

        $output .= '<div class="medila-career-card__body">';

        // Header: icon + badges
        $output .= '<div class="medila-career-card__header">';
        if ($icon) {
            $output .= '<div class="medila-career-card__icon"><img src="' . esc_url($icon) . '" alt=""></div>';
        } else {
            $output .= '<div class="medila-career-card__icon medila-career-card__icon--default"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#00a278" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>';
        }
        $output .= '<div class="medila-career-card__badges">';
        if ($dept_name) {
            $output .= '<span class="medila-career-card__badge medila-career-card__badge--dept">' . esc_html($dept_name) . '</span>';
        }
        if ($employment_label) {
            $output .= '<span class="medila-career-card__badge medila-career-card__badge--type">' . esc_html($employment_label) . '</span>';
        }
        $output .= '</div></div>';

        // Title
        $output .= '<h3 class="medila-career-card__title">' . get_the_title() . '</h3>';

        // Meta: location + salary
        if ($location || $salary) {
            $output .= '<div class="medila-career-card__meta">';
            if ($location) {
                $output .= '<span class="medila-career-card__location"><svg width="14" height="14" viewBox="0 0 24 24" fill="#009ab2" style="vertical-align:-2px;margin-right:4px;"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5z"/></svg>' . esc_html($location) . '</span>';
            }
            if ($salary) {
                $output .= '<span class="medila-career-card__salary"><svg width="14" height="14" viewBox="0 0 24 24" fill="#00a278" style="vertical-align:-2px;margin-right:4px;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17.93V20h-2v-.07A7.02 7.02 0 0 1 5.7 16l1.44-1.44c.97 1.86 2.93 3.14 5.16 3.38v-5.6l-.6-.22C9.87 11.36 9 10.28 9 9c0-1.63 1.34-2.97 3-3V5h2v1c1.13.12 2.16.57 3 1.27L15.56 8.7A4.99 4.99 0 0 0 13 7.67v5.07l.6.22c1.83.68 2.7 1.76 2.7 3.04 0 1.63-1.34 2.97-3 3v.93z"/></svg>' . esc_html($salary) . '</span>';
            }
            $output .= '</div>';
        }

        // Excerpt
        if ($excerpt) {
            $output .= '<p class="medila-career-card__excerpt">' . esc_html(wp_trim_words($excerpt, 20, '...')) . '</p>';
        }

        // CTA
        $output .= '<div class="medila-career-card__cta"><span>Detail pozice</span><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg></div>';

        $output .= '</div></a>';
    }

    $output .= '</div>';

    // Styles
    $output .= '<style>
    .medila-career-grid {
        display: grid;
        grid-template-columns: repeat(' . $cols . ', 1fr);
        gap: 24px;
    }
    .medila-career-card {
        display: flex;
        flex-direction: column;
        background: #fff;
        border-radius: 14px;
        overflow: hidden;
        text-decoration: none;
        color: inherit;
        box-shadow: 0 2px 20px rgba(50,71,71,0.07);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        position: relative;
    }
    .medila-career-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 12px 40px rgba(50,71,71,0.14);
    }
    .medila-career-card__accent {
        height: 5px;
        background: linear-gradient(90deg, #00a278 0%, #009ab2 100%);
    }
    .medila-career-card__body {
        padding: 28px 28px 24px;
        display: flex;
        flex-direction: column;
        flex: 1;
    }
    .medila-career-card__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 16px;
        gap: 12px;
    }
    .medila-career-card__icon {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        overflow: hidden;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .medila-career-card__icon img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .medila-career-card__icon--default {
        background: #e8f8f4;
    }
    .medila-career-card__badges {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        justify-content: flex-end;
    }
    .medila-career-card__badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .medila-career-card__badge--dept {
        background: #e8f8f4;
        color: #00a278;
    }
    .medila-career-card__badge--type {
        background: #e6f4f7;
        color: #009ab2;
    }
    .medila-career-card__title {
        font-family: "Raleway", sans-serif;
        font-size: 20px;
        font-weight: 700;
        color: #1a1a2e;
        margin: 0 0 12px;
        line-height: 1.35;
    }
    .medila-career-card:hover .medila-career-card__title {
        color: #00a278;
    }
    .medila-career-card__meta {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 14px;
    }
    .medila-career-card__location,
    .medila-career-card__salary {
        font-size: 13px;
        color: #555;
        display: flex;
        align-items: center;
    }
    .medila-career-card__excerpt {
        font-size: 14px;
        line-height: 1.65;
        color: #666;
        margin: 0 0 0;
        flex: 1;
    }
    .medila-career-card__cta {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-top: 20px;
        padding-top: 18px;
        border-top: 1px solid #f0f0f0;
        font-size: 14px;
        font-weight: 700;
        letter-spacing: 1px;
        text-transform: uppercase;
        background: linear-gradient(90deg, #00a278, #009ab2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        transition: gap 0.3s ease;
    }
    .medila-career-card__cta svg {
        stroke: #009ab2;
        transition: transform 0.3s ease;
    }
    .medila-career-card:hover .medila-career-card__cta {
        gap: 10px;
    }
    .medila-career-card:hover .medila-career-card__cta svg {
        transform: translateX(4px);
    }
    @media (max-width: 980px) {
        .medila-career-grid { grid-template-columns: 1fr 1fr; }
        .medila-career-card__body { padding: 22px 22px 20px; }
    }
    @media (max-width: 600px) {
        .medila-career-grid { grid-template-columns: 1fr; gap: 16px; }
        .medila-career-card__body { padding: 20px 18px 18px; }
        .medila-career-card__title { font-size: 18px; }
        .medila-career-card__header { flex-direction: column; }
        .medila-career-card__badges { justify-content: flex-start; }
    }
    </style>';

    wp_reset_postdata();
    return $output;
}
