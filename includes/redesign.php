<?php
/**
 * Medila Care 2.0 redesign — data + components that the new pages need.
 *
 *  - Ambulance: region (kraj) + "accepting new patients" fields
 *  - [medila_ambulance_regions]  practice cards grouped by region
 *  - Map: rich place detail (marker colour/glyph, open status, hours table,
 *    promo) for ambulances, plus MEDILA collection centres (wppm_place posts
 *    of type "odberove-centrum") as a second point type
 *  - [medila_map_legend]         legend under the map
 *  - Career: patient base / work mode / start fields
 *  - [medila_career_cards]       job cards in the new fixed-field standard
 *  - single career_position      new detail layout with a working enquiry form
 *
 * Requires WP Places Map 1.1.0+ for the rich detail fields; older versions
 * simply ignore the extra keys.
 */

if (!defined('ABSPATH')) exit;

define('MEDILA_RD_TEAL', '#156D6D');
define('MEDILA_RD_TEAL_DEEP', '#0B4646');
define('MEDILA_RD_RED', '#C0392B');
define('MEDILA_RD_RED_DEEP', '#922B21');
define('MEDILA_RD_ODBER_TERM', 'odberove-centrum');

// 24×24 stroke icons, "|" separates path strings (WP Places Map glyph format).
define('MEDILA_RD_GLYPH_STETHOSCOPE', 'M11 2v2|M5 2v2|M5 3H4a2 2 0 0 0-2 2v4a6 6 0 0 0 12 0V5a2 2 0 0 0-2-2h-1|M8 15a6 6 0 0 0 12 0v-3|M22 10a2 2 0 1 1-4 0a2 2 0 1 1 4 0');
define('MEDILA_RD_GLYPH_DROP', 'M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z');

function medila_rd_regions() {
    return [
        'praha'            => 'Hlavní město Praha',
        'stredocesky'      => 'Středočeský kraj',
        'jihocesky'        => 'Jihočeský kraj',
        'plzensky'         => 'Plzeňský kraj',
        'karlovarsky'      => 'Karlovarský kraj',
        'ustecky'          => 'Ústecký kraj',
        'liberecky'        => 'Liberecký kraj',
        'kralovehradecky'  => 'Královéhradecký kraj',
        'pardubicky'       => 'Pardubický kraj',
        'vysocina'         => 'Kraj Vysočina',
        'jihomoravsky'     => 'Jihomoravský kraj',
        'olomoucky'        => 'Olomoucký kraj',
        'zlinsky'          => 'Zlínský kraj',
        'moravskoslezsky'  => 'Moravskoslezský kraj',
    ];
}

function medila_rd_svg($name, $size = 16) {
    $paths = [
        'pin'    => '<path d="M12 21s-7-6.5-7-11a7 7 0 0 1 14 0c0 4.5-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'phone'  => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.86 19.86 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.86 19.86 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.37 1.9.72 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.35 1.85.59 2.81.72A2 2 0 0 1 22 16.92z"/>',
        'mail'   => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/>',
        'arrow'  => '<path d="M5 12h14M12 5l7 7-7 7"/>',
        'check'  => '<path d="M20 6 9 17l-5-5"/>',
        'cal'    => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'flask'  => '<path d="M9 2v6.5L4.5 17a2.5 2.5 0 0 0 2.2 3.7h10.6a2.5 2.5 0 0 0 2.2-3.7L15 8.5V2"/><path d="M9 2h6"/>',
        'lock'   => '<rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'build'  => '<path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/>',
    ];
    return '<svg width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ($paths[$name] ?? '') . '</svg>';
}

function medila_rd_glyph_svg($glyph, $size = 14) {
    $out = '';
    foreach (explode('|', $glyph) as $d) {
        $out .= '<path d="' . esc_attr(trim($d)) . '"/>';
    }
    return '<svg width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $out . '</svg>';
}

/**
 * "PRAKTICKÝ LÉKAŘ BRNO-ŘEČKOVICE" → "Praktický lékař Brno-Řečkovice".
 * Ambulance titles are stored in capitals; the new cards set them in sentence
 * case with capitalised place names. Mixed-case titles pass through untouched.
 */
