<?php
/**
 * Ambulance Detail - Shortcodes for Divi Theme Builder
 * Each shortcode renders a specific field, returning empty string if not set.
 */

if (!defined('ABSPATH')) exit;

// Generic field shortcode: [medila_amb field="doctor"]
add_shortcode('medila_amb', 'medila_amb_shortcode');
function medila_amb_shortcode($atts) {
    $atts = shortcode_atts(['field' => '', 'before' => '', 'after' => ''], $atts);
    if (!$atts['field']) return '';

    $id = get_the_ID();
    $value = get_post_meta($id, '_ambulance_' . sanitize_key($atts['field']), true);

    if (!$value) return '';

    return wp_kses_post($atts['before']) . esc_html($value) . wp_kses_post($atts['after']);
}

// Doctors shortcode: [medila_amb_doctors]
add_shortcode('medila_amb_doctors', 'medila_amb_doctors_shortcode');
function medila_amb_doctors_shortcode() {
    $id = get_the_ID();
    $doctor1 = get_post_meta($id, '_ambulance_doctor', true);
    $doctor2 = get_post_meta($id, '_ambulance_doctor2', true);

    if (!$doctor1 && !$doctor2) return '';

    $output = '<div class="mad-doctors">';
    if ($doctor1) {
        $output .= '<div class="mad-doctor-chip"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#00a278" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg><span>' . esc_html($doctor1) . '</span></div>';
    }
    if ($doctor2) {
        $output .= '<div class="mad-doctor-chip"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#00a278" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg><span>' . esc_html($doctor2) . '</span></div>';
    }
    $output .= '</div>';

    return $output;
}

// Phone (hero style) shortcode: [medila_amb_phone_hero label="Telefon:"]
add_shortcode('medila_amb_phone_hero', 'medila_amb_phone_hero_shortcode');
function medila_amb_phone_hero_shortcode($atts) {
    $atts = shortcode_atts(['label' => 'Telefon:'], $atts);
    $phone = get_post_meta(get_the_ID(), '_ambulance_phone', true);
    if (!$phone) return '';

    $tel = preg_replace('/\s+/', '', $phone);
    $output  = '<div class="mad-phone-hero">';
    if ($atts['label']) {
        $output .= '<span class="mad-phone-hero__label">' . esc_html($atts['label']) . '</span>';
    }
    $output .= '<a href="tel:' . esc_attr($tel) . '" class="mad-phone-hero__number"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#00a278" stroke-width="2.2" style="vertical-align:-3px;margin-right:8px;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>' . esc_html($phone) . '</a>';
    $output .= '</div>';
    return $output;
}

// Contact card shortcode: [medila_amb_contact]
add_shortcode('medila_amb_contact', 'medila_amb_contact_shortcode');
function medila_amb_contact_shortcode() {
    $id = get_the_ID();
    $phone   = get_post_meta($id, '_ambulance_phone', true);
    $email   = get_post_meta($id, '_ambulance_email', true);
    $address = get_post_meta($id, '_ambulance_address', true);
    $nurse   = get_post_meta($id, '_ambulance_nurse', true);

    if (!$phone && !$email && !$address && !$nurse) return '';

    $output = '<div class="mad-info-list">';

    if ($phone) {
        $output .= '<div class="mad-info-row"><span class="mad-info-label">Telefon</span><a href="tel:' . esc_attr(preg_replace('/\s+/', '', $phone)) . '" class="mad-info-value mad-link">' . esc_html($phone) . '</a></div>';
    }
    if ($email) {
        $output .= '<div class="mad-info-row"><span class="mad-info-label">E-mail</span><a href="mailto:' . esc_attr($email) . '" class="mad-info-value mad-link">' . esc_html($email) . '</a></div>';
    }
    if ($address) {
        $map_url = 'https://maps.google.com/?q=' . urlencode($address);
        $output .= '<div class="mad-info-row"><span class="mad-info-label">Adresa</span><span class="mad-info-value">' . esc_html($address) . ' <a href="' . esc_url($map_url) . '" class="mad-map-link" target="_blank" rel="noopener">zobrazit na mapě</a></span></div>';
    }
    if ($nurse) {
        $output .= '<div class="mad-info-row"><span class="mad-info-label">Sestra</span><span class="mad-info-value">' . esc_html($nurse) . '</span></div>';
    }

    $output .= '</div>';
    return $output;
}

