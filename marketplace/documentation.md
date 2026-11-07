# ReviQuo — Documentation

Documentation for the **Divi Marketplace edition** of ReviQuo, version **3.1.0**.

This edition includes every feature the plugin implements. There is no free tier, no
license manager, no separate premium build and no upgrade prompts. Updates are delivered
through the Divi Marketplace update system.

## Contents

1. [What the plugin does](#1-what-the-plugin-does)
2. [Requirements and compatibility](#2-requirements-and-compatibility)
3. [What this edition includes](#3-what-this-edition-includes)
4. [Installation and first-time setup](#4-installation-and-first-time-setup)
5. [Admin navigation and permissions](#5-admin-navigation-and-permissions)
6. [Review Settings: General](#6-review-settings-general)
7. [Review Settings: Storefront](#7-review-settings-storefront)
8. [Review Settings: Appearance](#8-review-settings-appearance)
9. [Review Settings: Recording Modal](#9-review-settings-recording-modal)
10. [Saving and resetting settings](#10-saving-and-resetting-settings)
11. [Customer review workflow](#11-customer-review-workflow)
12. [Moderating media reviews](#12-moderating-media-reviews)
13. [Native product-page enhancements](#13-native-product-page-review-enhancements)
14. [Reusable review displays](#14-reusable-review-displays)
15. [Divi 4 and Divi 5 modules](#15-divi-4-and-divi-5-modules)
16. [Email review reminders](#16-email-review-reminders)
17. [Headless REST API](#17-headless-rest-api)
18. [Privacy and stored data](#18-privacy-and-stored-data)
19. [Developer reference](#19-developer-reference)
20. [Troubleshooting](#20-troubleshooting)
21. [Recommended launch checklist](#21-recommended-launch-checklist)

## 1. What the plugin does

ReviQuo extends WooCommerce's native product-review system. It does not create a
separate review type: review text, ratings, approval state, author information and purchase
verification continue to use WordPress comments and WooCommerce review APIs.

The plugin adds:

- In-browser webcam and microphone recording on product review forms.
- Upload of an existing video file.
- Upload of as many as five photos per review.
- Client-side previews for recorded videos, uploaded videos and photos.
- Optional rules that require recorded video, uploaded video or at least one photo.
- An optional required consent checkbox with editable text.
- A dedicated media-review moderation screen.
- An average-rating summary, rating filters, media filters, verified-customer badges and a media lightbox.
- Reusable review displays that can be inserted with a shortcode, Gutenberg block, Elementor widget or Divi module (Divi 4 and Divi 5).
- Status-based review-reminder emails with delayed delivery and unsubscribe handling.
- Media enrichment for WooCommerce product/review structured data.
- A public, read-only REST API for headless storefronts.
- WordPress personal-data export and erasure integration.

## 2. Requirements and compatibility

- WordPress 6.8 or later.
- PHP 7.4 or later.
- WooCommerce installed and active.
- Product reviews enabled in **WooCommerce > Settings > Products**.
- Divi Theme or Divi Builder for the Divi 4 and Divi 5 modules. The shortcode, Gutenberg
  block and Elementor widget work without Divi, so the plugin is usable on a non-Divi site.

The plugin declares WooCommerce HPOS (High-Performance Order Storage) compatibility.

Webcam recording additionally requires:

- A browser with `navigator.mediaDevices.getUserMedia`, `MediaRecorder`, `File` and `DataTransfer` support.
- A secure page served over HTTPS. Browsers normally permit camera access on `localhost` during development as well.
- Customer permission to use the camera and microphone.

The upload size is not configured by this plugin. The effective maximum is determined by PHP,
the web server, WordPress and any host-level media limits. For large video uploads, check
`upload_max_filesize`, `post_max_size`, request timeouts and the WordPress upload limit.

## 3. What this edition includes

Every feature is enabled from activation. Specifically, the following are available with no
license step, no trial and no unlock:

- Webcam recording with a maximum recording length of one to ten minutes.
- Existing video upload and up to five photos per review.
- Required-media rules and the consent checkbox.
- Media moderation screen.
- Product-page rating summary, rating filters and media filters.
- Saved review displays in **list, grid, masonry and slider** layouts.
- Verified-customer badges.
- Review-media structured-data (rich snippet) enrichment.
- **Unlimited** email reminder rules.
- Shortcode, Gutenberg block, Elementor widget, Divi 4 module, Divi 5 module and REST API.

This edition contains no license manager, no external updater, no advertising, no telemetry,
no trials and no upgrade prompts, and it makes no outbound HTTP requests of its own.

## 4. Installation and first-time setup

1. Install and activate WooCommerce.
2. Go to **Plugins > Add New > Upload Plugin** and upload `reviquo.zip`.
3. Activate **ReviQuo**.
4. In **WooCommerce > Settings > Products**, make sure product reviews and ratings are configured as required by the store.
5. Open **ReviQuo > Review Settings**.
6. Configure the General, Storefront, Appearance, Recording Modal and Email reminders tabs.
7. Create a saved display under **ReviQuo > Review Shortcode**.
8. Add the **Product Reviews** module in the Divi Visual Builder and select the saved display.
9. Open a public product page in a private browser window and submit a test review.
10. Verify the uploaded media, moderation status, public display, email delivery and mobile layout before inviting customers to use the feature.

Activation creates or updates the email-reminder database table and schedules migration of
older reminder data when needed.

## 5. Admin navigation and permissions

The plugin adds this WordPress admin menu:

- **ReviQuo > Review Settings** — collection, display, design, privacy and reminder configuration.
- **ReviQuo > Review Shortcode** — saved reusable review displays.
- **ReviQuo > Reviews** — reviews containing a plugin video or photo attachment.
- **ReviQuo > Email Reminders** — reminder delivery history and scheduled-item management.

These screens require the `manage_options` capability, which normally limits them to
administrators. Individual media-review moderation operations also check `moderate_comments`.

The settings page leaves normal WordPress and third-party admin notices intact so security,
update and compatibility messages remain visible.

## 6. Review Settings: General

Open **ReviQuo > Review Settings > General**.

### Review collection

**Webcam recording**

Adds a **Record Video** control to the WooCommerce review form. Selecting it opens a recording
modal, asks for camera and microphone permission, shows a live preview, records using the
browser's supported WebM or MP4 format, and lets the customer preview or record again before
attaching the result.

**Require before submitting** under Webcam recording

Requires a browser-recorded video. The plugin validates this in JavaScript for immediate
feedback and again on the server so the requirement cannot be bypassed by disabling
JavaScript. The requirement is enforced only while Webcam recording is enabled.

**Maximum recording length**

Sets an automatic stop time, from one to ten minutes. The default is two minutes. The timer is
displayed in the modal while recording. Longer recordings mean larger files — check the
hosting upload limits described in section 2 before raising this.

**Video file upload**

Adds a separate control for an existing video. The customer sees the selected filename and a
video preview and can clear the selection before submitting.

The implementation accepts MP4 and WebM MIME types. Browser and server MIME detection still
determine whether WordPress accepts a particular file.

**Require before submitting** under Video file upload

Requires a file in the separate upload control. This is independent of the webcam requirement:
if both switches are enabled, the customer must supply both a recording and an uploaded video.

**Photo upload**

Allows JPEG, PNG, GIF and WebP files. The customer can add files in multiple selections, see
thumbnails, remove individual photos, and submit no more than five photos per review. The
server also processes no more than five.

**Require before submitting** under Photo upload

Requires at least one photo. As with the video rules, it applies only while the photo uploader
is enabled. If all three required-media settings are active, all three requirements must be
satisfied.

### Moderation

**Auto-approve videos**

Despite the label, this switch controls reviews with any plugin media: recorded video,
uploaded video or photos.

- Off by default: media reviews are forced to Pending regardless of the normal WordPress comment default.
- On: the plugin leaves the approval decision to the normal WordPress/WooCommerce comment rules. It does not unconditionally approve a comment that another moderation rule marks as pending, spam or trash.
- Reviews without plugin media always follow the normal WordPress/WooCommerce moderation workflow.

For a pending review that contains video, the plugin emails the site's admin address and
creates a temporary admin notice. Photo-only reviews enter the same moderation queue, but the
dedicated pending-review email is currently sent only when uploaded video metadata exists.

## 7. Review Settings: Storefront

Open **ReviQuo > Review Settings > Storefront**.

### Display

**Rating summary**

Displays the product's average rating, WooCommerce stars, total review count and a
five-to-one-star histogram above the native WooCommerce review list. It appears only on
product pages, only when WooCommerce ratings are enabled, and only when at least one review
exists.

**Review filters**

Displays **All**, one-to-five-star and **With photos / videos** controls above the native
review list. The filter bar appears only when the product has at least two reviews. Rating
options with no matching reviews are disabled, and the media control appears only when at
least one approved review has plugin media.

Filtering on the native product review list happens in the browser against the reviews
currently rendered by WooCommerce. It does not fetch additional WooCommerce comment pages.

**Rich snippets**

Extends WooCommerce's existing product/review JSON-LD with uploaded media. It does not emit a
duplicate Product schema block.

- Review photos are added as `image` URLs.
- Review videos are added as `VideoObject` values containing a name, description, content URL and upload date.
- When the review also has a photo, its first photo is used as the video's `thumbnailUrl`.
- WooCommerce's product schema normally includes its five most recent rated reviews; this plugin enriches those entries.

This feature supplies structured data but cannot guarantee that a search engine will show a
rich result.

**Verified badge**

Displays a styled **Verified Customer** badge beside the review author and date when
WooCommerce confirms that the reviewer owns the product. It uses WooCommerce's
`wc_review_is_from_verified_owner()` result. The plugin hides WooCommerce's plain
"verified owner" label when the styled badge is enabled.

### Privacy

**Consent checkbox**

Adds a required checkbox to the review form. The configured text is shown beside it. When
submitted, the plugin saves the current site-local date and time in review comment metadata as
evidence that the checkbox was selected.

Enabling this option is a technical consent mechanism, not a complete privacy or legal
compliance program. Store owners should adapt the text and privacy policy to their
jurisdiction and actual data practices.

## 8. Review Settings: Appearance

Open **ReviQuo > Review Settings > Appearance**.

There are separate design panels for:

- Record Button.
- Video Upload Button.
- Photo Upload Button.
- Verified Customer Badge.

Each of the three button panels has a live preview and controls for:

- Background and text colors.
- Hover background and hover text colors.
- Button label.
- Icon or no icon.
- Border style: none, solid, dashed or dotted.
- Border width from 0 to 10 px.
- Normal and hover border colors.
- Border radius from 0 to 50 px.
- Font family, including theme default, common system/sans-serif fonts, serif fonts and monospace.
- Font weight from 300 through 800.
- Normal or italic style.
- Uppercase, capitalize, lowercase or unchanged text.
- Vertical padding from 0 to 40 px.
- Horizontal padding from 0 to 80 px.
- Font size from 10 to 24 px.
- Icon size from 10 to 32 px.

Available icon choices include video camera, camera, microphone, play, star, comment, pencil,
video file, picture, upload, stop, add and none.

The verified badge panel controls badge font size from 10 to 24 px, background color, and
text/icon color. The display switch itself is under the Storefront tab.

## 9. Review Settings: Recording Modal

Open **ReviQuo > Review Settings > Recording Modal**.

### Modal Design

Configure:

- Title text, color and icon.
- Modal background color.
- Modal corner radius from 0 to 30 px.
- Backdrop color.
- Backdrop opacity from 0% to 95%.

### Modal action buttons

The **Start Recording Button**, **Stop Recording Button** and **Add Video Button** each have
the same color, content, border, typography, spacing, size, icon and live-preview controls
described for the storefront buttons.

Customers can dismiss the recording modal with its close button, by selecting the backdrop, or
by selecting outside the modal. Closing the modal does not submit the review.

## 10. Saving and resetting settings

Use the page's **Save changes** action after editing. Email reminder settings use their own
save request; the other tabs share the main review-settings record.

**Reset settings** restores both review settings and email-reminder settings to defaults. It
also cancels every currently scheduled reminder. Important defaults include:

- All three capture methods enabled.
- No media type required.
- Media auto-approval off.
- Recording duration two minutes.
- Rating summary and filters on.
- Consent off.
- Email reminders off with one enabled Completed-status rule configured for a seven-day delay.

Resetting does not delete reviews, uploaded attachments, sent/cancelled reminder history,
saved review displays or customer opt-outs.

## 11. Customer review workflow

On a standard WooCommerce product page, a customer:

1. Chooses a star rating when ratings are enabled.
2. Writes review text. The plugin keeps this field required.
3. Optionally records a video, uploads an existing video and/or attaches photos according to the enabled methods.
4. Checks the consent box when enabled.
5. Submits the normal WooCommerce review form.

Uploaded files are passed through the WordPress media upload system and become Media Library
attachments, attached to the reviewed product and marked with plugin ownership metadata so
they can be cleaned up safely. Their public attachment URLs are associated with the review
comment. Review text and rating are still stored by WordPress/WooCommerce.

Anonymous submissions are restricted to approved file extensions and MIME types, the site's
upload limits, and nonce-verified product review requests.

After approval, videos and photos appear beneath the review text as thumbnails. Selecting a
thumbnail opens a responsive lightbox. The lightbox supports previous/next controls, Escape to
close, and Left/Right arrow navigation. Videos play with normal browser controls inside the
lightbox.

## 12. Moderating media reviews

Open **ReviQuo > Reviews**. This screen includes reviews that carry video or photo
metadata and provides:

- Author avatar, name and email.
- Review excerpt and current status.
- Product and storefront links.
- Star rating.
- First-video preview and video count.
- Photo thumbnails and photo count.
- Submission date and time.
- Search by review text, author or product title.
- Sorting by author, product or date.
- Status views for approved, pending, spam and trash.
- Pagination at 20 reviews per page.

Per-review and bulk actions include Approve, Unapprove, Mark as Spam, Not Spam, Move to Trash
and Restore. The Edit action opens WordPress's normal comment editor, while View opens the
public comment link.

Trashing a review does not immediately delete its uploaded attachments. Permanently deleting a
review removes the plugin-owned media uploaded with it. Use the WordPress privacy eraser when
fulfilling a personal-data erasure request.

## 13. Native product-page review enhancements

The native WooCommerce review area can show:

- Aggregate score and star distribution.
- Rating and media filters.
- Verified Customer badges.
- Video/photo thumbnail galleries and lightbox viewing.
- The normal WooCommerce "awaiting approval" message for a review visible to its submitter.

These enhancements rely on standard WooCommerce review template hooks. A theme that completely
replaces the review form, review metadata or review list without running WooCommerce hooks may
prevent some features from appearing.

Public CSS and JavaScript are loaded on WooCommerce product pages and pages whose main content
contains the plugin shortcode or block. Rendering the shortcode from a widget or builder also
enqueues the assets as a fallback. The frontend interactions require jQuery.

## 14. Reusable review displays

Open **ReviQuo > Review Shortcode** to create named displays. A display is a reusable
configuration stored privately in WordPress; its generated shortcode contains only the saved
display ID. Change the display once and every module, block, widget and shortcode using it
updates.

### Managing displays

The list screen supports:

- Search by display name or shortcode.
- Filter by source or layout.
- Copy the generated shortcode.
- Edit, duplicate or trash one display.
- Select and trash multiple displays.

Trashing a display makes its shortcode, block, Elementor widget and Divi modules render no
output. It does not delete any customer reviews.

### Display configuration

**Shortcode Name** is the private admin label.

**Layout** supports List, Grid, Masonry, and Slider. Masonry cards pack responsively while retaining
left-to-right review order.

**Slider** shows a full page of cards at a time using the desktop, tablet and mobile column
settings, and adds these options:

- Autoplay, with a delay of 1–20 seconds. Autoplay pauses while the pointer is over the slider
  and is disabled for visitors who prefer reduced motion.
- Arrows — previous/next buttons beside the cards.
- Dots — pagination below the cards.

Visitors can also swipe or use the arrow keys. The slider has no Load More button: reaching the
last card loads the next set of reviews automatically, and once every review is loaded it
rewinds to the first card.

**Visibility controls** independently show or hide:

- Rating Summary.
- Review Filters.
- Product Name.
- Customer Avatar.
- Verified Badge.
- Photos & Videos.

**Responsive design controls** include:

- Desktop, tablet and mobile columns: 1–4 each.
- Card gap: 0–48 px.
- Card radius: 0–32 px.
- Background, text, accent and star colors.

The renderer automatically chooses dark or white text for controls placed on the selected
accent color, based on relative contrast.

**Review Source** supports:

- Current product — uses the current WooCommerce product. On a non-product page there is no product context, so the public display shows no reviews.
- Selected products — one or more published, top-level WooCommerce products chosen in the builder.
- All products — all approved reviews on published WooCommerce products.

**Preview Product** supplies context for the builder's live preview, especially for a Current
product display. It does not change the saved source.

**Reviews Per Load** accepts 1–24 and defaults to 6.

**Order** supports Newest first, Oldest first, Highest rated and Lowest rated. Equal ratings
fall back to newest comment ID first.

The live preview displays representative sample reviews when the selected source has no
approved reviews; the public display does not show samples.

### Shortcode usage

Copy the generated value into any shortcode-aware location:

```text
[reviquo id="123"]
```

Only `id` is accepted as a shortcode attribute. All other behavior comes from the saved
display.

Long result sets use an AJAX **Load More** button (the slider loads more automatically instead). Filters perform server-side queries against
the full selected source and reset the current result page. Every display instance keeps its
own filter and page state, so multiple displays can coexist on one page.

### Gutenberg block usage

1. Create at least one saved display under **ReviQuo > Review Shortcode**.
2. Edit a post, page or product in the block editor.
3. Insert the **Product Reviews** block from the Widgets category.
4. Choose a saved Shortcode setting.
5. Use the toolbar to refresh the live preview or open the display editor.

The block is dynamic and stores only the saved display ID. It supports wide/full alignment and
anchors. Its inspector shows the selected source and layout and provides the equivalent
shortcode. If the display is deleted, the editor prompts for another selection and the
frontend renders nothing.

When the block is placed while editing a product, the editor passes that product as preview
context for Current product displays.

### Elementor usage

1. Create a saved review display.
2. Open Elementor and find the **Product Reviews** category.
3. Drag the **Product Reviews** widget into the layout.
4. Choose a display in **Shortcode setting**.

The widget uses the same server renderer, responsive layout, filters, lightbox and Load More
behavior as the shortcode. Elementor reloads its preview after selection changes. Plain-content
exports contain the equivalent `[reviquo]` shortcode.

## 15. Divi 4 and Divi 5 modules

The plugin registers native modules for both Divi 4 and Divi 5. Each module stores only the
saved display ID and loads an authenticated, server-rendered preview inside the Visual
Builder, so what you see while editing is what the storefront renders.

### Divi 5 usage

1. Create a saved review display.
2. Open a page or product in the Divi 5 Visual Builder.
3. Add the **Product Reviews** module.
4. Open **Content > Review Display** and choose a **Shortcode setting**.

On the frontend the module delegates to the same shortcode renderer, so responsive layouts,
filters, the media lightbox and Load More behavior remain consistent with the other
integrations. A Current product display receives product context when the module is used on a
WooCommerce product; it has no product context on an ordinary page.

The integration registers only when Divi 5 APIs are available and does not make Divi a plugin
dependency. If the saved display is trashed, the Visual Builder reports that it is unavailable
and the frontend renders no module output.

### Divi 4 usage

Sites running the legacy Divi 4 builder get an equivalent **Product Reviews** module:

1. Create a saved review display.
2. Open a page or product in the Divi Builder.
3. Add the **Product Reviews** module.
4. Choose a **Shortcode setting** in the module's Content tab.

The Divi 4 module registers on `et_builder_ready`, stores the saved display ID in the
`[sktpr_divi4_reviews]` module shortcode, and uses the same authenticated Visual Builder
preview endpoint and frontend shortcode renderer as the Divi 5 module.

### Migrating a layout from Divi 4 to Divi 5

When the Divi 5 builder is enabled, only the Divi 5 module loads in the Visual Builder. The
Divi 5 module declares `sktpr_divi4_reviews` as its Divi 4 counterpart, so existing Divi 4
layouts are recognized. Reselect the display after migrating a page to Divi 5 if the selection
does not carry over.

## 16. Email review reminders

Open **ReviQuo > Review Settings > Email reminders**.

### Shared delivery settings

**Enable reminders** is the master switch. A valid From name, valid From email and at least one
enabled complete rule are required when it is on.

Turning the master switch off and saving marks every scheduled reminder as cancelled and clears
its individual WordPress Cron event. It does not delete reminder history.

The From address should use the store's domain and a properly configured transactional email
service. `wp_mail()` returning success means WordPress handed off the message; it does not
guarantee inbox delivery.

### Reminder rules

You can create as many rules as the store needs. Each rule has:

- An on/off switch.
- A WooCommerce order-status trigger.
- A delay from 0 to 365 days. Zero sends immediately during the status-change request.
- A required subject.
- Required HTML-capable content.
- An HTML/Preview view using representative sample data.

Each rule sends at most once per order because the database enforces a unique order-and-rule
combination. Moving an order out of and back into the same trigger status does not create
another reminder for that rule.

Deleting a rule does not cancel reminders already scheduled from it. A queued reminder stores a
snapshot of the sender, subject and content, so later edits or rule deletion do not alter that
queued email. To cancel queued items, use **ReviQuo > Email Reminders**, disable the
master reminder switch, or cancel/refund the order.

### Template placeholders

The subject supports `{customer_name}`, `{order_number}`, `{order_date}`, `{site_name}` and
`{site_url}`. The message body supports those five plus `{products_list}` and
`{unsubscribe_link}`:

| Placeholder | Replacement |
| --- | --- |
| `{customer_name}` | Customer billing first name when sending; the full billing name is retained in the reminder record. |
| `{order_number}` | WooCommerce order number. |
| `{order_date}` | Order creation date formatted with the WordPress date setting. |
| `{site_name}` | WordPress site name. |
| `{site_url}` | Site home URL. |
| `{products_list}` | HTML cards for reviewable products, each with image, name and Write Review link. |
| `{unsubscribe_link}` | Tokenized unsubscribe URL. |

If `{unsubscribe_link}` is absent from the message, the plugin automatically appends an
unsubscribe footer. `{products_list}` is replaced in the message body during delivery; include
it where the product review buttons should appear.

### Scheduling and delivery rules

When an order enters a matching status, the plugin:

1. Confirms reminders and the rule are enabled.
2. Rejects an invalid billing email.
3. Checks the hashed customer opt-out list.
4. Confirms the order contains at least one simple or variable product.
5. Stores a reminder row and a template snapshot.
6. Sends immediately for a zero-day delay or schedules a single WordPress Cron event.

Before sending, it rechecks the master switch, order existence, cancelled/refunded state,
unsubscribe state and reviewable products.

- Changing an order to Cancelled or Refunded cancels its scheduled reminders.
- Invalid email, opt-out, scheduling failure, successful delivery, failed delivery and manual cancellation are recorded in WooCommerce order notes where applicable.
- A failed reminder is marked Failed; there is no automatic retry after `wp_mail()` failure.
- An hourly fallback cron processes reminder rows that are due but whose individual event did not run.

Because WordPress Cron is traffic-driven by default, delivery can occur after the exact
scheduled time on a low-traffic site. For dependable timing, disable the default traffic-driven
runner only after configuring a real server cron to call WordPress Cron regularly.

### Reminder management screen

Open **ReviQuo > Email Reminders** to see up to the first 100 records ordered by
scheduled time. Columns show order, customer, email, trigger, scheduled time, status and
actions.

Statuses are Scheduled, Overdue, Sent, Cancelled and Failed. Scheduled rows can be cancelled
individually. The order link opens the WooCommerce order, and sent rows show their sent time.

### Customer unsubscribe behavior

Each email contains a signed unsubscribe URL. Selecting it:

- Validates an HMAC token tied to the email address.
- Adds a one-way hash of the address to the opt-out option.
- Cancels scheduled reminders for that address.
- Shows a confirmation page without requiring the customer to log in.

The hash-only opt-out record is intentionally retained during personal-data erasure so the
customer's preference continues to be honored.

## 17. Headless REST API

The namespace is:

```text
/wp-json/sktpr/v1
```

All endpoints below are public, unauthenticated, read-only `GET` endpoints. They expose only
approved, top-level WooCommerce reviews attached to published products. Pending/spam/trash
comments, customer emails, IP addresses and user-agent values are not returned.

### Endpoints

| Endpoint | Purpose |
| --- | --- |
| `/reviews` | Paginated reviews across all or selected products. |
| `/reviews/{id}` | One approved review. |
| `/products/{product_id}/review-summary` | Aggregate data for one product. |
| `/review-displays` | Paginated published saved displays. |
| `/review-displays/{id}` | One saved display and its effective public settings. |
| `/review-displays/{id}/reviews` | Reviews using the display's saved source, order and page size. |

### `/reviews` query parameters

| Parameter | Default | Notes |
| --- | --- | --- |
| `product_id` | `0` | One published, top-level product ID. |
| `product_ids` | empty | Comma-separated IDs or repeated/array values supported by WordPress request parsing. Can be combined with `product_id`. |
| `page` | `1` | Minimum 1. |
| `per_page` | `10` | 1–100. |
| `rating` | unset | Integer 1–5. |
| `media` | `false` | Boolean; limits results to reviews with a video or photo. |
| `order` | `newest` | `newest`, `oldest`, `highest` or `lowest`. |

Examples:

```text
GET /wp-json/sktpr/v1/reviews?product_id=123&rating=5&media=true&per_page=12
GET /wp-json/sktpr/v1/reviews?product_ids=12,34,56&order=highest&page=2
GET /wp-json/sktpr/v1/products/123/review-summary
```

### Collection response shape

`/reviews` returns:

- `reviews` — normalized review objects.
- `summary` — totals for the full requested product scope, not only the current page.
- `pagination` — page, page size, result total and total pages.
- `filters` — normalized product IDs, rating, media flag and order.
- `X-WP-Total` and `X-WP-TotalPages` response headers.

Each review object includes:

- `id`, local ISO date, GMT ISO date and integer rating.
- `content.rendered` and `content.plain`.
- Public author name, 96-pixel avatar URL and WooCommerce verified-owner boolean.
- Product ID, name, slug and permalink.
- `media.images` and `media.videos` URL arrays.
- Self and product links.

The summary includes `total`, `rated`, two-decimal `average`, counts keyed 1 through 5, and
`with_media`.

### Saved display endpoints

`/review-displays` accepts `page` and `per_page` (default 20, maximum 100) and returns display
objects plus pagination fields and headers.

A display object includes its ID, name, GMT modification date, effective settings, and links to
itself and its reviews.

`/review-displays/{id}/reviews` accepts `page`, `rating`, `media` and `context_product_id`. The
saved display supplies `order` and `per_page`; clients cannot override them on this endpoint.

For a display whose source is Current product, `context_product_id` is required:

```text
GET /wp-json/sktpr/v1/review-displays/45/reviews?context_product_id=123&media=true
```

The response is the normal review collection plus a `display` object. Invalid or unpublished
products/displays return 404; omitting required product context returns 400.

The site remains responsible for its desired REST API cache headers, CORS policy, rate limiting
and CDN behavior.

## 18. Privacy and stored data

### Storage locations

| Data | Storage |
| --- | --- |
| Review text, author, status, date | WordPress comments table. |
| Star rating | WooCommerce `rating` comment meta. |
| Video URLs | `uploaded_video_url` comment meta. |
| Photo URLs | `uploaded_image_url` comment meta. |
| Consent timestamp | `sktpr_gdpr_consent` comment meta. |
| Uploaded files | WordPress Media Library / uploads directory. |
| Main settings | `sktpr_review_settings` option. |
| Email settings and rules | `sktpr_email_reminder_settings` option. |
| Saved displays | Private `sktpr_review_sc` posts with `_sktpr_shortcode_settings` metadata. |
| Reminder history | `{prefix}sktpr_email_reminders` custom table. |
| Reminder opt-outs | HMAC hashes in `sktpr_email_optouts`. |

All plugin data is stored on the site. The plugin does not transmit review content, customer
data or usage data anywhere.

The reminder table stores order ID, rule ID, trigger status, customer name, customer email, a
sender/template snapshot, scheduled time, status, created/updated times and sent time. Its
unique `(order_id, rule_id)` key enforces once-per-rule-per-order behavior.

### WordPress privacy tools

Under **Tools > Export Personal Data**, the plugin registers:

- **Product Review Media** — media URLs, consent timestamp and reviewed product.
- **Review Reminder Emails** — reminder customer/order/status/schedule data.

Under **Tools > Erase Personal Data**, it:

- Permanently deletes review media attachments and their plugin comment metadata.
- Deletes reminder rows for the email and clears their pending cron events.
- Leaves review-comment erasure/anonymization to WordPress core's comment eraser.
- Retains the unreadable hashed opt-out entry.

The plugin also contributes suggested text to the WordPress Privacy Policy Guide.

### Deactivation and removal

Deactivation clears the plugin's scheduled cron hooks. It does not delete settings, reviews,
attachments, saved displays, reminder history, the reminder table or opt-out hashes. There is
no uninstall routine that removes that data automatically.

Before permanently removing the plugin, export any required records and define a
store-specific retention/deletion procedure. Do not delete the custom table or options unless
permanent removal is intended and backups exist.

## 19. Developer reference

### Important identifiers

- Plugin namespace: `SKTPREVIEW`.
- Main version constant: `SKTPR_VERSION`.
- Marketplace build constant: `SKTPR_MARKETPLACE_BUILD`.
- Shortcode: `reviquo`.
- Block: `sktpr/reviews`.
- Elementor widget name: `sktpr-product-reviews`.
- Divi 5 module: `sktpr/divi5-reviews`.
- Divi 4 module shortcode: `sktpr_divi4_reviews`.
- Saved display post type: `sktpr_review_sc`.
- REST namespace: `sktpr/v1`.
- Reminder table suffix: `sktpr_email_reminders`.

### Public filters

The plugin intentionally exposes only a small explicit extension surface:

```php
// Change the displayed unit after the maximum recording duration.
add_filter( 'sktpr_video_duration_unit', function ( $unit ) {
	return $unit;
} );

// Change the theme template override directory.
add_filter( 'sktpr_templates_folder', function () {
	return 'product-reviews';
} );
```

The plugin also integrates with WooCommerce's review form, review list, review metadata,
comment classes, order-status changes and structured-data filters. Treat internal class methods
and AJAX actions as implementation details unless a stable API is documented in a future
release.

### Template overrides

The loader checks the plugin's `templates/` directory, then a parent theme, then a child theme.
By default, overrides belong in:

```text
your-theme/product-reviews/
your-child-theme/product-reviews/
```

The current templates are `admin-settings.php` and `review-shortcodes.php`. These are admin
application mounting templates, not storefront review-card templates. The
`sktpr_templates_folder` filter can change the directory name.

### Compiled assets

The shipped package contains compiled assets only, under `assets/admin/`, `assets/public/`,
`assets/blocks/`, `assets/divi4/` and `assets/divi5/`. Divi 5's shared frontend/Visual Builder
metadata is in `divi5/reviews/module.json`. Customize the plugin through the settings screens,
the filters above, theme CSS, or template overrides rather than by editing compiled files,
which are replaced on every update.

### Translation

Every user-facing string uses the `product-reviews` text domain, and a `.pot` catalogue ships
in `languages/`. Storefront button labels, recorder and gallery messages, filters, validation
messages, the default reminder email body and all admin strings are translatable.

## 20. Troubleshooting

### The record button is missing

- Confirm Webcam recording is enabled and settings were saved.
- Confirm the page is a WooCommerce single-product page using the normal review form filters.
- Confirm reviews are enabled for the product and globally in WooCommerce.
- Check whether the theme replaces the WooCommerce review form without applying `woocommerce_product_review_comment_form_args`.

### The browser cannot access the camera

- Use HTTPS or localhost.
- Allow both camera and microphone permission for the site.
- Close other applications using the camera.
- Try a current Chrome, Firefox, Edge or Safari version with MediaRecorder support.
- Use the separate video upload control when recording is unsupported.

### An uploaded video is not attached

- Use MP4 or WebM for uploaded review videos.
- Check the WordPress upload limit and PHP `upload_max_filesize`/`post_max_size`.
- Check WordPress's allowed MIME types and any security plugin restrictions.
- Confirm the review form has `multipart/form-data`; the public script sets it on `#commentform`.
- Inspect the Media Library for an uploaded file and the review's `uploaded_video_url` metadata.

### A media review is not public

- Open **ReviQuo > Reviews** and check Pending, Spam and Trash.
- If Auto-approve videos is off, approve the media review manually.
- Clear page/CDN caches after approval.
- Confirm the theme executes `woocommerce_review_comment_text` and `woocommerce_review_meta`.

### The verified badge does not appear

- Enable Verified badge under **Review Settings > Storefront**.
- Confirm WooCommerce can match the review email/customer to an order for that product.
- The badge is intentionally hidden for pending reviews.

### The Divi module shows no reviews

- Confirm the module has a saved display selected and that the display has not been trashed.
- A Current product display needs a WooCommerce product context. Use Selected products or All products on ordinary pages, or place the module on a product page.
- Confirm at least one approved review exists for the selected source. The Visual Builder shows sample reviews where the public page shows none.

### Filters or Load More do not respond

- Check the browser console for JavaScript errors.
- Confirm jQuery and `assets/public/js/public.min.js` loaded.
- Confirm AJAX requests to `admin-ajax.php` are not blocked by a cache, firewall or maintenance rule.
- Exclude the public Load More action from rules that block unauthenticated WordPress AJAX.

### Reminder emails are late or missing

- Confirm the master switch and the applicable rule are enabled.
- Confirm the order actually changed into the configured status after the rule was enabled.
- Check **ReviQuo > Email Reminders** and WooCommerce order notes.
- Confirm the customer has a valid billing email, has not unsubscribed, and the order contains a simple or variable product.
- Verify WordPress Cron with a cron inspection tool or WP-CLI.
- Configure SMTP/transactional email and inspect its delivery logs.
- Remember that a given rule is recorded only once per order, even after failure or cancellation.

## 21. Recommended launch checklist

- WooCommerce product reviews and ratings are enabled as intended.
- The review form works for logged-in and guest customers according to store policy.
- Required-media combinations are practical and clearly communicated.
- HTTPS camera recording succeeds on desktop and mobile browsers used by customers.
- MP4/WebM upload and five-photo upload have been tested against hosting limits.
- Consent text and the store privacy policy have been reviewed.
- Pending media-review moderation has an assigned owner.
- Rating summary, filters, badges, lightbox and saved displays match the theme.
- Divi 4 and/or Divi 5 modules render the intended display on both the Visual Builder and the public page.
- Reminder sender authentication, Cron, templates, delays, order statuses and unsubscribe links have been tested with a real order.
- The headless API, if used, has appropriate caching, CORS and rate controls.
- Backup and retention procedures cover comments, Media Library files, options, saved displays, reminder rows and opt-out hashes.