function medila_rd_display_title($title) {
    $title = html_entity_decode(wp_strip_all_tags($title), ENT_QUOTES, 'UTF-8');
    if ($title === '' || mb_strtoupper($title, 'UTF-8') !== $title) {
        return $title;
    }
    $t = mb_convert_case(mb_strtolower($title, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    // Generic words that stay lower case after the first word.
    $lower = ['Lékař', 'Lékařka', 'Ordinace', 'Ambulance', 'Pro', 'A', 'Nad', 'Pod', 'U', 'Na', 'V', 'Ve', 'Dorost', 'Děti'];
    $words = explode(' ', $t);
    foreach ($words as $i => $w) {
        if ($i > 0 && in_array($w, $lower, true)) {
            $words[$i] = mb_strtolower($w, 'UTF-8');
        }
    }
    return implode(' ', $words);
}

/**
 * Parse the free-form hours field:
 *   "PO: Objednaní 7:00-12:00 | Poradna pro zvané 12:00-14:00"
 *   "ST: Objednaní 12:00-17:00 | Poradna 10:00-12:00  17:00-18:00"
 * into a day × column table plus a machine schedule for the open/closed badge.
 */
function medila_rd_parse_hours($text) {
    $day_map = ['PO' => 1, 'ÚT' => 2, 'UT' => 2, 'ST' => 3, 'ČT' => 4, 'CT' => 4, 'PÁ' => 5, 'PA' => 5, 'SO' => 6, 'NE' => 7];
    $columns  = [];
    $by_day   = [];
    $schedule = [];

    foreach (preg_split('/\r\n|\r|\n/', (string) $text) as $line) {
        $line = trim($line);
        if (!preg_match('/^([A-ZÁČÚ]{2})\s*:\s*(.+)$/u', $line, $m)) {
            continue;
        }
        $day_label = mb_strtoupper($m[1], 'UTF-8');
        if (!isset($day_map[$day_label])) {
            continue;
        }
        $iso = $day_map[$day_label];
        $by_day[$day_label] = $by_day[$day_label] ?? [];

        foreach (explode('|', $m[2]) as $segment) {
            $segment = trim($segment);
            if ($segment === '') continue;
            preg_match_all('/(\d{1,2})[:.](\d{2})\s*[-–]\s*(\d{1,2})[:.](\d{2})/u', $segment, $rm, PREG_SET_ORDER);
            if (!$rm) continue;
            $label = trim(preg_replace('/\d.*$/su', '', $segment), " \t:–-");
            $label = $label !== '' ? $label : 'Otevřeno';
            if (!in_array($label, $columns, true)) {
                $columns[] = $label;
            }
            $ranges = [];
            foreach ($rm as $r) {
                $from = sprintf('%d:%02d', $r[1], $r[2]);
                $to   = sprintf('%d:%02d', $r[3], $r[4]);
                $ranges[] = $from . '–' . $to;
                $schedule[$iso][] = [sprintf('%02d:%02d', $r[1], $r[2]), sprintf('%02d:%02d', $r[3], $r[4])];
            }
            $by_day[$day_label][$label] = implode(', ', $ranges);
        }
    }

    $rows = [];
    foreach ($by_day as $day => $cells) {
        $row = ['day' => $day, 'cells' => []];
        foreach ($columns as $col) {
            $row['cells'][] = $cells[$col] ?? '';
        }
        $rows[] = $row;
    }

    return [
        'columns'  => $columns,
        'rows'     => $rows,
        'schedule' => (object) $schedule,
    ];
}

// ---------------------------------------------------------------------------
// Ambulance fields: region + accepting new patients
// ---------------------------------------------------------------------------

add_action('add_meta_boxes', 'medila_rd_ambulance_meta_box');
function medila_rd_ambulance_meta_box() {
    add_meta_box('medila_rd_ambulance', 'Mapa a seznam ordinací', 'medila_rd_ambulance_meta_box_render', 'ambulance', 'side', 'default');
}

function medila_rd_ambulance_meta_box_render($post) {
    wp_nonce_field('medila_rd_ambulance', 'medila_rd_ambulance_nonce');
    $region    = get_post_meta($post->ID, '_ambulance_region', true);
    $accepting = get_post_meta($post->ID, '_ambulance_accepting', true);
    echo '<p><label for="ambulance_region"><strong>Kraj</strong></label><br><select id="ambulance_region" name="ambulance_region" style="width:100%"><option value="">— vyberte —</option>';
    foreach (medila_rd_regions() as $slug => $name) {
        echo '<option value="' . esc_attr($slug) . '"' . selected($region, $slug, false) . '>' . esc_html($name) . '</option>';
    }
    echo '</select><span class="description">Podle kraje se ordinace řadí na stránce Pro pacienty.</span></p>';
    echo '<p><label><input type="checkbox" name="ambulance_accepting" value="1"' . checked($accepting, '1', false) . '> Přijímáme nové pacienty</label><br><span class="description">Zobrazí v detailu na mapě výzvu k registraci.</span></p>';
}

add_action('save_post_ambulance', 'medila_rd_ambulance_save', 10, 1);
function medila_rd_ambulance_save($post_id) {
    if (!isset($_POST['medila_rd_ambulance_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['medila_rd_ambulance_nonce'])), 'medila_rd_ambulance')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $region = isset($_POST['ambulance_region']) ? sanitize_key(wp_unslash($_POST['ambulance_region'])) : '';
    if ($region && isset(medila_rd_regions()[$region])) {
        update_post_meta($post_id, '_ambulance_region', $region);
    } else {
        delete_post_meta($post_id, '_ambulance_region');
    }
    update_post_meta($post_id, '_ambulance_accepting', empty($_POST['ambulance_accepting']) ? '0' : '1');
}

// ---------------------------------------------------------------------------
// [medila_ambulance_regions] — practice cards grouped by region
// ---------------------------------------------------------------------------

add_shortcode('medila_ambulance_regions', 'medila_rd_ambulance_regions_shortcode');
function medila_rd_ambulance_regions_shortcode($atts) {
    $atts = shortcode_atts(['columns' => 3], $atts);

    $posts = get_posts([
        'post_type'      => 'ambulance',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);
    if (!$posts) {
        return '<p class="mc-empty">Žádné ordinace k zobrazení.</p>';
    }

    $regions = medila_rd_regions();
    $groups  = [];
    foreach ($posts as $p) {
        $slug = get_post_meta($p->ID, '_ambulance_region', true);
        $key  = isset($regions[$slug]) ? $slug : '_other';
        $groups[$key][] = $p;
    }
    // Keep the official region order, "unassigned" last.
    $ordered = [];
    foreach (array_keys($regions) as $slug) {
        if (isset($groups[$slug])) $ordered[$slug] = $groups[$slug];
    }
    if (isset($groups['_other'])) $ordered['_other'] = $groups['_other'];

    $out = '<div class="mc-regions" style="--mc-cols:' . max(1, (int) $atts['columns']) . '">';
    foreach ($ordered as $slug => $items) {
        $name = $regions[$slug] ?? 'Další ordinace';
        $out .= '<section class="mc-region" aria-labelledby="mc-region-' . esc_attr($slug) . '">';
        $out .= '<h3 class="mc-region__title" id="mc-region-' . esc_attr($slug) . '">' . esc_html($name) . '</h3>';
        $out .= '<div class="mc-amb-grid">';
        foreach ($items as $p) {
            $out .= medila_rd_ambulance_card($p);
        }
        $out .= '</div></section>';
    }
    return $out . '</div>';
}

function medila_rd_ambulance_card($p) {
    $id      = $p->ID;
    $doctors = function_exists('medila_get_ambulance_doctors') ? medila_get_ambulance_doctors($id) : [];
    $address = get_post_meta($id, '_ambulance_address', true);
    $phone   = ltrim((string) get_post_meta($id, '_ambulance_phone', true), "'");
    $email   = get_post_meta($id, '_ambulance_email', true);
    $ins     = (string) get_post_meta($id, '_ambulance_insurance', true);
    $accept  = get_post_meta($id, '_ambulance_accepting', true) === '1';

    $h  = '<article class="mc-amb-card">';
    $h .= '<div class="mc-amb-card__body">';
    if ($accept) {
        $h .= '<span class="mc-tag mc-tag--accent">Přijímáme nové pacienty</span>';
    }
    $h .= '<h4 class="mc-amb-card__title"><a href="' . esc_url(get_permalink($p)) . '">' . esc_html(medila_rd_display_title(get_the_title($p))) . '</a></h4>';
    if ($doctors) {
        $h .= '<p class="mc-amb-card__doctors">' . esc_html(implode(', ', wp_list_pluck($doctors, 'name'))) . '</p>';
    }
    $h .= '<ul class="mc-amb-card__meta">';
    if ($address) $h .= '<li>' . medila_rd_svg('pin', 15) . '<span>' . esc_html($address) . '</span></li>';
    if ($phone)   $h .= '<li>' . medila_rd_svg('phone', 15) . '<a href="tel:' . esc_attr(preg_replace('/\s+/', '', $phone)) . '">' . esc_html($phone) . '</a></li>';
    if ($email)   $h .= '<li>' . medila_rd_svg('mail', 15) . '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a></li>';
    $h .= '</ul>';
    if ($ins) {
        $names = [];
        foreach (array_filter(array_map('trim', explode(',', $ins))) as $item) {
            $names[] = trim(explode('|', $item, 2)[0]);
        }
        $h .= '<p class="mc-amb-card__ins"><span class="screen-reader-text">Smluvní pojišťovny: </span>';
        foreach (array_filter($names) as $n) {
            $h .= '<span class="mc-chip">' . esc_html($n) . '</span>';
        }
        $h .= '</p>';
    }
    $h .= '</div>';
    $h .= '<a class="mc-amb-card__cta" href="' . esc_url(get_permalink($p)) . '" aria-label="Detail ordinace ' . esc_attr(medila_rd_display_title(get_the_title($p))) . '"><span>Detail ordinace</span>' . medila_rd_svg('arrow', 16) . '</a>';
    return $h . '</article>';
}

// ---------------------------------------------------------------------------
// Map: rich detail for ambulances + collection centres as a second type
// ---------------------------------------------------------------------------

add_filter('wppm_facilities_query_args', 'medila_rd_places_query_args', 20, 1);
function medila_rd_places_query_args($args) {
    if (!function_exists('medila_places_bridge_available') || !medila_places_bridge_available()) {
        return $args;
    }
    // The bridge (priority 10) narrowed this to the ambulance CPT; add the
    // collection centres, which live in the map plugin's own place type.
    $args['post_type'] = ['ambulance', 'wppm_place'];
    return $args;
}

add_filter('wppm_facility', 'medila_rd_places_facility', 20, 2);
function medila_rd_places_facility($row, $post) {
    if ($post->post_type === 'ambulance' && is_array($row)) {
        $hours = medila_rd_parse_hours(get_post_meta($post->ID, '_ambulance_hours', true));
        $row['title']        = medila_rd_display_title($row['title']);
        $row['phone']        = ltrim((string) $row['phone'], "'");
        $row['marker_color'] = MEDILA_RD_TEAL;
        $row['marker_glyph'] = MEDILA_RD_GLYPH_STETHOSCOPE;
        $row['accent']       = MEDILA_RD_TEAL;
        $row['accent_deep']  = MEDILA_RD_TEAL_DEEP;
        $row['cta_label']    = 'Detail ordinace';
        $row['kind']         = 'ordinace';
        $row['description']  = '';
        if ($hours['rows']) {
            $row['hours_table'] = ['columns' => $hours['columns'], 'rows' => $hours['rows']];
            $row['schedule']    = $hours['schedule'];
        }
        if (get_post_meta($post->ID, '_ambulance_accepting', true) === '1') {
            $row['promo'] = [
                'title'        => 'Přijímáme nové pacienty',
                'text'         => 'Přidejte se k nám!',
                'button_label' => 'Zaregistrovat',
                'button_url'   => home_url('/pro-pacienty/#registrace'),
            ];
        }
        return $row;
    }

    if ($post->post_type === 'wppm_place' && has_term(MEDILA_RD_ODBER_TERM, 'wppm_place_type', $post)) {
        $lat = get_post_meta($post->ID, '_wppm_lat', true);
        $lng = get_post_meta($post->ID, '_wppm_lng', true);
        if ($lat === '' || $lng === '') {
            return null;
        }
        $hours = medila_rd_parse_hours(get_post_meta($post->ID, '_wppm_hours', true));
        $out = [
            'id'           => $post->ID,
            'title'        => get_the_title($post),
            'lat'          => $lat,
            'lng'          => $lng,
            'type'         => MEDILA_RD_ODBER_TERM,
            'type_label'   => '',
            'address'      => get_post_meta($post->ID, '_wppm_address', true),
            // The stored address already ends with the town.
            'city'         => '',
            'zip'          => '',
            'phone'        => get_post_meta($post->ID, '_wppm_phone', true),
            'email'        => get_post_meta($post->ID, '_wppm_email', true),
            'website'      => get_post_meta($post->ID, '_wppm_website', true),
            'hours'        => get_post_meta($post->ID, '_wppm_hours', true),
            'description'  => wp_strip_all_tags($post->post_content),
            'image'        => '',
            'marker_color' => MEDILA_RD_RED,
            'marker_glyph' => MEDILA_RD_GLYPH_DROP,
            'accent'       => MEDILA_RD_RED,
            'accent_deep'  => MEDILA_RD_RED_DEEP,
            'cta_label'    => 'Detail odběrového centra',
            'kind'         => 'odber',
        ];
        if ($hours['rows']) {
            $out['hours_table'] = ['columns' => $hours['columns'], 'rows' => $hours['rows']];
            $out['schedule']    = $hours['schedule'];
        }
        return $out;
    }

    return $row;
}

// Any other wppm_place (none today) must not leak onto the Medila map.
add_filter('wppm_facilities', 'medila_rd_places_only_known', 20, 1);
function medila_rd_places_only_known($places) {
    return array_values(array_filter($places, static function ($p) {
        return !empty($p['kind']);
    }));
}

add_shortcode('medila_map_legend', 'medila_rd_map_legend_shortcode');
function medila_rd_map_legend_shortcode() {
    return '<ul class="mc-legend" aria-label="Legenda mapy">'
        . '<li><span class="mc-legend__dot" style="background:' . MEDILA_RD_TEAL . '">' . medila_rd_glyph_svg(MEDILA_RD_GLYPH_STETHOSCOPE, 13) . '</span>Lékař</li>'
        . '<li><span class="mc-legend__dot" style="background:' . MEDILA_RD_RED . '">' . medila_rd_glyph_svg(MEDILA_RD_GLYPH_DROP, 13) . '</span>Odběrové centrum</li>'
        . '</ul>';
}

// ---------------------------------------------------------------------------
// Career: new fixed fields + cards
// ---------------------------------------------------------------------------

function medila_rd_career_fields() {
    return [
        '_career_patient_base' => ['Velikost pacientského kmene', 'např. 1 900 pacientů'],
        '_career_work_mode'    => ['Pracovní režim', 'např. Až 35 h/týden, možnost 4denního týdne'],
        '_career_start'        => ['Nástup', 'např. Nejdříve od 1. 8. 2026'],
    ];
}

add_action('add_meta_boxes', 'medila_rd_career_meta_box');
function medila_rd_career_meta_box() {
    add_meta_box('medila_rd_career', 'Karta pozice (pevná pole)', 'medila_rd_career_meta_box_render', 'career_position', 'side', 'high');
}

function medila_rd_career_meta_box_render($post) {
    wp_nonce_field('medila_rd_career', 'medila_rd_career_nonce');
    foreach (medila_rd_career_fields() as $key => [$label, $ph]) {
        $id = ltrim($key, '_');
        echo '<p><label for="' . esc_attr($id) . '"><strong>' . esc_html($label) . '</strong></label><br>';
        echo '<input type="text" id="' . esc_attr($id) . '" name="' . esc_attr($id) . '" value="' . esc_attr(get_post_meta($post->ID, $key, true)) . '" placeholder="' . esc_attr($ph) . '" style="width:100%"></p>';
    }
    echo '<p class="description">Prázdné pole se na kartě zobrazí jako „k doplnění“.</p>';
}

add_action('save_post_career_position', 'medila_rd_career_save', 20, 1);
function medila_rd_career_save($post_id) {
    if (!isset($_POST['medila_rd_career_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['medila_rd_career_nonce'])), 'medila_rd_career')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    foreach (array_keys(medila_rd_career_fields()) as $key) {
        $field = ltrim($key, '_');
        $val   = isset($_POST[$field]) ? sanitize_text_field(wp_unslash($_POST[$field])) : '';
        $val === '' ? delete_post_meta($post_id, $key) : update_post_meta($post_id, $key, $val);
    }
}

function medila_rd_employment_label($type) {
    $labels = ['full-time' => 'Plný úvazek', 'part-time' => 'Částečný úvazek', 'contract' => 'DPP / DPČ', 'flexible' => 'Flexibilní'];
    return $labels[$type] ?? '';
}

/** "Hledáme lékaře/lékařku (L2/L3) v Humpolci (Kraj Vysočina)" → drop the trailing region. */
function medila_rd_career_title($title) {
    return trim(preg_replace('/\s*\((?:[^()]*kraj[^()]*|Kraj[^()]*)\)\s*$/u', '', html_entity_decode($title, ENT_QUOTES, 'UTF-8')));
}

add_shortcode('medila_career_cards', 'medila_rd_career_cards_shortcode');
function medila_rd_career_cards_shortcode($atts) {
    $atts = shortcode_atts(['count' => 24, 'department' => ''], $atts);

    $args = [
        'post_type'      => 'career_position',
        'post_status'    => 'publish',
        'posts_per_page' => (int) $atts['count'],
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];
    if ($atts['department']) {
        $args['tax_query'] = [['taxonomy' => 'department', 'field' => 'slug', 'terms' => explode(',', $atts['department'])]];
    }
    $posts = get_posts($args);
    if (!$posts) {
        return '<p class="mc-empty">Momentálně nemáme žádné otevřené pozice.</p>';
    }

    $out = '<div class="mc-job-grid">';
    foreach ($posts as $p) {
        $location = get_post_meta($p->ID, '_career_location', true);
        $type     = medila_rd_employment_label(get_post_meta($p->ID, '_career_employment_type', true));
        $out .= '<article class="mc-job-card">';
        $out .= '<div class="mc-job-card__tags">';
        if ($location) $out .= '<span class="mc-tag">' . medila_rd_svg('pin', 13) . esc_html($location) . '</span>';
        if ($type)     $out .= '<span class="mc-tag mc-tag--soft">' . esc_html($type) . '</span>';
        $out .= '</div>';
        $out .= '<h3 class="mc-job-card__title"><a href="' . esc_url(get_permalink($p)) . '">' . esc_html(medila_rd_career_title(get_the_title($p))) . '</a></h3>';
        $out .= '<dl class="mc-job-card__facts">';
        foreach (medila_rd_career_fields() as $key => [$label]) {
            $val = get_post_meta($p->ID, $key, true);
            $out .= '<div><dt>' . esc_html($label) . '</dt><dd' . ($val === '' ? ' class="is-missing"' : '') . '>' . esc_html($val !== '' ? $val : 'k doplnění') . '</dd></div>';
        }
        $out .= '</dl>';
        $out .= '<a class="mc-btn mc-btn--primary mc-job-card__cta" href="' . esc_url(get_permalink($p)) . '">Detail pozice</a>';
        $out .= '</article>';
    }
    return $out . '</div>';
}

// ---------------------------------------------------------------------------
// Career detail: new layout + enquiry form
// ---------------------------------------------------------------------------

add_filter('template_include', 'medila_rd_career_template', 99);
function medila_rd_career_template($template) {
    if (is_singular('career_position') && !get_post_meta(get_queried_object_id(), '_medila_rd_legacy_layout', true)) {
        return plugin_dir_path(__DIR__) . 'templates/single-career-position.php';
    }
    return $template;
}

/**
 * Split the stored job description into an intro + titled bullet sections.
 * Descriptions are free HTML: a few intro paragraphs, then "<strong>Heading</strong>"
 * followed by a <ul>. Anything unexpected ends up in the intro untouched.
 */
function medila_rd_career_sections($html) {
    $result = ['intro' => [], 'sections' => [], 'outro' => []];
    if (trim($html) === '' || !class_exists('DOMDocument')) {
        $result['intro'][] = $html;
        return $result;
    }
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div id="r">' . wpautop($html) . '</div>');
    libxml_clear_errors();
    $root = $doc->getElementById('r');

    // Flatten wrapper <section>/<div> elements the old editor left behind.
    $nodes = [];
    $walk = function ($parent) use (&$walk, &$nodes) {
        foreach (iterator_to_array($parent->childNodes) as $n) {
            if ($n instanceof DOMElement && in_array(strtolower($n->tagName), ['section', 'div'], true)) {
                $walk($n);
            } else {
                $nodes[] = $n;
            }
        }
    };
    $walk($root);

    $current = null;
    $pending_title = null;
    foreach ($nodes as $n) {
        if ($n instanceof DOMText && trim($n->textContent) === '') continue;
        $tag  = $n instanceof DOMElement ? strtolower($n->tagName) : '#text';
        $text = trim(preg_replace('/\s+/u', ' ', $n->textContent));

        if ($tag === 'ul' || $tag === 'ol') {
            $items = [];
            foreach ($n->getElementsByTagName('li') as $li) {
                $t = trim(preg_replace('/\s+/u', ' ', $li->textContent));
                if ($t !== '') $items[] = $t;
            }
            $title = $pending_title ?? ($current['title'] ?? '');
            $result['sections'][] = ['title' => $title, 'items' => $items];
            $pending_title = null;
            $current = null;
            continue;
        }
        // A short, fully bold paragraph (or a lone <strong>) is a section heading.
        $is_heading = $text !== '' && mb_strlen($text) < 60 && (
            $tag === 'strong' || $tag === 'b' ||
            ($tag === 'p' && $n->getElementsByTagName('strong')->length === 1 && trim($n->getElementsByTagName('strong')->item(0)->textContent) === $text)
        );
        if ($is_heading) {
            $pending_title = $text;
            continue;
        }
        if ($text === '') continue;
        // Headline sentence ("Aktuálně hledáme … Máte zájem o spolupráci?") stays in the intro as text.
        if ($result['sections']) {
            $result['outro'][] = $text;
        } else {
            $result['intro'][] = $text;
        }
    }
    return $result;
}

add_action('admin_post_nopriv_medila_rd_job_enquiry', 'medila_rd_job_enquiry');
add_action('admin_post_medila_rd_job_enquiry', 'medila_rd_job_enquiry');
function medila_rd_job_enquiry() {
    $post_id = isset($_POST['position']) ? absint($_POST['position']) : 0;
    $back    = $post_id ? get_permalink($post_id) : home_url('/kariera/');
    if (!$post_id || !isset($_POST['_mcnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_mcnonce'])), 'medila_rd_job_' . $post_id)) {
        wp_safe_redirect(add_query_arg('odeslano', 'chyba', $back) . '#dotaznik');
        exit;
    }
    // Honeypot: bots fill every field.
    if (!empty($_POST['website'])) {
        wp_safe_redirect(add_query_arg('odeslano', '1', $back) . '#dotaznik');
        exit;
    }
    $name    = sanitize_text_field(wp_unslash($_POST['jmeno'] ?? ''));
    $email   = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $phone   = sanitize_text_field(wp_unslash($_POST['telefon'] ?? ''));
    $message = sanitize_textarea_field(wp_unslash($_POST['zprava'] ?? ''));
    $consent = !empty($_POST['souhlas']);
    if ($name === '' || !is_email($email) || !$consent) {
        wp_safe_redirect(add_query_arg('odeslano', 'chyba', $back) . '#dotaznik');
        exit;
    }
    $to = apply_filters('medila_rd_job_enquiry_to', 'kariera@medilacare.cz', $post_id);
    $body = "Pozice: " . get_the_title($post_id) . "\n" . get_permalink($post_id) . "\n\n"
        . "Jméno: {$name}\nE-mail: {$email}\nTelefon: {$phone}\n\nZpráva:\n{$message}\n";
    wp_mail($to, 'Zájem o pozici: ' . wp_strip_all_tags(get_the_title($post_id)), $body, ['Reply-To: ' . $name . ' <' . $email . '>']);
    wp_safe_redirect(add_query_arg('odeslano', '1', $back) . '#dotaznik');
    exit;
}