// Hours shortcode: [medila_amb_hours]
add_shortcode('medila_amb_hours', 'medila_amb_hours_shortcode');
function medila_amb_hours_shortcode() {
    $hours = get_post_meta(get_the_ID(), '_ambulance_hours', true);
    if (!$hours) return '';

    $lines = array_filter(array_map('trim', explode("\n", $hours)));
    if (empty($lines)) return '';

    $has_structure = false;
    foreach ($lines as $line) {
        if (strpos($line, '|') !== false) { $has_structure = true; break; }
    }

    if (!$has_structure) {
        $output = '<div class="mad-hours-simple">';
        foreach ($lines as $line) {
            $output .= '<div class="mad-info-row"><span class="mad-info-value" style="width:100%;">' . esc_html($line) . '</span></div>';
        }
        return $output . '</div>';
    }

    $all_columns = [];
    $parsed_rows = [];
    foreach ($lines as $line) {
        $parts = explode(':', $line, 2);
        if (count($parts) < 2) continue;
        $day = trim($parts[0]);
        $slots = array_filter(array_map('trim', explode('|', trim($parts[1]))));
        $row = ['day' => $day, 'slots' => []];
        foreach ($slots as $slot) {
            if (preg_match('/^(.+?)\s+([\d:.\s\-–]+)$/', $slot, $m)) {
                $type = trim($m[1]);
                $time = trim($m[2]);
                if (!in_array($type, $all_columns)) $all_columns[] = $type;
                $row['slots'][$type] = $time;
            }
        }
        $parsed_rows[] = $row;
    }

    $output = '<table class="mad-hours-table"><thead><tr><th></th>';
    foreach ($all_columns as $col) $output .= '<th>' . esc_html($col) . '</th>';
    $output .= '</tr></thead><tbody>';
    foreach ($parsed_rows as $row) {
        $output .= '<tr><td>' . esc_html($row['day']) . '</td>';
        foreach ($all_columns as $col) {
            $output .= '<td>' . esc_html(isset($row['slots'][$col]) ? $row['slots'][$col] : '—') . '</td>';
        }
        $output .= '</tr>';
    }
    return $output . '</tbody></table>';
}

// Services shortcode: [medila_amb_services]
add_shortcode('medila_amb_services', 'medila_amb_services_shortcode');
function medila_amb_services_shortcode() {
    $services = get_post_meta(get_the_ID(), '_ambulance_services', true);
    if (!$services) return '';

    $items = array_filter(array_map('trim', explode("\n", $services)));
    if (empty($items)) return '';

    $output = '<ul class="mad-services">';
    foreach ($items as $item) {
        $output .= '<li>' . esc_html($item) . '</li>';
    }
    return $output . '</ul>';
}

// Insurance shortcode: [medila_amb_insurance]
// Supports format "Name|URL, Name|URL, Name" — entries with URL render as links.
add_shortcode('medila_amb_insurance', 'medila_amb_insurance_shortcode');
function medila_amb_insurance_shortcode() {
    $insurance = get_post_meta(get_the_ID(), '_ambulance_insurance', true);
    if (!$insurance) return '';

    $items = array_filter(array_map('trim', explode(',', $insurance)));
    $output = '<div class="mad-insurance-tags">';
    foreach ($items as $item) {
        $parts = array_map('trim', explode('|', $item, 2));
        $name = $parts[0];
        $url  = isset($parts[1]) ? $parts[1] : '';

        if ($url && filter_var($url, FILTER_VALIDATE_URL)) {
            $output .= '<a href="' . esc_url($url) . '" class="mad-insurance-tag mad-insurance-tag--link" target="_blank" rel="noopener">' . esc_html($name) . '</a>';
        } else {
            $output .= '<span class="mad-insurance-tag">' . esc_html($name) . '</span>';
        }
    }
    return $output . '</div>';
}

