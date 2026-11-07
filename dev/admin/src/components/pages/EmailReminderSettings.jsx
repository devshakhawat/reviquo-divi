import React, { useMemo, useState } from 'react';
import { Mail, FileText, ChevronDown, Plus, Trash2 } from 'lucide-react';
import { Switch, Row } from './GeneralSettings';

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function applyPlaceholders(template, values) {
  return Object.entries(values).reduce(
    (content, [placeholder, value]) => content.split(placeholder).join(value),
    String(template ?? '')
  );
}

function buildEmailPreview(settings, previewData) {
  const siteName = previewData.site_name || 'Your Store';
  const siteUrl = String(previewData.site_url || 'https://example.com').replace(/\/+$/, '');
  const unsubscribeUrl = `${siteUrl}/?sktpr_unsubscribe=alex%40example.com&sktpr_token=preview`;
  const productUrl = `${siteUrl}/product/sample-product/#reviews`;
  const productImage = previewData.product_image
    ? `<img src="${escapeHtml(
        previewData.product_image
      )}" alt="${escapeHtml(
        previewData.product_name || 'Sample product'
      )}" style="width: 60px; height: 60px; object-fit: cover; float: left; margin-right: 15px; border-radius: 3px;">`
    : '';
  const productsHtml = `<div style="margin: 20px 0;">
    <div style="border: 1px solid #dfe2da; padding: 15px; margin: 10px 0; border-radius: 5px;">
      ${productImage}
      <h3 style="margin: 0 0 10px 0; font-size: 16px;">${escapeHtml(
        previewData.product_name || 'Sample product'
      )}</h3>
      <a href="${escapeHtml(
        productUrl
      )}" style="background: #ff694d; color: #ffffff; padding: 8px 16px; text-decoration: none; border-radius: 3px; display: inline-block; font-weight: 700;">Write Review</a>
      <div style="clear: both;"></div>
    </div>
  </div>`;
  const templateValues = {
    '{customer_name}': previewData.customer_name || 'Alex Johnson',
    '{order_number}': previewData.order_number || '1042',
    '{order_date}': previewData.order_date || 'August 14, 2026',
    '{site_name}': siteName,
    '{site_url}': siteUrl,
    '{review_reward}': 'up to 15%',
  };
  const htmlValues = Object.fromEntries(
    Object.entries(templateValues).map(([placeholder, value]) => [placeholder, escapeHtml(value)])
  );
  htmlValues['{products_list}'] = productsHtml;
  htmlValues['{unsubscribe_link}'] = escapeHtml(unsubscribeUrl);

  const template = String(settings.email_content ?? '');
  const hasUnsubscribePlaceholder = template.includes('{unsubscribe_link}');
  let body = applyPlaceholders(template, htmlValues);

  if (!hasUnsubscribePlaceholder) {
    body += `<p style="margin-top: 30px; font-size: 12px; color: #69716c;">No longer want review reminder emails? <a href="${escapeHtml(
      unsubscribeUrl
    )}">Unsubscribe</a></p>`;
  }

  return {
    subject: applyPlaceholders(settings.email_subject, templateValues),
    document: `<!doctype html>
      <html>
        <head>
          <meta charset="utf-8">
          <meta http-equiv="Content-Security-Policy" content="default-src 'none'; img-src https: http: data:; style-src 'unsafe-inline'; font-src https: data:; script-src 'none'; object-src 'none'; base-uri 'none'; form-action 'none'">
          <meta name="viewport" content="width=device-width, initial-scale=1">
          <style>html { background: #f7f5ef; } body { box-sizing: border-box; max-width: 680px; min-height: 100vh; margin: 0 auto; padding: 28px; background: #fff; color: #17201e; font-family: Arial, sans-serif; line-height: 1.5; overflow-wrap: anywhere; } img { max-width: 100%; } a { color: #17201e; pointer-events: none; }</style>
        </head>
        <body>${body}</body>
      </html>`,
  };
}

function Placeholder({ children }) {
  return (
    <code className="rounded bg-muted px-1 py-0.5 text-xs text-foreground">{children}</code>
  );
}

