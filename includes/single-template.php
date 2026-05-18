<?php
/**
 * Ambulance Detail - Shortcodes for Divi Theme Builder
 * Each shortcode renders a specific field, returning empty string if not set.
 */

if (!defined('ABSPATH')) exit;

// -----------------------------------------------------------------------------
// Multi-doctor / multi-nurse helpers (since v1.4.0)
// Read the new `_ambulance_doctors` / `_ambulance_nurses` newline-separated
// fields, falling back to the legacy single-value keys for old posts that
// haven't been edited and re-saved yet.
// -----------------------------------------------------------------------------
// Each line of _ambulance_doctors / _ambulance_nurses is parsed as
// "Name | https://photo-url.jpg". Photo URL is optional. Returns array of
// ['name' => string, 'photo' => string].
function medila_parse_personnel_line($line) {
    $parts = array_map('trim', explode('|', $line, 2));
    $name  = $parts[0];
    $photo = isset($parts[1]) ? $parts[1] : '';
    if ($photo && !filter_var($photo, FILTER_VALIDATE_URL)) $photo = '';
    return ['name' => $name, 'photo' => $photo];
}

function medila_get_ambulance_doctors($post_id) {
    $multi = get_post_meta($post_id, '_ambulance_doctors', true);
    if ($multi) {
        $result = [];
        foreach (array_filter(array_map('trim', explode("\n", $multi))) as $line) {
            $result[] = medila_parse_personnel_line($line);
        }
        return $result;
    }
    $legacy = [];
    $d1 = get_post_meta($post_id, '_ambulance_doctor', true);
    $d2 = get_post_meta($post_id, '_ambulance_doctor2', true);
    if ($d1) $legacy[] = ['name' => $d1, 'photo' => ''];
    if ($d2) $legacy[] = ['name' => $d2, 'photo' => ''];
    return $legacy;
}

function medila_get_ambulance_nurses($post_id) {
    $multi = get_post_meta($post_id, '_ambulance_nurses', true);
    if ($multi) {
        $result = [];
        foreach (array_filter(array_map('trim', explode("\n", $multi))) as $line) {
            $result[] = medila_parse_personnel_line($line);
        }
        return $result;
    }
    $legacy_nurse = get_post_meta($post_id, '_ambulance_nurse', true);
    return $legacy_nurse ? [['name' => $legacy_nurse, 'photo' => '']] : [];
}

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
    $doctors = medila_get_ambulance_doctors(get_the_ID());
    if (!$doctors) return '';

    $output = '<div class="mad-doctors">';
    foreach ($doctors as $d) {
        $avatar = $d['photo']
            ? '<img class="mad-doctor-chip__photo" src="' . esc_url($d['photo']) . '" alt="' . esc_attr($d['name']) . '">'
            : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#00a278" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
        $cls = $d['photo'] ? 'mad-doctor-chip mad-doctor-chip--has-photo' : 'mad-doctor-chip';
        $output .= '<div class="' . $cls . '">' . $avatar . '<span>' . esc_html($d['name']) . '</span></div>';
    }
    return $output . '</div>';
}

// Nurses shortcode: [medila_amb_nurses]
add_shortcode('medila_amb_nurses', 'medila_amb_nurses_shortcode');
function medila_amb_nurses_shortcode() {
    $nurses = medila_get_ambulance_nurses(get_the_ID());
    if (!$nurses) return '';

    $output = '<div class="mad-doctors mad-doctors--nurses">';
    foreach ($nurses as $n) {
        $avatar = $n['photo']
            ? '<img class="mad-doctor-chip__photo" src="' . esc_url($n['photo']) . '" alt="' . esc_attr($n['name']) . '">'
            : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#009ab2" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
        $cls = $n['photo'] ? 'mad-doctor-chip mad-doctor-chip--nurse mad-doctor-chip--has-photo' : 'mad-doctor-chip mad-doctor-chip--nurse';
        $output .= '<div class="' . $cls . '">' . $avatar . '<span>' . esc_html($n['name']) . '</span></div>';
    }
    return $output . '</div>';
}

