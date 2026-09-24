<?php
/**
 * Markup Repair Reel (homepage + /r/{model}-{problema}).
 *
 * @package WebGSM
 */
if (!defined('ABSPATH')) {
    exit;
}

$route = function_exists('webgsm_rr_current_route') ? webgsm_rr_current_route() : null;
$offer = ($route && function_exists('webgsm_rr_get_offer'))
    ? webgsm_rr_get_offer($route['model_slug'], $route['problem'], $route['sku'])
    : null;
$json_ld = function_exists('webgsm_rr_json_ld') ? webgsm_rr_json_ld($route, $offer) : null;
$shop    = function_exists('webgsm_rr_shop_url') ? webgsm_rr_shop_url() : home_url('/shop/');
$is_repair_landing = function_exists('webgsm_rr_is_repair_landing') && webgsm_rr_is_repair_landing();
$is_home_parts     = is_front_page() && !$route && !$is_repair_landing;
$repair_url        = function_exists('webgsm_rr_repair_landing_url') ? webgsm_rr_repair_landing_url() : home_url('/estimeaza-reparatia/');
$nav_links         = function_exists('webgsm_rr_shop_nav_links') ? webgsm_rr_shop_nav_links(8) : array();
$home_desc         = function_exists('webgsm_rr_home_seo_description')
    ? webgsm_rr_home_seo_description()
    : 'Magazin online piese GSM — Timișoara.';
