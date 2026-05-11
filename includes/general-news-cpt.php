<?php
/**
 * Medila Care - General News (site-wide)
 * Separate from ambulance_news (which is per-ambulance). Used for company-
 * wide announcements that appear on the homepage and have their own archive.
 *
 * CPT post type: medila_news
 * URL slug:      /novinky/
 *
 * Shortcodes:
 *   [medila_gn_grid count="3" columns="3" title="Novinky"]   homepage grid
 *   [medila_gn_archive]                                       archive listing
 *   [medila_gn_single]                                        single-post body
 *
 * @since 1.9.0
 */

if (!defined('ABSPATH')) exit;

// =============================================================================
// CPT registration
// =============================================================================
add_action('init', 'medila_register_general_news_cpt');
function medila_register_general_news_cpt() {
    register_post_type('medila_news', [
        'labels' => [
            'name'               => 'Novinky',
            'singular_name'      => 'Novinka',
            'menu_name'          => 'Novinky',
            'add_new'            => 'Přidat novinku',
            'add_new_item'       => 'Přidat novou novinku',
            'edit_item'          => 'Upravit novinku',
            'new_item'           => 'Nová novinka',
            'view_item'          => 'Zobrazit novinku',
            'search_items'       => 'Hledat novinky',
            'all_items'          => 'Všechny novinky',
            'not_found'          => 'Žádné novinky nenalezeny',
            'not_found_in_trash' => 'Žádné novinky v koši',
        ],
        'public'        => true,
        'has_archive'   => 'novinky',
        'rewrite'       => ['slug' => 'novinky', 'with_front' => false],
        'menu_icon'     => 'dashicons-format-aside',
        'supports'      => ['title', 'editor', 'thumbnail', 'excerpt'],
        'show_in_rest'  => true,
        'menu_position' => 9,
    ]);
}

// Make CPT available in Divi
add_filter('et_builder_post_types', 'medila_add_general_news_to_divi');
function medila_add_general_news_to_divi($post_types) {
    $post_types[] = 'medila_news';
    return $post_types;
}
add_filter('et_builder_get_builder_post_types', 'medila_add_general_news_to_divi_builder');
function medila_add_general_news_to_divi_builder($post_types) {
    $post_types[] = 'medila_news';
    return $post_types;
}

