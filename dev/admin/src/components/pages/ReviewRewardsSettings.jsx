import React, { useMemo, useState } from 'react';
import { Gift, Ticket, ShieldCheck, Mail, Store, Type, Image, Video } from 'lucide-react';
import { Switch, Row } from './GeneralSettings';
import { ProFeatureGate } from '../ProFeature';

const tierMeta = [
  { key: 'text', label: 'Text review', desc: 'Written review only.', icon: Type },
  { key: 'photo', label: 'Photo review', desc: 'At least one photo.', icon: Image },
  { key: 'video', label: 'Video review', desc: 'A recorded or uploaded video.', icon: Video },
];

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

export function formatDiscount(tier, currencySymbol) {
  const amount = Number(tier?.amount) || 0;
  return tier?.discount_type === 'fixed_cart' ? `${currencySymbol}${amount}` : `${amount}%`;
}

// Mirrors Review_Rewards::get_reward_teaser(): the most valuable enabled tier,
// prefixed with "up to" when several tiers are enabled.
export function rewardTeaser(settings, currencySymbol) {
  if (!settings.enable_review_rewards) return '';
  const enabled = tierMeta
    .map(({ key }) => settings.tiers?.[key])
    .filter((tier) => tier?.enabled && Number(tier.amount) > 0);
  if (!enabled.length) return '';
  const amount = formatDiscount(enabled[enabled.length - 1], currencySymbol);
  return enabled.length > 1 ? `up to ${amount}` : amount;
}

function buildEmailPreview(settings, previewData, currencySymbol) {
  const siteUrl = String(previewData.site_url || 'https://example.com').replace(/\/+$/, '');
  const expiry = new Date();
  expiry.setDate(expiry.getDate() + (Number(settings.expiry_days) || 0));
  const values = {
    '{customer_name}': previewData.customer_name || 'Alex Johnson',
    '{coupon_code}': `${settings.coupon_prefix || ''}X7K2M9QA`,
    '{coupon_amount}': formatDiscount(settings.tiers?.video, currencySymbol),
    '{coupon_expiry}': Number(settings.expiry_days) > 0 ? expiry.toLocaleDateString() : 'No expiry',
    '{product_name}': previewData.product_name || 'Sample product',
    '{product_url}': `${siteUrl}/product/sample-product/`,
    '{shop_url}': `${siteUrl}/shop/`,
    '{site_name}': previewData.site_name || 'Your Store',
    '{site_url}': siteUrl,
  };
  const htmlValues = Object.fromEntries(Object.entries(values).map(([key, value]) => [key, escapeHtml(value)]));

  return {
    subject: applyPlaceholders(settings.email_subject, values),
    document: `<!doctype html>
      <html>
        <head>
          <meta charset="utf-8">
          <meta http-equiv="Content-Security-Policy" content="default-src 'none'; img-src https: http: data:; style-src 'unsafe-inline'; font-src https: data:; script-src 'none'; object-src 'none'; base-uri 'none'; form-action 'none'">
          <meta name="viewport" content="width=device-width, initial-scale=1">
          <style>html { background: #f7f5ef; } body { box-sizing: border-box; max-width: 680px; min-height: 100vh; margin: 0 auto; padding: 28px; background: #fff; color: #17201e; font-family: Arial, sans-serif; line-height: 1.5; overflow-wrap: anywhere; } img { max-width: 100%; } a { pointer-events: none; }</style>
        </head>
        <body>${applyPlaceholders(settings.email_content, htmlValues)}</body>
      </html>`,
  };
}

function Placeholder({ children }) {
  return <code className="rounded bg-muted px-1 py-0.5 text-xs text-foreground">{children}</code>;
}

function CardHead({ title, description, icon: Icon }) {
  return (
    <div className="sktpr-gcard-head">
      <span className="sktpr-gcard-icon">
        <Icon size={15} />
      </span>
      <span className="min-w-0 flex-1">
        <span className="sktpr-gcard-titlerow">
          <h3>{title}</h3>
        </span>
        <span className="sktpr-gcard-sub block">{description}</span>
      </span>
    </div>
  );
}

