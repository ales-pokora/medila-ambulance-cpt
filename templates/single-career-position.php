<?php
/**
 * Career position detail — Medila Care 2.0 layout.
 * Content is the stored description, re-laid out: intro, numbered sections
 * with check-grids, start date, CTA and an enquiry form.
 */

if (!defined('ABSPATH')) exit;

get_header();

while (have_posts()) :
    the_post();
    $id        = get_the_ID();
    $location  = get_post_meta($id, '_career_location', true);
    $type      = medila_rd_employment_label(get_post_meta($id, '_career_employment_type', true));
    $work_mode = get_post_meta($id, '_career_work_mode', true);
    $start     = get_post_meta($id, '_career_start', true);
    $parts     = medila_rd_career_sections(get_post_field('post_content', $id));
    $sent      = isset($_GET['odeslano']) ? sanitize_key(wp_unslash($_GET['odeslano'])) : '';

    // "Nástup" gets its own highlighted box, not a one-item grid.
    $start_section = null;
    foreach ($parts['sections'] as $i => $s) {
        if (mb_stripos($s['title'], 'nástup') === 0) {
            $start_section = $s;
            unset($parts['sections'][$i]);
        }
    }
    $start_text = $start ?: ($start_section['items'][0] ?? '');
    $parts['sections'] = array_values($parts['sections']);
    ?>
    <main id="main-content" class="mc-job" tabindex="-1">
        <header class="mc-job__hero">
            <div class="mc-wrap">
                <nav class="mc-breadcrumb" aria-label="Drobečková navigace">
                    <a href="<?php echo esc_url(home_url('/pro-lekare/')); ?>">Pro lékaře</a>
                    <span aria-hidden="true">/</span>
                    <a href="<?php echo esc_url(home_url('/pro-lekare/#zamestnani')); ?>">Volné pozice</a>
                    <?php if ($location) : ?><span aria-hidden="true">/</span><span aria-current="page"><?php echo esc_html($location); ?></span><?php endif; ?>
                </nav>
                <h1 class="mc-job__title"><?php echo medila_rd_cz(esc_html(medila_rd_career_title(get_the_title()))); ?></h1>
                <ul class="mc-job__tags">
                    <?php if ($location) : ?><li class="mc-tag mc-tag--glass"><?php echo esc_html($location); ?></li><?php endif; ?>
                    <?php if ($type) : ?><li class="mc-tag mc-tag--glass"><?php echo esc_html($type); ?></li><?php endif; ?>
                    <?php if ($work_mode) : ?><li class="mc-tag mc-tag--highlight"><?php echo esc_html($work_mode); ?></li><?php endif; ?>
                    <?php if ($start_text) : ?><li class="mc-tag mc-tag--glass">Nástup <?php echo esc_html(lcfirst($start_text)); ?></li><?php endif; ?>
                </ul>
            </div>
        </header>

        <section class="mc-job__intro">
            <div class="mc-wrap">
                <?php foreach ($parts['intro'] as $p) : ?>
                    <p><?php echo medila_rd_cz(esc_html($p)); ?></p>
                <?php endforeach; ?>
                <ul class="mc-strip" aria-label="Co získáte ve skupině Medila Care">
                    <li><span class="mc-strip__ic"><?php echo medila_rd_svg('flask', 16); ?></span>Vlastní laboratorní diagnostika (síť MEDILA)</li>
                    <li><span class="mc-strip__ic"><?php echo medila_rd_svg('lock', 16); ?></span>Bezpečný zdravotnický software</li>
                    <li><span class="mc-strip__ic"><?php echo medila_rd_svg('build', 16); ?></span>Zázemí holdingu FONS JK — ve zdravotnictví přes 30 let</li>
                </ul>
            </div>
        </section>

        <section class="mc-job__body">
            <div class="mc-wrap">
                <?php
                $n = 0;
                foreach ($parts['sections'] as $s) :
                    $n++;
                    ?>
                    <div class="mc-job__section">
                        <h2 class="mc-numhead"><span class="mc-numhead__num" aria-hidden="true"><?php echo (int) $n; ?></span><?php echo medila_rd_cz(esc_html($s['title'] ?: 'Co nabízíme')); ?></h2>
                        <ul class="mc-checkgrid">
                            <?php foreach ($s['items'] as $item) : ?>
                                <li><?php echo medila_rd_svg('check', 16); ?><span><?php echo medila_rd_cz(esc_html($item)); ?></span></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>

                <?php if ($start_text) : $n++; ?>
                    <div class="mc-job__section">
                        <h2 class="mc-numhead"><span class="mc-numhead__num" aria-hidden="true"><?php echo (int) $n; ?></span>Nástup</h2>
                        <p class="mc-startbox"><?php echo medila_rd_svg('cal', 22); ?><strong><?php echo esc_html($start_text); ?></strong></p>
                    </div>
                <?php endif; ?>

                <div class="mc-ctaband">
                    <h2>Máte zájem o&nbsp;spolupráci?</h2>
                    <p>Rádi se s&nbsp;vámi nezávazně potkáme a&nbsp;vše projdeme osobně&nbsp;– vyplňte dotazník na této stránce, nebo nám napište přímo.</p>
                    <div class="mc-ctaband__row">
                        <a class="mc-btn mc-btn--light" href="#dotaznik">Vyplnit dotazník</a>
                        <a class="mc-btn mc-btn--ghost" href="mailto:kariera@medilacare.cz">kariera@medilacare.cz</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="mc-job__form" id="dotaznik">
            <div class="mc-wrap">
                <h2 class="mc-numhead"><span class="mc-numhead__num" aria-hidden="true"><?php echo (int) $n + 1; ?></span>Máte zájem? Napište&nbsp;nám</h2>
                <?php if ($sent === '1') : ?>
                    <p class="mc-notice mc-notice--ok" role="status">Děkujeme, zprávu jsme přijali. Ozveme se vám co nejdříve.</p>
                <?php elseif ($sent === 'chyba') : ?>
                    <p class="mc-notice mc-notice--err" role="alert">Zprávu se nepodařilo odeslat. Zkontrolujte prosím jméno, e-mail a souhlas se zpracováním údajů.</p>
                <?php endif; ?>
                <form class="mc-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="medila_rd_job_enquiry">
                    <input type="hidden" name="position" value="<?php echo (int) $id; ?>">
                    <?php wp_nonce_field('medila_rd_job_' . $id, '_mcnonce'); ?>
                    <p class="mc-form__hp" aria-hidden="true"><label>Web <input type="text" name="website" tabindex="-1" autocomplete="off"></label></p>
                    <p><label for="mc-jmeno">Jméno a&nbsp;příjmení</label><input id="mc-jmeno" name="jmeno" type="text" autocomplete="name" required></p>
                    <p><label for="mc-email">E-mail</label><input id="mc-email" name="email" type="email" autocomplete="email" required></p>
                    <p><label for="mc-telefon">Telefon</label><input id="mc-telefon" name="telefon" type="tel" autocomplete="tel"></p>
                    <p><label for="mc-zprava">Zpráva</label><textarea id="mc-zprava" name="zprava" rows="4"></textarea></p>
                    <p class="mc-form__consent"><label><input type="checkbox" name="souhlas" value="1" required> <span>Souhlasím se <a href="<?php echo esc_url(get_privacy_policy_url() ?: home_url('/ochrana-osobnich-udaju/')); ?>">zpracováním osobních údajů</a> pro účely vyřízení mého dotazu.</span></label></p>
                    <p><button class="mc-btn mc-btn--primary mc-btn--block" type="submit">Odeslat</button></p>
                </form>
            </div>
        </section>
        <div class="mc-wave mc-job__wave" aria-hidden="true"></div>
    </main>
    <?php
endwhile;

get_footer();
