Elegant Themes Marketplace listing assets

* product-featured-1426x796.jpg — required featured image, RGB JPEG, 1426×796. Carries the headline copy in the left column.
* product-featured-clean.jpg — the same artwork with no text, kept so the copy can be re-typeset without re-generating the illustration.
* product-featured-with-text.png — lossless copy of the finished featured image.
* product-square-500x500.jpg — required square product image, RGB JPEG, 500×500.
* module-icon-16x16.svg — required one-color Divi module icon, 16×16, #2b87da.
* product-featured-source.png and product-square-source.png — generated editable raster sources.
* screenshots/ — seven genuine plugin screenshots of the current admin UI, in listing order:
    1. Review shortcode builder, masonry layout, with the live preview
    2. The same builder switched to the grid layout, showing the rating summary
    3. Review Shortcodes list — reusable saved displays
    4. Storefront settings — rating summary, filters, rich snippets, verified badge
    5. Appearance settings — button styling with the live preview panel
    6. Recording modal settings
    7. Email reminders — per-status reminder rules

  The captures come from a Freemius-enabled dev install, so `bin/marketplace-screenshots.py`
  removes what the marketplace edition does not ship: the "Pro" tier badge and the
  "License" button in the plugin header, and the Freemius sidebar entries (Account,
  Contact Us, Support Forum, Pricing, and "Freemius Debug"). Sidebar entries are removed
  by deleting the band they occupy and sliding the rest of the menu up, so it still reads
  as a real WordPress menu. Re-run that script after re-capturing rather than editing the
  PNGs by hand. `.wordpress-org/screenshots` is a separate set for the WordPress.org
  listing, where the Pro badge and the licensing UI are real and must stay.

The featured image copy is composited by `bin/featured-image-text.py`, which reads
product-featured-clean.jpg and writes the JPEG and PNG above. Re-run it after any
wording change rather than drawing over the finished JPEG, which would double the text.

The featured and square images were generated with OpenAI's built-in image generation tool on 2026-08-26. The final JPEGs were mechanically resized/cropped with Pillow to the exact Elegant Themes dimensions. The SVG is an original code-native asset.
