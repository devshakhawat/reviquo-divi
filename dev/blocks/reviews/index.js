import './editor.css';

(function (wp, config) {
  'use strict';

  if (!wp?.blocks || !wp?.blockEditor || !wp?.components || !wp?.element) {
    return;
  }

  const { registerBlockType, createBlock } = wp.blocks;
  const { BlockControls, InspectorControls, useBlockProps } = wp.blockEditor;
  const {
    Button,
    Notice,
    PanelBody,
    Placeholder,
    SelectControl,
    Spinner,
    TextControl,
    ToolbarButton,
    ToolbarGroup,
  } = wp.components;
  const { createElement: el, Fragment, useEffect, useMemo, useState } = wp.element;
  const { useSelect } = wp.data;
  const { __, sprintf } = wp.i18n;
  const apiFetch = wp.apiFetch;
  const items = Array.isArray(config?.items) ? config.items : [];

  const sourceLabels = {
    current: __('Current product', 'product-reviews'),
    selected: __('Selected products', 'product-reviews'),
    all: __('All products', 'product-reviews'),
  };

  const layoutLabels = {
    list: __('List', 'product-reviews'),
    grid: __('Grid', 'product-reviews'),
    masonry: __('Masonry', 'product-reviews'),
    slider: __('Slider', 'product-reviews'),
    gallery: __('Media Gallery', 'product-reviews'),
    testimonial: __('Testimonial', 'product-reviews'),
    spotlight: __('Spotlight', 'product-reviews'),
    compact: __('Compact List', 'product-reviews'),
    reels: __('Reels', 'product-reviews'),
    marquee: __('Marquee', 'product-reviews'),
    timeline: __('Timeline', 'product-reviews'),
    accordion: __('Accordion', 'product-reviews'),
    chat: __('Chat Bubbles', 'product-reviews'),
  };

  function getItem(id) {
    return items.find((item) => Number(item.id) === Number(id));
  }

  function DisplayPicker({ value, onChange, compact = false }) {
    const options = useMemo(
      () => [
        { value: '', label: __('Choose a shortcode setting…', 'product-reviews'), disabled: true },
        ...items.map((item) => ({ value: String(item.id), label: item.name })),
      ],
      []
    );

    return el(SelectControl, {
      label: __('Shortcode setting', 'product-reviews'),
      help: compact ? undefined : __('Choose one of your saved review shortcode settings.', 'product-reviews'),
      value: value ? String(value) : '',
      options,
      onChange: (nextValue) => onChange(Number.parseInt(nextValue, 10) || 0),
      __next40pxDefaultSize: true,
      __nextHasNoMarginBottom: true,
    });
  }

  function ManageButtons() {
    if (!config?.canManage) {
      return null;
    }

    return el(
      'div',
      { className: 'sktpr-block-actions' },
      el(Button, { variant: 'primary', href: config.createUrl, target: '_blank', rel: 'noopener noreferrer' }, __('Create display', 'product-reviews')),
      el(Button, { variant: 'secondary', href: config.manageUrl, target: '_blank', rel: 'noopener noreferrer' }, __('Manage displays', 'product-reviews'))
    );
  }

  function EmptyState({ onChange }) {
    return el(
      Placeholder,
      {
        className: 'sktpr-block-placeholder',
        icon: 'star-filled',
        label: __('Product Reviews', 'product-reviews'),
        instructions: items.length
          ? __('Choose a saved review display to show on this page.', 'product-reviews')
          : __('Create a review display before adding it to this page.', 'product-reviews'),
      },
      items.length ? el(DisplayPicker, { value: 0, onChange }) : null,
      el(ManageButtons)
    );
  }

  function Edit({ attributes, setAttributes }) {
    const id = Number(attributes.id) || 0;
    const selected = getItem(id);
    const [preview, setPreview] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [refreshKey, setRefreshKey] = useState(0);
    const [copied, setCopied] = useState(false);
    const postId = useSelect(
      (select) => Number(select('core/editor')?.getCurrentPostId?.()) || 0,
      []
    );
    const blockProps = useBlockProps({ className: 'sktpr-reviews-block-editor' });

    useEffect(() => {
      let active = true;

      if (!id || !selected) {
        setPreview('');
        setError('');
        return () => {
          active = false;
        };
      }

      setLoading(true);
      setError('');
      apiFetch({
        path: `/sktpr/v1/review-shortcodes/${id}/preview?context_product_id=${postId}`,
      })
        .then((response) => {
          if (active) {
            setPreview(response?.html || '');
          }
        })
        .catch((requestError) => {
          if (active) {
            setError(requestError?.message || __('The preview could not be loaded.', 'product-reviews'));
          }
        })
        .finally(() => {
          if (active) {
            setLoading(false);
          }
        });

      return () => {
        active = false;
      };
    }, [id, postId, refreshKey]);

    useEffect(() => {
      if (!copied) {
        return undefined;
      }
      const timeout = window.setTimeout(() => setCopied(false), 2000);
      return () => window.clearTimeout(timeout);
    }, [copied]);

    const chooseDisplay = (nextId) => {
      setAttributes({ id: nextId });
      setPreview('');
      setError('');
    };

    const copyShortcode = () => {
      if (!selected?.shortcode || !navigator.clipboard) {
        return;
      }
      navigator.clipboard.writeText(selected.shortcode).then(() => setCopied(true));
    };

    if (!id) {
      return el('div', blockProps, el(EmptyState, { onChange: chooseDisplay }));
    }

    if (!selected) {
      return el(
        'div',
        blockProps,
        el(
          Placeholder,
          {
            className: 'sktpr-block-placeholder',
            icon: 'warning',
            label: __('Review display unavailable', 'product-reviews'),
            instructions: __('The saved display may have been deleted. Choose another display.', 'product-reviews'),
          },
          items.length ? el(DisplayPicker, { value: 0, onChange: chooseDisplay }) : null,
          el(ManageButtons)
        )
      );
    }

    const inspector = el(
      InspectorControls,
      null,
      el(
        PanelBody,
        { title: __('Review display', 'product-reviews') },
        el(DisplayPicker, { value: id, onChange: chooseDisplay }),
        el(
          'dl',
          { className: 'sktpr-block-details' },
          el('div', null, el('dt', null, __('Source', 'product-reviews')), el('dd', null, sourceLabels[selected.source] || selected.source)),
          el('div', null, el('dt', null, __('Layout', 'product-reviews')), el('dd', null, layoutLabels[selected.layout] || selected.layout))
        ),
        config?.canManage
          ? el(Button, { variant: 'secondary', href: `${config.manageUrl}&view=edit&id=${id}`, target: '_blank', rel: 'noopener noreferrer' }, __('Edit display settings', 'product-reviews'))
          : null
      ),
      el(
        PanelBody,
        { title: __('Shortcode', 'product-reviews'), initialOpen: false },
        el(TextControl, {
          label: __('Equivalent shortcode', 'product-reviews'),
          value: selected.shortcode,
          readOnly: true,
          onFocus: (event) => event.target.select(),
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true,
        }),
        el(Button, { variant: 'secondary', onClick: copyShortcode, disabled: !navigator.clipboard }, copied ? __('Copied!', 'product-reviews') : __('Copy shortcode', 'product-reviews'))
      )
    );

    const controls = el(
      BlockControls,
      null,
      el(
        ToolbarGroup,
        null,
        el(ToolbarButton, {
          icon: 'update',
          label: __('Refresh preview', 'product-reviews'),
          onClick: () => setRefreshKey((key) => key + 1),
        }),
        config?.canManage
          ? el(ToolbarButton, {
              icon: 'edit',
              label: __('Edit display settings', 'product-reviews'),
              href: `${config.manageUrl}&view=edit&id=${id}`,
              target: '_blank',
              rel: 'noopener noreferrer',
            })
          : null
      )
    );

    return el(
      Fragment,
      null,
      inspector,
      controls,
      el(
        'div',
        blockProps,
        el(
          'div',
          { className: 'sktpr-block-setting-bar' },
          el(DisplayPicker, { value: id, onChange: chooseDisplay, compact: true })
        ),
        el(
          'div',
          { className: 'sktpr-block-preview-header' },
          el(
            'div',
            null,
            el('strong', null, selected.name),
            el(
              'span',
              null,
              sprintf(
                /* translators: 1: review source, 2: review layout. */
                __('%1$s source · %2$s layout', 'product-reviews'),
                sourceLabels[selected.source] || selected.source,
                layoutLabels[selected.layout] || selected.layout
              )
            )
          ),
          el('span', { className: 'sktpr-block-preview-label' }, __('Editor preview', 'product-reviews'))
        ),
        error
          ? el(
              Notice,
              { status: 'error', isDismissible: false },
              error,
              ' ',
              el(Button, { variant: 'link', onClick: () => setRefreshKey((key) => key + 1) }, __('Try again', 'product-reviews'))
            )
          : null,
        el(
          'div',
          {
            className: `sktpr-block-preview${loading ? ' is-loading' : ''}`,
            'aria-busy': loading,
            'aria-live': 'polite',
          },
          loading ? el('div', { className: 'sktpr-block-spinner' }, el(Spinner), el('span', null, __('Loading preview…', 'product-reviews'))) : null,
          preview ? el('div', { dangerouslySetInnerHTML: { __html: preview } }) : null
        )
      )
    );
  }

  registerBlockType('sktpr/reviews', {
    edit: Edit,
    save: () => null,
    transforms: {
      from: [
        {
          type: 'shortcode',
          tag: 'reviquo',
          attributes: {
            id: {
              type: 'number',
              shortcode: (attributes) => Number.parseInt(attributes.named?.id, 10) || 0,
            },
          },
        },
      ],
      to: [
        {
          type: 'block',
          blocks: ['core/shortcode'],
          transform: ({ id }) => createBlock('core/shortcode', { text: `[reviquo id="${Number(id) || 0}"]` }),
        },
      ],
    },
  });
})(window.wp, window.sktprReviewsBlock || {});