// Helper
function medila_get_general_news($limit = 3) {
    return get_posts([
        'post_type'      => 'medila_news',
        'posts_per_page' => (int) $limit,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);
}

// Flush rewrites on activation
register_activation_hook(MEDILA_PLUGIN_FILE, 'medila_general_news_activate');
function medila_general_news_activate() {
    medila_register_general_news_cpt();
    flush_rewrite_rules();
}

// =============================================================================
// [medila_gn_grid] — homepage 3-card grid in career-card style
// =============================================================================
add_shortcode('medila_gn_grid', 'medila_gn_grid_shortcode');
function medila_gn_grid_shortcode($atts) {
    $atts = shortcode_atts([
        'count'             => 3,
        'columns'           => 3,
        'title'             => 'Novinky',
        'show_archive_link' => 'yes',
        'archive_text'      => 'Všechny novinky',
    ], $atts);

    $news = medila_get_general_news((int) $atts['count']);
    if (!$news) return '';

    $archive_url = get_post_type_archive_link('medila_news');
    $cols        = max(1, min(4, (int) $atts['columns']));

    ob_start();
    ?>
    <div class="medila-gn-section">
        <?php if ($atts['title'] || ($atts['show_archive_link'] === 'yes' && $archive_url)) : ?>
            <div class="medila-gn-section__head">
                <?php if ($atts['title']) : ?>
                    <h2 class="medila-gn-section__title"><?php echo esc_html($atts['title']); ?></h2>
                <?php endif; ?>
                <?php if ($atts['show_archive_link'] === 'yes' && $archive_url) : ?>
                    <a href="<?php echo esc_url($archive_url); ?>" class="medila-gn-section__link"><?php echo esc_html($atts['archive_text']); ?> <span aria-hidden="true">→</span></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="medila-gn-grid" style="grid-template-columns:repeat(<?php echo $cols; ?>,1fr);">
            <?php foreach ($news as $n) :
                $thumb   = get_the_post_thumbnail_url($n->ID, 'medium_large') ?: '';
                $excerpt = $n->post_excerpt ?: wp_trim_words(strip_shortcodes($n->post_content), 20);
                $date    = get_the_date('j. n. Y', $n);
            ?>
                <a href="<?php echo esc_url(get_permalink($n)); ?>" class="medila-gn-card">
                    <div class="medila-gn-card__accent"></div>
                    <div class="medila-gn-card__body">
                        <div class="medila-gn-card__header">
                            <?php if ($thumb) : ?>
                                <div class="medila-gn-card__icon medila-gn-card__icon--photo"><img src="<?php echo esc_url($thumb); ?>" alt=""></div>
                            <?php else : ?>
                                <div class="medila-gn-card__icon medila-gn-card__icon--default">
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#00a278" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                                </div>
                            <?php endif; ?>
                            <span class="medila-gn-card__date"><?php echo esc_html($date); ?></span>
                        </div>
                        <h3 class="medila-gn-card__title"><?php echo esc_html(get_the_title($n)); ?></h3>
                        <?php if ($excerpt) : ?>
                            <p class="medila-gn-card__excerpt"><?php echo esc_html($excerpt); ?></p>
                        <?php endif; ?>
                        <div class="medila-gn-card__cta">
                            <span>Číst více</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <style>
    .medila-gn-section{margin:50px 0;}
    .medila-gn-section__head{display:flex;justify-content:space-between;align-items:baseline;margin-bottom:26px;flex-wrap:wrap;gap:12px;}
    .medila-gn-section__title{font-family:"Raleway",sans-serif;font-size:32px;font-weight:800;color:#1a1a2e;margin:0;letter-spacing:-.4px;}
    .medila-gn-section__link{color:#00a278 !important;text-decoration:none !important;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;transition:color .2s;}
    .medila-gn-section__link:hover{color:#1a1a2e !important;}
    .medila-gn-grid{display:grid;gap:24px;}
    .medila-gn-card{display:flex;flex-direction:column;background:#fff;border-radius:14px;overflow:hidden;text-decoration:none !important;color:inherit !important;box-shadow:0 2px 20px rgba(50,71,71,0.07);transition:transform .3s ease,box-shadow .3s ease;position:relative;}
    .medila-gn-card:hover{transform:translateY(-6px);box-shadow:0 12px 40px rgba(50,71,71,0.14);}
    .medila-gn-card__accent{height:5px;background:#00a278;}
    .medila-gn-card__body{padding:28px 28px 24px;display:flex;flex-direction:column;flex:1;}
    .medila-gn-card__header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;gap:12px;}
    .medila-gn-card__icon{width:52px;height:52px;border-radius:12px;overflow:hidden;flex-shrink:0;display:flex;align-items:center;justify-content:center;}
    .medila-gn-card__icon--photo img{width:100%;height:100%;object-fit:cover;}
    .medila-gn-card__icon--default{background:#e8f8f4;}
    .medila-gn-card__date{font-size:11px;font-weight:700;color:#00a278 !important;text-transform:uppercase;letter-spacing:1px;}
    .medila-gn-card__title{font-family:"Raleway",sans-serif;font-size:20px;font-weight:700;color:#1a1a2e !important;margin:0 0 12px;line-height:1.35;letter-spacing:-.2px;}
    .medila-gn-card:hover .medila-gn-card__title{color:#00a278 !important;}
    .medila-gn-card__excerpt{font-size:14px;line-height:1.65;color:#666 !important;margin:0 0 16px;flex:1;font-weight:500;}
    .medila-gn-card__cta{display:flex;align-items:center;gap:6px;margin-top:auto;padding-top:18px;border-top:1px solid #f0f0f0;font-size:13px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#00a278 !important;transition:gap .3s ease;}
    .medila-gn-card__cta svg{stroke:#00a278;transition:transform .3s ease;}
    .medila-gn-card:hover .medila-gn-card__cta{gap:10px;}
    .medila-gn-card:hover .medila-gn-card__cta svg{transform:translateX(4px);}
    @media(max-width:980px){
        .medila-gn-grid{grid-template-columns:repeat(2,1fr) !important;}
        .medila-gn-card__body{padding:22px 22px 20px;}
        .medila-gn-section__title{font-size:26px;}
    }
    @media(max-width:600px){
        .medila-gn-section{margin:36px 0;}
        .medila-gn-grid{grid-template-columns:1fr !important;gap:14px;}
        .medila-gn-card__body{padding:20px 18px 18px;}
        .medila-gn-card__title{font-size:18px;}
        .medila-gn-section__title{font-size:22px;}
        .medila-gn-section__head{margin-bottom:18px;}
    }
    </style>
    <?php
    return ob_get_clean();
}

// =============================================================================
// [medila_gn_archive] — archive listing (same .madx-* design as ambulance news)
// =============================================================================
add_shortcode('medila_gn_archive', 'medila_gn_archive_shortcode');
function medila_gn_archive_shortcode($atts) {
    $atts = shortcode_atts([
        'per_page' => 12,
        'title'    => 'Novinky z Medila Care',
        'subtitle' => 'Sledujte naše novinky, akce a změny v ordinacích.',
    ], $atts);

    $paged = max(1, (int) (get_query_var('paged') ?: get_query_var('page') ?: 1));

    $query = new WP_Query([
        'post_type'      => 'medila_news',
        'posts_per_page' => (int) $atts['per_page'],
        'paged'          => $paged,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

    ob_start();
    ?>
    <div class="madx madx-na">
        <section class="madx-na-hero">
            <div class="madx-na-hero__inner">
                <span class="madx-tag"><span class="madx-tag__dot"></span>Novinky</span>
                <h1 class="madx-na-hero__title"><?php echo esc_html($atts['title']); ?></h1>
                <p class="madx-na-hero__sub"><?php echo esc_html($atts['subtitle']); ?></p>
            </div>
        </section>

        <?php if ($query->have_posts()) : ?>
            <section class="madx-newsgrid madx-newsgrid--archive">
                <div class="madx-newsgrid__items">
                    <?php while ($query->have_posts()) : $query->the_post();
                        $thumb   = get_the_post_thumbnail_url(get_the_ID(), 'medium_large') ?: '';
                        $excerpt = get_the_excerpt() ?: wp_trim_words(strip_shortcodes(get_the_content()), 22);
                        $date    = get_the_date('j. n. Y');
                    ?>
                        <a href="<?php the_permalink(); ?>" class="madx-newsitem">
                            <?php if ($thumb) : ?>
                                <div class="madx-newsitem__media" style="background-image:url('<?php echo esc_url($thumb); ?>');"></div>
                            <?php else : ?>
                                <div class="madx-newsitem__media madx-newsitem__media--placeholder">
                                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#00a278" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                                </div>
                            <?php endif; ?>
                            <div class="madx-newsitem__body">
                                <span class="madx-newsitem__date"><?php echo esc_html($date); ?></span>
                                <h3 class="madx-newsitem__title"><?php the_title(); ?></h3>
                                <?php if ($excerpt) : ?>
                                    <p class="madx-newsitem__excerpt"><?php echo esc_html($excerpt); ?></p>
                                <?php endif; ?>
                                <span class="madx-newsitem__cta">Číst více <span aria-hidden="true">→</span></span>
                            </div>
                        </a>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>

                <?php
                $big = 999999999;
                $pagination = paginate_links([
                    'base'      => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                    'format'    => '?paged=%#%',
                    'current'   => $paged,
                    'total'     => $query->max_num_pages,
                    'prev_text' => '←',
                    'next_text' => '→',
                    'type'      => 'array',
                ]);
                if ($pagination) :
                ?>
                    <nav class="madx-pagination" aria-label="Stránkování novinek">
                        <?php foreach ($pagination as $link) : ?>
                            <span class="madx-pagination__item"><?php echo $link; ?></span>
                        <?php endforeach; ?>
                    </nav>
                <?php endif; ?>
            </section>
        <?php else : ?>
            <section class="madx-na-empty">
                <div class="madx-na-empty__icon">
                    <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="1.5"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                </div>
                <h2>Žádné novinky</h2>
                <p>Zatím zde nejsou žádné novinky. Zkuste se prosím vrátit později.</p>
                <a href="<?php echo esc_url(home_url()); ?>" class="madx-btn madx-btn--outline">Zpět na úvodní stránku</a>
            </section>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// =============================================================================
// [medila_gn_single] — single news body (same .madx-* design as ambulance news)
// =============================================================================
add_shortcode('medila_gn_single', 'medila_gn_single_shortcode');
function medila_gn_single_shortcode() {
    $id = get_the_ID();
    if (!$id || get_post_type($id) !== 'medila_news') return '';

    $thumb = get_the_post_thumbnail_url($id, 'full') ?: '';
    $title = get_the_title($id);
    $date  = get_the_date('j. n. Y', $id);

    remove_shortcode('medila_gn_single');
    $content = apply_filters('the_content', get_post_field('post_content', $id));
    add_shortcode('medila_gn_single', 'medila_gn_single_shortcode');

    $archive_link = get_post_type_archive_link('medila_news');

    $related = get_posts([
        'post_type'      => 'medila_news',
        'posts_per_page' => 3,
        'post__not_in'   => [$id],
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

    ob_start();
    ?>
    <article class="madx madx-ns">
        <section class="madx-ns-hero">
            <?php if ($thumb) : ?>
                <div class="madx-ns-hero__bg" style="background-image:url('<?php echo esc_url($thumb); ?>');"></div>
            <?php endif; ?>
            <div class="madx-ns-hero__overlay"></div>
            <div class="madx-ns-hero__inner">
                <div class="madx-ns-hero__crumbs">
                    <a href="<?php echo esc_url($archive_link); ?>">Novinky</a>
                </div>
                <h1 class="madx-ns-hero__title"><?php echo esc_html($title); ?></h1>
                <div class="madx-ns-hero__meta">
                    <span class="madx-ns-hero__date">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <?php echo esc_html($date); ?>
                    </span>
                </div>
            </div>
        </section>

        <section class="madx-ns-content"><?php echo $content; ?></section>

        <section class="madx-ns-back">
            <a href="<?php echo esc_url($archive_link); ?>" class="madx-btn madx-btn--primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="15 18 9 12 15 6"/></svg>
                Všechny novinky
            </a>
            <a href="<?php echo esc_url(home_url()); ?>" class="madx-btn madx-btn--outline">Zpět na úvod</a>
        </section>

        <?php if ($related) : ?>
            <section class="madx-newsgrid">
                <div class="madx-newsgrid__head">
                    <h2 class="madx-newsgrid__title">Další novinky</h2>
                    <a href="<?php echo esc_url($archive_link); ?>" class="madx-newsgrid__archive">Zobrazit všechny <span aria-hidden="true">→</span></a>
                </div>
                <div class="madx-newsgrid__items">
                    <?php foreach ($related as $n) :
                        $r_thumb   = get_the_post_thumbnail_url($n->ID, 'medium_large') ?: '';
                        $r_excerpt = $n->post_excerpt ?: wp_trim_words(strip_shortcodes($n->post_content), 22);
                        $r_date    = get_the_date('j. n. Y', $n);
                    ?>
                        <a href="<?php echo esc_url(get_permalink($n)); ?>" class="madx-newsitem">
                            <?php if ($r_thumb) : ?>
                                <div class="madx-newsitem__media" style="background-image:url('<?php echo esc_url($r_thumb); ?>');"></div>
                            <?php else : ?>
                                <div class="madx-newsitem__media madx-newsitem__media--placeholder">
                                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#00a278" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                                </div>
                            <?php endif; ?>
                            <div class="madx-newsitem__body">
                                <span class="madx-newsitem__date"><?php echo esc_html($r_date); ?></span>
                                <h3 class="madx-newsitem__title"><?php echo esc_html(get_the_title($n)); ?></h3>
                                <?php if ($r_excerpt) : ?>
                                    <p class="madx-newsitem__excerpt"><?php echo esc_html($r_excerpt); ?></p>
                                <?php endif; ?>
                                <span class="madx-newsitem__cta">Číst více <span aria-hidden="true">→</span></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </article>
    <?php
    return ob_get_clean();
}