// Booking button shortcode: [medila_amb_booking text="Objednat se"]
add_shortcode('medila_amb_booking', 'medila_amb_booking_shortcode');
function medila_amb_booking_shortcode($atts) {
    $atts = shortcode_atts(['text' => 'Objednat se online', 'class' => 'mad-btn mad-btn--primary'], $atts);
    $url = get_post_meta(get_the_ID(), '_ambulance_booking_url', true);
    if (!$url) return '';
    return '<a href="' . esc_url($url) . '" class="' . esc_attr($atts['class']) . '" target="_blank" rel="noopener">' . esc_html($atts['text']) . '</a>';
}

// Pricelist button shortcode: [medila_amb_pricelist text="Ceník"]
add_shortcode('medila_amb_pricelist', 'medila_amb_pricelist_shortcode');
function medila_amb_pricelist_shortcode($atts) {
    $atts = shortcode_atts(['text' => 'Ceník'], $atts);
    $url = get_post_meta(get_the_ID(), '_ambulance_pricelist_url', true);
    if (!$url) return '';
    return '<a href="' . esc_url($url) . '" class="mad-btn mad-btn--outline" target="_blank" rel="noopener">' . esc_html($atts['text']) . '</a>';
}

// News shortcode: [medila_amb_news]
add_shortcode('medila_amb_news', 'medila_amb_news_shortcode');
function medila_amb_news_shortcode() {
    $news = get_post_meta(get_the_ID(), '_ambulance_news', true);
    if (!$news) return '';
    return '<p class="mad-text">' . nl2br(esc_html($news)) . '</p>';
}

// Registration shortcode: [medila_amb_registration]
add_shortcode('medila_amb_registration', 'medila_amb_registration_shortcode');
function medila_amb_registration_shortcode() {
    $reg = get_post_meta(get_the_ID(), '_ambulance_registration', true);
    if (!$reg) return '';
    return '<p class="mad-text">' . nl2br(esc_html($reg)) . '</p>';
}