function SectionHeader({ id, title, description, icon: Icon, neutral, open, onToggle }) {
  return (
    <button
      type="button"
      className="sktpr-gcard-head w-full cursor-pointer border-0 text-left"
      aria-expanded={open}
      aria-controls={id}
      onClick={onToggle}
    >
      <span className={`sktpr-gcard-icon ${neutral ? 'sktpr-gcard-icon--neutral' : ''}`}>
        <Icon size={15} />
      </span>
      <span className="min-w-0 flex-1">
        <span className="sktpr-gcard-titlerow">
          <h3>{title}</h3>
        </span>
        <span className="sktpr-gcard-sub block">{description}</span>
      </span>
      <ChevronDown
        size={17}
        className={`shrink-0 text-muted-foreground transition-transform duration-200 ${
          open ? 'rotate-180' : ''
        }`}
      />
    </button>
  );
}

function createRule(settings, orderStatuses) {
  const source = settings.rules?.[0] || {};
  const status = Object.prototype.hasOwnProperty.call(orderStatuses, 'completed')
    ? 'completed'
    : Object.keys(orderStatuses)[0] || '';
  const id = globalThis.crypto?.randomUUID?.() || `rule-${Date.now()}-${Math.random().toString(16).slice(2)}`;

  return {
    id,
    enabled: true,
    trigger_order_status: status,
    email_delay_days: 7,
    email_subject: source.email_subject || 'How was your recent purchase, {customer_name}?',
    email_content:
      source.email_content ||
      '<p>Hi {customer_name},</p><p>We would love to hear what you think about your recent purchase.</p>{products_list}',
  };
}

