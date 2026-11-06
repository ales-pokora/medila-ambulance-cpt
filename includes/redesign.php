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
// Collection centres: Care Grey, so they read as secondary to the practices.
define('MEDILA_RD_GREY', '#73827F');
define('MEDILA_RD_GREY_DEEP', '#424C4A');
define('MEDILA_RD_ODBER_TERM', 'odberove-centrum');

// 24×24 stroke icons, "|" separates path strings (WP Places Map glyph format).
define('MEDILA_RD_GLYPH_STETHOSCOPE', 'M11 2v2|M5 2v2|M5 3H4a2 2 0 0 0-2 2v4a6 6 0 0 0 12 0V5a2 2 0 0 0-2-2h-1|M8 15a6 6 0 0 0 12 0v-3|M22 10a2 2 0 1 1-4 0a2 2 0 1 1 4 0');
define('MEDILA_RD_GLYPH_DROP', 'M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z');

function medila_rd_regions() {
    // Alphabetical — the order visitors scan the list in (and the mockup's).
    return [
        'praha'            => 'Hlavní město Praha',
        'jihocesky'        => 'Jihočeský kraj',
        'jihomoravsky'     => 'Jihomoravský kraj',
        'karlovarsky'      => 'Karlovarský kraj',
        'vysocina'         => 'Kraj Vysočina',
        'kralovehradecky'  => 'Královéhradecký kraj',
        'liberecky'        => 'Liberecký kraj',
        'moravskoslezsky'  => 'Moravskoslezský kraj',
        'olomoucky'        => 'Olomoucký kraj',
        'pardubicky'       => 'Pardubický kraj',
        'plzensky'         => 'Plzeňský kraj',
        'stredocesky'      => 'Středočeský kraj',
        'ustecky'          => 'Ústecký kraj',
        'zlinsky'          => 'Zlínský kraj',
    ];
}

/**
 * Czech typography: glue one-letter prepositions/conjunctions (v, s, z, k, o,
 * u, a, i) to the following word so they never end a line. Takes escaped text.
 */
