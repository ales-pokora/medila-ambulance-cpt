<?php
/**
 * Medila Care - Ambulance Reviews
 * Star rating + text review system for each ambulance.
 *
 * - Public submission form: [medila_amb_review_form]
 * - Display reviews + form: [medila_amb_reviews] (auto-included in [medila_amb_full])
 * - Compact rating chip: [medila_amb_rating]
 * - Admin moderation list on each ambulance edit screen (filter low ratings,
 *   delete per row) plus a dedicated "Hodnocení" CPT menu.
 *
 * @since 1.8.0
 */

if (!defined('ABSPATH')) exit;

// =============================================================================
// CPT registration (admin-only — reviews aren't browsable on the frontend)
// =============================================================================
add_action('init', 'medila_register_ambulance_review_cpt');
function medila_register_ambulance_review_cpt() {
    register_post_type('ambulance_review', [
        'labels' => [
            'name'               => 'Hodnocení',
            'singular_name'      => 'Hodnocení',
            'menu_name'          => 'Hodnocení',
            'add_new'            => 'Přidat hodnocení',
            'add_new_item'       => 'Přidat nové hodnocení',
            'edit_item'          => 'Upravit hodnocení',
            'all_items'          => 'Všechna hodnocení',
            'search_items'       => 'Hledat hodnocení',
            'not_found'          => 'Žádná hodnocení nenalezena',
            'not_found_in_trash' => 'Žádná hodnocení v koši',
        ],
        'public'          => false,
        'show_ui'         => true,
        'show_in_menu'    => true,
        'supports'        => ['title', 'editor'],
        'menu_icon'       => 'dashicons-star-filled',
        'menu_position'   => 8,
        'capability_type' => 'post',
    ]);
}

// =============================================================================
// Edit-screen meta box (rating + ambulance + author name)
// =============================================================================
add_action('add_meta_boxes', 'medila_add_review_meta_box');
function medila_add_review_meta_box() {
    add_meta_box('review_details', 'Detaily hodnocení', 'medila_review_meta_box_callback', 'ambulance_review', 'normal', 'high');
}