function ReminderRuleEditor({ rule, index, settings, orderStatuses, previewData, onUpdate, onRemove }) {
  const [contentTab, setContentTab] = useState('html');
  const [open, setOpen] = useState(index === 0);
  const [errors, setErrors] = useState({});
  const emailPreview = useMemo(
    () => buildEmailPreview({ ...rule, from_name: settings.from_name, from_email: settings.from_email }, previewData),
    [rule.email_content, rule.email_subject, settings.from_email, settings.from_name, previewData]
  );
  const panelId = `sktpr-reminder-rule-${rule.id}`;
  const statusLabel = orderStatuses[rule.trigger_order_status] || rule.trigger_order_status || 'Choose status';
  const delay = Number(rule.email_delay_days) || 0;
  const timingLabel = delay === 0 ? 'Immediately' : `${delay} day${delay === 1 ? '' : 's'} later`;

  const validateField = (field) => {
    const empty = !String(rule[field] ?? '').trim();
    setErrors((current) => ({ ...current, [field]: empty ? 'This field is required.' : '' }));
  };

  const handleTabKeyDown = (event) => {
    if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;

    event.preventDefault();
    const nextTab = event.key === 'ArrowLeft' || event.key === 'Home' ? 'html' : 'preview';
    setContentTab(nextTab);
    document.getElementById(`sktpr-email-${rule.id}-${nextTab}-tab`)?.focus();
  };

  return (
    <article className="sktpr-reminder-rule">
      <div className="sktpr-reminder-rule-head">
        <button
          type="button"
          className="sktpr-reminder-rule-toggle"
          aria-expanded={open}
          aria-controls={panelId}
          onClick={() => setOpen((current) => !current)}
        >
          <span className="sktpr-reminder-rule-index">{index + 1}</span>
          <span className="min-w-0 flex-1">
            <strong>{statusLabel}</strong>
            <small>{timingLabel} · {rule.email_subject || 'Untitled reminder'}</small>
          </span>
          <ChevronDown size={17} className={`shrink-0 transition-transform ${open ? 'rotate-180' : ''}`} />
        </button>
        <Switch
          id={`rule-${rule.id}-enabled`}
          label={`${rule.enabled ? 'Disable' : 'Enable'} ${statusLabel} reminder`}
          checked={Boolean(rule.enabled)}
          onChange={(value) => onUpdate('enabled', value)}
        />
        <button
          type="button"
          className="sktpr-reminder-rule-delete"
          aria-label={`Delete ${statusLabel} reminder`}
          onClick={onRemove}
        >
          <Trash2 size={17} />
        </button>
      </div>

      {open && (
        <div id={panelId} className="sktpr-reminder-rule-body">
          <div className="sktpr-reminder-rule-delivery">
            <label className="sktpr-field" htmlFor={`rule-${rule.id}-status`}>
              Trigger status
              <select
                id={`rule-${rule.id}-status`}
                className="sktpr-select"
                value={rule.trigger_order_status}
                onChange={(event) => onUpdate('trigger_order_status', event.target.value)}
              >
                {Object.entries(orderStatuses).map(([value, label]) => (
                  <option key={value} value={value}>{label}</option>
                ))}
              </select>
              <small>The order status that starts this reminder.</small>
            </label>

            <label className="sktpr-field" htmlFor={`rule-${rule.id}-delay`}>
              Delay in days
              <input
                id={`rule-${rule.id}-delay`}
                className="sktpr-input"
                type="number"
                min="0"
                max="365"
                value={rule.email_delay_days}
                onChange={(event) => onUpdate('email_delay_days', event.target.value)}
              />
              <small>Use 0 to send immediately.</small>
            </label>
          </div>

          <label className="sktpr-field" htmlFor={`rule-${rule.id}-subject`}>
            Subject
            <input
              id={`rule-${rule.id}-subject`}
              className={`sktpr-input ${errors.email_subject ? 'is-invalid' : ''}`}
              type="text"
              value={rule.email_subject}
              aria-invalid={Boolean(errors.email_subject)}
              aria-describedby={errors.email_subject ? `rule-${rule.id}-subject-error` : undefined}
              onChange={(event) => onUpdate('email_subject', event.target.value)}
              onBlur={() => validateField('email_subject')}
            />
            {errors.email_subject ? <small id={`rule-${rule.id}-subject-error`} className="sktpr-field-error" role="alert">{errors.email_subject}</small> : null}
          </label>

          <div className="sktpr-field">
            <div className="sktpr-email-content-heading">
              <span id={`rule-${rule.id}-content-label`}>Content</span>
              <div className="sktpr-email-content-tabs" role="tablist" aria-label={`Reminder ${index + 1} content view`} onKeyDown={handleTabKeyDown}>
                {[
                  ['html', 'HTML'],
                  ['preview', 'Preview'],
                ].map(([value, label]) => (
                  <button
                    key={value}
                    id={`sktpr-email-${rule.id}-${value}-tab`}
                    type="button"
                    role="tab"
                    aria-selected={contentTab === value}
                    aria-controls={`sktpr-email-${rule.id}-${value}-panel`}
                    tabIndex={contentTab === value ? 0 : -1}
                    className={contentTab === value ? 'is-active' : ''}
                    onClick={() => setContentTab(value)}
                  >
                    {label}
                  </button>
                ))}
              </div>
            </div>

            {contentTab === 'html' ? (
              <div id={`sktpr-email-${rule.id}-html-panel`} role="tabpanel" aria-labelledby={`sktpr-email-${rule.id}-html-tab`}>
                <textarea
                  id={`rule-${rule.id}-content`}
                  className={`sktpr-textarea ${errors.email_content ? 'is-invalid' : ''}`}
                  rows="10"
                  aria-labelledby={`rule-${rule.id}-content-label`}
                  aria-invalid={Boolean(errors.email_content)}
                  aria-describedby={errors.email_content ? `rule-${rule.id}-content-error` : undefined}
                  value={rule.email_content}
                  onChange={(event) => onUpdate('email_content', event.target.value)}
                  onBlur={() => validateField('email_content')}
                />
                {errors.email_content ? <small id={`rule-${rule.id}-content-error`} className="sktpr-field-error" role="alert">{errors.email_content}</small> : null}
              </div>
            ) : (
              <div id={`sktpr-email-${rule.id}-preview-panel`} className="sktpr-email-preview" role="tabpanel" aria-labelledby={`sktpr-email-${rule.id}-preview-tab`}>
                <div className="sktpr-email-preview-meta">
                  <span className="sktpr-email-preview-badge">Sample data</span>
                  <dl>
                    <div><dt>From</dt><dd>{settings.from_name || previewData.site_name || 'Your Store'} &lt;{settings.from_email || 'store@example.com'}&gt;</dd></div>
                    <div><dt>Subject</dt><dd>{emailPreview.subject || '(No subject)'}</dd></div>
                  </dl>
                </div>
                <iframe className="sktpr-email-preview-frame" title={`Rendered preview for reminder ${index + 1}`} sandbox="" srcDoc={emailPreview.document} />
              </div>
            )}

            <small>
              Placeholders: <Placeholder>{'{customer_name}'}</Placeholder>,{' '}
              <Placeholder>{'{order_number}'}</Placeholder>,{' '}
              <Placeholder>{'{order_date}'}</Placeholder>,{' '}
              <Placeholder>{'{site_name}'}</Placeholder>,{' '}
              <Placeholder>{'{site_url}'}</Placeholder>,{' '}
              <Placeholder>{'{products_list}'}</Placeholder>,{' '}
              <Placeholder>{'{unsubscribe_link}'}</Placeholder>,{' '}
              <Placeholder>{'{review_reward}'}</Placeholder>.
            </small>
            <small>
              <Placeholder>{'{review_reward}'}</Placeholder> shows the best review reward (e.g. "up to 15%") and is empty
              unless Review rewards are enabled.
            </small>
            <small>An unsubscribe link is added to the footer automatically if omitted.</small>
          </div>
        </div>
      )}
    </article>
  );
}