$repair_desc       = function_exists('webgsm_rr_repair_seo_description')
    ? webgsm_rr_repair_seo_description()
    : 'Estimează costul reparației telefonului — Timișoara.';
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#05060c">
    <meta name="description" content="<?php echo esc_attr($route
        ? sprintf('Preț live schimbare %s %s în Timișoara. Vezi calitățile, alege repararea sau doar piesa.', $route['problem'] === 'baterie' ? 'baterie' : 'ecran', $route['model_name'])
        : ($is_repair_landing ? $repair_desc : $home_desc)); ?>">
    <?php wp_head(); ?>
    <?php if ($json_ld) : ?>
    <script type="application/ld+json"><?php echo wp_json_encode($json_ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <?php endif; ?>
</head>
<body <?php body_class('webgsm-reel'); ?>>
<?php wp_body_open(); ?>

<div id="webgsm-reel" class="rr" data-step="1">
    <header class="rr-top">
        <a class="rr-logo" href="<?php echo esc_url(home_url('/')); ?>">Web<span class="rr-accent-gold">GSM</span></a>
        <nav class="rr-top-nav" aria-label="Navigare scurtă">
            <a class="rr-top-shop" href="<?php echo esc_url($shop); ?>">Magazin</a>
            <?php if ($is_home_parts) : ?>
            <a class="rr-top-repair" href="<?php echo esc_url($repair_url); ?>">Reparații</a>
            <?php endif; ?>
            <span class="rr-loc"><span class="rr-accent-cyan">Timișoara</span></span>
        </nav>
    </header>

    <main class="rr-stage" id="rr-stage">
        <?php if ($is_home_parts) : ?>
        <section class="rr-screen rr-hero rr-hero-parts is-active" data-screen="1" aria-label="Magazin piese">
            <h1 class="rr-hook">Piese pentru <span class="rr-accent-cyan">telefoane</span></h1>
            <p class="rr-sub">Ecrane, baterii, componente — <span class="rr-accent-gold">stoc live</span>, prețuri clare, livrare din <span class="rr-accent-cyan">Timișoara</span>.</p>

            <a class="rr-btn rr-btn-primary rr-shop-cta" href="<?php echo esc_url($shop); ?>">Intră în magazin</a>

            <?php if ($nav_links) : ?>
            <div class="rr-cats">
                <p class="rr-cats-label">Categorii populare</p>
                <div class="rr-cats-grid">
                    <?php foreach ($nav_links as $link) : ?>
                    <a class="rr-cat" href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['label']); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="rr-repair-teaser">
                <p class="rr-repair-teaser-label">Telefonul are nevoie de service?</p>
                <a class="rr-repair-teaser-link" href="<?php echo esc_url($repair_url); ?>">Estimează costul reparației telefonului</a>
            </div>
        </section>
        <?php elseif ($is_repair_landing && !$route) : ?>
        <section class="rr-screen rr-hero rr-hero-repair is-active" data-screen="1" aria-label="Estimare reparație">
            <nav class="rr-crumb" aria-label="Breadcrumb">
                <a href="<?php echo esc_url(home_url('/')); ?>">Acasă</a>
                <span aria-hidden="true">/</span>
                <span>Estimează reparația</span>
            </nav>
            <h1 class="rr-hook">Estimează <span class="rr-accent-cyan">reparația telefonului</span></h1>
            <p class="rr-sub">Alege modelul, piesele și calitatea — vezi <span class="rr-accent-gold">preț live</span> (piesă + manoperă) din gestiunea WebGSM.</p>

            <button type="button" class="rr-btn rr-btn-primary rr-shop-cta" data-go="2">Începe estimarea</button>
            <p class="rr-repair-alt"><a href="<?php echo esc_url($shop); ?>">Prefer doar piesa</a> — intră direct în magazin.</p>
        </section>
        <?php else : ?>
        <section class="rr-screen rr-hero is-active" data-screen="1" aria-label="Alegere" hidden>
            <h1 class="rr-hook">Piese pentru <span class="rr-accent-cyan">telefoane</span></h1>
            <p class="rr-sub">Ecrane, baterii, componente — <span class="rr-accent-gold">stoc live</span> și livrare din Timișoara.</p>
        </section>
        <?php endif; ?>

        <section class="rr-screen" data-screen="2" hidden aria-label="Brand">
            <button type="button" class="rr-back" data-back>Înapoi</button>
            <h2 class="rr-title">Ce telefon ai?</h2>
            <div class="rr-choices" id="rr-brands">
                <button type="button" class="rr-choice" data-brand="apple">
                    <span class="rr-choice-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="28" height="28"><path fill="currentColor" d="M16.4 12.6c0-2.3 1.9-3.4 2-3.5-1.1-1.6-2.8-1.8-3.4-1.8-1.4-.2-2.8.9-3.5.9s-1.8-.8-3-.8c-1.5 0-3 .9-3.8 2.3-1.6 2.8-.4 7 1.2 9.3.8 1.1 1.7 2.3 2.9 2.3 1.2 0 1.6-.7 3-.7s1.8.7 3 .7 2-.1 2.9-2.3c.4-.8.8-1.6 1.1-2.5-2.9-1.1-3.4-5.3-3.4-5.9zm-3.2-6.3c.6-.8 1.1-1.8 1-2.9-1 .1-2.1.7-2.8 1.5-.6.7-1.2 1.8-1 2.8 1.1.1 2.2-.5 2.8-1.4z"/></svg>
                    </span>
                    <span class="rr-choice-name">Apple</span>
                </button>
                <button type="button" class="rr-choice" data-brand="samsung">
                    <span class="rr-choice-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="28" height="28"><rect x="5" y="3" width="14" height="18" rx="3" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="18" r="1" fill="currentColor"/></svg>
                    </span>
                    <span class="rr-choice-name">Samsung</span>
                </button>
                <button type="button" class="rr-choice" data-brand="xiaomi">
                    <span class="rr-choice-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="28" height="28"><rect x="4" y="4" width="16" height="16" rx="3" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M8 16V8h5.2a3.2 3.2 0 0 1 0 6.4H11v1.6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                    </span>
                    <span class="rr-choice-name">Xiaomi</span>
                </button>
            </div>
        </section>

        <section class="rr-screen" data-screen="3" hidden aria-label="Model">
            <button type="button" class="rr-back" data-back>Înapoi</button>
            <h2 class="rr-title">Model</h2>
            <label class="rr-search-wrap">
                <span class="screen-reader-text">Caută modelul</span>
                <input type="search" id="rr-model-search" class="rr-search" placeholder="Caută modelul…" autocomplete="off">
            </label>
            <div class="rr-list" id="rr-models" role="list"></div>
            <p class="rr-empty" id="rr-models-empty" hidden>Nu găsim modele pentru brandul ăsta. Scrie-ne pe WhatsApp.</p>
        </section>

        <section class="rr-screen" data-screen="4" hidden aria-label="Problemă">
            <button type="button" class="rr-back" data-back>Înapoi</button>
            <h2 class="rr-title">Ce vrei să schimbi?</h2>
            <p class="rr-picked" id="rr-picked-model"></p>
            <p class="rr-sub rr-sub-compact">Poți bifa <span class="rr-accent-cyan">mai multe</span> — manopera se calculează o singură dată (cea mai mare).</p>
            <div class="rr-checks rr-problems-multi" id="rr-problems">
                <?php foreach (webgsm_rr_problems() as $key => $meta) : ?>
                <label class="rr-check">
                    <input type="checkbox" name="problems[]" value="<?php echo esc_attr($key); ?>">
                    <span>
                        <strong><?php echo esc_html($meta['label']); ?></strong>
                        <?php if (!empty($meta['hint'])) : ?>
                        <span class="rr-check-hint"> — <?php echo esc_html($meta['hint']); ?></span>
                        <?php endif; ?>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>
            <button type="button" class="rr-btn rr-btn-primary" id="rr-problems-continue" disabled>Alege calitățile</button>
        </section>

        <section class="rr-screen rr-screen-menu" data-screen="5" hidden aria-label="Meniu calități">
            <button type="button" class="rr-back" data-back>Înapoi</button>
            <p class="rr-kicker" id="rr-menu-kicker">Estimare</p>
            <h2 class="rr-title" id="rr-menu-title">Alege pentru fiecare piesă</h2>
            <div class="rr-menu-stack" id="rr-menu-stack"></div>
            <div class="rr-menu rr-menu-single" id="rr-menu" role="list" hidden></div>
            <p class="rr-empty" id="rr-menu-empty" hidden>Nu avem încă oferte pentru combinația asta. Scrie-ne — îți confirmăm pe WhatsApp.</p>
            <p class="rr-contact-hint">
                <button type="button" class="rr-link" data-contact>Nu știi care ți se potrivește? Scrie-ne</button>
            </p>

            <div class="rr-act" id="rr-act" hidden>
                <div class="rr-act-summary" id="rr-act-summary"></div>
                <div class="rr-act-btns">
                    <div class="rr-micro-wrap" id="rr-micro-wrap" hidden>
                        <label class="rr-check">
                            <input type="checkbox" id="rr-microsoldering" value="1">
                            <span id="rr-micro-label">Mutare cip original</span>
                        </label>
                    </div>
                    <button type="button" class="rr-btn rr-btn-primary" data-act="repair">Trimite telefonul</button>
                    <button type="button" class="rr-btn rr-btn-ghost" data-act="part">Vreau doar piesele</button>
                </div>
                <p class="rr-fine" id="rr-labor-note">Montajul îl confirmăm pe WhatsApp. Atelier: Timișoara, Samuil Micu 27.</p>
                <p class="rr-montaj-note" id="rr-montaj-note" hidden>Manoperă: regula MAX — la mai multe piese plătești cea mai mare manoperă, nu suma.</p>
            </div>
        </section>

        <section class="rr-screen rr-screen-intake" data-screen="6" hidden aria-label="Formular trimitere telefon">
            <button type="button" class="rr-back" data-back>Înapoi</button>
            <p class="rr-kicker">Trimitere telefon</p>
            <h2 class="rr-title">Starea telefonului</h2>
            <p class="rr-sub rr-sub-compact">Bifează tot ce se aplică. Pozele față/spate ne ajută la documentare înainte de preluare.</p>

            <form id="rr-intake-form" class="rr-intake" novalidate>
                <fieldset class="rr-fieldset">
                    <legend class="rr-fieldset-legend">Alte probleme observate</legend>
                    <div class="rr-checks" id="rr-intake-issues">
                        <?php
                        $issues = function_exists('webgsm_rr_intake_issues') ? webgsm_rr_intake_issues() : array();
                        foreach ($issues as $key => $label) :
                            ?>
                        <label class="rr-check">
                            <input type="checkbox" name="issues[]" value="<?php echo esc_attr($key); ?>">
                            <span><?php echo esc_html($label); ?></span>
                        </label>
                            <?php
                        endforeach;
                        ?>
                    </div>
                </fieldset>

                <label class="rr-field">
                    <span class="rr-field-label">Alte detalii (opțional)</span>
                    <textarea id="rr-intake-notes" class="rr-textarea" name="notes" rows="3" placeholder="Ex.: cadă lovită, urme lichid, cod blocat…"></textarea>
                </label>

                <div class="rr-photo-grid">
                    <div class="rr-photo-field">
                        <span class="rr-field-label">Poză față telefon <span class="rr-req">*</span></span>
                        <label class="rr-photo-btn">
                            <input type="file" id="rr-photo-front" name="photo_front" accept="image/jpeg,image/png,image/webp,image/heic,image/heif" capture="environment" required>
                            <span class="rr-photo-btn-text">Alege / fotografiază fața</span>
                        </label>
                        <div class="rr-photo-preview" id="rr-preview-front" hidden></div>
                    </div>
                    <div class="rr-photo-field">
                        <span class="rr-field-label">Poză spate telefon <span class="rr-req">*</span></span>
                        <label class="rr-photo-btn">
                            <input type="file" id="rr-photo-back" name="photo_back" accept="image/jpeg,image/png,image/webp,image/heic,image/heif" capture="environment" required>
                            <span class="rr-photo-btn-text">Alege / fotografiază spatele</span>
                        </label>
                        <div class="rr-photo-preview" id="rr-preview-back" hidden></div>
                    </div>
                </div>

                <label class="rr-waiver">
                    <input type="checkbox" id="rr-waiver" name="waiver" value="1" required>
                    <span class="rr-waiver-text"><?php echo esc_html(function_exists('webgsm_rr_waiver_text') ? webgsm_rr_waiver_text() : ''); ?></span>
                </label>

                <p class="rr-form-error" id="rr-form-error" hidden></p>

                <button type="submit" class="rr-btn rr-btn-primary" id="rr-intake-submit">Continuă pe WhatsApp</button>
                <p class="rr-fine">Încărcăm pozele pe server, apoi deschidem WhatsApp cu detaliile completate.</p>
            </form>
        </section>
    </main>

    <div class="rr-steps rr-steps--sim" aria-hidden="true" hidden>
        <i data-dot="2"></i><i data-dot="3"></i><i data-dot="4"></i><i data-dot="5"></i><i data-dot="6"></i>
    </div>

    <?php if ($is_repair_landing && !$route) : ?>
    <aside class="rr-seo-copy">
        <h2>Reparații telefoane în Timișoara</h2>
        <p>Estimează online costul reparației telefonului tău — ecran spart, baterie slabă, carcasă, cameră sau alte piese. Calculatorul WebGSM afișează preț live din gestiune: costul piesei, manopera și totalul, pentru Apple, Samsung și Xiaomi.</p>
        <p>Poți comanda doar piesa din <a href="<?php echo esc_url($shop); ?>">magazinul online</a> sau trimite telefonul la service-ul nostru din Timișoara (Samuil Micu 27). Verifici calitățile disponibile, compari variantele și continui pe WhatsApp cu detaliile completate.</p>
        <h3>Cum funcționează estimarea</h3>
        <ul>
            <li>Alegi brandul și modelul telefonului</li>
            <li>Bifezi ce vrei schimbat (ecran, baterie, carcasă etc.)</li>
            <li>Selectezi calitatea piesei — preț actualizat din stoc</li>
            <li>Vezi total piesă + manoperă sau comanzi doar componenta</li>
        </ul>
    </aside>
    <?php endif; ?>

    <?php if ($is_home_parts && $nav_links) : ?>
    <footer class="rr-foot">
        <nav class="rr-foot-nav" aria-label="Catalog">
            <a href="<?php echo esc_url($shop); ?>">Catalog piese</a>
            <a href="<?php echo esc_url($repair_url); ?>">Estimează reparația</a>
            <?php foreach ($nav_links as $link) : ?>
            <a href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['label']); ?></a>
            <?php endforeach; ?>
        </nav>
        <p class="rr-foot-meta">WebGSM · Samuil Micu 27, Timișoara</p>
    </footer>
    <?php elseif ($is_repair_landing && !$route) : ?>
    <footer class="rr-foot">
        <nav class="rr-foot-nav" aria-label="Legături utile">
            <a href="<?php echo esc_url(home_url('/')); ?>">Acasă</a>
            <a href="<?php echo esc_url($shop); ?>">Catalog piese</a>
        </nav>
        <p class="rr-foot-meta">WebGSM · Samuil Micu 27, Timișoara</p>
    </footer>
    <?php endif; ?>
</div>

<?php wp_footer(); ?>
</body>
</html>