function medila_review_meta_box_callback($post) {
    wp_nonce_field('medila_review_meta', 'medila_review_nonce');
    $rating = (int) get_post_meta($post->ID, '_review_rating', true);
    $amb_id = (int) get_post_meta($post->ID, '_review_ambulance', true);
    $author = get_post_meta($post->ID, '_review_author_name', true);

    $ambulances = get_posts([
        'post_type'      => 'ambulance',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'post_status'    => ['publish', 'draft', 'private'],
    ]);
    ?>
    <style>
        .medila-rfield{margin-bottom:16px;}
        .medila-rfield label{display:block;font-weight:600;font-size:13px;margin-bottom:4px;color:#1a1a2e;}
        .medila-rfield select, .medila-rfield input[type="text"]{width:100%;max-width:420px;padding:6px 10px;}
    </style>
    <div class="medila-rfield">
        <label for="review_ambulance">Ambulance *</label>
        <select name="review_ambulance" id="review_ambulance">
            <option value="">— vyberte ambulanci —</option>
            <?php foreach ($ambulances as $a) : ?>
                <option value="<?php echo esc_attr($a->ID); ?>" <?php selected($amb_id, $a->ID); ?>><?php echo esc_html($a->post_title); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="medila-rfield">
        <label for="review_rating">Hodnocení *</label>
        <select name="review_rating" id="review_rating">
            <?php for ($i = 5; $i >= 1; $i--) : ?>
                <option value="<?php echo $i; ?>" <?php selected($rating, $i); ?>><?php echo str_repeat('★', $i) . ' (' . $i . ')'; ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="medila-rfield">
        <label for="review_author_name">Jméno autora</label>
        <input type="text" name="review_author_name" id="review_author_name" value="<?php echo esc_attr($author); ?>" placeholder="Jan Novák">
    </div>
    <?php
}

add_action('save_post_ambulance_review', 'medila_save_review_meta');
function medila_save_review_meta($post_id) {
    if (!isset($_POST['medila_review_nonce']) || !wp_verify_nonce($_POST['medila_review_nonce'], 'medila_review_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['review_rating'])) {
        update_post_meta($post_id, '_review_rating', max(1, min(5, (int) $_POST['review_rating'])));
    }
    if (isset($_POST['review_ambulance'])) {
        update_post_meta($post_id, '_review_ambulance', (int) $_POST['review_ambulance']);
    }
    if (isset($_POST['review_author_name'])) {
        update_post_meta($post_id, '_review_author_name', sanitize_text_field($_POST['review_author_name']));
    }
}

// =============================================================================
// Reviews list admin columns
// =============================================================================
add_filter('manage_ambulance_review_posts_columns', 'medila_review_admin_columns');
function medila_review_admin_columns($cols) {
    return [
        'cb'          => $cols['cb'] ?? '',
        'title'       => 'Titulek',
        'rating'      => '★',
        'author_name' => 'Autor',
        'ambulance'   => 'Ambulance',
        'date'        => 'Datum',
    ];
}

add_action('manage_ambulance_review_posts_custom_column', 'medila_review_admin_column_content', 10, 2);
function medila_review_admin_column_content($column, $post_id) {
    if ($column === 'rating') {
        $r = (int) get_post_meta($post_id, '_review_rating', true);
        echo '<span style="color:#f59e0b;font-size:14px;letter-spacing:1px;">' . str_repeat('★', $r) . '<span style="color:#d4d4d4;">' . str_repeat('★', 5 - $r) . '</span></span>';
    } elseif ($column === 'author_name') {
        echo esc_html(get_post_meta($post_id, '_review_author_name', true));
    } elseif ($column === 'ambulance') {
        $amb_id = (int) get_post_meta($post_id, '_review_ambulance', true);
        if ($amb_id) {
            echo '<a href="' . esc_url(get_edit_post_link($amb_id)) . '">' . esc_html(get_the_title($amb_id)) . '</a>';
        } else {
            echo '<em style="color:#999;">(nepřiřazeno)</em>';
        }
    }
}

add_filter('manage_edit-ambulance_review_sortable_columns', 'medila_review_sortable_columns');
function medila_review_sortable_columns($cols) {
    $cols['rating'] = 'rating';
    return $cols;
}

// =============================================================================
// "Hodnocení této ambulance" meta box on each ambulance edit screen.
// Lists all reviews with delete buttons, low-rating quick filter, stats.
// =============================================================================
add_action('add_meta_boxes', 'medila_add_reviews_metabox_to_ambulance');
function medila_add_reviews_metabox_to_ambulance() {
    add_meta_box('ambulance_reviews_panel', 'Hodnocení této ambulance', 'medila_ambulance_reviews_panel_callback', 'ambulance', 'normal', 'default');
}

function medila_ambulance_reviews_panel_callback($post) {
    $reviews = get_posts([
        'post_type'      => 'ambulance_review',
        'posts_per_page' => -1,
        'post_status'    => ['publish', 'pending', 'draft'],
        'meta_query'     => [[
            'key'   => '_review_ambulance',
            'value' => $post->ID,
        ]],
        'orderby' => 'date',
        'order'   => 'DESC',
    ]);

    $add_url = admin_url('post-new.php?post_type=ambulance_review&_amb=' . $post->ID);
    ?>
    <style>
        .mr-panel-stats{display:flex;gap:24px;align-items:center;background:#f9f9f9;padding:16px 20px;border-radius:10px;margin-bottom:16px;border:1px solid #e0e0e0;}
        .mr-panel-avg{font-size:32px;font-weight:800;color:#1a1a2e;line-height:1;}
        .mr-panel-stars{color:#f59e0b;font-size:18px;letter-spacing:2px;line-height:1;}
        .mr-panel-count{color:#666;font-size:13px;font-weight:600;}
        .mr-filter{margin:0 0 12px;font-size:13px;}
        .mr-row{display:grid;grid-template-columns:90px 160px 1fr 140px;gap:14px;align-items:center;padding:10px 12px;border-bottom:1px solid #f0f0f0;background:#fff;}
        .mr-row:last-child{border-bottom:none;}
        .mr-row.is-low{background:#fffaf0;}
        .mr-row.is-vlow{background:#fef2f2;}
        .mr-rstars{color:#f59e0b;font-size:14px;letter-spacing:1px;}
        .mr-rstars .empty{color:#d4d4d4;}
        .mr-rauthor{font-weight:700;color:#1a1a2e;font-size:13px;}
        .mr-rdate{font-size:11px;color:#888;display:block;margin-top:2px;}
        .mr-rexcerpt{font-size:13px;color:#555;line-height:1.5;}
        .mr-ractions{font-size:12px;text-align:right;}
        .mr-rdelete{color:#dc2626;text-decoration:none;}
        .mr-rdelete:hover{text-decoration:underline;color:#991b1b;}
        .mr-empty{padding:30px;text-align:center;color:#666;background:#fafafa;border-radius:10px;}
    </style>

    <?php if (!$reviews) : ?>
        <div class="mr-empty">
            <p style="margin:0 0 12px;">Tato ambulance zatím nemá žádná hodnocení.</p>
            <a href="<?php echo esc_url($add_url); ?>" class="button">+ Přidat hodnocení</a>
        </div>
    <?php else :
        $total = count($reviews);
        $sum = 0;
        foreach ($reviews as $r) $sum += (int) get_post_meta($r->ID, '_review_rating', true);
        $avg = $total ? round($sum / $total, 1) : 0;
        $avg_int = (int) round($avg);
    ?>
        <div class="mr-panel-stats">
            <div>
                <div class="mr-panel-avg"><?php echo esc_html($avg); ?> <span style="font-size:16px;color:#888;font-weight:600;">/ 5</span></div>
                <div class="mr-panel-stars" style="margin-top:4px;">
                    <?php echo str_repeat('★', $avg_int); ?><span style="color:#d4d4d4;"><?php echo str_repeat('★', 5 - $avg_int); ?></span>
                </div>
            </div>
            <div class="mr-panel-count"><?php echo esc_html($total); ?> hodnocení celkem</div>
            <div style="margin-left:auto;">
                <a href="<?php echo esc_url($add_url); ?>" class="button">+ Přidat hodnocení</a>
            </div>
        </div>

        <p class="mr-filter">
            <label>
                <input type="checkbox" id="mr-show-low-only">
                Zobrazit pouze nízká hodnocení (≤ 3 ★)
            </label>
        </p>

        <div class="mr-list" style="background:#fff;border:1px solid #e8eded;border-radius:10px;overflow:hidden;">
            <?php foreach ($reviews as $r) :
                $rating  = (int) get_post_meta($r->ID, '_review_rating', true);
                $author  = get_post_meta($r->ID, '_review_author_name', true);
                $excerpt = wp_trim_words(strip_shortcodes($r->post_content), 16);
                $cls     = $rating <= 2 ? 'is-vlow' : ($rating <= 3 ? 'is-low' : '');
                $edit    = get_edit_post_link($r->ID);
                $del     = get_delete_post_link($r->ID, '', true);
            ?>
                <div class="mr-row <?php echo esc_attr($cls); ?>" data-rating="<?php echo esc_attr($rating); ?>">
                    <div class="mr-rstars" title="<?php echo esc_attr($rating); ?> ★"><?php echo str_repeat('★', $rating); ?><span class="empty"><?php echo str_repeat('★', 5 - $rating); ?></span></div>
                    <div>
                        <span class="mr-rauthor"><?php echo esc_html($author ?: get_the_title($r) ?: '—'); ?></span>
                        <span class="mr-rdate"><?php echo esc_html(get_the_date('j. n. Y', $r)); ?></span>
                    </div>
                    <div class="mr-rexcerpt"><?php echo esc_html($excerpt ?: '— (bez textu) —'); ?></div>
                    <div class="mr-ractions">
                        <?php if ($edit) : ?>
                            <a href="<?php echo esc_url($edit); ?>">Upravit</a> |
                        <?php endif; ?>
                        <a class="mr-rdelete" href="<?php echo esc_url($del); ?>" onclick="return confirm('Smazat toto hodnocení? Tuto akci nelze vrátit.');">Smazat</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <script>
        (function(){
            var toggle = document.getElementById('mr-show-low-only');
            if (!toggle) return;
            toggle.addEventListener('change', function(){
                var rows = document.querySelectorAll('.mr-row');
                rows.forEach(function(row){
                    var r = parseInt(row.dataset.rating, 10);
                    row.style.display = (toggle.checked && r > 3) ? 'none' : '';
                });
            });
        })();
        </script>
    <?php endif; ?>
    <?php
}

// Pre-fill ambulance dropdown when "Add new" is launched from a specific
// ambulance edit screen via ?_amb={id}
add_action('add_meta_boxes_ambulance_review', 'medila_review_prefill_ambulance', 11);
function medila_review_prefill_ambulance($post) {
    if (!empty($_GET['_amb']) && $post->post_status === 'auto-draft') {
        update_post_meta($post->ID, '_review_ambulance', (int) $_GET['_amb']);
    }
}

// =============================================================================
// Helpers
// =============================================================================
function medila_get_ambulance_reviews($ambulance_id, $limit = -1) {
    if (!$ambulance_id) return [];
    return get_posts([
        'post_type'      => 'ambulance_review',
        'posts_per_page' => (int) $limit,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => [[
            'key'   => '_review_ambulance',
            'value' => (int) $ambulance_id,
        ]],
    ]);
}

function medila_get_ambulance_rating_stats($ambulance_id) {
    $reviews = medila_get_ambulance_reviews($ambulance_id);
    $total = count($reviews);
    if (!$total) return ['avg' => 0, 'count' => 0, 'reviews' => []];
    $sum = 0;
    foreach ($reviews as $r) $sum += (int) get_post_meta($r->ID, '_review_rating', true);
    return [
        'avg'     => round($sum / $total, 1),
        'count'   => $total,
        'reviews' => $reviews,
    ];
}

// =============================================================================
// PUBLIC: submission form  [medila_amb_review_form]
// =============================================================================
add_shortcode('medila_amb_review_form', 'medila_amb_review_form_shortcode');
function medila_amb_review_form_shortcode() {
    $id = get_the_ID();
    if (!$id || get_post_type($id) !== 'ambulance') return '';

    $msg = ''; $msg_type = '';
    if (!empty($_GET['review_submitted'])) {
        $msg = 'Děkujeme za vaše hodnocení!';
        $msg_type = 'success';
    } elseif (!empty($_GET['review_error'])) {
        $msg = 'Hodnocení se nepodařilo odeslat. Zkontrolujte prosím povinná pole a zkuste to znovu.';
        $msg_type = 'error';
    }

    ob_start();
    ?>
    <div class="madx-rform-wrap">
        <h3 class="madx-rform__title">Ohodnoťte ordinaci</h3>
        <p class="madx-rform__sub">Pomozte ostatním pacientům — sdílejte své zkušenosti.</p>

        <?php if ($msg) : ?>
            <div class="madx-rform__msg madx-rform__msg--<?php echo esc_attr($msg_type); ?>"><?php echo esc_html($msg); ?></div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="madx-rform">
            <input type="hidden" name="action" value="medila_submit_review">
            <input type="hidden" name="ambulance_id" value="<?php echo esc_attr($id); ?>">
            <input type="hidden" name="redirect_to" value="<?php echo esc_url(get_permalink($id)); ?>">
            <?php wp_nonce_field('medila_review_submit', 'medila_review_nonce_field'); ?>
            <input type="text" name="website" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;width:1px;height:1px;" aria-hidden="true">

            <div class="madx-rform__row">
                <label>Hodnocení *</label>
                <div class="madx-stars-input">
                    <?php for ($i = 5; $i >= 1; $i--) : ?>
                        <input type="radio" name="rating" value="<?php echo $i; ?>" id="madx-rating-<?php echo $i; ?>" required>
                        <label for="madx-rating-<?php echo $i; ?>" title="<?php echo $i; ?> ★">★</label>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="madx-rform__row">
                <label for="madx-reviewer-name">Jméno *</label>
                <input type="text" name="reviewer_name" id="madx-reviewer-name" required maxlength="60" placeholder="Jan Novák">
            </div>

            <div class="madx-rform__row">
                <label for="madx-reviewer-content">Vaše zkušenost</label>
                <textarea name="reviewer_content" id="madx-reviewer-content" rows="4" maxlength="1000" placeholder="Co se vám líbilo? Co lze zlepšit?"></textarea>
            </div>

            <button type="submit" class="madx-btn madx-btn--primary">Odeslat hodnocení</button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

// Handle form submission (logged in + anonymous)
add_action('admin_post_nopriv_medila_submit_review', 'medila_handle_review_submit');
add_action('admin_post_medila_submit_review',        'medila_handle_review_submit');
function medila_handle_review_submit() {
    $redirect = isset($_POST['redirect_to']) ? esc_url_raw($_POST['redirect_to']) : home_url();

    if (!isset($_POST['medila_review_nonce_field']) || !wp_verify_nonce($_POST['medila_review_nonce_field'], 'medila_review_submit')) {
        wp_safe_redirect(add_query_arg('review_error', '1', $redirect));
        exit;
    }

    // Honeypot — silently treat as success so bots don't retry
    if (!empty($_POST['website'])) {
        wp_safe_redirect(add_query_arg('review_submitted', '1', $redirect));
        exit;
    }

    $amb_id  = (int) ($_POST['ambulance_id'] ?? 0);
    $rating  = (int) ($_POST['rating'] ?? 0);
    $name    = sanitize_text_field($_POST['reviewer_name'] ?? '');
    $content = sanitize_textarea_field($_POST['reviewer_content'] ?? '');

    if (!$amb_id || $rating < 1 || $rating > 5 || !$name) {
        wp_safe_redirect(add_query_arg('review_error', '1', $redirect));
        exit;
    }
    if (get_post_type($amb_id) !== 'ambulance') {
        wp_safe_redirect(add_query_arg('review_error', '1', $redirect));
        exit;
    }

    $title = $name . ' — ' . str_repeat('★', $rating);
    $post_status = apply_filters('medila_review_status', 'publish');

    $post_id = wp_insert_post([
        'post_type'    => 'ambulance_review',
        'post_title'   => $title,
        'post_content' => $content,
        'post_status'  => $post_status,
    ]);

    if ($post_id && !is_wp_error($post_id)) {
        update_post_meta($post_id, '_review_rating', $rating);
        update_post_meta($post_id, '_review_ambulance', $amb_id);
        update_post_meta($post_id, '_review_author_name', $name);
        wp_safe_redirect(add_query_arg('review_submitted', '1', $redirect));
        exit;
    }

    wp_safe_redirect(add_query_arg('review_error', '1', $redirect));
    exit;
}

// =============================================================================
// PUBLIC: reviews block  [medila_amb_reviews]
// Auto-included in [medila_amb_full]. Shows summary, list, and form.
// =============================================================================
add_shortcode('medila_amb_reviews', 'medila_amb_reviews_shortcode');
function medila_amb_reviews_shortcode($atts) {
    $atts = shortcode_atts([
        'count'     => 6,
        'show_form' => 'yes',
    ], $atts);

    $id = get_the_ID();
    if (!$id || get_post_type($id) !== 'ambulance') return '';

    $stats = medila_get_ambulance_rating_stats($id);

    ob_start();
    ?>
    <section class="madx-reviews">
        <header class="madx-reviews__head">
            <h2 class="madx-reviews__title">Hodnocení pacientů</h2>
            <?php if ($stats['count']) : ?>
                <div class="madx-reviews__summary">
                    <div class="madx-reviews__avg"><?php echo esc_html($stats['avg']); ?><span class="madx-reviews__avgmax">/5</span></div>
                    <div class="madx-reviews__big">
                        <?php
                        $full = floor($stats['avg']);
                        $half = ($stats['avg'] - $full) >= 0.5;
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $full)               echo '<span class="madx-star madx-star--full">★</span>';
                            elseif ($i === $full + 1 && $half) echo '<span class="madx-star madx-star--half"><span>★</span>★</span>';
                            else                            echo '<span class="madx-star madx-star--empty">★</span>';
                        }
                        ?>
                    </div>
                    <div class="madx-reviews__count"><?php echo esc_html($stats['count']); ?> <?php echo $stats['count'] === 1 ? 'hodnocení' : 'hodnocení'; ?></div>
                </div>
            <?php endif; ?>
        </header>

        <?php if ($stats['count']) : ?>
            <div class="madx-reviews__list">
                <?php
                $shown = array_slice($stats['reviews'], 0, (int) $atts['count']);
                foreach ($shown as $r) :
                    $rating  = (int) get_post_meta($r->ID, '_review_rating', true);
                    $author  = get_post_meta($r->ID, '_review_author_name', true);
                    $date    = get_the_date('j. n. Y', $r);
                    $content = $r->post_content;
                ?>
                    <article class="madx-review">
                        <header class="madx-review__head">
                            <div class="madx-review__author"><?php echo esc_html($author ?: get_the_title($r) ?: 'Pacient'); ?></div>
                            <div class="madx-review__stars" title="<?php echo esc_attr($rating); ?> ★">
                                <?php for ($i = 1; $i <= 5; $i++) : ?>
                                    <span class="madx-star <?php echo $i <= $rating ? 'madx-star--full' : 'madx-star--empty'; ?>">★</span>
                                <?php endfor; ?>
                            </div>
                        </header>
                        <?php if ($content) : ?>
                            <p class="madx-review__content"><?php echo nl2br(esc_html($content)); ?></p>
                        <?php endif; ?>
                        <div class="madx-review__date"><?php echo esc_html($date); ?></div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <p class="madx-reviews__empty">Tato ordinace zatím nemá žádná hodnocení. Buďte první, kdo se podělí o svou zkušenost.</p>
        <?php endif; ?>

        <?php if ($atts['show_form'] === 'yes') : ?>
            <?php echo medila_amb_review_form_shortcode(); ?>
        <?php endif; ?>
    </section>
    <?php
    return ob_get_clean();
}

// =============================================================================
// PUBLIC: compact rating chip  [medila_amb_rating]
// Useful in the hero or listing cards.
// =============================================================================
add_shortcode('medila_amb_rating', 'medila_amb_rating_shortcode');
function medila_amb_rating_shortcode($atts) {
    $atts = shortcode_atts(['show_count' => 'yes'], $atts);
    $id = get_the_ID();
    if (!$id || get_post_type($id) !== 'ambulance') return '';

    $stats = medila_get_ambulance_rating_stats($id);
    if (!$stats['count']) return '';

    $full = floor($stats['avg']);
    $half = ($stats['avg'] - $full) >= 0.5;
    $out  = '<span class="madx-rating-chip" title="' . esc_attr($stats['avg']) . ' / 5">';
    $out .= '<span class="madx-rating-chip__stars">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $full)                   $out .= '<span class="madx-star madx-star--full">★</span>';
        elseif ($i === $full + 1 && $half) $out .= '<span class="madx-star madx-star--half"><span>★</span>★</span>';
        else                               $out .= '<span class="madx-star madx-star--empty">★</span>';
    }
    $out .= '</span>';
    $out .= '<strong class="madx-rating-chip__value">' . esc_html($stats['avg']) . '</strong>';
    if ($atts['show_count'] === 'yes') {
        $out .= '<span class="madx-rating-chip__count">(' . esc_html($stats['count']) . ')</span>';
    }
    $out .= '</span>';
    return $out;
}
