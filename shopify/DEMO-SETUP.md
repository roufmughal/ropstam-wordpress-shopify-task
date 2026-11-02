# Shopify demo setup

This checklist uses sample products and does not require credentials in the repository.

## Catalog

Create these three collections and place the products below in the matching collection. Create products as active and available to the Online Store sales channel unless marked sold out.

| Collection / builder group | Product | Price | Inventory / variants |
|---|---|---:|---|
| Bundle Group 1 - Apparel | Demo Item 1 - Everyday Tee | Rs. 2,500 | 10 |
| Bundle Group 1 - Apparel | Demo Item 1 - Hoodie | Rs. 3,400 | 10 |
| Bundle Group 1 - Apparel | Demo Item 1 - Cap | Rs. 1,800 | 0 (sold out) |
| Bundle Group 2 - Accessories | Demo Item 2 - Canvas Tote | Rs. 1,500 | 10 |
| Bundle Group 2 - Accessories | Demo Item 2 - Water Bottle | Rs. 1,900 | Two variants: Black and Blue; 5 each |
| Bundle Group 2 - Accessories | Demo Item 2 - Keychain | Rs. 500 | 10 |
| Bundle Group 3 - Drinkware | Demo Item 3 - Ceramic Mug | Rs. 1,200 | 10 |
| Bundle Group 3 - Drinkware | Demo Item 3 - Travel Mug | Rs. 2,200 | 10 |
| Bundle Group 3 - Drinkware | Demo Item 3 - Coaster Set | Rs. 900 | 10 |

Set the section's three collection settings to the matching collections. The section can also use hand-picked product lists, which override a collection.

## Discount code fallback

1. In **Shopify admin → Discounts**, create a code such as `BUNDLE10`.
2. Configure a 10% product discount with a minimum quantity of 3 items, limited to the three bundle collections. Match the discount percentage to **Bundle discount (%)** in the section.
3. In the theme editor, set **Shopify discount code** to `BUNDLE10`. The section will then display the discounted estimate and apply the code after the bundle's cart-add request succeeds.
4. Test with one in-stock item from each group. Confirm the discount in the cart/checkout. Remove one qualifying item and confirm that the cart no longer meets the three-item minimum.
5. Test the sold-out cap and both Water Bottle variants. The sold-out item/variant should be unavailable; selecting a different variant should update the displayed price.
6. Confirm each cart line displays its `Bundle` property and does not display the underscore-prefixed `_bundle_id` or `_bundle_group` properties. Add a second bundle using the same product variants and confirm its distinct bundle ID keeps its lines separate.

This discount-code fallback is not bundle-aware: any three eligible products in the cart can qualify, including products added outside the builder. A Discount Function that validates three cart lines sharing one `_bundle_id` is the exact solution when the store supports custom-app Functions.

Never commit store passwords, admin credentials, or API tokens.