function TierCard({ meta, tier, currencySymbol, onChange }) {
  const Icon = meta.icon;
  const id = `reward-tier-${meta.key}`;

  return (
    <div className={`rounded-lg border p-4 ${tier.enabled ? 'border-primary/30 bg-primary/5' : 'border-border bg-card'}`}>
      <div className="mb-3 flex items-center gap-2">
        <Icon size={16} className="shrink-0 text-muted-foreground" />
        <span className="min-w-0 flex-1">
          <strong className="block text-sm text-foreground">{meta.label}</strong>
          <small className="text-xs text-muted-foreground">{meta.desc}</small>
        </span>
        <Switch
          id={`${id}-enabled`}
          label={`${tier.enabled ? 'Disable' : 'Enable'} ${meta.label} reward`}
          checked={Boolean(tier.enabled)}
          onChange={(value) => onChange('enabled', value)}
        />
      </div>
      <div className="grid grid-cols-2 gap-2">
        <label className="sktpr-field" htmlFor={`${id}-type`}>
          Type
          <select
            id={`${id}-type`}
            className="sktpr-select"
            value={tier.discount_type}
            disabled={!tier.enabled}
            onChange={(event) => onChange('discount_type', event.target.value)}
          >
            <option value="percent">Percent</option>
            <option value="fixed_cart">Fixed ({currencySymbol})</option>
          </select>
        </label>
        <label className="sktpr-field" htmlFor={`${id}-amount`}>
          Amount
          <input
            id={`${id}-amount`}
            className="sktpr-input"
            type="number"
            min="0"
            max={tier.discount_type === 'percent' ? 100 : undefined}
            step="0.01"
            value={tier.amount}
            disabled={!tier.enabled}
            onChange={(event) => onChange('amount', event.target.value)}
          />
        </label>
      </div>
    </div>
  );
}

