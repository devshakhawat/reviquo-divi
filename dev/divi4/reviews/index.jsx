import React, { Component } from 'react';
import jQuery from 'jquery';

const config = window.sktprDivi4 || {};
const strings = config.strings || {};

// Module props that map 1:1 to shortcode-builder settings (custom mode).
const SETTING_KEYS = [
  'source', 'product_ids', 'layout', 'per_page', 'order',
  'show_summary', 'show_filters', 'show_product', 'show_review_text', 'show_avatar', 'show_verified', 'show_photos', 'show_videos',
  'columns', 'tablet_columns', 'mobile_columns',
  'slider_autoplay', 'slider_delay', 'slider_arrows', 'slider_dots',
  'gap', 'radius', 'background_color', 'text_color', 'accent_color', 'star_color',
];

const NUMERIC_KEYS = ['per_page', 'columns', 'tablet_columns', 'mobile_columns', 'slider_delay', 'gap', 'radius'];

const isCustom = (props) => props.settings_mode === 'custom';

const inlineSettings = (props) => {
  const settings = {};
  SETTING_KEYS.forEach((key) => {
    const value = props[key];
    if (value !== undefined && value !== null && value !== '') {
      settings[key] = NUMERIC_KEYS.includes(key) ? Number.parseInt(value, 10) : value;
    }
  });
  return settings;
};

// A stable string identifying what the preview depends on.
const previewKey = (props) => (
  isCustom(props)
    ? `custom:${JSON.stringify(inlineSettings(props))}`
    : `saved:${Number.parseInt(props.display_id, 10) || 0}`
);

const PADDING_SELECTOR = '%%order_class%% .sktpr-shortcode-card, %%order_class%% .sktpr-shortcode-chat-bubble';
const PADDING_VALUE = /^-?\d*\.?\d+(px|em|rem|%|vw|vh)?$/;

const paddingDeclaration = (value) => {
  if (typeof value !== 'string' || value === '') {
    return '';
  }
  return value.split('|').slice(0, 4).map((side, index) => {
    const trimmed = side.trim();
    return trimmed && PADDING_VALUE.test(trimmed)
      ? `padding-${['top', 'right', 'bottom', 'left'][index]}: ${trimmed};`
      : '';
  }).join('');
};

// Divi 4 bundles React 16, so this stays a class component (no hooks).
class ReviewsModule extends Component {
  constructor(props) {
    super(props);
    this.state = { key: '', html: '', loading: false, error: '' };
    this.abortController = null;
    this.timer = null;
  }

  componentDidMount() {
    this.fetchPreview();
  }

  componentDidUpdate(prevProps) {
    if (previewKey(prevProps) !== previewKey(this.props)) {
      // Debounced so dragging a range slider doesn't fire a request per step.
      window.clearTimeout(this.timer);
      this.timer = window.setTimeout(() => this.fetchPreview(), 350);
    }
  }

  componentWillUnmount() {
    window.clearTimeout(this.timer);
    if (this.abortController) {
      this.abortController.abort();
    }
  }

  fetchPreview() {
    const key = previewKey(this.props);
    const custom = isCustom(this.props);
    const displayId = Number.parseInt(this.props.display_id, 10) || 0;

    if (this.abortController) {
      this.abortController.abort();
    }

    if ((!custom && !displayId) || !config.restUrl) {
      this.setState({ key, html: '', loading: false, error: '' });
      return;
    }

    this.abortController = new AbortController();
    this.setState({ loading: true, error: '' });

    const contextProductId = Number(config.contextProductId) || 0;
    const request = custom
      ? window.fetch(`${config.restUrl}inline-preview`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'X-WP-Nonce': config.restNonce || '', 'Content-Type': 'application/json' },
        body: JSON.stringify({ settings: inlineSettings(this.props), context_product_id: contextProductId }),
        signal: this.abortController.signal,
      })
      : window.fetch(`${config.restUrl}${displayId}/preview?context_product_id=${contextProductId}`, {
        credentials: 'same-origin',
        headers: { 'X-WP-Nonce': config.restNonce || '' },
        signal: this.abortController.signal,
      });

    request
      .then((response) => {
        if (!response.ok) {
          throw new Error('Preview unavailable');
        }
        return response.json();
      })
      .then((response) => {
        this.setState({ key, html: response?.html || '', loading: false, error: '' });
      })
      .catch((error) => {
        if (error.name !== 'AbortError') {
          this.setState({
            key,
            html: '',
            loading: false,
            error: strings.unavailable || 'The selected shortcode setting is unavailable.',
          });
        }
      });
  }

  render() {
    const custom = isCustom(this.props);
    const displayId = Number.parseInt(this.props.display_id, 10) || 0;
    const { key, html, loading, error } = this.state;

    if (!custom && !displayId) {
      return (
        <div className="sktpr-divi4-placeholder">
          {strings.configure || 'Choose a shortcode setting in the Content panel.'}
        </div>
      );
    }

    // Keep showing the previous render while a settings change reloads it.
    if (!html && (loading || key !== previewKey(this.props))) {
      return <div className="sktpr-divi4-placeholder">{strings.loading || 'Loading review display…'}</div>;
    }

    if (error || !html) {
      return <div className="sktpr-divi4-placeholder">{error || (strings.unavailable || 'The selected shortcode setting is unavailable.')}</div>;
    }

    return <div className="sktpr-divi4-reviews__inner" dangerouslySetInnerHTML={{ __html: html }} />;
  }
}

// Assigned outside the class body: a `static` class field would make the
// bundler emit top-level helper vars that leak onto window in an IIFE build
// (one was named `_`, clobbering Underscore and crashing the Divi builder).
ReviewsModule.slug = 'sktpr_divi4_reviews';

// Visual Builder CSS for the custom card padding field (the frontend gets it
// from PHP). Divi applies the device entries inside its own media queries.
ReviewsModule.css = (props) => {
  const rules = [];
  const responsive = String(props.card_padding_last_edited || '').startsWith('on');
  [['desktop', props.card_padding], ['tablet', responsive ? props.card_padding_tablet : ''], ['phone', responsive ? props.card_padding_phone : '']]
    .forEach(([device, value]) => {
      const declaration = paddingDeclaration(value);
      if (declaration) {
        rules.push(device === 'desktop'
          ? { selector: PADDING_SELECTOR, declaration }
          : { selector: PADDING_SELECTOR, declaration, device });
      }
    });
  return [rules];
};

jQuery(window).on('et_builder_api_ready', (event, API) => {
  API.registerModules([ReviewsModule]);
});
