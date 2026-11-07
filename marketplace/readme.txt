=== ReviQuo ===
Requires at least: 6.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 3.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Collect customer video and photo reviews, then place responsive saved review displays anywhere with native Divi 4 and Divi 5 modules.

== Description ==

ReviQuo enhances WooCommerce's native review system with webcam recordings, video uploads, up to five customer photos, rating summaries, review filters, verified-customer badges, media lightboxes, rich structured data, reusable review displays, and automated reminder emails.

The Elegant Themes Marketplace edition includes every feature. It contains no separate license manager, updater, advertising, telemetry, or upgrade prompts. Updates are delivered through the Divi Marketplace update system.

== Requirements ==

* WordPress 6.8 or later
* PHP 7.4 or later
* WooCommerce
* Divi Theme or Divi Builder for the Divi modules; shortcode, block, and Elementor integrations remain available outside Divi
* HTTPS for in-browser camera and microphone recording

== Installation ==

1. Install and activate WooCommerce and Divi.
2. Upload `reviquo.zip` under Plugins > Add New > Upload Plugin.
3. Activate ReviQuo.
4. Enable product reviews under WooCommerce > Settings > Products.
5. Configure ReviQuo > Review Settings.
6. Create a saved display under ReviQuo > Review Shortcode.
7. Add the Product Reviews module in the Divi Visual Builder and select the saved display.

== Divi Integration ==

The plugin registers native modules for both Divi 4 and Divi 5. Each module includes a saved-display selector with help text and a live, server-rendered Visual Builder preview. Divi 4 module content is mapped to the Divi 5 module through the declared legacy shortcode name.

== Privacy ==

Review media is stored locally in the WordPress media library. Reminder information is stored in the site's WordPress database. The plugin registers personal-data exporters, erasers, and suggested Privacy Policy Guide text. Permanently deleting a review removes plugin-owned media uploaded with that review.

== Changelog ==

= 3.3.0 - 2026-11-06 =
* Added: Media Gallery layout for saved review displays: media-first tiles for reviews with photos or videos that open in the lightbox.
* Added: Testimonial layout: centered quote cards with the customer's avatar, name, and product below.
* Added: Spotlight layout: the first review featured large beside a column of compact cards.
* Added: Compact List layout: dense rows with a one-line meta row, a two-line excerpt, and small media thumbnails.
* Added: Reels layout: vertical video tiles in a horizontally scrolling row, with muted preview playback on hover.
* Added: Marquee layout: an endlessly scrolling strip of review cards that pauses on hover and stays still for reduced-motion visitors.
* Added: Timeline layout: reviews alternating either side of a dated vertical line.
* Added: Accordion layout: collapsible review rows that expand to the full review and media, one open at a time.
* Added: Chat Bubbles layout: reviews as alternating speech bubbles beside each customer's avatar.

= 3.2.1 - 2026-10-17 =
* Added: Slider layout for saved review displays, with responsive columns, optional autoplay, arrows, pagination dots, swipe and keyboard navigation, and automatic loading of more reviews.
* Added: Slider previews in the shortcode builder, block editor, Elementor, and Divi 4/5 Visual Builders.
* Removed: The redundant fullscreen button in the shortcode builder's live preview.
* Fixed: The Load More button never appeared for saved review displays with more reviews than one page.
* Fixed: Rating and media filters were hidden in saved review displays.

= 3.1.0 - 2026-09-11 =
* Added: Option to hide the review text in saved review displays.
* Added: Separate photo and video visibility controls for saved review displays.
* Improved: Legacy reminder migration now uses WooCommerce order APIs and works on HPOS stores.
* Improved: Redesigned Video Reviews admin list with a combined Media column, lightbox thumbnails and status badges.
* Improved: Security hardening with prepared database queries, sanitized nonce checks, safe redirects and escaped output.
* Improved: Review uploads also accept MKV video files.
* Improved: Clearer corner-radius and backdrop-opacity controls in Recording Modal settings.
* Fixed: The WooCommerce-required notice no longer appears on plugin install and update screens.
* Fixed: Review reminder emails skip order items that are not products.
* Changed: New review displays default to All products.

= 3.0.5 - 2026-08-26 =
* Added: Dedicated Elegant Themes Marketplace build with all features enabled and no external updater, licensing SDK, advertising, telemetry, trials, or upgrade prompts.
* Added: Exact marketplace featured, square, and Divi module icon assets.
* Added: WooCommerce HPOS compatibility declaration and complete plugin requirement headers.
* Added: Privacy Policy Guide content and review-media cleanup when comments are permanently deleted.
* Improved: Restricted anonymous media uploads to approved extensions, MIME types, site upload limits, and nonce-verified product review requests.
* Improved: Made the settings dashboard responsive for narrow WordPress admin viewports.
* Improved: Localized public recording, gallery, filter, and validation messages.
* Fixed: Attached uploaded media to the reviewed product and recorded ownership metadata for safe cleanup.
* Fixed: Restored normal WordPress and third-party admin notices on plugin pages.
* Fixed: Pointed the admin Docs and Help links at the marketplace instead of the WordPress.org listing.
* Fixed: Removed the Free/Pro tier badge from the admin header, which has no meaning in the marketplace edition.
* Fixed: Made the review form nonce and the settings AJAX action plugin-specific so they cannot collide with another plugin.
* Fixed: Made the storefront button labels, the default reminder email body, the admin menu entry and the settings save notices translatable.
* Fixed: Limited migration debug logging to WP_DEBUG installations.
