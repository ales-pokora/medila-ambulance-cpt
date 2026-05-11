<?php
/**
 * Plugin Name: Medila Care - Custom Post Types
 * Description: Custom Post Types for medical practices (ambulance) and career positions with custom fields and taxonomies.
 * Version: 1.7.1
 * Author: Medila Care
 * Text Domain: medila-ambulance
 */

if (!defined('ABSPATH')) exit;

// Plugin file path used by submodules registering activation hooks
if (!defined('MEDILA_PLUGIN_FILE')) {
    define('MEDILA_PLUGIN_FILE', __FILE__);
}

// ============================================================================
// Auto-update from GitHub (Plugin Update Checker by YahnisElsts)
// ----------------------------------------------------------------------------
// To release a new version:
//   1. Bump the "Version:" header above
//   2. Commit + push to GitHub
//   3. Tag the commit:  git tag v1.2.1 && git push origin --tags
//   4. WordPress will offer the update in Plugins → Installed Plugins
// ============================================================================
// Defensive: any failure during update-check setup must NOT crash the site.
// Disable auto-update entirely by defining MEDILA_DISABLE_AUTO_UPDATE in wp-config.php.
if (!defined('MEDILA_DISABLE_AUTO_UPDATE') && file_exists(__DIR__ . '/lib/plugin-update-checker/plugin-update-checker.php')) {
    try {
        require_once __DIR__ . '/lib/plugin-update-checker/plugin-update-checker.php';

        if (class_exists('YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory')) {
            $medilaUpdateChecker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
                'https://github.com/ales-pokora/medila-ambulance-cpt/',
                __FILE__,
                'medila-ambulance-cpt'
            );

            if ($medilaUpdateChecker && method_exists($medilaUpdateChecker, 'setBranch')) {
                $medilaUpdateChecker->setBranch('main');
            }

            if ($medilaUpdateChecker && method_exists($medilaUpdateChecker, 'getVcsApi')) {
                $vcs = $medilaUpdateChecker->getVcsApi();
                if ($vcs && method_exists($vcs, 'enableReleaseAssets')) {
                    $vcs->enableReleaseAssets();
                }
            }

            if (defined('MEDILA_GH_TOKEN') && MEDILA_GH_TOKEN && $medilaUpdateChecker && method_exists($medilaUpdateChecker, 'setAuthentication')) {
                $medilaUpdateChecker->setAuthentication(MEDILA_GH_TOKEN);
            }
        }
    } catch (\Throwable $e) {
        // Swallow any error — auto-update is a convenience, not a hard dependency.
        // Recover by logging and continuing.
        if (function_exists('error_log')) {
            error_log('[medila-ambulance-cpt] Auto-updater init failed: ' . $e->getMessage());
        }
    }
}

// Register Custom Post Type
add_action('init', 'medila_register_ambulance_cpt');
function medila_register_ambulance_cpt() {
    $labels = [
        'name'               => 'Ambulance',
        'singular_name'      => 'Ambulance',
        'menu_name'          => 'Ambulance',
        'add_new'            => 'Přidat ambulanci',
        'add_new_item'       => 'Přidat novou ambulanci',
        'edit_item'          => 'Upravit ambulanci',
        'new_item'           => 'Nová ambulance',
        'view_item'          => 'Zobrazit ambulanci',
        'search_items'       => 'Hledat ambulance',
        'not_found'          => 'Žádné ambulance nenalezeny',
        'not_found_in_trash' => 'Žádné ambulance v koši',
        'all_items'          => 'Všechny ambulance',
    ];

    register_post_type('ambulance', [
        'labels'        => $labels,
        'public'        => true,
        'has_archive'   => true,
        'rewrite'       => ['slug' => 'ambulance', 'with_front' => false],
        'menu_icon'     => 'dashicons-plus-alt',
        'supports'      => ['title', 'editor', 'thumbnail', 'excerpt'],
        'show_in_rest'  => true,
        'menu_position' => 5,
    ]);
}

