<?php
/**
 * Medila Care - Ambulance News templates
 * Two drop-in shortcodes that render the entire styled body of the news pages:
 *
 *   [medila_news_single]   single news detail (use in Divi Theme Builder body
 *                          for "All Aktualita Posts")
 *   [medila_news_archive]  news listing with optional ?amb={id} filter
 *                          (use in Divi Theme Builder body for "All Aktualita
 *                          Archive Pages")
 *
 * @since 1.7.0
 */

if (!defined('ABSPATH')) exit;

// =============================================================================
// SINGLE NEWS DETAIL: [medila_news_single]
// =============================================================================
add_shortcode('medila_news_single', 'medila_news_single_shortcode');
function medila_news_single_shortcode() {
    $id = get_the_ID();
    if (!$id || get_post_type($id) !== 'ambulance_news') return '';

    $amb_id    = (int) get_post_meta($id, '_news_ambulance', true);
    $amb_title = $amb_id ? get_the_title($amb_id) : '';
    $amb_link  = $amb_id ? get_permalink($amb_id)  : '';

    $thumb   = get_the_post_thumbnail_url($id, 'full') ?: '';
    $title   = get_the_title($id);
    $date    = get_the_date('j. n. Y', $id);

    // Guard against accidental recursion if [medila_news_single] is in the
    // post content itself instead of the Divi Theme Builder template.
    remove_shortcode('medila_news_single');
    $content = apply_filters('the_content', get_post_field('post_content', $id));
    add_shortcode('medila_news_single', 'medila_news_single_shortcode');

    $archive_link = get_post_type_archive_link('ambulance_news');
    $archive_url  = $amb_id ? add_query_arg('amb', $amb_id, $archive_link) : $archive_link;

    // Up to 3 other recent news from the same ambulance
    $related = [];
    if ($amb_id) {
        $related = get_posts([
            'post_type'      => 'ambulance_news',
            'posts_per_page' => 3,
            'post__not_in'   => [$id],
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => [[
                'key'   => '_news_ambulance',
                'value' => $amb_id,
            ]],
        ]);
    }

    ob_start();
    ?>
    <article class="madx madx-ns">

        <!-- HERO -->
        <section class="madx-ns-hero">
            <?php if ($thumb) : ?>
                <div class="madx-ns-hero__bg" style="background-image:url('<?php echo esc_url($thumb); ?>');"></div>
            <?php endif; ?>
            <div class="madx-ns-hero__overlay"></div>
            <div class="madx-ns-hero__inner">
                <div class="madx-ns-hero__crumbs">
                    <a href="<?php echo esc_url($archive_link); ?>">Aktuality</a>
                    <?php if ($amb_link) : ?>
                        <span aria-hidden="true">/</span>
                        <a href="<?php echo esc_url($amb_link); ?>"><?php echo esc_html($amb_title); ?></a>
                    <?php endif; ?>
                </div>
                <h1 class="madx-ns-hero__title"><?php echo esc_html($title); ?></h1>
                <div class="madx-ns-hero__meta">
                    <span class="madx-ns-hero__date">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <?php echo esc_html($date); ?>
                    </span>
                    <?php if ($amb_title) : ?>
                        <span class="madx-ns-hero__amb">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <?php echo esc_html($amb_title); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- CONTENT -->
        <section class="madx-ns-content">
            <?php echo $content; ?>
        </section>

        <!-- BACK CTAS -->
        <section class="madx-ns-back">
            <?php if ($amb_link) : ?>
                <a href="<?php echo esc_url($amb_link); ?>" class="madx-btn madx-btn--primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="15 18 9 12 15 6"/></svg>
                    Zpět na ambulanci
                </a>
            <?php endif; ?>
            <a href="<?php echo esc_url($archive_url); ?>" class="madx-btn madx-btn--outline">Všechny aktuality</a>
        </section>

        <!-- RELATED -->
        <?php if ($related) : ?>
        <section class="madx-newsgrid">
            <div class="madx-newsgrid__head">
                <h2 class="madx-newsgrid__title">Další aktuality z této ordinace</h2>
                <a href="<?php echo esc_url($archive_url); ?>" class="madx-newsgrid__archive">Zobrazit všechny <span aria-hidden="true">→</span></a>
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

// =============================================================================
// NEWS ARCHIVE LISTING: [medila_news_archive]
// =============================================================================
add_shortcode('medila_news_archive', 'medila_news_archive_shortcode');
function medila_news_archive_shortcode($atts) {
    $atts = shortcode_atts([
        'per_page'     => 12,
        'show_filters' => 'yes',
    ], $atts);

    $filter_amb_id    = !empty($_GET['amb']) && is_numeric($_GET['amb']) ? (int) $_GET['amb'] : 0;
    $filter_amb_title = $filter_amb_id ? get_the_title($filter_amb_id) : '';

    $paged = max(1, (int) (get_query_var('paged') ?: get_query_var('page') ?: 1));

    $args = [
        'post_type'      => 'ambulance_news',
        'posts_per_page' => (int) $atts['per_page'],
        'paged'          => $paged,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];
    if ($filter_amb_id) {
        $args['meta_query'] = [[
            'key'   => '_news_ambulance',
            'value' => $filter_amb_id,
        ]];
    }
    $query = new WP_Query($args);

    $all_ambs = [];
    if ($atts['show_filters'] === 'yes') {
        $all_ambs = get_posts([
            'post_type'      => 'ambulance',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'post_status'    => 'publish',
        ]);
    }

    $archive_link = get_post_type_archive_link('ambulance_news');

    ob_start();
    ?>
    <div class="madx madx-na">

        <!-- HERO -->
        <section class="madx-na-hero">
            <div class="madx-na-hero__inner">
                <span class="madx-tag"><span class="madx-tag__dot"></span>Aktuality</span>
                <h1 class="madx-na-hero__title">
                    <?php if ($filter_amb_title) : ?>
                        Aktuality z <?php echo esc_html($filter_amb_title); ?>
                    <?php else : ?>
                        Aktuality z ordinací
                    <?php endif; ?>
                </h1>
                <p class="madx-na-hero__sub">
                    Uzavírky, nová vyšetření, organizační změny a vše ostatní, co potřebujete vědět<?php if ($filter_amb_title) echo ' o ' . esc_html($filter_amb_title); ?>.
                </p>
            </div>
        </section>

        <!-- FILTER CHIPS -->
        <?php if ($atts['show_filters'] === 'yes' && $all_ambs) : ?>
        <section class="madx-na-filters">
            <a href="<?php echo esc_url($archive_link); ?>" class="madx-chip <?php echo $filter_amb_id ? '' : 'madx-chip--active'; ?>">Všechny ordinace</a>
            <?php foreach ($all_ambs as $amb) :
                $url    = add_query_arg('amb', $amb->ID, $archive_link);
                $active = ($filter_amb_id === (int) $amb->ID) ? 'madx-chip--active' : '';
            ?>
                <a href="<?php echo esc_url($url); ?>" class="madx-chip <?php echo esc_attr($active); ?>"><?php echo esc_html($amb->post_title); ?></a>
            <?php endforeach; ?>
        </section>
        <?php endif; ?>

        <!-- LIST / EMPTY -->
        <?php if ($query->have_posts()) : ?>
            <section class="madx-newsgrid madx-newsgrid--archive">
                <div class="madx-newsgrid__items">
                    <?php while ($query->have_posts()) : $query->the_post();
                        $n_id        = get_the_ID();
                        $r_thumb     = get_the_post_thumbnail_url($n_id, 'medium_large') ?: '';
                        $r_excerpt   = get_the_excerpt() ?: wp_trim_words(strip_shortcodes(get_the_content()), 22);
                        $r_date      = get_the_date('j. n. Y');
                        $r_amb_id    = (int) get_post_meta($n_id, '_news_ambulance', true);
                        $r_amb_title = $r_amb_id ? get_the_title($r_amb_id) : '';
                    ?>
                        <a href="<?php the_permalink(); ?>" class="madx-newsitem">
                            <?php if ($r_thumb) : ?>
                                <div class="madx-newsitem__media" style="background-image:url('<?php echo esc_url($r_thumb); ?>');"></div>
                            <?php else : ?>
                                <div class="madx-newsitem__media madx-newsitem__media--placeholder">
                                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#00a278" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                                </div>
                            <?php endif; ?>
                            <div class="madx-newsitem__body">
                                <span class="madx-newsitem__date"><?php echo esc_html($r_date); ?></span>
                                <h3 class="madx-newsitem__title"><?php the_title(); ?></h3>
                                <?php if ($r_amb_title && !$filter_amb_id) : ?>
                                    <span class="madx-newsitem__amb"><?php echo esc_html($r_amb_title); ?></span>
                                <?php endif; ?>
                                <?php if ($r_excerpt) : ?>
                                    <p class="madx-newsitem__excerpt"><?php echo esc_html($r_excerpt); ?></p>
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
                    <nav class="madx-pagination" aria-label="Stránkování aktualit">
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
                <h2>Žádné aktuality</h2>
                <p>
                    <?php if ($filter_amb_title) : ?>
                        Pro ordinaci <strong><?php echo esc_html($filter_amb_title); ?></strong> zatím nejsou zveřejněny žádné aktuality.
                    <?php else : ?>
                        Zatím zde nejsou žádné aktuality. Zkuste se prosím vrátit později.
                    <?php endif; ?>
                </p>
                <a href="<?php echo esc_url($filter_amb_id ? $archive_link : home_url()); ?>" class="madx-btn madx-btn--outline">
                    <?php echo $filter_amb_id ? 'Zobrazit všechny aktuality' : 'Zpět na úvodní stránku'; ?>
                </a>
            </section>
        <?php endif; ?>

    </div>
    <?php
    return ob_get_clean();
}

// =============================================================================
// Hide Divi default post title + meta on news pages, enqueue page-specific CSS
// =============================================================================
add_action('wp_enqueue_scripts', 'medila_news_template_styles');
function medila_news_template_styles() {
    if (!is_singular('ambulance_news') && !is_post_type_archive('ambulance_news')) return;

    $css = '
    /* Hide default theme title/meta — our shortcode renders its own */
    body.single-ambulance_news .et_post_meta_wrapper,
    body.single-ambulance_news h1.entry-title,
    body.single-ambulance_news .entry-title,
    body.single-ambulance_news .post-meta,
    body.single-ambulance_news .et_pb_title_meta_container,
    body.post-type-archive-ambulance_news .et_post_meta_wrapper,
    body.post-type-archive-ambulance_news h1.entry-title,
    body.post-type-archive-ambulance_news .entry-title,
    body.post-type-archive-ambulance_news .post-meta{display:none !important;}

    /* ===== NEWS SINGLE HERO ===== */
    .madx-ns-hero{position:relative;border-radius:28px;overflow:hidden;min-height:360px;display:flex;align-items:flex-end;margin-bottom:50px;isolation:isolate;}
    .madx-ns-hero__bg{position:absolute;inset:0;background-size:cover;background-position:center;z-index:0;}
    .madx-ns-hero__overlay{position:absolute;inset:0;background:rgba(0,162,120,0.92);z-index:1;}
    .madx-ns-hero__bg + .madx-ns-hero__overlay{background:linear-gradient(180deg,rgba(26,26,46,0.35) 0%,rgba(0,162,120,0.92) 100%);}
    .madx-ns-hero__inner{position:relative;z-index:2;padding:54px 56px 44px;color:#fff;width:100%;}
    .madx-ns-hero__crumbs{font-size:13px;font-weight:600;letter-spacing:.4px;margin-bottom:20px;color:rgba(255,255,255,0.92);}
    .madx-ns-hero__crumbs a{color:#fff;text-decoration:none;border-bottom:1px solid rgba(255,255,255,0.45);transition:border-color .2s;padding-bottom:1px;}
    .madx-ns-hero__crumbs a:hover{border-bottom-color:#fff;}
    .madx-ns-hero__crumbs span{margin:0 8px;opacity:.5;}
    .madx-ns-hero__title{font-family:"Raleway",sans-serif;font-size:44px;font-weight:800;line-height:1.1;margin:0 0 22px;color:#fff;letter-spacing:-.8px;}
    .madx-ns-hero__meta{display:flex;flex-wrap:wrap;gap:22px;font-size:14px;color:rgba(255,255,255,0.94);font-weight:500;}
    .madx-ns-hero__date,.madx-ns-hero__amb{display:inline-flex;align-items:center;gap:8px;}

    /* ===== NEWS CONTENT ===== */
    .madx-ns-content{max-width:780px;margin:0 auto 50px;font-size:17px;line-height:1.75;color:#333;}
    .madx-ns-content > *:first-child{margin-top:0;}
    .madx-ns-content > *:last-child{margin-bottom:0;}
    .madx-ns-content h2{font-family:"Raleway",sans-serif;font-size:28px;font-weight:800;color:var(--ink);margin:38px 0 16px;letter-spacing:-.4px;}
    .madx-ns-content h3{font-family:"Raleway",sans-serif;font-size:22px;font-weight:700;color:var(--ink);margin:30px 0 14px;}
    .madx-ns-content h4{font-family:"Raleway",sans-serif;font-size:18px;font-weight:700;color:var(--ink);margin:24px 0 10px;}
    .madx-ns-content p{margin:0 0 18px;}
    .madx-ns-content a{color:var(--g);text-decoration:underline;text-decoration-color:rgba(0,162,120,0.35);text-underline-offset:3px;transition:text-decoration-color .2s,color .2s;}
    .madx-ns-content a:hover{text-decoration-color:var(--g);color:var(--ink);}
    .madx-ns-content ul,.madx-ns-content ol{margin:0 0 18px;padding-left:24px;}
    .madx-ns-content li{margin-bottom:8px;}
    .madx-ns-content blockquote{border-left:4px solid var(--g);background:#f2f7f5;padding:22px 28px;margin:28px 0;border-radius:0 14px 14px 0;font-style:italic;color:#3a3a3a;font-size:16px;}
    .madx-ns-content blockquote p:last-child{margin-bottom:0;}
    .madx-ns-content img{max-width:100%;height:auto;border-radius:14px;margin:24px 0;display:block;}
    .madx-ns-content hr{border:none;border-top:1px solid #e8eded;margin:36px 0;}
    .madx-ns-content code{background:#f2f7f5;color:var(--g);padding:2px 8px;border-radius:6px;font-size:.92em;}
    .madx-ns-content table{width:100%;border-collapse:collapse;margin:24px 0;}
    .madx-ns-content table th,.madx-ns-content table td{padding:12px 16px;border-bottom:1px solid #e8eded;text-align:left;}
    .madx-ns-content table th{background:#f2f7f5;color:var(--ink);font-weight:700;}

    /* ===== BACK CTA STRIP ===== */
    .madx-ns-back{display:flex;gap:14px;justify-content:center;flex-wrap:wrap;padding:32px 0 50px;border-top:1px solid #e8eded;border-bottom:1px solid #e8eded;margin-bottom:50px;}

    /* ===== ARCHIVE HERO ===== */
    .madx-na-hero{position:relative;border-radius:28px;overflow:hidden;background:var(--g);color:#fff;margin-bottom:30px;padding:60px 56px 56px;isolation:isolate;}
    .madx-na-hero__inner{position:relative;z-index:2;max-width:720px;}
    .madx-na-hero .madx-tag{background:rgba(255,255,255,0.18);border-color:rgba(255,255,255,0.28);}
    .madx-na-hero__title{font-family:"Raleway",sans-serif;font-size:46px;font-weight:800;line-height:1.05;margin:18px 0 14px;color:#fff;letter-spacing:-.9px;}
    .madx-na-hero__sub{font-size:16px;line-height:1.65;color:rgba(255,255,255,0.93);margin:0;font-weight:500;max-width:560px;}

    /* ===== FILTER CHIPS ===== */
    .madx-na-filters{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:40px;}
    .madx-chip{display:inline-flex;align-items:center;padding:10px 20px;background:#f2f7f5;color:var(--ink);border-radius:30px;font-size:13px;font-weight:700;text-decoration:none;letter-spacing:.3px;border:2px solid transparent;transition:all .25s;}
    .madx-chip:hover{background:#e8f8f4;color:var(--g);transform:translateY(-1px);}
    .madx-chip--active,.madx-chip--active:hover{background:var(--g);color:#fff;transform:none;}

    /* Archive grid sizing — 3 columns desktop */
    .madx-newsgrid--archive .madx-newsgrid__items{grid-template-columns:repeat(auto-fill,minmax(300px,1fr));}
    .madx-newsitem__amb{display:inline-block;font-size:10.5px;font-weight:700;color:var(--b);text-transform:uppercase;letter-spacing:.8px;background:#e6f4f7;padding:3px 10px;border-radius:8px;margin-bottom:10px;align-self:flex-start;}

    /* ===== PAGINATION ===== */
    .madx-pagination{display:flex;justify-content:center;gap:8px;margin-top:44px;flex-wrap:wrap;}
    .madx-pagination__item .page-numbers{display:inline-flex;align-items:center;justify-content:center;min-width:44px;height:44px;padding:0 14px;border-radius:12px;background:#fff;color:var(--ink);text-decoration:none;font-weight:700;font-size:14px;border:1px solid #e8eded;transition:all .22s;}
    .madx-pagination__item .page-numbers:hover{background:var(--g);color:#fff;border-color:var(--g);transform:translateY(-2px);box-shadow:0 8px 18px rgba(0,162,120,0.25);}
    .madx-pagination__item .page-numbers.current{background:var(--g);color:#fff;border-color:var(--g);}
    .madx-pagination__item .page-numbers.dots{background:transparent;border:none;color:#888;cursor:default;}
    .madx-pagination__item .page-numbers.dots:hover{transform:none;box-shadow:none;background:transparent;color:#888;}

    /* ===== EMPTY STATE ===== */
    .madx-na-empty{text-align:center;padding:70px 30px;background:#fff;border-radius:22px;border:1px solid #e8eded;}
    .madx-na-empty__icon{width:88px;height:88px;background:#f2f7f5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 22px;}
    .madx-na-empty h2{font-family:"Raleway",sans-serif;font-size:26px;font-weight:800;color:var(--ink);margin:0 0 10px;}
    .madx-na-empty p{color:#666;font-size:15px;margin:0 auto 24px;max-width:440px;line-height:1.6;}

    /* ===== MOBILE ===== */
    @media(max-width:980px){
        .madx-ns-hero__inner{padding:42px 32px;}
        .madx-ns-hero__title{font-size:34px;}
        .madx-na-hero{padding:46px 32px;}
        .madx-na-hero__title{font-size:34px;}
        .madx-ns-content{font-size:16px;}
    }
    @media(max-width:600px){
        .madx-ns-hero{border-radius:20px;min-height:300px;margin-bottom:32px;}
        .madx-ns-hero__inner{padding:30px 22px;}
        .madx-ns-hero__title{font-size:24px;}
        .madx-ns-hero__crumbs{font-size:12px;margin-bottom:14px;}
        .madx-ns-hero__meta{gap:14px;}
        .madx-ns-content{font-size:15.5px;margin-bottom:36px;}
        .madx-ns-content h2{font-size:24px;}
        .madx-ns-content h3{font-size:19px;}
        .madx-ns-content blockquote{padding:18px 22px;}
        .madx-ns-back{flex-direction:column;padding:24px 0 36px;margin-bottom:36px;}
        .madx-ns-back .madx-btn{width:100%;justify-content:center;}
        .madx-na-hero{border-radius:20px;padding:36px 22px;}
        .madx-na-hero__title{font-size:26px;}
        .madx-na-hero__sub{font-size:14.5px;}
        .madx-na-filters{gap:8px;margin-bottom:28px;}
        .madx-chip{padding:8px 14px;font-size:12px;}
        .madx-pagination__item .page-numbers{min-width:38px;height:38px;font-size:13px;}
        .madx-na-empty{padding:48px 20px;}
        .madx-na-empty__icon{width:72px;height:72px;}
        .madx-na-empty h2{font-size:22px;}
    }
    ';

    wp_register_style('medila-news-template', false);
    wp_enqueue_style('medila-news-template');
    wp_add_inline_style('medila-news-template', $css);
}