// Phone (hero style) shortcode: [medila_amb_phone_hero label="Telefon:" book_text="Objednat"]
add_shortcode('medila_amb_phone_hero', 'medila_amb_phone_hero_shortcode');
function medila_amb_phone_hero_shortcode($atts) {
    $atts = shortcode_atts([
        'label'     => 'Telefon:',
        'book_text' => 'Objednat',
    ], $atts);
    $id = get_the_ID();
    $phone       = get_post_meta($id, '_ambulance_phone', true);
    $booking_url = get_post_meta($id, '_ambulance_booking_url', true);
    if (!$phone) return '';

    $output = '<div class="mad-phone-hero">';
    if ($atts['label']) {
        $output .= '<span class="mad-phone-hero__label">' . esc_html($atts['label']) . '</span>';
    }
    $tel = preg_replace('/\s+/', '', $phone);
    $output .= '<a href="tel:' . esc_attr($tel) . '" class="mad-phone-hero__number"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#00a278" stroke-width="2.2" style="vertical-align:-3px;margin-right:8px;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>' . esc_html($phone) . '</a>';
    if ($booking_url) {
        $output .= '<a href="' . esc_url($booking_url) . '" target="_blank" rel="noopener" class="mad-btn mad-btn--primary mad-phone-hero__book">' . esc_html($atts['book_text']) . '</a>';
    }
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
    $nurses  = medila_get_ambulance_nurses($id);

    if (!$phone && !$email && !$address && !$nurses) return '';

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
    if ($nurses) {
        $label  = count($nurses) > 1 ? 'Sestry' : 'Sestra';
        $names  = implode(', ', array_map(function($n){ return $n['name']; }, $nurses));
        $output .= '<div class="mad-info-row"><span class="mad-info-label">' . esc_html($label) . '</span><span class="mad-info-value">' . esc_html($names) . '</span></div>';
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

// News grid shortcode: [medila_amb_news_grid count="4" title="Aktuality z ordinace"]
// Renders the latest N news posts (ambulance_news CPT) linked to the current
// ambulance, with click-through to single news posts and a link to the
// filtered archive at /aktuality/?amb={id}.
add_shortcode('medila_amb_news_grid', 'medila_amb_news_grid_shortcode');
function medila_amb_news_grid_shortcode($atts) {
    $atts = shortcode_atts([
        'count'        => 4,
        'title'        => 'Aktuality z ordinace',
        'archive_text' => 'Zobrazit všechny aktuality',
    ], $atts);

    $id = get_the_ID();
    if (!$id || !function_exists('medila_get_ambulance_news')) return '';

    $news = medila_get_ambulance_news($id, (int) $atts['count']);
    if (!$news) return '';

    $archive_url = add_query_arg('amb', $id, get_post_type_archive_link('ambulance_news'));

    $out  = '<section class="madx-newsgrid">';
    $out .= '<div class="madx-newsgrid__head">';
    $out .= '<h2 class="madx-newsgrid__title">' . esc_html($atts['title']) . '</h2>';
    if ($archive_url) {
        $out .= '<a href="' . esc_url($archive_url) . '" class="madx-newsgrid__archive">' . esc_html($atts['archive_text']) . ' <span aria-hidden="true">→</span></a>';
    }
    $out .= '</div>';

    $out .= '<div class="madx-newsgrid__items">';
    foreach ($news as $n) {
        $thumb   = get_the_post_thumbnail_url($n->ID, 'medium_large') ?: '';
        $excerpt = $n->post_excerpt ?: wp_trim_words(strip_shortcodes($n->post_content), 22);
        $date    = get_the_date('j. n. Y', $n);

        $out .= '<a href="' . esc_url(get_permalink($n)) . '" class="madx-newsitem">';
        if ($thumb) {
            $out .= '<div class="madx-newsitem__media" style="background-image:url(\'' . esc_url($thumb) . '\');"></div>';
        } else {
            $out .= '<div class="madx-newsitem__media madx-newsitem__media--placeholder"><svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#00a278" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg></div>';
        }
        $out .= '<div class="madx-newsitem__body">';
        $out .= '<span class="madx-newsitem__date">' . esc_html($date) . '</span>';
        $out .= '<h3 class="madx-newsitem__title">' . esc_html($n->post_title) . '</h3>';
        if ($excerpt) {
            $out .= '<p class="madx-newsitem__excerpt">' . esc_html($excerpt) . '</p>';
        }
        $out .= '<span class="madx-newsitem__cta">Číst více <span aria-hidden="true">→</span></span>';
        $out .= '</div></a>';
    }
    $out .= '</div></section>';
    return $out;
}

// Back link on single news pages: [medila_news_back]
// Renders "← Zpět na ambulance {Name}" — only outputs if a parent ambulance is set.
add_shortcode('medila_news_back', 'medila_news_back_shortcode');
function medila_news_back_shortcode($atts) {
    $atts = shortcode_atts(['text' => 'Zpět na ambulanci'], $atts);

    $id = get_the_ID();
    if (!$id || get_post_type($id) !== 'ambulance_news') return '';
    if (!function_exists('medila_get_news_ambulance_id')) return '';

    $amb_id = medila_get_news_ambulance_id($id);
    if (!$amb_id) return '';

    $amb_link  = get_permalink($amb_id);
    $amb_title = get_the_title($amb_id);
    if (!$amb_link) return '';

    return '<a href="' . esc_url($amb_link) . '" class="madx-news-back"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="15 18 9 12 15 6"/></svg> ' . esc_html($atts['text']) . ' <strong>' . esc_html($amb_title) . '</strong></a>';
}

// ============================================================================
// Full ambulance detail layout: [medila_amb_full]
// ----------------------------------------------------------------------------
// Drop-in shortcode that renders the entire styled detail page. Use one Code
// module in your Divi Theme Builder ambulance template body.
// ============================================================================
add_shortcode('medila_amb_full', 'medila_amb_full_shortcode');
function medila_amb_full_shortcode() {
    $id = get_the_ID();
    if (!$id || get_post_type($id) !== 'ambulance') return '';

    $doctors      = medila_get_ambulance_doctors($id);
    $nurses       = medila_get_ambulance_nurses($id);
    $phone        = get_post_meta($id, '_ambulance_phone', true);
    $email        = get_post_meta($id, '_ambulance_email', true);
    $address      = get_post_meta($id, '_ambulance_address', true);
    $hours        = get_post_meta($id, '_ambulance_hours', true);
    $news         = get_post_meta($id, '_ambulance_news', true);
    $services     = get_post_meta($id, '_ambulance_services', true);
    $insurance    = get_post_meta($id, '_ambulance_insurance', true);
    $booking_url  = get_post_meta($id, '_ambulance_booking_url', true);
    $pricelist    = get_post_meta($id, '_ambulance_pricelist_url', true);
    $registration = get_post_meta($id, '_ambulance_registration', true);

    $specs        = get_the_terms($id, 'specialization');
    $spec_name    = ($specs && !is_wp_error($specs)) ? $specs[0]->name : '';
    $thumbnail    = get_the_post_thumbnail_url($id, 'large') ?: '';
    $tel          = $phone ? preg_replace('/\s+/', '', $phone) : '';
    $title        = get_the_title($id);

    ob_start();
    ?>
    <div class="madx">

        <!-- HERO -->
        <section class="madx-hero">
            <?php if ($thumbnail) : ?>
                <div class="madx-hero__bg" style="background-image:url('<?php echo esc_url($thumbnail); ?>');"></div>
            <?php endif; ?>
            <div class="madx-hero__overlay"></div>
            <div class="madx-hero__inner">
                <?php if ($spec_name) : ?>
                    <span class="madx-tag"><span class="madx-tag__dot"></span><?php echo esc_html($spec_name); ?></span>
                <?php endif; ?>
                <h1 class="madx-hero__title"><?php echo esc_html($title); ?></h1>

                <?php if ($doctors || $nurses) : ?>
                <div class="madx-doctors">
                    <?php
                    $roster = [];
                    foreach ($doctors as $d) $roster[] = ['role' => 'Lékař',  'name' => $d['name'], 'photo' => $d['photo'], 'cls' => ''];
                    foreach ($nurses  as $n) $roster[] = ['role' => 'Sestra', 'name' => $n['name'], 'photo' => $n['photo'], 'cls' => 'madx-doctor--nurse'];
                    foreach ($roster as $p) :
                        $icon_cls = $p['photo'] ? 'madx-doctor__icon madx-doctor__icon--photo' : 'madx-doctor__icon';
                    ?>
                        <div class="madx-doctor <?php echo esc_attr($p['cls']); ?>">
                            <div class="<?php echo esc_attr($icon_cls); ?>">
                                <?php if ($p['photo']) : ?>
                                    <img src="<?php echo esc_url($p['photo']); ?>" alt="<?php echo esc_attr($p['name']); ?>">
                                <?php else : ?>
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#00a278" stroke-width="2.2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                <?php endif; ?>
                            </div>
                            <div>
                                <span class="madx-doctor__role"><?php echo esc_html($p['role']); ?></span>
                                <strong class="madx-doctor__name"><?php echo esc_html($p['name']); ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ($booking_url || $phone) : ?>
                <div class="madx-hero__actions">
                    <?php if ($booking_url) : ?>
                        <a href="<?php echo esc_url($booking_url); ?>" target="_blank" rel="noopener" class="madx-btn madx-btn--primary">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            Objednat se online
                        </a>
                    <?php endif; ?>
                    <?php if ($phone) : ?>
                        <a href="tel:<?php echo esc_attr($tel); ?>" class="madx-btn madx-btn--ghost">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                            <span><?php echo esc_html($phone); ?></span>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- QUICK INFO STRIP -->
        <?php if ($phone || $email || $address) : ?>
        <section class="madx-strip">
            <?php if ($phone) : ?>
                <a href="tel:<?php echo esc_attr($tel); ?>" class="madx-strip__item">
                    <div class="madx-strip__icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    </div>
                    <div class="madx-strip__text">
                        <span class="madx-strip__label">Zavolat</span>
                        <strong><?php echo esc_html($phone); ?></strong>
                    </div>
                </a>
            <?php endif; ?>
            <?php if ($email) : ?>
                <a href="mailto:<?php echo esc_attr($email); ?>" class="madx-strip__item">
                    <div class="madx-strip__icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    </div>
                    <div class="madx-strip__text">
                        <span class="madx-strip__label">Napsat e-mail</span>
                        <strong><?php echo esc_html($email); ?></strong>
                    </div>
                </a>
            <?php endif; ?>
            <?php if ($address) : ?>
                <a href="https://maps.google.com/?q=<?php echo urlencode($address); ?>" target="_blank" rel="noopener" class="madx-strip__item">
                    <div class="madx-strip__icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                    <div class="madx-strip__text">
                        <span class="madx-strip__label">Adresa ordinace</span>
                        <strong><?php echo esc_html($address); ?></strong>
                    </div>
                </a>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <!-- PINNED NOTICE (single-line urgent banner, from _ambulance_news textarea) -->
        <?php if ($news) : ?>
        <section class="madx-news">
            <div class="madx-news__icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
            </div>
            <div class="madx-news__body">
                <span class="madx-news__label">Důležité oznámení</span>
                <p><?php echo nl2br(esc_html($news)); ?></p>
            </div>
        </section>
        <?php endif; ?>

        <!-- NEWS GRID (from ambulance_news CPT) -->
        <?php echo do_shortcode('[medila_amb_news_grid count="4"]'); ?>

        <!-- MAIN GRID -->
        <section class="madx-grid">

            <!-- Hours -->
            <?php if ($hours) : ?>
            <article class="madx-card madx-card--hours madx-card--full">
                <header class="madx-card__head">
                    <div class="madx-card__icon madx-card__icon--blue">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <h2 class="madx-card__title">Ordinační doba</h2>
                </header>
                <div class="madx-card__body">
                    <?php echo medila_amb_render_hours_premium($hours); ?>
                </div>
            </article>
            <?php endif; ?>

            <!-- Services -->
            <?php if ($services) : ?>
            <article class="madx-card madx-card--services">
                <header class="madx-card__head">
                    <div class="madx-card__icon madx-card__icon--green">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <h2 class="madx-card__title">Nabídka ordinace</h2>
                </header>
                <div class="madx-card__body">
                    <?php
                    $items = array_filter(array_map('trim', explode("\n", $services)));
                    if ($items) : ?>
                        <ul class="madx-services">
                            <?php foreach ($items as $item) : ?>
                                <li><?php echo esc_html($item); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </article>
            <?php endif; ?>

            <!-- Insurance -->
            <?php if ($insurance) : ?>
            <article class="madx-card madx-card--insurance">
                <header class="madx-card__head">
                    <div class="madx-card__icon madx-card__icon--blue">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </div>
                    <h2 class="madx-card__title">Smluvní pojišťovny</h2>
                </header>
                <div class="madx-card__body">
                    <div class="madx-insurance">
                        <?php
                        $ins_items = array_filter(array_map('trim', explode(',', $insurance)));
                        foreach ($ins_items as $item) :
                            $parts = array_map('trim', explode('|', $item, 2));
                            $name  = $parts[0];
                            $url   = isset($parts[1]) ? $parts[1] : '';
                            if ($url && filter_var($url, FILTER_VALIDATE_URL)) :
                        ?>
                            <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener" class="madx-pill madx-pill--link">
                                <span><?php echo esc_html($name); ?></span>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M7 17L17 7M7 7h10v10"/></svg>
                            </a>
                        <?php else : ?>
                            <span class="madx-pill"><?php echo esc_html($name); ?></span>
                        <?php endif;
                        endforeach; ?>
                    </div>
                </div>
            </article>
            <?php endif; ?>

        </section>

        <!-- REGISTRATION CALLOUT -->
        <?php if ($registration) : ?>
        <section class="madx-callout">
            <div class="madx-callout__icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
            </div>
            <div class="madx-callout__body">
                <span class="madx-callout__label">Registrace nových pacientů</span>
                <p><?php echo nl2br(esc_html($registration)); ?></p>
            </div>
            <?php if ($booking_url) : ?>
                <a href="<?php echo esc_url($booking_url); ?>" target="_blank" rel="noopener" class="madx-btn madx-btn--primary madx-btn--callout">Objednat se</a>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <!-- MAP -->
        <?php if ($address) : ?>
        <section class="madx-map">
            <iframe
                src="https://www.google.com/maps?q=<?php echo urlencode($address); ?>&output=embed"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                title="Mapa ordinace"></iframe>
        </section>
        <?php endif; ?>

        <!-- REVIEWS (from ambulance_review CPT) -->
        <?php echo do_shortcode('[medila_amb_reviews count="6" show_form="yes"]'); ?>

        <!-- BOTTOM CTA -->
        <?php if ($booking_url || $pricelist) : ?>
        <section class="madx-bottom-cta">
            <h2 class="madx-bottom-cta__title">Začněte s péčí, která má jméno</h2>
            <p class="madx-bottom-cta__text">Objednejte se online během pár vteřin nebo si prohlédněte ceník našich služeb.</p>
            <div class="madx-bottom-cta__buttons">
                <?php if ($booking_url) : ?>
                    <a href="<?php echo esc_url($booking_url); ?>" target="_blank" rel="noopener" class="madx-btn madx-btn--primary madx-btn--lg">Objednat se online</a>
                <?php endif; ?>
                <?php if ($pricelist) : ?>
                    <a href="<?php echo esc_url($pricelist); ?>" target="_blank" rel="noopener" class="madx-btn madx-btn--outline madx-btn--lg">Zobrazit ceník</a>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

    </div>
    <?php
    return ob_get_clean();
}

// Premium hours table with today-highlighting
function medila_amb_render_hours_premium($hours_raw) {
    $lines = array_filter(array_map('trim', explode("\n", $hours_raw)));
    if (empty($lines)) return '';

    // Map Czech day labels → ISO weekday (1=Mon … 7=Sun)
    $day_map = [
        'po' => 1, 'pondělí' => 1, 'pondeli' => 1,
        'út' => 2, 'ut' => 2, 'úterý' => 2, 'utery' => 2,
        'st' => 3, 'středa' => 3, 'streda' => 3,
        'čt' => 4, 'ct' => 4, 'čtvrtek' => 4, 'ctvrtek' => 4,
        'pá' => 5, 'pa' => 5, 'pátek' => 5, 'patek' => 5,
        'so' => 6, 'sobota' => 6,
        'ne' => 7, 'neděle' => 7, 'nedele' => 7,
    ];
    $today_iso = (int) date('N');

    $has_structure = false;
    foreach ($lines as $line) {
        if (strpos($line, '|') !== false) { $has_structure = true; break; }
    }

    if (!$has_structure) {
        $out = '<div class="madx-hours-simple">';
        foreach ($lines as $line) {
            $out .= '<div class="madx-hours-simple__row">' . esc_html($line) . '</div>';
        }
        return $out . '</div>';
    }

    $all_columns = [];
    $parsed_rows = [];
    foreach ($lines as $line) {
        $parts = explode(':', $line, 2);
        if (count($parts) < 2) continue;
        $day_label = trim($parts[0]);
        $slots = array_filter(array_map('trim', explode('|', trim($parts[1]))));
        $row = ['day' => $day_label, 'slots' => []];
        foreach ($slots as $slot) {
            if (preg_match('/^(.+?)\s+([\d:.\s\-–]+)$/', $slot, $m)) {
                $type = trim($m[1]);
                $time = trim($m[2]);
                if (!in_array($type, $all_columns, true)) $all_columns[] = $type;
                $row['slots'][$type] = $time;
            }
        }
        $key = mb_strtolower($day_label);
        $row['is_today'] = isset($day_map[$key]) && $day_map[$key] === $today_iso;
        $parsed_rows[] = $row;
    }

    if (empty($parsed_rows)) return '';

    $out  = '<div class="madx-hours-wrap"><table class="madx-hours"><thead><tr><th>Den</th>';
    foreach ($all_columns as $col) $out .= '<th>' . esc_html($col) . '</th>';
    $out .= '</tr></thead><tbody>';
    foreach ($parsed_rows as $row) {
        $cls = $row['is_today'] ? ' class="is-today"' : '';
        $out .= '<tr' . $cls . '><td><span class="madx-hours__day">' . esc_html($row['day']) . '</span>' . ($row['is_today'] ? '<span class="madx-hours__today">Dnes</span>' : '') . '</td>';
        foreach ($all_columns as $col) {
            $val = isset($row['slots'][$col]) ? $row['slots'][$col] : '—';
            $out .= '<td>' . esc_html($val) . '</td>';
        }
        $out .= '</tr>';
    }
    return $out . '</tbody></table></div>';
}

// Enqueue frontend styles for ambulance detail
add_action('wp_enqueue_scripts', 'medila_ambulance_detail_styles');
function medila_ambulance_detail_styles() {
    if (!is_singular('ambulance')) return;

    $css = '
    .mad-doctors{display:flex;flex-wrap:wrap;gap:12px;margin:5px 0 0;}
    .mad-doctor-chip{display:inline-flex;align-items:center;gap:8px;background:#fff;padding:8px 18px;border-radius:30px;font-size:15px;font-weight:600;color:#00a278;box-shadow:0 2px 8px rgba(0,0,0,0.06);}
    .mad-doctor-chip--nurse{color:#009ab2;}
    .mad-doctor-chip--has-photo{padding-left:6px;}
    .mad-doctor-chip__photo{width:28px;height:28px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid #fff;box-shadow:0 0 0 1px rgba(0,162,120,0.18);}
    .mad-doctor-chip--nurse .mad-doctor-chip__photo{box-shadow:0 0 0 1px rgba(0,154,178,0.18);}

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
    .mad-services li::before{content:"";position:absolute;left:0;top:15px;width:8px;height:8px;border-radius:50%;background:#00a278;}

    /* Card accent bars: solid brand colors instead of gradient */
    .mad-card__accent{background:#009ab2 !important;height:4px;}
    .mad-card__accent--green{background:#00a278 !important;}

    .mad-insurance-tags{display:flex;flex-wrap:wrap;gap:8px;}
    .mad-insurance-tag{display:inline-block;padding:8px 18px;background:#f2f7f5;color:#00a278;border-radius:30px;font-size:14px;font-weight:600;letter-spacing:.3px;text-decoration:none;transition:background .2s,color .2s,transform .2s;}
    .mad-insurance-tag--link:hover{background:#00a278;color:#fff;transform:translateY(-1px);}

    .mad-btn{display:inline-block;padding:16px 36px;border-radius:100px;font-size:14px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;text-decoration:none;transition:all .3s;cursor:pointer;}
    .mad-btn--primary{background:#00a278;color:#fff;}
    .mad-btn--primary:hover{background:#1a1a2e;color:#fff;transform:translateY(-2px);box-shadow:0 6px 20px rgba(26,26,46,.3);}
    .mad-btn--outline{background:transparent;color:#00a278;border:2px solid #00a278;}
    .mad-btn--outline:hover{background:#00a278;color:#fff;transform:translateY(-2px);}

    .mad-text{font-size:15px;line-height:1.8;color:#555;margin:0;}

    /* Breathing room between floating navbar and hero on ambulance single */
    body.single-ambulance #main-content{padding-top:60px;}
    @media(max-width:980px){
        body.single-ambulance #main-content{padding-top:40px;}
    }

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

    /* Mobile: reclaim side gutters on ambulance single so cards use more of the viewport
       and long contact text (address/email/phone) cannot overflow the card. */
    @media(max-width:980px){
        body.single-ambulance .et_pb_section{padding-left:0 !important;padding-right:0 !important;}
        body.single-ambulance .et_pb_row{padding-left:14px !important;padding-right:14px !important;width:100% !important;max-width:100% !important;}
        body.single-ambulance .et_pb_column{margin-bottom:14px;}
        body.single-ambulance .et_pb_blurb_content,
        body.single-ambulance .et_pb_blurb_container,
        body.single-ambulance .et_pb_blurb_description{min-width:0;word-break:break-word;overflow-wrap:anywhere;}
        body.single-ambulance .et_pb_text_inner{word-break:break-word;overflow-wrap:anywhere;}
    }
    @media(max-width:600px){
        .mad-doctors{flex-direction:column;gap:8px;}
        .mad-doctor-chip{font-size:14px;padding:6px 14px;}
        .mad-info-row{flex-direction:column;gap:2px;}
        .mad-info-value{text-align:left;}
        .mad-hours-table{font-size:12px;}
        .mad-hours-table th,.mad-hours-table td{padding:8px 6px;}
        .mad-btn{padding:14px 28px;font-size:13px;width:100%;text-align:center;}
        body.single-ambulance .et_pb_row{padding-left:10px !important;padding-right:10px !important;}
    }

    /* ============================================================
       PREMIUM FULL LAYOUT (.madx-*) used by [medila_amb_full]
       ============================================================ */
    .madx{--g:#00a278;--b:#009ab2;--ink:#1a1a2e;--mute:#6b7280;--soft:#f2f7f5;font-family:"Poppins","Raleway",-apple-system,BlinkMacSystemFont,sans-serif;color:var(--ink);max-width:1180px;margin:0 auto;padding:0 16px 60px;}
    .madx *{box-sizing:border-box;}

    /* HERO */
    .madx-hero{position:relative;border-radius:28px;overflow:hidden;min-height:440px;display:flex;align-items:center;margin-bottom:60px;isolation:isolate;}
    .madx-hero__bg{position:absolute;inset:0;background-size:cover;background-position:center;z-index:0;}
    .madx-hero__overlay{position:absolute;inset:0;background:rgba(0,162,120,0.93);z-index:1;}
    .madx-hero__inner{position:relative;z-index:2;padding:70px 60px;max-width:860px;color:#fff;width:100%;}

    .madx-tag{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,0.15);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);color:#fff;padding:8px 18px;border-radius:30px;font-size:12px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;border:1px solid rgba(255,255,255,0.25);margin-bottom:22px;}
    .madx-tag__dot{width:6px;height:6px;background:#fff;border-radius:50%;box-shadow:0 0 0 4px rgba(255,255,255,0.25);}

    .madx-hero__title{font-family:"Raleway",sans-serif;font-size:52px;font-weight:800;line-height:1.05;margin:0 0 28px;color:#fff;letter-spacing:-1px;}

    .madx-doctors{display:flex;flex-wrap:wrap;gap:14px;margin-bottom:34px;}
    .madx-doctor{display:flex;align-items:center;gap:14px;background:rgba(255,255,255,0.16);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);padding:12px 22px 12px 12px;border-radius:18px;border:1px solid rgba(255,255,255,0.22);transition:transform .25s,background .25s;}
    .madx-doctor:hover{transform:translateY(-2px);background:rgba(255,255,255,0.22);}
    .madx-doctor__icon{width:44px;height:44px;background:#fff;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;}
    .madx-doctor__icon--photo{padding:0;}
    .madx-doctor__icon--photo img{width:100%;height:100%;object-fit:cover;display:block;}
    .madx-doctor__role{display:block;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:rgba(255,255,255,0.85);margin-bottom:2px;}
    .madx-doctor__name{display:block;font-size:15px;font-weight:700;color:#fff;letter-spacing:-0.1px;}

    .madx-hero__actions{display:flex;flex-wrap:wrap;gap:12px;}

    /* BUTTONS - !important on color rules so theme link colors do not bleed through */
    .madx-btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;padding:15px 30px;border-radius:100px;font-size:14px;font-weight:700;letter-spacing:.5px;text-decoration:none !important;transition:all .3s cubic-bezier(.4,0,.2,1);cursor:pointer;border:2px solid transparent;line-height:1;}
    .madx-btn--primary{background:#fff;color:var(--g) !important;}
    .madx-btn--primary:hover{background:var(--ink);color:#fff !important;transform:translateY(-2px);box-shadow:0 14px 36px rgba(26,26,46,0.32);}
    .madx-btn--ghost{background:rgba(255,255,255,0.12);color:#fff !important;border:2px solid rgba(255,255,255,0.32);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);}
    .madx-btn--ghost:hover{background:#fff;color:var(--g) !important;border-color:#fff;transform:translateY(-2px);}
    .madx-btn--outline{background:transparent;color:var(--g) !important;border:2px solid var(--g);}
    .madx-btn--outline:hover{background:var(--g);color:#fff !important;transform:translateY(-2px);box-shadow:0 14px 36px rgba(0,162,120,0.32);}
    .madx-btn--lg{padding:18px 38px;font-size:15px;}
    .madx-btn--callout{margin-left:auto;flex-shrink:0;background:#fff;color:var(--b) !important;}
    .madx-btn--callout:hover{background:var(--ink);color:#fff !important;}

    /* QUICK STRIP */
    .madx-strip{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:18px;margin:-40px 0 60px;position:relative;z-index:5;}
    .madx-strip__item{display:flex;align-items:center;gap:16px;padding:22px 24px;background:#fff;border-radius:18px;box-shadow:0 12px 40px rgba(50,71,71,0.10);text-decoration:none !important;color:var(--ink) !important;transition:all .3s ease;border:1px solid #f0f4f3;}
    .madx-strip__item:hover{transform:translateY(-4px);box-shadow:0 22px 50px rgba(50,71,71,0.16);border-color:transparent;}
    .madx-strip__icon{width:48px;height:48px;background:var(--g);color:#fff;border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 6px 18px rgba(0,162,120,0.32);}
    .madx-strip__text{min-width:0;flex:1;}
    .madx-strip__label{display:block;font-size:10.5px;font-weight:700;color:var(--mute) !important;text-transform:uppercase;letter-spacing:1px;margin-bottom:3px;}
    .madx-strip__item strong{display:block;font-size:15px;font-weight:700;color:var(--ink) !important;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}

    /* NEWS BANNER */
    .madx-news{display:flex;gap:22px;align-items:flex-start;padding:28px 32px;background:#f2f7f5;border-left:5px solid var(--g);border-radius:18px;margin-bottom:40px;box-shadow:0 4px 24px rgba(0,162,120,0.08);}
    .madx-news__icon{width:52px;height:52px;background:var(--g);color:#fff;border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 6px 16px rgba(0,162,120,0.30);}
    .madx-news__label{display:block;font-size:11px;font-weight:700;color:var(--g);text-transform:uppercase;letter-spacing:1.2px;margin-bottom:6px;}
    .madx-news__body p{margin:0;font-size:16px;line-height:1.7;color:var(--ink);font-weight:500;}

    /* NEWS GRID */
    .madx-newsgrid{margin-bottom:50px;}
    .madx-newsgrid__head{display:flex;align-items:baseline;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;}
    .madx-newsgrid__title{font-family:"Raleway",sans-serif;font-size:26px;font-weight:800;color:var(--ink);margin:0;letter-spacing:-.3px;}
    .madx-newsgrid__archive{color:var(--g) !important;font-size:13px;font-weight:700;text-decoration:none !important;text-transform:uppercase;letter-spacing:.8px;transition:color .2s;}
    .madx-newsgrid__archive:hover{color:var(--ink) !important;}
    .madx-newsgrid__items{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:22px;}
    .madx-newsitem{display:flex;flex-direction:column;background:#fff;border-radius:18px;overflow:hidden;text-decoration:none;color:inherit;box-shadow:0 4px 24px rgba(50,71,71,0.07);border:1px solid #f0f4f3;transition:transform .35s ease,box-shadow .35s ease,border-color .35s ease;}
    .madx-newsitem:hover{transform:translateY(-5px);box-shadow:0 18px 40px rgba(50,71,71,0.13);border-color:transparent;}
    .madx-newsitem__media{height:170px;background-size:cover;background-position:center;background-color:#e8f8f4;}
    .madx-newsitem__media--placeholder{display:flex;align-items:center;justify-content:center;}
    .madx-newsitem__body{padding:22px 24px 24px;flex:1;display:flex;flex-direction:column;}
    .madx-newsitem__date{display:inline-block;font-size:11px;font-weight:700;color:var(--g);text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;}
    .madx-newsitem__title{font-family:"Raleway",sans-serif;font-size:18px;font-weight:800;color:var(--ink);margin:0 0 10px;line-height:1.3;letter-spacing:-.2px;transition:color .25s;}
    .madx-newsitem:hover .madx-newsitem__title{color:var(--g);}
    .madx-newsitem__excerpt{margin:0 0 16px;font-size:14px;line-height:1.6;color:#666;font-weight:500;flex:1;}
    .madx-newsitem__cta{color:var(--g);font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;margin-top:auto;}

    /* News single back-link */
    .madx-news-back{display:inline-flex;align-items:center;gap:6px;color:var(--g) !important;text-decoration:none !important;font-size:14px;font-weight:600;padding:10px 18px;background:#f2f7f5;border-radius:100px;transition:all .25s;}
    .madx-news-back:hover{background:var(--g);color:#fff !important;}
    .madx-news-back strong{font-weight:700;}

    /* GRID */
    .madx-grid{display:grid;grid-template-columns:1fr 1fr;gap:28px;margin-bottom:40px;}
    .madx-card{background:#fff;border-radius:22px;overflow:hidden;box-shadow:0 4px 30px rgba(50,71,71,0.06);transition:all .4s cubic-bezier(.4,0,.2,1);border:1px solid #f0f4f3;}
    .madx-card:hover{transform:translateY(-4px);box-shadow:0 22px 50px rgba(50,71,71,0.12);border-color:transparent;}
    .madx-card--full{grid-column:1 / -1;}
    .madx-card__head{display:flex;align-items:center;gap:14px;padding:28px 30px 0;}
    .madx-card__icon{width:46px;height:46px;border-radius:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
    .madx-card__icon--green{background:#e8f8f4;color:var(--g);}
    .madx-card__icon--blue{background:#e6f4f7;color:var(--b);}
    .madx-card__title{font-family:"Raleway",sans-serif;font-size:21px;font-weight:800;color:var(--ink);margin:0;letter-spacing:-.4px;}
    .madx-card__body{padding:22px 30px 30px;}

    /* HOURS */
    .madx-hours-wrap{overflow-x:auto;border-radius:14px;}
    .madx-hours{width:100%;border-collapse:separate;border-spacing:0;font-size:14px;}
    .madx-hours th{background:var(--g);color:#fff;font-size:11px;text-transform:uppercase;letter-spacing:1.2px;font-weight:700;padding:14px 14px;text-align:center;}
    .madx-hours th:first-child{text-align:left;border-radius:12px 0 0 0;}
    .madx-hours th:last-child{border-radius:0 12px 0 0;}
    .madx-hours td{padding:16px 14px;text-align:center;border-bottom:1px solid #f1f4f3;color:#555;font-weight:500;}
    .madx-hours td:first-child{text-align:left;position:relative;}
    .madx-hours__day{font-weight:800;color:var(--ink);}
    .madx-hours__today{display:inline-block;margin-left:10px;font-size:10px;background:var(--g);color:#fff;padding:3px 10px;border-radius:10px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;vertical-align:1px;}
    .madx-hours tr:last-child td{border-bottom:none;}
    .madx-hours tr.is-today td{background:rgba(0,162,120,0.08);}
    .madx-hours tr.is-today td:first-child .madx-hours__day{color:var(--g);}
    .madx-hours-simple{display:grid;gap:8px;}
    .madx-hours-simple__row{padding:12px 16px;background:#f8faf9;border-radius:10px;font-size:14px;color:#444;font-weight:500;}

    /* SERVICES */
    .madx-services{list-style:none !important;padding:0 !important;margin:0 !important;display:grid;gap:6px;}
    .madx-services li{list-style:none !important;position:relative;padding:14px 18px 14px 56px;font-size:15px;color:#333;font-weight:500;border-radius:12px;transition:all .25s;line-height:1.5;}
    .madx-services li::marker{content:"" !important;}
    .madx-services li::before{content:"";position:absolute;left:16px;top:50%;transform:translateY(-50%);width:28px;height:28px;background:var(--g);border-radius:50%;box-shadow:0 4px 10px rgba(0,162,120,0.28);}
    .madx-services li::after{content:"";position:absolute;left:24px;top:50%;transform:translateY(-66%) rotate(45deg);width:6px;height:11px;border:solid #fff;border-width:0 2.5px 2.5px 0;}
    .madx-services li:hover{background:#f5fbf9;padding-left:60px;}

    /* INSURANCE */
    .madx-insurance{display:flex;flex-wrap:wrap;gap:10px;}
    .madx-pill{display:inline-flex;align-items:center;gap:6px;padding:11px 22px;background:#f2f7f5;color:var(--g);border-radius:30px;font-size:14px;font-weight:700;letter-spacing:.3px;text-decoration:none;border:2px solid transparent;transition:all .28s ease;}
    .madx-pill--link{cursor:pointer;}
    .madx-pill--link:hover{background:var(--g);color:#fff;transform:translateY(-2px);box-shadow:0 10px 24px rgba(0,162,120,0.32);}
    .madx-pill--link svg{opacity:.6;transition:opacity .25s,transform .25s;}
    .madx-pill--link:hover svg{opacity:1;transform:translate(2px,-2px);}

    /* CALLOUT */
    .madx-callout{display:flex;align-items:center;gap:24px;padding:32px 36px;background:var(--b);border-radius:22px;color:#fff;margin-bottom:40px;overflow:hidden;position:relative;}
    .madx-callout__icon{width:64px;height:64px;background:var(--g);color:#fff;border-radius:18px;display:flex;align-items:center;justify-content:center;flex-shrink:0;z-index:1;box-shadow:0 12px 28px rgba(0,0,0,0.20);}
    .madx-callout__body{flex:1;z-index:1;min-width:0;}
    .madx-callout__label{display:block;font-size:11px;font-weight:700;color:rgba(255,255,255,0.75);text-transform:uppercase;letter-spacing:1.2px;margin-bottom:8px;}
    .madx-callout__body p{margin:0;font-size:16px;line-height:1.65;color:rgba(255,255,255,0.94);font-weight:500;}

    /* MAP */
    .madx-map{border-radius:22px;overflow:hidden;box-shadow:0 4px 30px rgba(50,71,71,0.08);margin-bottom:40px;height:400px;border:1px solid #f0f4f3;}
    .madx-map iframe{width:100%;height:100%;border:0;display:block;}

    /* ============================================================
       REVIEWS  (.madx-reviews / .madx-rform / .madx-rating-chip)
       used by [medila_amb_reviews], [medila_amb_review_form],
       [medila_amb_rating]
       ============================================================ */
    .madx-reviews{margin:0 0 40px;padding:40px 44px;background:#fff;border-radius:22px;border:1px solid #f0f4f3;box-shadow:0 4px 24px rgba(50,71,71,0.06);}
    .madx-reviews__head{display:flex;justify-content:space-between;align-items:center;gap:30px;margin-bottom:28px;flex-wrap:wrap;padding-bottom:24px;border-bottom:1px solid #f0f4f3;}
    .madx-reviews__title{font-family:"Raleway",sans-serif;font-size:26px;font-weight:800;color:var(--ink);margin:0;letter-spacing:-.3px;}
    .madx-reviews__summary{display:flex;align-items:center;gap:14px;}
    .madx-reviews__avg{font-size:34px;font-weight:800;color:var(--ink);line-height:1;display:flex;align-items:baseline;gap:2px;}
    .madx-reviews__avgmax{font-size:14px;color:var(--mute);font-weight:600;margin-left:4px;}
    .madx-reviews__big{font-size:22px;letter-spacing:2px;line-height:1;}
    .madx-reviews__count{color:var(--mute) !important;font-size:13px;font-weight:600;}

    .madx-star{display:inline-block;color:#d4d4d4;line-height:1;}
    .madx-star--full{color:#f59e0b;}
    .madx-star--empty{color:#d4d4d4;}
    .madx-star--half{position:relative;color:#d4d4d4;display:inline-block;}
    .madx-star--half > span{position:absolute;top:0;left:0;width:50%;overflow:hidden;color:#f59e0b;}

    .madx-reviews__list{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px;margin-bottom:30px;}
    .madx-review{background:#f8faf9;padding:22px 24px;border-radius:14px;border:1px solid #f0f4f3;}
    .madx-review__head{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;gap:10px;flex-wrap:wrap;}
    .madx-review__author{font-weight:700;color:var(--ink) !important;font-size:14.5px;}
    .madx-review__stars{font-size:15px;letter-spacing:1px;}
    .madx-review__content{font-size:14px;line-height:1.65;color:#444 !important;margin:0 0 10px;font-weight:500;}
    .madx-review__date{font-size:11px;color:var(--mute) !important;font-weight:600;text-transform:uppercase;letter-spacing:.6px;}
    .madx-reviews__empty{font-size:15px;color:#666;margin:0 0 28px;text-align:center;padding:32px 24px;background:#f8faf9;border-radius:14px;border:1px dashed #e0e6e3;}

    /* Compact rating chip (for hero / listing cards) */
    .madx-rating-chip{display:inline-flex;align-items:center;gap:8px;background:#fff;padding:8px 14px;border-radius:30px;box-shadow:0 2px 10px rgba(50,71,71,0.08);font-size:14px;}
    .madx-rating-chip__stars{font-size:14px;letter-spacing:1px;line-height:1;}
    .madx-rating-chip__value{font-weight:800;color:var(--ink);font-size:14px;}
    .madx-rating-chip__count{color:var(--mute);font-size:12px;font-weight:600;}

    /* Submission form */
    .madx-rform-wrap{margin-top:30px;padding-top:30px;border-top:1px solid #f0f4f3;}
    .madx-rform__title{font-family:"Raleway",sans-serif;font-size:22px;font-weight:800;color:var(--ink);margin:0 0 6px;letter-spacing:-.2px;}
    .madx-rform__sub{font-size:14px;color:var(--mute);margin:0 0 22px;}
    .madx-rform__msg{padding:14px 18px;border-radius:12px;margin-bottom:22px;font-weight:600;font-size:14px;}
    .madx-rform__msg--success{background:#e8f8f4;color:var(--g);border:1px solid #c4ecd9;}
    .madx-rform__msg--error{background:#fef2f2;color:#dc2626;border:1px solid #fecaca;}

    .madx-rform{display:block;}
    .madx-rform__row{margin-bottom:18px;}
    .madx-rform__row > label{display:block;font-weight:700;color:var(--ink);font-size:13px;margin-bottom:8px;letter-spacing:.2px;}
    .madx-rform__row input[type="text"],
    .madx-rform__row textarea{width:100%;padding:13px 16px;border:1px solid #e0e6e3;border-radius:12px;font-family:inherit;font-size:14px;color:var(--ink);background:#fff;transition:border-color .2s,box-shadow .2s;}
    .madx-rform__row input[type="text"]:focus,
    .madx-rform__row textarea:focus{outline:none;border-color:var(--g);box-shadow:0 0 0 3px rgba(0,162,120,0.12);}
    .madx-rform__row textarea{resize:vertical;min-height:96px;font-family:"Poppins","Raleway",sans-serif;}

    /* Star rating input — flex row-reverse for the classic ~ sibling trick */
    .madx-stars-input{display:inline-flex;flex-direction:row-reverse;justify-content:flex-end;}
    .madx-stars-input input{position:absolute;opacity:0;pointer-events:none;}
    .madx-stars-input label{cursor:pointer;color:#d4d4d4;margin:0 3px;font-size:32px;line-height:1;transition:color .15s,transform .15s;}
    .madx-stars-input label:hover{transform:scale(1.08);}
    .madx-stars-input input:checked ~ label{color:#f59e0b;}
    .madx-stars-input:hover label{color:#d4d4d4 !important;}
    .madx-stars-input label:hover,
    .madx-stars-input label:hover ~ label{color:#f59e0b !important;}
    .madx-stars-input input:focus-visible + label{outline:2px solid var(--g);outline-offset:3px;border-radius:4px;}

    @media(max-width:600px){
        .madx-reviews{padding:22px 18px;border-radius:16px;}
        .madx-reviews__head{flex-direction:column;align-items:flex-start;gap:18px;padding-bottom:18px;margin-bottom:20px;}
        .madx-reviews__title{font-size:22px;}
        .madx-reviews__summary{flex-wrap:wrap;}
        .madx-reviews__list{gap:14px;}
        .madx-review{padding:18px 18px;border-radius:12px;}
        .madx-rform-wrap{margin-top:24px;padding-top:24px;}
        .madx-rform__title{font-size:18px;}
        .madx-stars-input label{font-size:28px;margin:0 2px;}
    }

    /* BOTTOM CTA */
    .madx-bottom-cta{background:#f2f7f5;border-radius:26px;padding:64px 40px;text-align:center;margin-bottom:20px;position:relative;overflow:hidden;}
    .madx-bottom-cta__title{font-family:"Raleway",sans-serif;font-size:34px;font-weight:800;color:var(--ink);margin:0 0 14px;letter-spacing:-.6px;}
    .madx-bottom-cta__text{font-size:16px;color:#555;margin:0 auto 30px;line-height:1.65;max-width:520px;}
    .madx-bottom-cta__buttons{display:flex;justify-content:center;gap:14px;flex-wrap:wrap;}
    .madx-bottom-cta .madx-btn--primary{background:var(--g);color:#fff !important;}
    .madx-bottom-cta .madx-btn--primary:hover{background:var(--ink);color:#fff !important;}

    /* RESPONSIVE */
    @media(max-width:980px){
        .madx{padding:0 12px 40px;}
        .madx-hero{min-height:auto;margin-bottom:50px;}
        .madx-hero__inner{padding:50px 32px;}
        .madx-hero__title{font-size:38px;}
        .madx-grid{grid-template-columns:1fr;gap:20px;}
        .madx-strip{margin:-30px 8px 50px;}
        .madx-callout{flex-direction:column;text-align:center;gap:18px;padding:32px 24px;}
        .madx-btn--callout{margin-left:0;}
        .madx-news{padding:24px;}
        .madx-bottom-cta__title{font-size:28px;}
    }
    @media(max-width:600px){
        .madx{padding:0 6px 40px;}
        .madx-hero{border-radius:18px;}
        .madx-hero__inner{padding:30px 18px;}
        .madx-hero__title{font-size:28px;line-height:1.15;}
        .madx-tag{font-size:11px;padding:7px 14px;}
        .madx-doctor{flex:1 1 100%;padding:10px 16px 10px 10px;}
        .madx-doctor__icon{width:38px;height:38px;}
        .madx-hero__actions{flex-direction:column;}
        .madx-btn{width:100%;padding:14px 22px;}
        .madx-strip{margin:-20px 0 36px;grid-template-columns:1fr;gap:10px;}
        .madx-strip__item{padding:16px 18px;border-radius:14px;}
        .madx-strip__item strong{font-size:14px;}
        .madx-news{flex-direction:column;gap:14px;padding:18px 16px;border-radius:14px;}
        .madx-card{border-radius:16px;}
        .madx-card__head{padding:20px 18px 0;gap:12px;}
        .madx-card__title{font-size:18px;}
        .madx-card__body{padding:16px 18px 20px;}
        .madx-hours{font-size:12.5px;}
        .madx-hours th,.madx-hours td{padding:10px 8px;}
        .madx-hours__today{display:block;margin:4px 0 0;width:fit-content;}
        .madx-services li{padding:12px 12px 12px 48px;font-size:14px;}
        .madx-services li::before{left:12px;width:24px;height:24px;}
        .madx-services li::after{left:19px;width:5px;height:9px;}
        .madx-callout{padding:24px 18px;border-radius:16px;gap:14px;}
        .madx-callout__icon{width:52px;height:52px;}
        .madx-map{height:280px;border-radius:16px;}
        .madx-bottom-cta{padding:36px 18px;border-radius:18px;}
        .madx-bottom-cta__title{font-size:22px;}
        .madx-bottom-cta__text{font-size:14.5px;}
        .madx-newsgrid{margin-bottom:36px;}
        .madx-newsgrid__head{margin-bottom:18px;}
        .madx-newsgrid__title{font-size:22px;}
        .madx-newsgrid__items{gap:14px;}
        .madx-newsitem{border-radius:14px;}
        .madx-newsitem__media{height:160px;}
        .madx-newsitem__body{padding:18px 18px 20px;}
        .madx-newsitem__title{font-size:16px;}
        .madx-newsitem__excerpt{font-size:13.5px;margin-bottom:14px;}
    }
    ';

    wp_register_style('medila-ambulance-detail', false);
    wp_enqueue_style('medila-ambulance-detail');
    wp_add_inline_style('medila-ambulance-detail', $css);
}