function medila_rd_cz($escaped) {
    return preg_replace('/(?<![\\w&;])([vszkouaiVSZKOUAI]) (?=\\S)/u', '$1&nbsp;', $escaped);
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
// Ambulance amenities — pictogram row on the practice detail (as on medila.cz)
// ---------------------------------------------------------------------------

/** slug => [label, 24×24 stroke icon markup] */
function medila_rd_amenities() {
    return [
        'bezbarierovy'  => ['Bezbariérový přístup', '<circle cx="11" cy="4" r="2"/><path d="M11 7v6h5l3 6"/><path d="M8 10a6 6 0 1 0 7.5 8.5"/>'],
        'vytah'         => ['Výtah', '<rect x="4" y="2" width="16" height="20" rx="2"/><path d="m9 9 3-3 3 3"/><path d="m9 15 3 3 3-3"/>'],
        'parkovani'     => ['Parkování u ordinace', '<rect x="3" y="3" width="18" height="18" rx="3"/><path d="M9 17V7h4a3 3 0 0 1 0 6H9"/>'],
        'terminal'      => ['Platba kartou', '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/>'],
        'wifi'          => ['Wi-Fi zdarma', '<path d="M5 12.5a10 10 0 0 1 14 0"/><path d="M8.5 16a5 5 0 0 1 7 0"/><path d="M2 9a15 15 0 0 1 20 0"/><circle cx="12" cy="19.5" r="1"/>'],
        'automat'       => ['Nápojový automat', '<path d="M17 8h1a3 3 0 0 1 0 6h-1"/><path d="M3 8h14v6a5 5 0 0 1-5 5H8a5 5 0 0 1-5-5z"/><path d="M7 2v3M11 2v3"/>'],
        'objednani'     => ['Možnost objednání', '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/>'],
        'odbery'        => ['Odběry krve v ordinaci', '<path d="' . esc_attr(MEDILA_RD_GLYPH_DROP) . '"/>'],
    ];
}

add_action('add_meta_boxes', 'medila_rd_amenities_meta_box');
function medila_rd_amenities_meta_box() {
    add_meta_box('medila_rd_amenities', 'Vybavení ordinace (piktogramy)', 'medila_rd_amenities_meta_box_render', 'ambulance', 'side', 'default');
}

function medila_rd_amenities_meta_box_render($post) {
    wp_nonce_field('medila_rd_amenities', 'medila_rd_amenities_nonce');
    $on = (array) get_post_meta($post->ID, '_ambulance_amenities', true);
    foreach (medila_rd_amenities() as $slug => [$label]) {
        echo '<label style="display:block;margin:0 0 6px"><input type="checkbox" name="ambulance_amenities[]" value="' . esc_attr($slug) . '"' . checked(in_array($slug, $on, true), true, false) . '> ' . esc_html($label) . '</label>';
    }
    echo '<p class="description">Zaškrtnuté se zobrazí v detailu ordinace jako piktogramy.</p>';
}

add_action('save_post_ambulance', 'medila_rd_amenities_save', 10, 1);
function medila_rd_amenities_save($post_id) {
    if (!isset($_POST['medila_rd_amenities_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['medila_rd_amenities_nonce'])), 'medila_rd_amenities')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    $picked = isset($_POST['ambulance_amenities']) ? array_map('sanitize_key', (array) wp_unslash($_POST['ambulance_amenities'])) : [];
    $picked = array_values(array_intersect(array_keys(medila_rd_amenities()), $picked));
    $picked ? update_post_meta($post_id, '_ambulance_amenities', $picked) : delete_post_meta($post_id, '_ambulance_amenities');
}

/** Pictogram list for one practice, '' when none are ticked. */
function medila_rd_amenities_html($post_id) {
    $on = (array) get_post_meta($post_id, '_ambulance_amenities', true);
    $items = '';
    foreach (medila_rd_amenities() as $slug => [$label, $icon]) {
        if (!in_array($slug, $on, true)) continue;
        $items .= '<li class="madx-amenity"><span class="madx-amenity__ic"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $icon . '</svg></span><span>' . esc_html($label) . '</span></li>';
    }
    return $items ? '<ul class="madx-amenities">' . $items . '</ul>' : '';
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

    $out = '<p class="mc-empty mc-regions__empty" hidden>Vybranému filtru neodpovídá žádná ordinace. <button type="button" class="mc-link-btn" data-mc-filters-reset>Zrušit filtr</button></p>';
    $out .= '<div class="mc-regions" id="mc-regions" style="--mc-cols:' . max(1, (int) $atts['columns']) . '">';
    foreach ($ordered as $slug => $items) {
        $name = $regions[$slug] ?? 'Další ordinace';
        $out .= '<section class="mc-region' . (count($items) > 1 ? ' mc-region--wide' : '') . '" aria-labelledby="mc-region-' . esc_attr($slug) . '">';
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

    $town    = medila_rd_town(function_exists('medila_places_city_from_address') ? medila_places_city_from_address((string) $address) : '');
    $region  = (string) get_post_meta($id, '_ambulance_region', true);

    $h  = '<article class="mc-amb-card" data-region="' . esc_attr($region) . '" data-town="' . esc_attr($town) . '">';
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
        $row['type']         = 'ordinace';
        $row['cluster_group'] = 'ordinace';
        $row['town']         = medila_rd_town($row['city'] ?? '');
        $row['marker_color'] = MEDILA_RD_TEAL;
        // Logo pin in Care Teal, like the collection-point maps on medila.cz.
        $row['marker_icon']      = plugins_url('assets/img/pin-ordinace.svg', __DIR__);
        $row['marker_icon_size'] = [40, 52];
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
            'cluster_group' => MEDILA_RD_ODBER_TERM,
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
            'marker_color' => MEDILA_RD_GREY,
            'marker_glyph' => MEDILA_RD_GLYPH_DROP,
            'accent'       => MEDILA_RD_GREY,
            'accent_deep'  => MEDILA_RD_GREY_DEEP,
            'cta_label'    => 'Detail odběrového centra',
            'kind'         => 'odber',
        ];
        $parts = explode(',', (string) $out['address']);
        $out['town'] = medila_rd_town(end($parts));
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

/**
 * Town for the filter bar: "Brno-Řečkovice" / "Brno (střed)" / "Červená Voda 333" → "Brno" / "Brno" / "Červená Voda".
 */
function medila_rd_town($text) {
    $t = trim(preg_replace('/\s*\(.*\)\s*$/u', '', (string) $text));
    $t = trim(preg_replace('/\s+\d[\d\/]*\s*$/u', '', $t));
    $t = trim(preg_replace('/^\d{3}\s?\d{2}\s+/u', '', $t)); // leading PSČ
    $t = trim(preg_replace('/(?:\s+\p{Ll}+)+$/u', '', $t)); // "Trutnov poliklinika" → "Trutnov"
    return trim(explode('-', $t, 2)[0]);
}

function medila_rd_places_assets() {
    $js = plugin_dir_path(__DIR__) . 'assets/js/places.js';
    wp_enqueue_script('medila-rd-places', plugins_url('assets/js/places.js', __DIR__), [], (string) @filemtime($js), ['in_footer' => true, 'strategy' => 'defer']);
}

/**
 * Legend under the map. Each entry is also a toggle: show only that kind of
 * place, click again for all of them.
 */
add_shortcode('medila_map_legend', 'medila_rd_map_legend_shortcode');
function medila_rd_map_legend_shortcode() {
    medila_rd_places_assets();
    $pin = '<img class="mc-legend__pin" src="' . esc_url(plugins_url('assets/img/pin-ordinace.svg', __DIR__)) . '" alt="" width="20" height="26">';
    return '<div class="mc-legend" role="group" aria-label="Legenda mapy — kliknutím zobrazíte jen daný typ" data-mc-legend>'
        . '<button type="button" class="mc-legend__item" data-type="ordinace" aria-pressed="false">' . $pin . 'Lékař</button>'
        . '<button type="button" class="mc-legend__item" data-type="' . esc_attr(MEDILA_RD_ODBER_TERM) . '" aria-pressed="false"><span class="mc-legend__dot" style="background:' . MEDILA_RD_GREY . '">' . medila_rd_glyph_svg(MEDILA_RD_GLYPH_DROP, 13) . '</span>Odběrové centrum</button>'
        . '</div>';
}

/**
 * [medila_place_filters] — kraj / město (and optionally type) filters for the
 * map on the same page and for the [medila_ambulance_regions] list. Options are
 * filled from the map's loaded places, so the bar only offers what exists.
 */
add_shortcode('medila_place_filters', 'medila_rd_place_filters_shortcode');
function medila_rd_place_filters_shortcode($atts) {
    $atts = shortcode_atts(['types' => 'yes', 'list' => ''], $atts);
    medila_rd_places_assets();
    $chev = '<svg class="mc-filters__chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
    $h  = '<form class="mc-filters" data-mc-place-filters' . ($atts['list'] ? ' data-list="' . esc_attr($atts['list']) . '"' : '') . ' onsubmit="return false" aria-label="Filtr míst">';
    $h .= '<div class="mc-filters__field"><label for="mc-f-kraj">Kraj</label><span class="mc-filters__select"><select id="mc-f-kraj" name="kraj"><option value="">Všechny kraje</option>';
    foreach (medila_rd_regions() as $slug => $name) {
        $h .= '<option value="' . esc_attr($slug) . '">' . esc_html($name) . '</option>';
    }
    $h .= '</select>' . $chev . '</span></div>';
    $h .= '<div class="mc-filters__field"><label for="mc-f-mesto">Město</label><span class="mc-filters__select"><select id="mc-f-mesto" name="mesto"><option value="">Všechna města</option></select>' . $chev . '</span></div>';
    if ($atts['types'] === 'yes') {
        $h .= '<div class="mc-filters__field"><label for="mc-f-typ">Typ</label><span class="mc-filters__select"><select id="mc-f-typ" name="typ">'
            . '<option value="">Lékaři i odběrová centra</option><option value="ordinace">Jen lékaři</option><option value="' . esc_attr(MEDILA_RD_ODBER_TERM) . '">Jen odběrová centra</option>'
            . '</select>' . $chev . '</span></div>';
    }
    $h .= '<button type="button" class="mc-filters__reset" data-reset hidden>Zrušit filtr</button>';
    $h .= '<p class="mc-filters__count" aria-live="polite"></p>';
    return $h . '</form>';
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

/**
 * Position group for the Kariéra filter: the `department` term when it is one
 * of the three, otherwise guessed from the title.
 *
 * @return array{0:string,1:string} [slug, label]
 */
function medila_rd_career_group($post) {
    $groups = medila_rd_career_groups();
    $terms  = get_the_terms($post, 'department');
    if ($terms && !is_wp_error($terms)) {
        foreach ($terms as $t) {
            if (isset($groups[$t->slug])) return [$t->slug, $groups[$t->slug]];
        }
    }
    $title = mb_strtolower(get_the_title($post), 'UTF-8');
    if (preg_match('/lékař|lekar/u', $title)) return ['lekar', $groups['lekar']];
    if (preg_match('/sestr|zdravotn/u', $title)) return ['sestra', $groups['sestra']];
    return ['administrativa', $groups['administrativa']];
}

function medila_rd_career_groups() {
    return ['administrativa' => 'Administrativa', 'sestra' => 'Zdravotní sestra', 'lekar' => 'Lékař'];
}

/**
 * [medila_career_cards count="-1" initial="6" filters="yes" hide="patient_base"]
 *  - initial  cards shown before "Zobrazit další pozice" (0 = all)
 *  - filters  position type + location selects above the grid
 *  - hide     comma list of fixed fields to leave out: patient_base, work_mode, start
 */
add_shortcode('medila_career_cards', 'medila_rd_career_cards_shortcode');
function medila_rd_career_cards_shortcode($atts) {
    $atts = shortcode_atts(['count' => 24, 'department' => '', 'columns' => 3, 'initial' => 0, 'filters' => 'no', 'hide' => ''], $atts);

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

    $hide   = array_map(static function ($k) { return '_career_' . trim($k); }, array_filter(explode(',', $atts['hide'])));
    $fields = array_diff_key(medila_rd_career_fields(), array_flip($hide));
    $initial = max(0, (int) $atts['initial']);
    $with_filters = $atts['filters'] === 'yes';
    $interactive  = $with_filters || ($initial && count($posts) > $initial);

    $cards = '';
    $found_groups = [];
    $found_towns  = [];
    foreach ($posts as $i => $p) {
        $location = get_post_meta($p->ID, '_career_location', true);
        $type     = medila_rd_employment_label(get_post_meta($p->ID, '_career_employment_type', true));
        [$group, $group_label] = medila_rd_career_group($p);
        $town = medila_rd_town($location);
        $found_groups[$group] = $group_label;
        if ($town !== '') $found_towns[$town] = true;

        $cards .= '<article class="mc-job-card" data-group="' . esc_attr($group) . '" data-town="' . esc_attr($town) . '"' . ($initial && $i >= $initial ? ' hidden' : '') . '>';
        $cards .= '<div class="mc-job-card__tags">';
        if ($location) $cards .= '<span class="mc-tag">' . medila_rd_svg('pin', 13) . esc_html($location) . '</span>';
        if ($type)     $cards .= '<span class="mc-tag mc-tag--soft">' . esc_html($type) . '</span>';
        $cards .= '</div>';
        $cards .= '<h3 class="mc-job-card__title"><a href="' . esc_url(get_permalink($p)) . '">' . medila_rd_cz(esc_html(medila_rd_career_title(get_the_title($p)))) . '</a></h3>';
        // The fixed fields are the doctor-position standard (package 5). Other
        // roles (nurses, admin) only show the fields that are actually filled.
        $is_doctor = $group === 'lekar';
        $facts = '';
        foreach ($fields as $key => [$label]) {
            $val = get_post_meta($p->ID, $key, true);
            if ($val === '' && !$is_doctor) continue;
            $facts .= '<div><dt>' . esc_html($label) . '</dt><dd' . ($val === '' ? ' class="is-missing"' : '') . '>' . esc_html($val !== '' ? $val : 'k doplnění') . '</dd></div>';
        }
        $cards .= $facts ? '<dl class="mc-job-card__facts">' . $facts . '</dl>' : '<div class="mc-job-card__facts"></div>';
        $cards .= '<a class="mc-btn mc-btn--primary mc-job-card__cta" href="' . esc_url(get_permalink($p)) . '">Detail pozice</a>';
        $cards .= '</article>';
    }

    $grid = '<div class="mc-job-grid" style="--mc-job-cols:' . max(1, min(4, (int) $atts['columns'])) . '">' . $cards . '</div>';
    if (!$interactive) {
        return $grid;
    }

    wp_enqueue_script('medila-rd-careers', plugins_url('assets/js/careers.js', __DIR__), [], (string) @filemtime(plugin_dir_path(__DIR__) . 'assets/js/careers.js'), ['in_footer' => true, 'strategy' => 'defer']);

    $out = '<div class="mc-jobs" data-mc-jobs data-initial="' . $initial . '">';
    if ($with_filters) {
        $chev = '<svg class="mc-filters__chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
        $out .= '<form class="mc-filters mc-filters--jobs" onsubmit="return false" aria-label="Filtr pozic">';
        $out .= '<div class="mc-filters__field"><label for="mc-j-typ">Typ pozice</label><span class="mc-filters__select"><select id="mc-j-typ" name="typ"><option value="">Všechny pozice</option>';
        foreach (medila_rd_career_groups() as $slug => $label) {
            if (isset($found_groups[$slug])) $out .= '<option value="' . esc_attr($slug) . '">' . esc_html($label) . '</option>';
        }
        $out .= '</select>' . $chev . '</span></div>';
        $towns = array_keys($found_towns);
        usort($towns, static function ($a, $b) { return class_exists('Collator') ? (new Collator('cs_CZ'))->compare($a, $b) : strcmp($a, $b); });
        $out .= '<div class="mc-filters__field"><label for="mc-j-misto">Lokalita</label><span class="mc-filters__select"><select id="mc-j-misto" name="misto"><option value="">Všechny lokality</option>';
        foreach ($towns as $t) {
            $out .= '<option>' . esc_html($t) . '</option>';
        }
        $out .= '</select>' . $chev . '</span></div>';
        $out .= '<button type="button" class="mc-filters__reset" data-reset hidden>Zrušit filtr</button>';
        $out .= '<p class="mc-filters__count" aria-live="polite"></p>';
        $out .= '</form>';
    }
    $out .= $grid;
    $out .= '<p class="mc-empty mc-jobs__empty" hidden>Vybranému filtru teď neodpovídá žádná pozice. Napište nám i tak na <a href="mailto:kariera@medilacare.cz">kariera@medilacare.cz</a>.</p>';
    if ($initial && count($posts) > $initial) {
        $out .= '<div class="mc-center mc-jobs__more"><button type="button" class="mc-btn mc-btn--outline" data-more>Zobrazit další pozice <span data-more-count>(' . (count($posts) - $initial) . ')</span></button></div>';
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