// Enqueue frontend styles for ambulance detail
add_action('wp_enqueue_scripts', 'medila_ambulance_detail_styles');
function medila_ambulance_detail_styles() {
    if (!is_singular('ambulance')) return;

    $css = '
    .mad-doctors{display:flex;flex-wrap:wrap;gap:12px;margin:5px 0 0;}
    .mad-doctor-chip{display:inline-flex;align-items:center;gap:8px;background:#fff;padding:8px 18px;border-radius:30px;font-size:15px;font-weight:600;color:#00a278;box-shadow:0 2px 8px rgba(0,0,0,0.06);}

    .mad-phone-hero{display:inline-flex;align-items:center;flex-wrap:wrap;gap:10px;margin-top:18px;}
    .mad-phone-hero__label{font-size:14px;font-weight:600;color:#555;text-transform:uppercase;letter-spacing:.5px;}
    .mad-phone-hero__number{font-size:22px;font-weight:800;color:#1a1a2e;text-decoration:none;letter-spacing:.3px;transition:color .2s;}
    .mad-phone-hero__number:hover{color:#00a278;}
    @media(max-width:600px){
        .mad-phone-hero__number{font-size:18px;}
    }

    .mad-info-list{}
    .mad-info-row{display:flex;justify-content:space-between;align-items:baseline;padding:12px 0;border-bottom:1px solid #f3f3f3;}
    .mad-info-row:last-child{border-bottom:none;}
    .mad-info-label{font-size:13px;color:#888;font-weight:500;flex-shrink:0;min-width:80px;}
    .mad-info-value{font-size:15px;color:#333;font-weight:500;text-align:right;}
    .mad-link{color:#009ab2;text-decoration:none;transition:color .2s;}
    .mad-link:hover{color:#00a278;}
    .mad-map-link{display:inline-block;font-size:12px;color:#009ab2;text-decoration:none;margin-left:6px;padding:2px 10px;background:#e6f4f7;border-radius:12px;transition:all .2s;}
    .mad-map-link:hover{background:#009ab2;color:#fff;}

    .mad-hours-table{width:100%;border-collapse:separate;border-spacing:0;font-size:14px;}
    .mad-hours-table th{background:#f8faf9;color:#00a278;font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.5px;padding:10px 12px;text-align:center;border-bottom:2px solid #e8f4f0;}
    .mad-hours-table th:first-child{border-radius:8px 0 0 0;text-align:left;}
    .mad-hours-table th:last-child{border-radius:0 8px 0 0;}
    .mad-hours-table td{padding:10px 12px;text-align:center;border-bottom:1px solid #f3f3f3;color:#555;}
    .mad-hours-table td:first-child{font-weight:700;color:#1a1a2e;text-align:left;}
    .mad-hours-table tr:last-child td{border-bottom:none;}
    .mad-hours-table tr:hover td{background:#f8faf9;}

    .mad-services,
    .mad-services li{list-style:none !important;}
    .mad-services{padding:0 !important;margin:0 !important;}
    .mad-services li{position:relative;padding:8px 0 8px 24px;font-size:15px;color:#444;border-bottom:1px solid #f5f5f5;background:none !important;}
    .mad-services li::marker{content:"" !important;color:transparent !important;}
    .mad-services li:last-child{border-bottom:none;}
    .mad-services li::before{content:"";position:absolute;left:0;top:15px;width:8px;height:8px;border-radius:50%;background:linear-gradient(135deg,#00a278,#009ab2);}

    /* Card accent bars: solid brand colors instead of gradient */
    .mad-card__accent{background:#009ab2 !important;height:4px;}
    .mad-card__accent--green{background:#00a278 !important;}

    .mad-insurance-tags{display:flex;flex-wrap:wrap;gap:8px;}
    .mad-insurance-tag{display:inline-block;padding:8px 18px;background:#f2f7f5;color:#00a278;border-radius:30px;font-size:14px;font-weight:600;letter-spacing:.3px;text-decoration:none;transition:background .2s,color .2s,transform .2s;}
    .mad-insurance-tag--link:hover{background:#00a278;color:#fff;transform:translateY(-1px);}

    .mad-btn{display:inline-block;padding:16px 36px;border-radius:100px;font-size:14px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;text-decoration:none;transition:all .3s;cursor:pointer;}
    .mad-btn--primary{background:linear-gradient(29deg,#00a278 0%,#009ab2 100%);color:#fff;}
    .mad-btn--primary:hover{background:#270b3a;color:#fff;transform:translateY(-2px);box-shadow:0 6px 20px rgba(39,11,58,.3);}
    .mad-btn--outline{background:transparent;color:#00a278;border:2px solid #00a278;}
    .mad-btn--outline:hover{background:#00a278;color:#fff;transform:translateY(-2px);}

    .mad-text{font-size:15px;line-height:1.8;color:#555;margin:0;}

    /* Hide default theme post title and meta (autor / date) on single ambulance */
    body.single-ambulance .et_post_meta_wrapper,
    body.single-ambulance h1.entry-title,
    body.single-ambulance .entry-title,
    body.single-ambulance .post-meta,
    body.single-ambulance .et_pb_title_meta_container,
    body.single-ambulance #left-area > article > .et_post_meta_wrapper,
    body.single-ambulance .breadcrumb,
    body.single-ambulance .breadcrumbs,
    body.single-ambulance #breadcrumbs{display:none !important;}

    @media(max-width:600px){
        .mad-doctors{flex-direction:column;gap:8px;}
        .mad-doctor-chip{font-size:14px;padding:6px 14px;}
        .mad-info-row{flex-direction:column;gap:2px;}
        .mad-info-value{text-align:left;}
        .mad-hours-table{font-size:12px;}
        .mad-hours-table th,.mad-hours-table td{padding:8px 6px;}
        .mad-btn{padding:14px 28px;font-size:13px;width:100%;text-align:center;}
    }
    ';

    wp_register_style('medila-ambulance-detail', false);
    wp_enqueue_style('medila-ambulance-detail');
    wp_add_inline_style('medila-ambulance-detail', $css);
}
