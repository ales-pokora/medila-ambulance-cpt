<?php
/**
 * Bridge: feed the WP Places Map plugin from the `ambulance` CPT.
 *
 * Without this, the map reads its own `wppm_place` post type and every
 * ambulance would have to be entered twice. The filters used here ship with
 * wp-places-map 1.0.22+; if that plugin is missing or older, nothing happens.
 *
 * Coordinates are geocoded from `_ambulance_address` on demand and cached in
 * post meta, so each address costs one Geocoding API call ever. Editing the
 * address clears the cached pair and it is looked up again.
 */

if (!defined('ABSPATH')) exit;

define('MEDILA_PLACES_SOURCE_CPT', 'ambulance');
define('MEDILA_PLACES_LAT_KEY', '_ambulance_lat');
define('MEDILA_PLACES_LNG_KEY', '_ambulance_lng');
define('MEDILA_PLACES_GEOCODED_KEY', '_ambulance_geocoded_address');

/**
 * The map is only wired up when wp-places-map is active and new enough to
 * expose the source filters.
 */
function medila_places_bridge_available() {
    return class_exists('WPPM_Rest')
        && defined('WPPM_VERSION')
        && version_compare(WPPM_VERSION, '1.0.22', '>=');
}

// ---------------------------------------------------------------------------
// Source
// ---------------------------------------------------------------------------

add_filter('wppm_facilities_query_args', 'medila_places_query_args', 10, 1);
function medila_places_query_args($args) {
    if (!medila_places_bridge_available()) {
        return $args;
    }

    $args['post_type'] = MEDILA_PLACES_SOURCE_CPT;
    // The ambulance CPT has no wppm_place_type terms.
    unset($args['tax_query']);

    return $args;
}

add_filter('wppm_facility', 'medila_places_build_facility', 10, 2);
function medila_places_build_facility($row, $post) {
    if (!medila_places_bridge_available() || $post->post_type !== MEDILA_PLACES_SOURCE_CPT) {
        return $row;
    }

    $coords = medila_places_coordinates($post->ID);
    if (!$coords) {
        return null; // no usable address yet — the map skips it
    }

    $address = trim((string) get_post_meta($post->ID, '_ambulance_address', true));
    $thumb   = get_the_post_thumbnail_url($post->ID, 'medium');

    return [
        'id'          => $post->ID,
        'title'       => get_the_title($post),
        'lat'         => $coords['lat'],
        'lng'         => $coords['lng'],
        'type'        => '',
        'type_label'  => '',
        'address'     => $address,
        'city'        => medila_places_city_from_address($address),
        'zip'         => medila_places_zip_from_address($address),
        'phone'       => (string) get_post_meta($post->ID, '_ambulance_phone', true),
        'email'       => (string) get_post_meta($post->ID, '_ambulance_email', true),
        // Sends the map's primary CTA to the ambulance detail page.
        'website'     => get_permalink($post),
        'hours'       => (string) get_post_meta($post->ID, '_ambulance_hours', true),
        'description' => wp_strip_all_tags(get_the_excerpt($post)),
        'image'       => $thumb ? $thumb : '',
    ];
}

// The ambulance CPT has no place types, so the pills would render empty.
add_filter('wppm_filter_terms', '__return_empty_array', 10, 0);

// Saving an ambulance must invalidate the map's places cache.
add_filter('wppm_cache_post_types', 'medila_places_cache_post_types');
function medila_places_cache_post_types($types) {
    $types[] = MEDILA_PLACES_SOURCE_CPT;
    return $types;
}

// ---------------------------------------------------------------------------
// Coordinates
// ---------------------------------------------------------------------------

/**
 * Stored coordinates for an ambulance, geocoding them once if needed.
 *
 * @return array|null { lat, lng } or null when there is no address or the
 *                    lookup failed (a failure is not cached, so it retries).
 */
function medila_places_coordinates($post_id) {
    $lat = get_post_meta($post_id, MEDILA_PLACES_LAT_KEY, true);
    $lng = get_post_meta($post_id, MEDILA_PLACES_LNG_KEY, true);

    if ($lat !== '' && $lng !== '') {
        return ['lat' => (float) $lat, 'lng' => (float) $lng];
    }

    $address = trim((string) get_post_meta($post_id, '_ambulance_address', true));
    if ($address === '' || !class_exists('WPPM_Geocoder')) {
        return null;
    }

    $result = WPPM_Geocoder::geocode($address);
    if (is_wp_error($result)) {
        return null;
    }

    update_post_meta($post_id, MEDILA_PLACES_LAT_KEY, $result['lat']);
    update_post_meta($post_id, MEDILA_PLACES_LNG_KEY, $result['lng']);
    update_post_meta($post_id, MEDILA_PLACES_GEOCODED_KEY, $address);

    return ['lat' => (float) $result['lat'], 'lng' => (float) $result['lng']];
}

/**
 * Drop cached coordinates when the address changes, so the next map request
 * geocodes the new one. Priority 20 keeps it after the meta box save at 10.
 */
add_action('save_post_ambulance', 'medila_places_invalidate_coordinates', 20);
function medila_places_invalidate_coordinates($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    $address  = trim((string) get_post_meta($post_id, '_ambulance_address', true));
    $geocoded = trim((string) get_post_meta($post_id, MEDILA_PLACES_GEOCODED_KEY, true));

    if ($address !== $geocoded) {
        delete_post_meta($post_id, MEDILA_PLACES_LAT_KEY);
        delete_post_meta($post_id, MEDILA_PLACES_LNG_KEY);
        delete_post_meta($post_id, MEDILA_PLACES_GEOCODED_KEY);
    }
}

// ---------------------------------------------------------------------------
// Address parsing
// ---------------------------------------------------------------------------
// Addresses are entered free-form, e.g. "Knížete Václava 64, 439 23 Lenešice".
// The map only uses city/zip for the info window, so a best-effort read is
// enough — an unparseable address still shows in full via the 'address' key.

function medila_places_zip_from_address($address) {
    if (preg_match('/\b(\d{3}\s?\d{2})\b/u', $address, $m)) {
        return trim($m[1]);
    }
    return '';
}

function medila_places_city_from_address($address) {
    $parts = array_filter(array_map('trim', explode(',', $address)));
    if (!$parts) {
        return '';
    }

    // The part carrying the postcode is the city: "621 00 Brno" -> "Brno".
    // Taking the last part instead would yield the district ("Řečkovice") for
    // "Uprkova 1584/6, 621 00 Brno, Řečkovice".
    foreach ($parts as $part) {
        if (preg_match('/\d{3}\s?\d{2}/u', $part)) {
            $city = trim(preg_replace('/\d{3}\s?\d{2}/u', '', $part));
            if ($city !== '') {
                return $city;
            }
        }
    }

    return trim(end($parts));
}
