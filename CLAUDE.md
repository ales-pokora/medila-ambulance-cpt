# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

WordPress plugin for **medila.care** — a Czech medical-practice site running the **Divi** theme. Provides two custom post types (`ambulance` for medical practices, `career_position` for job listings) plus matching taxonomies, meta fields, listing shortcodes, and per-field shortcodes designed to be dropped into Divi Theme Builder templates.

There is no build/test/lint pipeline. Deploy by uploading the plugin folder to `/wp-content/plugins/medila-ambulance-cpt/`. After enabling or after changing CPT/taxonomy slugs, flush rewrite rules (WP admin → Settings → Permalinks → Save). The activation hook also flushes on plugin activate. Quick syntax check: `php -l <file>.php`.

## Architecture

### Entry point
`medila-ambulance-cpt.php` registers everything for the **ambulance** CPT and `require_once`s the two modules in `includes/`:
- `includes/career-cpt.php` — entire `career_position` CPT (registration, meta box, save handler, `[medila_career_list]` shortcode + inline-styled cards).
- `includes/single-template.php` — **detail-page shortcodes** for ambulance single posts.

### Two distinct rendering models — do not mix them up

1. **Listing shortcodes** (`[medila_ambulance_list]` in main file, `[medila_career_list]` in `career-cpt.php`) build complete grid markup with inline-styled cards, used inside Divi pages or `[shortcode]` modules. Both append a `<style>` block at the end of `$output`. Selectors: `.medila-ambulance-card` and `.medila-career-card__*`.

2. **Detail shortcodes** in `single-template.php` are **per-field**. Each one (`[medila_amb_doctors]`, `[medila_amb_contact]`, `[medila_amb_hours]`, `[medila_amb_services]`, `[medila_amb_insurance]`, `[medila_amb_booking]`, `[medila_amb_pricelist]`, `[medila_amb_news]`, `[medila_amb_registration]`, `[medila_amb_phone_hero]`, plus the generic `[medila_amb field="..."]`) returns a single bit of HTML for one meta field, returning `''` when the field is empty so empty modules collapse cleanly. They are meant to be placed individually inside Divi Theme Builder modules. Their CSS lives in **one** `wp_add_inline_style()` block in `medila_ambulance_detail_styles()` at the bottom of `single-template.php`, gated by `is_singular('ambulance')`. All class names use the `.mad-*` prefix.

   Note: an earlier version of this file used a `the_content` filter to inject one big template — that has been replaced. New ambulance detail UI work goes in the per-field shortcodes + the `.mad-*` inline stylesheet, **not** a `the_content` filter.

### Meta-field conventions

- Ambulance meta keys: `_ambulance_doctor`, `_ambulance_doctor2`, `_ambulance_nurse`, `_ambulance_phone`, `_ambulance_email`, `_ambulance_address`, `_ambulance_hours`, `_ambulance_news`, `_ambulance_services`, `_ambulance_insurance`, `_ambulance_booking_url`, `_ambulance_pricelist_url`, `_ambulance_registration`.
- Career meta keys: `_career_location`, `_career_employment_type`, `_career_salary`, `_career_icon`.
- Save handlers (`medila_save_ambulance_meta`, `medila_save_career_meta`) bucket fields into `$text_fields` / `$textarea_fields` / `$url_fields` and apply `sanitize_text_field` / `sanitize_textarea_field` / `esc_url_raw` accordingly. **When adding a new meta field, register it in the meta box callback AND in the matching bucket in the save handler** — missing it from the save handler is the most common bug here.
- Two fields use **structured string formats** that downstream shortcodes parse:
  - `_ambulance_hours`: one day per line, format `DEN: typ čas | typ čas | typ čas` (e.g. `PO: Objednaní 7:00-8:00 | Akutní 8:00-10:00`). `medila_amb_hours_shortcode` builds an HTML table from this; if no `|` is present, falls back to plain-line rendering.
  - `_ambulance_insurance`: comma-separated, each item optionally `Name|https://url` (e.g. `VZP|https://www.vzp.cz, VOZP, OZP`). Items with a valid URL render as `<a class="mad-insurance-tag mad-insurance-tag--link">`, others as `<span class="mad-insurance-tag">`.

### Divi integration

- `et_builder_post_types` + `et_builder_get_builder_post_types` filters register both CPTs with Divi Builder (so they appear in the Blog module and can use Divi Theme Builder).
- The ambulance single-post layout is **assembled in Divi Theme Builder**, not in PHP. Each meta field is added to the template via its detail shortcode. Default Divi post-title and post-meta are hidden via CSS in `single-template.php` targeting `body.single-ambulance .et_post_meta_wrapper`, `.entry-title`, `.et_pb_title_meta_container`, etc.
- Card accent bars on the ambulance detail are forced to solid brand colors (`.mad-card__accent` → `#009ab2`, `.mad-card__accent--green` → `#00a278`) with `!important` to override any gradient styling from Divi modules.

### Branding

- Brand green: `#00a278`. Brand blue: `#009ab2`. Cards use white backgrounds with `box-shadow: 0 2px 20px rgba(50,71,71,0.07)`. Typography: Raleway for headings, Poppins for body.
- All UI strings are in **Czech**. Match the existing tone (formal medical context — `MUDr.`, `Objednat se`, `Smluvní pojišťovny`).

## When making changes

- Don't add a `the_content` filter for ambulance singles — the file used to do that; the project moved to per-field shortcodes used inside Divi Theme Builder. Add a new shortcode in `single-template.php` instead.
- New CSS for ambulance detail goes inside the `$css = '...';` string in `medila_ambulance_detail_styles()`, not in a separate file. Listing-shortcode CSS stays appended to the shortcode's `$output`.
- Adding/renaming a CPT slug or taxonomy rewrite slug requires re-saving permalinks (or deactivating + reactivating the plugin) for the rewrite rules to take effect.
