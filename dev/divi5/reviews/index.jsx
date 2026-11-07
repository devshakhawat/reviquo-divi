import React, { useEffect, useState } from 'react';
import metadata from '../../../divi5/reviews/module.json';

const { addAction } = window?.vendor?.wp?.hooks || {};
const {
  ModuleContainer,
  StyleContainer,
  CssStyle,
  elementClassnames,
} = window?.divi?.module || {};
const { registerModule } = window?.divi?.moduleLibrary || {};
const config = window.sktprDivi5 || window.parent?.sktprDivi5 || window.top?.sktprDivi5 || {};
const strings = config.strings || {};

// Attributes holding Design-tab options for review elements (module.json).
const DESIGN_ELEMENTS = ['card', 'author', 'reviewText', 'productName', 'date', 'stars', 'summary', 'filters', 'loadMore'];
const SINGLE_COLUMN_LAYOUTS = ['list', 'spotlight', 'compact', 'marquee', 'timeline', 'accordion', 'chat'];
const NUMERIC_KEYS = ['per_page', 'columns', 'tablet_columns', 'mobile_columns', 'slider_delay', 'gap', 'radius'];

const reviewFields = metadata.attributes.reviews.settings.advanced;
const reviewDefaults = metadata.attributes.reviews.default.advanced;

const reviewValue = (attrs, subName) => (
  attrs?.reviews?.advanced?.[subName]?.desktop?.value ?? reviewDefaults[subName]?.desktop?.value
);

const isCustom = (attrs) => reviewValue(attrs, 'mode') === 'custom';

// sliderAutoplay -> slider_autoplay.
const toSettingKey = (subName) => subName.replace(/[A-Z]/g, (letter) => `_${letter.toLowerCase()}`);

const inlineSettings = (attrs) => {
  const settings = {};
  Object.keys(reviewFields).forEach((subName) => {
    if (subName === 'mode') {
      return;
    }
    const value = reviewValue(attrs, subName);
    if (value === undefined || value === null || value === '' || typeof value === 'object') {
      return;
    }
    const key = toSettingKey(subName);
    settings[key] = NUMERIC_KEYS.includes(key) ? Number.parseInt(value, 10) : value;
  });
  return settings;
};

// Saved-display picker options.
const options = {
  '': { label: strings.choose || 'Choose a shortcode setting' },
};
(Array.isArray(config.items) ? config.items : []).forEach((item) => {
  options[String(item.id)] = { label: `${item.name} (#${item.id})` };
});
metadata.attributes.display.settings.innerContent.item.component.props.options = options;

if (!config.isPro && reviewFields.showVerified) {
  reviewFields.showVerified.item.label = `${reviewFields.showVerified.item.label} (Pro)`;
}

// Show only the fields that apply to the current choices (Divi calls
// `visible` with the module's attrs).
const fieldVisibility = {
  sliderAutoplay: (attrs) => reviewValue(attrs, 'layout') === 'slider',
  sliderArrows: (attrs) => reviewValue(attrs, 'layout') === 'slider',
  sliderDots: (attrs) => reviewValue(attrs, 'layout') === 'slider',
  sliderDelay: (attrs) => reviewValue(attrs, 'layout') === 'slider' && reviewValue(attrs, 'sliderAutoplay') === 'on',
  columns: (attrs) => !SINGLE_COLUMN_LAYOUTS.includes(reviewValue(attrs, 'layout')),
  tabletColumns: (attrs) => !SINGLE_COLUMN_LAYOUTS.includes(reviewValue(attrs, 'layout')),
  mobileColumns: (attrs) => !SINGLE_COLUMN_LAYOUTS.includes(reviewValue(attrs, 'layout')),
  productIds: (attrs) => reviewValue(attrs, 'source') === 'selected',
};

Object.keys(reviewFields).forEach((subName) => {
  if (subName === 'mode') {
    return;
  }
  const extra = fieldVisibility[subName];
  reviewFields[subName].item.visible = ({ attrs }) => isCustom(attrs) && (!extra || extra(attrs));
});
metadata.attributes.display.settings.innerContent.item.visible = ({ attrs }) => !isCustom(attrs);

const ModuleStyles = ({ attrs, elements, settings, orderClass, mode, state, noStyleTag }) => (
  <StyleContainer mode={mode} state={state} noStyleTag={noStyleTag}>
    {elements.style({
      attrName: 'module',
      styleProps: {
        disabledOn: {
          disabledModuleVisibility: settings?.disabledModuleVisibility,
        },
      },
    })}
    {DESIGN_ELEMENTS.map((attrName) => (
      <React.Fragment key={attrName}>{elements.style({ attrName })}</React.Fragment>
    ))}
    {CssStyle && orderClass ? (
      <CssStyle selector={orderClass} attr={attrs?.css} cssFields={metadata.customCssFields} />
    ) : null}
  </StyleContainer>
);