// Register Taxonomy - Specialization
add_action('init', 'medila_register_specialization_taxonomy');
function medila_register_specialization_taxonomy() {
    register_taxonomy('specialization', 'ambulance', [
        'labels' => [
            'name'          => 'Specializace',
            'singular_name' => 'Specializace',
            'search_items'  => 'Hledat specializace',
            'all_items'     => 'Všechny specializace',
            'edit_item'     => 'Upravit specializaci',
            'add_new_item'  => 'Přidat specializaci',
            'menu_name'     => 'Specializace',
        ],
        'hierarchical' => true,
        'public'       => true,
        'rewrite'      => ['slug' => 'specializace'],
        'show_in_rest' => true,
    ]);
}

// Register Taxonomy - Location
add_action('init', 'medila_register_location_taxonomy');
function medila_register_location_taxonomy() {
    register_taxonomy('practice_location', 'ambulance', [
        'labels' => [
            'name'          => 'Lokace',
            'singular_name' => 'Lokace',
            'search_items'  => 'Hledat lokace',
            'all_items'     => 'Všechny lokace',
            'edit_item'     => 'Upravit lokaci',
            'add_new_item'  => 'Přidat lokaci',
            'menu_name'     => 'Lokace',
        ],
        'hierarchical' => true,
        'public'       => true,
        'rewrite'      => ['slug' => 'lokace'],
        'show_in_rest' => true,
    ]);
}

// Add Meta Boxes for custom fields
add_action('add_meta_boxes', 'medila_add_ambulance_meta_boxes');
function medila_add_ambulance_meta_boxes() {
    add_meta_box(
        'ambulance_details',
        'Detaily ambulance',
        'medila_ambulance_meta_box_callback',
        'ambulance',
        'normal',
        'high'
    );
}

