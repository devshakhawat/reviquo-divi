import React, { useState } from 'react';
import { Star, Lock, ChevronDown } from 'lucide-react';
import { Switch, Row } from './GeneralSettings';
import { ProBadge } from '../ProFeature';

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

export default function StorefrontSettings({ settings, onChange, isPro = false, upgradeUrl = '' }) {
  const consentOn = Boolean(settings.enable_gdpr_consent);
  const [openSections, setOpenSections] = useState({
    display: true,
    privacy: false,
  });

  const toggleSection = (section) =>
    setOpenSections((current) => ({ ...current, [section]: !current[section] }));

  return (
    <>
      <div className="mb-5">
        <h2 className="m-0 mb-1 text-xl font-semibold tracking-tight text-foreground">
          Storefront settings
        </h2>
        <p className="m-0 text-sm text-muted-foreground">
          Control how reviews appear and how customer data is handled.
        </p>
      </div>

      <div className="sktpr-gcard">
        <SectionHeader
          id="sktpr-storefront-display"
          title="Display"
          description="What shoppers see in the reviews section."
          icon={Star}
          open={openSections.display}
          onToggle={() => toggleSection('display')}
        />
        {openSections.display && (
          <div id="sktpr-storefront-display">
            <Row title="Rating summary" desc="Show average rating and star histogram.">
              <Switch
                id="show_rating_summary"
                checked={settings.show_rating_summary}
                onChange={(value) => onChange('show_rating_summary', value)}
              />
            </Row>
            <Row title="Review filters" desc="Filter reviews by rating or media.">
              <Switch
                id="show_review_filters"
                checked={settings.show_review_filters}
                onChange={(value) => onChange('show_review_filters', value)}
              />
            </Row>
            <Row title={<span className="flex items-center gap-2">Rich snippets {!isPro ? <ProBadge /> : null}</span>} desc="Add media to Google structured data.">
              <Switch
                id="enable_rich_snippets"
                checked={settings.enable_rich_snippets}
                disabled={!isPro}
                onChange={(value) => onChange('enable_rich_snippets', value)}
              />
            </Row>
            <Row title={<span className="flex items-center gap-2">Verified badge {!isPro ? <ProBadge /> : null}</span>} desc="Show a badge on reviews from verified buyers.">
              <Switch
                id="show_verified_badge"
                checked={settings.show_verified_badge}
                disabled={!isPro}
                onChange={(value) => onChange('show_verified_badge', value)}
              />
            </Row>
            {!isPro && upgradeUrl ? (
              <div className="border-t border-border px-5 py-4 text-sm text-muted-foreground">
                <a className="font-medium text-primary-strong" href={upgradeUrl}>Upgrade to Pro</a>{' '}
                to unlock rich snippets and verified-customer badges.
              </div>
            ) : null}
          </div>
        )}
      </div>

      <div className="sktpr-gcard">
        <SectionHeader
          id="sktpr-storefront-privacy"
          title="Privacy"
          description="How customer review data is handled."
          icon={Lock}
          open={openSections.privacy}
          onToggle={() => toggleSection('privacy')}
        />
        {openSections.privacy && (
          <div id="sktpr-storefront-privacy">
            <Row title="Consent checkbox" desc="Require consent before submitting a review.">
              <Switch
                id="enable_gdpr_consent"
                checked={settings.enable_gdpr_consent}
                onChange={(value) => onChange('enable_gdpr_consent', value)}
              />
            </Row>
            <div className="sktpr-gsub">
              <div className={`sktpr-gsubrow flex-col items-stretch ${consentOn ? '' : 'is-disabled'}`}>
                <div className="sktpr-grow-text max-w-none">
                  <div className="sktpr-grow-title">Consent text</div>
                  <div className="sktpr-grow-desc">Shown beside the checkbox.</div>
                </div>
                <textarea
                  id="gdpr_consent_text"
                  className="sktpr-textarea mt-2 min-h-[70px]"
                  value={settings.gdpr_consent_text || ''}
                  disabled={!consentOn}
                  onChange={(event) => onChange('gdpr_consent_text', event.target.value)}
                />
              </div>
            </div>
          </div>
        )}
      </div>
    </>
  );
}
