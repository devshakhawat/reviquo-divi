=== ReviQuo ===
Requires at least: 6.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 3.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Collect richer WooCommerce product reviews with customer videos, photos, verified-buyer labels, review filters, and automated email reminders.

== Description ==

ReviQuo extends the standard WooCommerce review form with webcam recording, video-file uploads, and product-photo uploads. Customers can add visual proof without leaving the product page.

The storefront experience helps visitors explore that feedback with a rating summary, star and media filters, verified-customer badges, and a lightbox for uploaded media. Automated reminder emails help bring customers back after an order to share their experience.

The plugin uses native WooCommerce reviews, so review moderation and familiar store workflows remain intact.

The WooCommerce Marketplace edition includes every feature. It contains no separate license manager, updater, advertising, telemetry, or upgrade prompts. Updates are delivered through WooCommerce.com.

== Features ==

* Record customer video directly from the WooCommerce review form
* Upload pre-recorded video files and up to five product photos
* Preview recorded media before submitting a review
* Set a maximum webcam recording length from 1 to 10 minutes
* Show a rating summary and filter reviews by rating or attached media
* Identify reviews from confirmed purchasers with a verified-customer badge
* Open review photos and videos in a responsive lightbox
* Build reusable list, grid, masonry, slider, media gallery, testimonial, spotlight, compact list, reels, marquee, timeline, accordion, or chat bubbles review shortcodes with product targeting and a live preview
* Insert saved review displays with a native Gutenberg block and live editor preview
* Add saved review displays with a dedicated Elementor widget and searchable dropdown
* Add saved review displays with native Divi 4 and Divi 5 modules and live Visual Builder previews
* Use approved reviews and saved displays from headless storefronts through a public read-only REST API
* Send multiple status-based review reminders with independent timing and messages
* Add aggregate-rating and review data to product structured data
* Require video recording, video upload, or photo upload before review submission
* Request consent with editable privacy text
* Moderate media reviews from a dedicated WordPress admin screen

== Requirements ==

* WordPress 6.8 or later
* PHP 7.4 or later
* WooCommerce
* HTTPS for in-browser camera and microphone recording

== Installation ==

1. Install and activate WooCommerce.
2. Upload `reviquo.zip` under Plugins > Add New > Upload Plugin.
3. Activate ReviQuo.
4. Make sure product reviews are enabled under WooCommerce > Settings > Products.
5. Open ReviQuo > Review Settings and configure the General, Storefront, Appearance, Recording Modal, and Email reminders tabs.
6. Optionally, create a reusable review display under ReviQuo > Review Shortcode.
7. Visit a product page and submit a test review before opening review collection to customers.

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
* Added: Slider layout for saved review displays, with responsive columns, optional autoplay, arrows, pagination dots, swipe and keyboard navigation, and automatic loading of more reviews
* Added: Slider previews in the shortcode builder, block editor, Elementor, and Divi 4/5 Visual Builders
* Removed: The redundant fullscreen button in the shortcode builder's live preview
* Fixed: The Load More button never appeared for saved review displays with more reviews than one page
* Fixed: Rating and media filters were hidden in saved review displays

= 3.1.0 - 2026-09-11 =
* Added: Deleting a review permanently now also deletes the media uploaded with it
* Improved: Review uploads are limited to MP4, WebM, MKV, JPEG, PNG, GIF and WebP files within the site's upload size limit
* Improved: Security hardening with a plugin-specific review form nonce and sanitized bulk-action input
* Improved: Store admins are now notified about photo-only reviews awaiting approval, not just video reviews
* Improved: Storefront messages, default button labels and the default reminder email are now translatable
* Fixed: A required video, file or photo rule no longer blocks submission while its uploader is turned off
* Fixed: Review media is now attached to the reviewed product in the Media Library

= 3.0.6 - 2026-08-22 =
* Added: Dedicated WooCommerce Marketplace build with all features enabled and no external updater, licensing SDK, advertising, telemetry, trials, or upgrade prompts
* Added: WooCommerce HPOS (High-Performance Order Storage) compatibility declaration
* Added: A WooCommerce dependency notice with install and activation actions
* Improved: Rewrote the legacy reminder migration on WooCommerce order APIs so it works on HPOS stores
* Improved: Prevented ReviewFlow from initializing until WooCommerce is active
* Changed: Set the default Start Recording and Record Video button text and hover colours to white

= 3.0.5 - 2026-08-21 =
* Added: Required photo-upload rules for review submissions
* Added: Multiple status-based review reminder rules with independent timing, subjects, and messages
* Added: Live email reminder preview with representative customer, order, store, and product data
* Added: Responsive masonry layout for review shortcodes
* Added: Native Gutenberg Product Reviews block with a persistent saved-shortcode dropdown and live editor preview
* Added: Block toolbar actions, shortcode conversion, copy controls, and direct links for managing saved review displays
* Added: Dedicated Elementor Product Reviews widget with a searchable saved-shortcode dropdown and live builder output
* Added: Native Divi 5 Product Reviews module with a saved-shortcode dropdown and live Visual Builder preview
* Added: Legacy Divi 4 Product Reviews module with the same saved-shortcode dropdown and Visual Builder preview
* Added: Public read-only REST API endpoints for approved reviews and saved review displays
* Improved: Refreshed the dashboard, reminder editor, and storefront review displays with the updated brand palette
* Improved: Loaded storefront assets for pages containing the Product Reviews block and matched its iframe preview to the frontend display
* Changed: Renamed the plugin display name to ReviewFlow