function medila_ambulance_meta_box_callback($post) {
    wp_nonce_field('medila_ambulance_meta', 'medila_ambulance_nonce');
    // Enable WP Media Library JS for the photo picker buttons (since v1.5.0)
    wp_enqueue_media();

    // Multi-doctor / multi-nurse (since v1.4.0). Legacy single-value fields are
    // migrated into the textarea on first edit so no data is lost.
    $doctors_multi = get_post_meta($post->ID, '_ambulance_doctors', true);
    if (!$doctors_multi) {
        $legacy = [];
        if ($d1 = get_post_meta($post->ID, '_ambulance_doctor', true))  $legacy[] = $d1;
        if ($d2 = get_post_meta($post->ID, '_ambulance_doctor2', true)) $legacy[] = $d2;
        $doctors_multi = $legacy ? implode("\n", $legacy) : '';
    }
    $nurses_multi = get_post_meta($post->ID, '_ambulance_nurses', true);
    if (!$nurses_multi) {
        $legacy_nurse = get_post_meta($post->ID, '_ambulance_nurse', true);
        $nurses_multi = $legacy_nurse ?: '';
    }

    $phone       = get_post_meta($post->ID, '_ambulance_phone', true);
    $email       = get_post_meta($post->ID, '_ambulance_email', true);
    $address     = get_post_meta($post->ID, '_ambulance_address', true);
    $hours       = get_post_meta($post->ID, '_ambulance_hours', true);
    $news        = get_post_meta($post->ID, '_ambulance_news', true);
    $pricelist   = get_post_meta($post->ID, '_ambulance_pricelist_url', true);
    $services    = get_post_meta($post->ID, '_ambulance_services', true);
    $booking_url = get_post_meta($post->ID, '_ambulance_booking_url', true);
    $registration = get_post_meta($post->ID, '_ambulance_registration', true);
    $insurance   = get_post_meta($post->ID, '_ambulance_insurance', true);
    ?>
    <style>
        .medila-meta-field { margin-bottom: 15px; }
        .medila-meta-field label { display: block; font-weight: 600; margin-bottom: 4px; }
        .medila-meta-field input,
        .medila-meta-field textarea,
        .medila-meta-field select { width: 100%; }
        .medila-meta-section { background: #f9f9f9; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e0e0e0; }
        .medila-meta-section h3 { margin: 0 0 12px; font-size: 14px; text-transform: uppercase; color: #00a278; letter-spacing: 0.5px; }
    </style>

    <div class="medila-meta-section">
        <h3>Personál</h3>
        <div class="medila-meta-field">
            <label for="ambulance_doctors">Lékaři</label>
            <textarea id="ambulance_doctors" name="ambulance_doctors" rows="5" placeholder="MUDr. Jan Novák | https://medila.care/wp-content/uploads/2026/jan.jpg&#10;MUDr. Jana Nováková | https://medila.care/wp-content/uploads/2026/jana.jpg&#10;MUDr. Petr Svoboda"><?php echo esc_textarea($doctors_multi); ?></textarea>
            <p class="description">
                Jeden lékař na řádek. Volitelně přidejte fotku: <code>Jméno | URL fotky</code>.
                <button type="button" class="button medila-photo-picker" data-target="ambulance_doctors">📷 Vybrat fotku z knihovny</button>
            </p>
        </div>
        <div class="medila-meta-field">
            <label for="ambulance_nurses">Sestry</label>
            <textarea id="ambulance_nurses" name="ambulance_nurses" rows="5" placeholder="Jana Dvořáková | https://medila.care/wp-content/uploads/2026/jana-d.jpg&#10;Marie Procházková&#10;(nepovinné)"><?php echo esc_textarea($nurses_multi); ?></textarea>
            <p class="description">
                Jedna sestra na řádek. Volitelně přidejte fotku: <code>Jméno | URL fotky</code>.
                <button type="button" class="button medila-photo-picker" data-target="ambulance_nurses">📷 Vybrat fotku z knihovny</button>
            </p>
        </div>
    </div>

    <script>
    (function($){
        if (typeof wp === 'undefined' || !wp.media) return;
        $(document).off('click.medilaPhoto').on('click.medilaPhoto', '.medila-photo-picker', function(e){
            e.preventDefault();
            var $btn = $(this);
            var target = document.getElementById($btn.data('target'));
            if (!target) return;
            var frame = wp.media({
                title: 'Vybrat fotku personálu',
                button: { text: 'Vložit URL fotky' },
                multiple: false,
                library: { type: 'image' }
            });
            frame.on('select', function(){
                var att = frame.state().get('selection').first().toJSON();
                var url = att.url;
                var start = target.selectionStart != null ? target.selectionStart : target.value.length;
                var end   = target.selectionEnd   != null ? target.selectionEnd   : target.value.length;
                var before = target.value.substring(0, start);
                var after  = target.value.substring(end);
                // Detect if current line already contains a "|" — if so, just paste the URL where the cursor is.
                var lineStart = before.lastIndexOf('\n') + 1;
                var currentLine = before.substring(lineStart);
                var prefix = currentLine.indexOf('|') > -1 ? '' : ' | ';
                target.value = before + prefix + url + after;
                target.focus();
                var newPos = before.length + prefix.length + url.length;
                target.setSelectionRange(newPos, newPos);
            });
            frame.open();
        });
    })(jQuery);
    </script>

    <div class="medila-meta-section">
        <h3>Kontakt</h3>
        <div class="medila-meta-field">
            <label for="ambulance_phone">Telefon</label>
            <input type="tel" id="ambulance_phone" name="ambulance_phone" value="<?php echo esc_attr($phone); ?>" placeholder="+420 123 456 789">
        </div>
        <div class="medila-meta-field">
            <label for="ambulance_email">E-mail</label>
            <input type="email" id="ambulance_email" name="ambulance_email" value="<?php echo esc_attr($email); ?>" placeholder="lenesice@medilacare.cz">
        </div>
        <div class="medila-meta-field">
            <label for="ambulance_address">Adresa ordinace</label>
            <input type="text" id="ambulance_address" name="ambulance_address" value="<?php echo esc_attr($address); ?>" placeholder="Knížete Václava 64, 439 23 Lenešice">
        </div>
    </div>

    <div class="medila-meta-section">
        <h3>Ordinační doba</h3>
        <div class="medila-meta-field">
            <label for="ambulance_hours">Ordinační hodiny</label>
            <textarea id="ambulance_hours" name="ambulance_hours" rows="6" placeholder="PO: Objednaní 7:00-8:00 | Akutní 8:00-10:00 | Objednaní 10:00-12:00&#10;ÚT: Objednaní 7:00-8:00 | Akutní 8:00-10:00 | Objednaní 10:00-12:00&#10;ST: Objednaní 7:00-8:00 | Akutní 8:00-10:00 | Objednaní 12:00-18:00&#10;ČT: Objednaní 7:00-8:00 | Akutní 8:00-10:00 | Objednaní 10:00-12:00&#10;PÁ: Objednaní 7:00-8:00 | Akutní 8:00-10:00 | Objednaní 10:00-12:00"><?php echo esc_textarea($hours); ?></textarea>
            <p class="description">Jeden den na řádek. Formát: DEN: typ čas | typ čas | typ čas</p>
        </div>
    </div>

    <div class="medila-meta-section">
        <h3>Služby a informace</h3>
        <div class="medila-meta-field">
            <label for="ambulance_news">Aktuality z ordinace</label>
            <textarea id="ambulance_news" name="ambulance_news" rows="3" placeholder="Aktuální informace pro pacienty..."><?php echo esc_textarea($news); ?></textarea>
        </div>
        <div class="medila-meta-field">
            <label for="ambulance_services">Nabídka ordinace</label>
            <textarea id="ambulance_services" name="ambulance_services" rows="4" placeholder="Pracovně-lékařské služby&#10;Programy pro samoplátce&#10;Telefonické konzultace"><?php echo esc_textarea($services); ?></textarea>
            <p class="description">Jedna služba na řádek.</p>
        </div>
        <div class="medila-meta-field">
            <label for="ambulance_insurance">Smluvní pojišťovny</label>
            <input type="text" id="ambulance_insurance" name="ambulance_insurance" value="<?php echo esc_attr($insurance); ?>" placeholder="VZP|https://www.vzp.cz, VOZP|https://www.vozp.cz, OZP">
            <p class="description">Oddělujte čárkou. Pro proklik na web použijte formát <code>Název|https://url</code> (např. <code>VZP|https://www.vzp.cz</code>). Pojišťovna bez URL se zobrazí jako text.</p>
        </div>
    </div>

    <div class="medila-meta-section">
        <h3>Odkazy</h3>
        <div class="medila-meta-field">
            <label for="ambulance_booking_url">Odkaz na objednání</label>
            <input type="url" id="ambulance_booking_url" name="ambulance_booking_url" value="<?php echo esc_url($booking_url); ?>" placeholder="https://portalpacienta.cz/...">
        </div>
        <div class="medila-meta-field">
            <label for="ambulance_pricelist_url">Ceník (odkaz)</label>
            <input type="url" id="ambulance_pricelist_url" name="ambulance_pricelist_url" value="<?php echo esc_url($pricelist); ?>" placeholder="https://medila.care/cenik-lenesice">
        </div>
        <div class="medila-meta-field">
            <label for="ambulance_registration">Registrace</label>
            <input type="text" id="ambulance_registration" name="ambulance_registration" value="<?php echo esc_attr($registration); ?>" placeholder="Informace o registraci nových pacientů">
        </div>
    </div>
    <?php
}

// Save Meta Box Data
add_action('save_post_ambulance', 'medila_save_ambulance_meta');
function medila_save_ambulance_meta($post_id) {
    if (!isset($_POST['medila_ambulance_nonce']) || !wp_verify_nonce($_POST['medila_ambulance_nonce'], 'medila_ambulance_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $text_fields = ['phone', 'email', 'address', 'insurance', 'registration'];
    foreach ($text_fields as $field) {
        if (isset($_POST['ambulance_' . $field])) {
            update_post_meta($post_id, '_ambulance_' . $field, sanitize_text_field($_POST['ambulance_' . $field]));
        }
    }

    $textarea_fields = ['hours', 'news', 'services', 'doctors', 'nurses'];
    foreach ($textarea_fields as $field) {
        if (isset($_POST['ambulance_' . $field])) {
            update_post_meta($post_id, '_ambulance_' . $field, sanitize_textarea_field($_POST['ambulance_' . $field]));
        }
    }

    $url_fields = ['booking_url', 'pricelist_url'];
    foreach ($url_fields as $field) {
        if (isset($_POST['ambulance_' . $field])) {
            update_post_meta($post_id, '_ambulance_' . $field, esc_url_raw($_POST['ambulance_' . $field]));
        }
    }
}

// Make CPT available in Divi Blog module
add_filter('et_builder_post_types', 'medila_add_ambulance_to_divi');
function medila_add_ambulance_to_divi($post_types) {
    $post_types[] = 'ambulance';
    return $post_types;
}

add_filter('et_builder_get_builder_post_types', 'medila_add_ambulance_to_divi_builder');
function medila_add_ambulance_to_divi_builder($post_types) {
    $post_types[] = 'ambulance';
    return $post_types;
}

// Register shortcode for ambulance listing (fallback if Divi Blog module doesn't work with CPT)
add_shortcode('medila_ambulance_list', 'medila_ambulance_list_shortcode');
function medila_ambulance_list_shortcode($atts) {
    $atts = shortcode_atts([
        'count'          => 9,
        'specialization' => '',
        'location'       => '',
        'columns'        => 3,
    ], $atts);

    $args = [
        'post_type'      => 'ambulance',
        'posts_per_page' => intval($atts['count']),
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
    ];

    if ($atts['specialization']) {
        $args['tax_query'][] = [
            'taxonomy' => 'specialization',
            'field'    => 'slug',
            'terms'    => explode(',', $atts['specialization']),
        ];
    }

    if ($atts['location']) {
        $args['tax_query'][] = [
            'taxonomy' => 'practice_location',
            'field'    => 'slug',
            'terms'    => explode(',', $atts['location']),
        ];
    }

    $query = new WP_Query($args);

    if (!$query->have_posts()) {
        return '<p style="text-align:center;color:#666;">Žádné ambulance k zobrazení.</p>';
    }

    $cols = intval($atts['columns']);
    $output = '<div class="medila-ambulance-grid" style="display:grid;grid-template-columns:repeat(' . $cols . ',1fr);gap:30px;">';

    while ($query->have_posts()) {
        $query->the_post();
        $id         = get_the_ID();
        $doctors_arr = function_exists('medila_get_ambulance_doctors') ? medila_get_ambulance_doctors($id) : [];
        $doctor1    = isset($doctors_arr[0]) ? $doctors_arr[0]['name'] : '';
        $doctor2    = isset($doctors_arr[1]) ? $doctors_arr[1]['name'] : '';
        $address    = get_post_meta($id, '_ambulance_address', true);
        $phone      = get_post_meta($id, '_ambulance_phone', true);
        $email      = get_post_meta($id, '_ambulance_email', true);
        $insurance  = get_post_meta($id, '_ambulance_insurance', true);
        $specs      = get_the_terms($id, 'specialization');
        $spec_name  = ($specs && !is_wp_error($specs)) ? $specs[0]->name : '';
        $thumbnail  = get_the_post_thumbnail_url($id, 'medium_large') ?: '';

        $output .= '<a href="' . get_permalink() . '" class="medila-ambulance-card" style="text-decoration:none;color:inherit;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 15px rgba(0,0,0,0.08);transition:transform 0.3s,box-shadow 0.3s;display:flex;flex-direction:column;">';

        if ($thumbnail) {
            $output .= '<div style="height:200px;overflow:hidden;"><img src="' . esc_url($thumbnail) . '" alt="' . esc_attr(get_the_title()) . '" style="width:100%;height:100%;object-fit:cover;"></div>';
        }

        $output .= '<div style="padding:24px;flex:1;display:flex;flex-direction:column;">';

        if ($spec_name) {
            $output .= '<span style="display:inline-block;background:#e6f7f3;color:#00a278;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;letter-spacing:0.5px;text-transform:uppercase;margin-bottom:12px;width:fit-content;">' . esc_html($spec_name) . '</span>';
        }

        $output .= '<h3 style="margin:0 0 8px;font-size:20px;color:#1a1a1a;font-weight:700;">' . get_the_title() . '</h3>';

        if ($doctor1 || $doctor2) {
            $output .= '<div style="margin:0 0 12px;">';
            if ($doctor1) {
                $output .= '<p style="margin:0 0 2px;color:#00a278;font-size:14px;font-weight:500;">' . esc_html($doctor1) . '</p>';
            }
            if ($doctor2) {
                $output .= '<p style="margin:0;color:#00a278;font-size:14px;font-weight:500;">' . esc_html($doctor2) . '</p>';
            }
            $output .= '</div>';
        }

        if ($address) {
            $output .= '<p style="margin:0 0 6px;color:#666;font-size:13px;line-height:1.5;"><svg width="12" height="12" viewBox="0 0 24 24" fill="#009ab2" style="vertical-align:-1px;margin-right:5px;"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5z"/></svg>' . esc_html($address) . '</p>';
        }

        if ($phone) {
            $output .= '<p style="margin:0 0 6px;color:#666;font-size:13px;line-height:1.5;"><svg width="12" height="12" viewBox="0 0 24 24" fill="#009ab2" style="vertical-align:-1px;margin-right:5px;"><path d="M6.62 10.79a15.05 15.05 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.01-.24c1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.57a1 1 0 0 1-.25 1.02l-2.2 2.2z"/></svg>' . esc_html($phone) . '</p>';
        }

        if ($email) {
            $output .= '<p style="margin:0 0 6px;color:#666;font-size:13px;line-height:1.5;"><svg width="12" height="12" viewBox="0 0 24 24" fill="#009ab2" style="vertical-align:-1px;margin-right:5px;"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>' . esc_html($email) . '</p>';
        }

        if ($insurance) {
            $output .= '<p style="margin:0;color:#888;font-size:12px;line-height:1.5;margin-top:4px;"><strong>Pojišťovny:</strong> ' . esc_html($insurance) . '</p>';
        }

        $output .= '<div style="margin-top:auto;padding-top:16px;"><span style="color:#009ab2;font-size:14px;font-weight:600;letter-spacing:0.5px;text-transform:uppercase;">Zobrazit detail &rarr;</span></div>';

        $output .= '</div></a>';
    }

    $output .= '</div>';

    // Responsive CSS
    $output .= '<style>
        .medila-ambulance-card:hover { transform:translateY(-4px); box-shadow:0 8px 30px rgba(0,0,0,0.12) !important; }
        @media (max-width:980px) { .medila-ambulance-grid { grid-template-columns:repeat(2,1fr) !important; } }
        @media (max-width:480px) { .medila-ambulance-grid { grid-template-columns:1fr !important; gap:20px !important; } }
    </style>';

    wp_reset_postdata();
    return $output;
}

// Flush rewrite rules on activation
register_activation_hook(__FILE__, 'medila_ambulance_activate');
function medila_ambulance_activate() {
    medila_register_ambulance_cpt();
    medila_register_specialization_taxonomy();
    medila_register_location_taxonomy();
    flush_rewrite_rules();
}

register_deactivation_hook(__FILE__, 'medila_ambulance_deactivate');
function medila_ambulance_deactivate() {
    flush_rewrite_rules();
}

// Load Career CPT module
require_once plugin_dir_path(__FILE__) . 'includes/career-cpt.php';

// Load Ambulance News CPT module
require_once plugin_dir_path(__FILE__) . 'includes/news-cpt.php';

// Load Ambulance single template (shortcodes, helpers, styles)
require_once plugin_dir_path(__FILE__) . 'includes/single-template.php';

// Load Ambulance News single + archive templates
require_once plugin_dir_path(__FILE__) . 'includes/news-template.php';
