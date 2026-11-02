# Ropstam Senior CMS Developer Task

One repo, two independent deliverables:

| Path | What |
|---|---|
| `wordpress/ropstam-services-calculator/` | Plugin: Services CPT + dynamic block / shortcode with a live add-on calculator |
| `shopify/` | Online Store 2.0 section `bundle-builder.liquid` + its JS/CSS assets |

**Demo links** (fill in before sending)
- WordPress: `<url>` (admin: `<user>` / `<pass>`)
- Shopify dev store: `<url>` (storefront password: `<pass>`)

---

## 1. WordPress

### Setup
1. Copy `wordpress/ropstam-services-calculator/` to `wp-content/plugins/` (or zip the folder and upload).
2. Activate **Ropstam Services Price Calculator** (flushes permalinks for `/services/`).
3. Add 3+ entries under **Services**; fill *Base price* and *Delivery time (days)* in the sidebar meta box.
4. Insert the **Services Price Calculator** block, or use `[ras_services_calculator heading="Get a quote"]`.

Requires WordPress 6.3+ (script `strategy`), PHP 7.4+. No build step, no Composer/npm dependencies.

### How it maps to the brief
| Requirement | Implementation |
|---|---|
| CPT *Services* + `base_price`, `delivery_time_days` | `includes/post-type.php`: `register_post_type( 'ras_service' )`, `register_post_meta()` with type, default, `sanitize_callback`, `auth_callback`, REST exposure. Admin meta box with nonce + capability + autosave checks |
| 3 most recently updated | `WP_Query` with `orderby => modified`, `no_found_rows => true` (no raw SQL anywhere) |
| Block or shortcode | Both. Dynamic block (`block.json`, PHP `render_callback`, tiny no-build editor script using ServerSideRender) and a shortcode share one render function |
| Vanilla JS live calculator | `assets/js/calculator.js`: radio (service) + checkboxes (add-ons), integer-cents maths, `Intl.NumberFormat`, live delivery-time estimate |
| Escaping / enqueue | `esc_html`, `esc_attr`, `esc_html__`; assets **registered** on `init`, **enqueued only when rendered** via `wp_enqueue_script/style`, deps `wp-dom-ready`, `defer` |

### Decisions
- **CPT slug `ras_service`** (public URL `/services/`) to avoid collisions with themes/plugins.
- **Add-ons** are filterable: `add_filter( 'ras_addons', fn( $a ) => $a )`; also `ras_currency` and `ras_currency_symbol`.
- **No-JS fallback:** the server renders the first service price/delivery, so the component is meaningful before JS runs.
- **Accessibility:** real `fieldset/legend`, native radios/checkboxes (keyboard + screen reader for free), `aria-live` result region, visible `:focus-within` ring.

### Limitations
- Add-ons are defined in PHP (filter), not editable in wp-admin.
- Calculator is an *estimate*; it does not create orders.

---

## 2. Shopify

### Setup (Shopify CLI)
```bash
shopify theme init my-theme        # or use an existing OS 2.0 theme (e.g. Dawn)
cd my-theme
cp -r ../shopify/sections/*  sections/
cp -r ../shopify/assets/*    assets/
cp -r ../shopify/templates/* templates/
shopify theme dev --store <your-store>.myshopify.com        # live preview
shopify theme push --unpublished --store <your-store>.myshopify.com   # shareable preview
```
Then in **Online Store → Pages** create a page and assign the *page.bundle-builder* template (or add the **Bundle builder** section to any template in the theme editor). In section settings pick a collection **or** a product list for each of the 3 groups.

### How it maps to the brief
| Requirement | Implementation |
|---|---|
| Section with schema, up to 3 collections/lists | `collection_1..3` and `product_list_1..3` (list overrides collection), group labels, products per group, discount %, button label, redirect toggle |
| Pick 1 per group | One native radio group (`fieldset` + `legend`) per group; variant `<select>` for multi-variant products |
| Live bundle price + `/cart/add.js` | `bundle-builder.js` sums selected variants, applies the display discount, and POSTs **one request with `items: [3 lines]}`** to `routes.cart_add_url + '.js'` (atomic: all 3 or none) |
| Line item properties | `Bundle` (visible, e.g. "3-Pack Bundle #a1b2c3"), `_bundle_id` (hidden, UUID), `_bundle_group` (hidden) link the three lines |
| Stock / loading states, no reload | Sold-out products disabled + badge; sold-out variants disabled; group-level "all sold out" note; button `aria-busy` + "Adding…", fieldsets disabled while in flight; API errors (incl. 422 stock) and network failures shown in a `role="alert"` region |
| Lean | Vanilla JS custom element (re-inits correctly in the theme editor), separate CSS file, no libraries |

### Decisions
- **Custom element** instead of global init code: zero setup, works with section re-renders.
- **`aria-disabled` instead of `disabled`** on the CTA so keyboard users can still focus it and hear why it can't submit.
- Money handled as minor units (`variant.price`) and formatted with `Intl.NumberFormat` using the shop currency/locale.
- Strings in the JS/Liquid are English; move to `locales/*.json` for a multi-language store.

### Limitations / assumptions
- **The bundle discount is display-only.** `/cart/add.js` cannot set prices. To actually charge the discounted price, add an automatic discount or a Shopify Function (Discount Function) that matches lines with the `_bundle_id` property (3 lines sharing one id).
- Zero-decimal currencies (e.g. JPY) would need the `/100` in `bundle-builder.js` adjusted.
- Products per group are capped by the `products_per_group` setting (max 12) to keep the DOM small.

---

## Manual QA checklist
- [ ] WP: edit a service, "recently updated" order changes; price/escaping with `<script>` in title is inert
- [ ] WP: keyboard only, Tab/Arrow/Space toggles; total announced politely
- [ ] Shopify: sold-out product not selectable; sold-out variant not selectable
- [ ] Shopify: pick 3 → Add → 3 cart lines share `_bundle_id`; offline → error message shown
- [ ] Both at 375px width, no horizontal scroll