export default function ReviewRewardsSettings({
  settings,
  previewData = {},
  currencySymbol = '$',
  isPro = false,
  upgradeUrl = '',
  onChange,
}) {
  const [contentTab, setContentTab] = useState('html');
  const tiers = settings.tiers || {};
  const teaser = rewardTeaser(settings, currencySymbol);
  const emailPreview = useMemo(
    () => buildEmailPreview(settings, previewData, currencySymbol),
    [settings, previewData, currencySymbol]
  );

  if (!isPro) {
    return (
      <ProFeatureGate
        title="Review rewards"
        description="Thank reviewers with a single-use discount coupon when their review is approved. Offer bigger rewards for photo and video reviews to collect more social proof."
        upgradeUrl={upgradeUrl}
      />
    );
  }

  const updateTier = (key, field, value) =>
    onChange('tiers', { ...tiers, [key]: { ...tiers[key], [field]: value } });

  const handleTabKeyDown = (event) => {
    if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
    event.preventDefault();
    const nextTab = event.key === 'ArrowLeft' || event.key === 'Home' ? 'html' : 'preview';
    setContentTab(nextTab);
    document.getElementById(`sktpr-reward-email-${nextTab}-tab`)?.focus();
  };

  return (
    <>
      <div className="mb-5">
        <h2 className="m-0 mb-1 text-xl font-semibold tracking-tight text-foreground">Review rewards</h2>
        <p className="m-0 text-sm text-muted-foreground">
          Email a single-use discount coupon when a review is approved. Rewards never depend on the star rating, which
          keeps them compliant with FTC and Google review policies.
        </p>
      </div>

      <div className="sktpr-gcard">
        <CardHead title="Reward tiers" description="The most valuable enabled tier a review qualifies for is issued." icon={Gift} />
        <Row title="Enable review rewards" desc="Create and email a coupon when an eligible review is approved.">
          <Switch
            id="enable_review_rewards"
            label="Enable review rewards"
            checked={Boolean(settings.enable_review_rewards)}
            onChange={(value) => onChange('enable_review_rewards', value)}
          />
        </Row>
        <div className="grid grid-cols-3 gap-3 p-4">
          {tierMeta.map((meta) => (
            <TierCard
              key={meta.key}
              meta={meta}
              tier={tiers[meta.key] || {}}
              currencySymbol={currencySymbol}
              onChange={(field, value) => updateTier(meta.key, field, value)}
            />
          ))}
        </div>
      </div>

      <div className="sktpr-gcard">
        <CardHead title="Coupon rules" description="Applied to every reward coupon. Coupons are single-use and locked to the reviewer's email." icon={Ticket} />
        <div className="sktpr-email-sender-grid">
          <label className="sktpr-field" htmlFor="coupon_prefix">
            Code prefix
            <input id="coupon_prefix" className="sktpr-input" type="text" maxLength="20" value={settings.coupon_prefix || ''} onChange={(event) => onChange('coupon_prefix', event.target.value.toUpperCase())} />
            <small>Letters, numbers, dashes. A random suffix is added.</small>
          </label>
          <label className="sktpr-field" htmlFor="expiry_days">
            Expires after (days)
            <input id="expiry_days" className="sktpr-input" type="number" min="0" max="3650" value={settings.expiry_days} onChange={(event) => onChange('expiry_days', event.target.value)} />
            <small>Use 0 for no expiry.</small>
          </label>
          <label className="sktpr-field" htmlFor="minimum_amount">
            Minimum spend ({currencySymbol})
            <input id="minimum_amount" className="sktpr-input" type="number" min="0" step="0.01" value={settings.minimum_amount ?? ''} onChange={(event) => onChange('minimum_amount', event.target.value)} />
            <small>Leave empty for no minimum.</small>
          </label>
        </div>
        <Row title="Individual use only" desc="The coupon cannot be combined with other coupons.">
          <Switch id="individual_use" label="Individual use only" checked={Boolean(settings.individual_use)} onChange={(value) => onChange('individual_use', value)} />
        </Row>
        <Row title="Exclude sale items" desc="The discount does not apply to products on sale.">
          <Switch id="exclude_sale_items" label="Exclude sale items" checked={Boolean(settings.exclude_sale_items)} onChange={(value) => onChange('exclude_sale_items', value)} />
        </Row>
      </div>

      <div className="sktpr-gcard">
        <CardHead title="Eligibility" description="Guard against coupon farming." icon={ShieldCheck} />
        <Row title="Verified owners only" desc="Only reviewers who bought the product earn a reward.">
          <Switch id="require_verified_owner" label="Verified owners only" checked={Boolean(settings.require_verified_owner)} onChange={(value) => onChange('require_verified_owner', value)} />
        </Row>
        <Row title="Revoke on unapprove" desc="Suspend an unused coupon if its review is later unapproved, spammed or trashed.">
          <Switch id="revoke_on_unapprove" label="Revoke on unapprove" checked={Boolean(settings.revoke_on_unapprove)} onChange={(value) => onChange('revoke_on_unapprove', value)} />
        </Row>
        <div className="sktpr-email-sender-grid">
          <label className="sktpr-field" htmlFor="reward_limit">
            Reward limit
            <select id="reward_limit" className="sktpr-select" value={settings.reward_limit} onChange={(event) => onChange('reward_limit', event.target.value)}>
              <option value="product">One per customer per product</option>
              <option value="order">One per customer per order</option>
              <option value="none">No limit</option>
            </select>
          </label>
          <label className="sktpr-field" htmlFor="min_review_length">
            Minimum review length
            <input id="min_review_length" className="sktpr-input" type="number" min="0" max="5000" value={settings.min_review_length} onChange={(event) => onChange('min_review_length', event.target.value)} />
            <small>Characters. Use 0 for no minimum.</small>
          </label>
        </div>
      </div>

      <div className="sktpr-gcard">
        <CardHead title="Reward email" description="Sent to the reviewer with their coupon. Uses the sender from Email reminders." icon={Mail} />
        <div className="p-4 pt-0">
          <label className="sktpr-field" htmlFor="reward_email_subject">
            Subject
            <input id="reward_email_subject" className="sktpr-input" type="text" value={settings.email_subject || ''} onChange={(event) => onChange('email_subject', event.target.value)} />
          </label>

          <div className="sktpr-field">
            <div className="sktpr-email-content-heading">
              <span id="reward-email-content-label">Content</span>
              <div className="sktpr-email-content-tabs" role="tablist" aria-label="Reward email content view" onKeyDown={handleTabKeyDown}>
                {[
                  ['html', 'HTML'],
                  ['preview', 'Preview'],
                ].map(([value, label]) => (
                  <button
                    key={value}
                    id={`sktpr-reward-email-${value}-tab`}
                    type="button"
                    role="tab"
                    aria-selected={contentTab === value}
                    aria-controls={`sktpr-reward-email-${value}-panel`}
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
              <div id="sktpr-reward-email-html-panel" role="tabpanel" aria-labelledby="sktpr-reward-email-html-tab">
                <textarea
                  className="sktpr-textarea"
                  rows="12"
                  aria-labelledby="reward-email-content-label"
                  value={settings.email_content || ''}
                  onChange={(event) => onChange('email_content', event.target.value)}
                />
              </div>
            ) : (
              <div id="sktpr-reward-email-preview-panel" className="sktpr-email-preview" role="tabpanel" aria-labelledby="sktpr-reward-email-preview-tab">
                <div className="sktpr-email-preview-meta">
                  <span className="sktpr-email-preview-badge">Sample data · video tier</span>
                  <dl>
                    <div><dt>Subject</dt><dd>{emailPreview.subject || '(No subject)'}</dd></div>
                  </dl>
                </div>
                <iframe className="sktpr-email-preview-frame" title="Rendered reward email preview" sandbox="" srcDoc={emailPreview.document} />
              </div>
            )}

            <small>
              Placeholders: <Placeholder>{'{coupon_code}'}</Placeholder> (required),{' '}
              <Placeholder>{'{coupon_amount}'}</Placeholder>, <Placeholder>{'{coupon_expiry}'}</Placeholder>,{' '}
              <Placeholder>{'{customer_name}'}</Placeholder>, <Placeholder>{'{product_name}'}</Placeholder>,{' '}
              <Placeholder>{'{product_url}'}</Placeholder>, <Placeholder>{'{shop_url}'}</Placeholder>,{' '}
              <Placeholder>{'{site_name}'}</Placeholder>, <Placeholder>{'{site_url}'}</Placeholder>.
            </small>
          </div>
        </div>
      </div>

      <div className="sktpr-gcard">
        <CardHead title="Promote the reward" description="Tell customers about the reward before they write their review." icon={Store} />
        <Row title="Show notice on the review form" desc="Displayed above the video and photo buttons.">
          <Switch id="show_form_notice" label="Show reward notice on the review form" checked={Boolean(settings.show_form_notice)} onChange={(value) => onChange('show_form_notice', value)} />
        </Row>
        <div className="p-4 pt-0">
          <label className="sktpr-field" htmlFor="form_notice_text">
            Notice text
            <input id="form_notice_text" className="sktpr-input" type="text" value={settings.form_notice_text || ''} onChange={(event) => onChange('form_notice_text', event.target.value)} />
            <small>
              <Placeholder>{'{review_reward}'}</Placeholder> becomes {teaser ? <strong>{teaser}</strong> : 'the best reward (enable rewards to preview)'}.
              The same placeholder works in reminder emails.
            </small>
          </label>
        </div>
      </div>
    </>
  );
}