const ModuleScriptData = ({ elements }) => (
  <React.Fragment>
    {elements.scriptData({ attrName: 'module' })}
  </React.Fragment>
);

const moduleClassnames = ({ classnamesInstance, attrs }) => {
  classnamesInstance.add(
    elementClassnames({ attrs: attrs?.module?.decoration ?? {} })
  );
};

const fetchJson = (url, init) => window.fetch(url, init).then((response) => {
  if (!response.ok) {
    throw new Error('Preview unavailable');
  }
  return response.json();
});

const ReviewsEdit = ({ attrs, elements, id, name }) => {
  const custom = isCustom(attrs);
  const displayId = Number.parseInt(attrs?.display?.innerContent?.desktop?.value, 10) || 0;
  const settingsJson = custom ? JSON.stringify(inlineSettings(attrs)) : '';
  const previewKey = custom ? `custom:${settingsJson}` : `saved:${displayId}`;
  const [preview, setPreview] = useState({ key: '', html: '', loading: false, error: '' });

  useEffect(() => {
    const controller = new AbortController();

    if ((!custom && !displayId) || !config.restUrl) {
      setPreview({ key: previewKey, html: '', loading: false, error: '' });
      return () => controller.abort();
    }

    setPreview((current) => ({ ...current, loading: true, error: '' }));
    const contextProductId = Number(config.contextProductId) || 0;

    // Debounced so dragging a range slider doesn't fire a request per step.
    const timer = window.setTimeout(() => {
      const request = custom
        ? fetchJson(`${config.restUrl}inline-preview`, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'X-WP-Nonce': config.restNonce || '', 'Content-Type': 'application/json' },
          body: JSON.stringify({ settings: JSON.parse(settingsJson), context_product_id: contextProductId }),
          signal: controller.signal,
        })
        : fetchJson(`${config.restUrl}${displayId}/preview?context_product_id=${contextProductId}`, {
          credentials: 'same-origin',
          headers: { 'X-WP-Nonce': config.restNonce || '' },
          signal: controller.signal,
        });

      request
        .then((response) => {
          setPreview({ key: previewKey, html: response?.html || '', loading: false, error: '' });
        })
        .catch((error) => {
          if (error.name !== 'AbortError') {
            setPreview({ key: previewKey, html: '', loading: false, error: strings.unavailable || 'The selected shortcode setting is unavailable.' });
          }
        });
    }, custom ? 350 : 0);

    return () => {
      window.clearTimeout(timer);
      controller.abort();
    };
  }, [previewKey]);

  let content = (
    <div className="sktpr-divi5-placeholder">
      {strings.configure || 'Choose a shortcode setting in the Content panel.'}
    </div>
  );

  if (custom || displayId) {
    if (preview.error) {
      content = <div className="sktpr-divi5-placeholder">{preview.error}</div>;
    } else if (preview.html) {
      // Keeps the previous render on screen while a settings change reloads it.
      content = <div className="sktpr-divi5-reviews__inner" dangerouslySetInnerHTML={{ __html: preview.html }} />;
    } else if (preview.loading || preview.key !== previewKey) {
      content = <div className="sktpr-divi5-placeholder">{strings.loading || 'Loading review display…'}</div>;
    } else {
      content = <div className="sktpr-divi5-placeholder">{strings.unavailable || 'The selected shortcode setting is unavailable.'}</div>;
    }
  }

  return (
    <ModuleContainer
      attrs={attrs}
      elements={elements}
      id={id}
      name={name}
      stylesComponent={ModuleStyles}
      classnamesFunction={moduleClassnames}
      scriptDataComponent={ModuleScriptData}
    >
      {elements.styleComponents({ attrName: 'module' })}
      {content}
    </ModuleContainer>
  );
};

const reviewsModule = {
  metadata,
  renderers: {
    edit: ReviewsEdit,
  },
};

if (addAction && registerModule && ModuleContainer && StyleContainer && elementClassnames) {
  addAction(
    'divi.moduleLibrary.registerModuleLibraryStore.after',
    'sktpr.divi5Reviews',
    () => {
      const { metadata: moduleMetadata, ...definition } = reviewsModule;
      registerModule(moduleMetadata, definition);
    }
  );
}