export default function EmailReminderSettings({
  settings,
  orderStatuses,
  previewData = {},
  isPro = false,
  upgradeUrl = '',
  onChange,
}) {
  const rules = Array.isArray(settings.rules) ? settings.rules : [];
  const freeLimitReached = !isPro && rules.length >= 1;
  const [deliveryOpen, setDeliveryOpen] = useState(true);
  const [announcement, setAnnouncement] = useState('');

  const updateRule = (id, key, value) => {
    onChange('rules', rules.map((rule) => (rule.id === id ? { ...rule, [key]: value } : rule)));
  };

  const addRule = () => {
    if (freeLimitReached) return;
    onChange('rules', [...rules, createRule(settings, orderStatuses)]);
    setAnnouncement('Reminder rule added.');
  };
  const removeRule = (id) => {
    if (window.confirm('Delete this reminder rule? Already scheduled emails will remain queued.')) {
      onChange('rules', rules.filter((rule) => rule.id !== id));
      setAnnouncement('Reminder rule deleted.');
    }
  };

  return (
    <>
      <div className="mb-5">
        <h2 className="m-0 mb-1 text-xl font-semibold tracking-tight text-foreground">Email reminders</h2>
        <p className="m-0 text-sm text-muted-foreground">Send different review requests as an order moves through its statuses.</p>
      </div>

      <div className="sktpr-gcard">
        <SectionHeader
          id="sktpr-email-delivery"
          title="Delivery"
          description="Master control and shared sender details."
          icon={Mail}
          open={deliveryOpen}
          onToggle={() => setDeliveryOpen((current) => !current)}
        />

        {deliveryOpen && (
          <div id="sktpr-email-delivery">
            <Row title="Enable reminders" desc="Send automated review requests to customers.">
              <Switch id="enable_email_reminders" label="Enable email reminders" checked={Boolean(settings.enable_email_reminders)} onChange={(value) => onChange('enable_email_reminders', value)} />
            </Row>
            <div className="sktpr-email-sender-grid">
              <label className="sktpr-field" htmlFor="from_name">
                From name
                <input id="from_name" className="sktpr-input" type="text" value={settings.from_name || ''} onChange={(event) => onChange('from_name', event.target.value)} />
              </label>
              <label className="sktpr-field" htmlFor="from_email">
                From email
                <input id="from_email" className="sktpr-input" type="email" value={settings.from_email || ''} onChange={(event) => onChange('from_email', event.target.value)} />
              </label>
            </div>
          </div>
        )}
      </div>

      <div className="sktpr-gcard">
        <div className="sktpr-reminder-rules-head">
          <span className="sktpr-gcard-icon"><FileText size={15} /></span>
          <span className="min-w-0 flex-1">
            <h3>Reminder rules</h3>
            <p>Each rule sends once per order when its status is reached.</p>
          </span>
          <div className="flex flex-col items-end gap-1.5">
            <button
              type="button"
              className="sktpr-react-button sktpr-react-button--secondary"
              onClick={addRule}
              disabled={freeLimitReached}
            >
              <Plus size={16} /> Add reminder
            </button>
            {!isPro ? (
              <small className="text-right text-xs text-muted-foreground">
                Free includes one reminder rule.{' '}
                {upgradeUrl ? <a className="font-medium text-primary-strong" href={upgradeUrl}>Unlock unlimited rules</a> : null}
              </small>
            ) : null}
          </div>
        </div>

        <div className="sr-only" aria-live="polite">{announcement}</div>
        <div className="sktpr-reminder-rules">
          {rules.length ? rules.map((rule, index) => (
            <ReminderRuleEditor
              key={rule.id}
              rule={rule}
              index={index}
              settings={settings}
              orderStatuses={orderStatuses}
              previewData={previewData}
              onUpdate={(key, value) => updateRule(rule.id, key, value)}
              onRemove={() => removeRule(rule.id)}
            />
          )) : (
            <div className="sktpr-reminder-empty">
              <FileText size={22} />
              <p>No reminder rules yet. Add one to choose a status and message.</p>
            </div>
          )}
        </div>
      </div>
    </>
  );
}
