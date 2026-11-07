import React, { useState } from 'react';
import { Video, ShieldCheck, ChevronDown } from 'lucide-react';

// Shared toggle used by other settings pages (Storefront). Kept here so those
// pages can import it. Renders its description only when one is provided.
export function Toggle({ id, label, description, checked, onChange, sub = false, disabled = false }) {
  return (
    <label
      className={`sktpr-react-toggle ${sub ? 'sktpr-react-toggle--sub' : ''}`}
      htmlFor={id}
    >
      <span>
        <strong>{label}</strong>
        {description ? <small>{description}</small> : null}
      </span>
      <input
        id={id}
        type="checkbox"
        checked={Boolean(checked)}
        disabled={disabled}
        onChange={(event) => onChange(event.target.checked)}
      />
      <i aria-hidden="true" />
    </label>
  );
}

export function Switch({ id, checked, disabled = false, label, onChange }) {
  return (
    <>
      <input
        id={id}
        type="checkbox"
        className="sktpr-switch-input sr-only"
        aria-label={label}
        checked={Boolean(checked)}
        disabled={disabled}
        onChange={(event) => onChange(event.target.checked)}
      />
      <label htmlFor={id} className="sktpr-switch" />
    </>
  );
}

export function Row({ title, desc, children }) {
  return (
    <div className="sktpr-grow">
      <div className="sktpr-grow-text">
        <div className="sktpr-grow-title">{title}</div>
        <div className="sktpr-grow-desc">{desc}</div>
      </div>
      <div className="sktpr-grow-control">{children}</div>
    </div>
  );
}

export function SubRow({ title, desc, disabled = false, children }) {
  return (
    <div className={`sktpr-gsubrow ${disabled ? 'is-disabled' : ''}`}>
      <div className="sktpr-grow-text">
        <div className="sktpr-grow-title">{title}</div>
        <div className="sktpr-grow-desc">{desc}</div>
      </div>
      <div className="sktpr-grow-control">{children}</div>
    </div>
  );
}

function SectionHeader({ id, title, description, icon: Icon, tag, neutral, open, onToggle }) {
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
          {tag ? <span className="sktpr-gtag">{tag}</span> : null}
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

export default function GeneralSettings({ settings, onChange, isPro = false, upgradeUrl = '' }) {
  const webcamOn = Boolean(settings.enable_video_btn);
  const uploadOn = Boolean(settings.show_file_uploader);
  const photoUploadOn = Boolean(settings.show_image_uploader);
  const [openSections, setOpenSections] = useState({
    collection: true,
    moderation: false,
  });

  const toggleSection = (section) =>
    setOpenSections((current) => ({ ...current, [section]: !current[section] }));

  return (
    <>
      <div className="mb-5">
        <h2 className="m-0 mb-1 text-xl font-semibold tracking-tight text-foreground">
          General settings
        </h2>
        <p className="m-0 text-sm text-muted-foreground">
          Choose how customers can attach video and photos to their product reviews.
        </p>
      </div>

      <div className="sktpr-gcard">
        <SectionHeader
          id="sktpr-general-collection"
          title="Review collection"
          description="Choose which capture methods are available to customers."
          icon={Video}
          tag="Essential"
          open={openSections.collection}
          onToggle={() => toggleSection('collection')}
        />

        {openSections.collection && (
          <div id="sktpr-general-collection">
        <Row
          title="Webcam recording"
          desc="Let customers record a testimonial directly on the product page."
        >
          <Switch
            id="enable_video_btn"
            checked={settings.enable_video_btn}
            onChange={(value) => onChange('enable_video_btn', value)}
          />
        </Row>

        <div className="sktpr-gsub">
          <SubRow
            title="Require before submitting"
            desc="Customers must record a video to submit a review."
            disabled={!webcamOn}
          >
            <Switch
              id="required_video_recording"
              checked={settings.required_video_recording}
              disabled={!webcamOn}
              onChange={(value) => onChange('required_video_recording', value)}
            />
          </SubRow>
          <SubRow
            title="Maximum recording length"
            desc="Recordings stop automatically at this limit."
            disabled={!webcamOn}
          >
            <div className="flex flex-col items-end gap-1.5">
              <select
                id="video_duration"
                className="sktpr-select w-40"
                value={String(settings.video_duration)}
                disabled={!webcamOn}
                onChange={(event) => onChange('video_duration', event.target.value)}
              >
                {Array.from({ length: 10 }, (_, index) => {
                  const minutes = index + 1;
                  const isLocked = !isPro && minutes > 2;
                  return (
                    <option key={minutes} value={String(minutes)} disabled={isLocked}>
                      {minutes} {minutes === 1 ? 'minute' : 'minutes'}{isLocked ? ' — Pro' : ''}
                    </option>
                  );
                })}
              </select>
              {!isPro ? (
                <small className="text-right text-xs text-muted-foreground">
                  1–2 minutes are free.{' '}
                  {upgradeUrl ? <a className="font-medium text-primary-strong" href={upgradeUrl}>Unlock 3–10 minutes</a> : null}
                </small>
              ) : null}
            </div>
          </SubRow>
        </div>

        <Row
          title="Video file upload"
          desc="Allow customers to upload a pre-recorded video file."
        >
          <Switch
            id="show_file_uploader"
            checked={settings.show_file_uploader}
            onChange={(value) => onChange('show_file_uploader', value)}
          />
        </Row>

        <div className="sktpr-gsub">
          <SubRow
            title="Require before submitting"
            desc="Customers must upload a video to submit a review."
            disabled={!uploadOn}
          >
            <Switch
              id="required_file_upload"
              checked={settings.required_file_upload}
              disabled={!uploadOn}
              onChange={(value) => onChange('required_file_upload', value)}
            />
          </SubRow>
        </div>

        <Row
          title="Photo upload"
          desc="Allow customers to attach up to 5 photos to their reviews."
        >
          <Switch
            id="show_image_uploader"
            checked={settings.show_image_uploader}
            onChange={(value) => onChange('show_image_uploader', value)}
          />
        </Row>

        <div className="sktpr-gsub">
          <SubRow
            title="Require before submitting"
            desc="Customers must upload at least one photo to submit a review."
            disabled={!photoUploadOn}
          >
            <Switch
              id="required_image_upload"
              checked={settings.required_image_upload}
              disabled={!photoUploadOn}
              onChange={(value) => onChange('required_image_upload', value)}
            />
          </SubRow>
        </div>
          </div>
        )}
      </div>

      <div className="sktpr-gcard">
        <SectionHeader
          id="sktpr-general-moderation"
          title="Moderation"
          description="Decide how video reviews get published."
          icon={ShieldCheck}
          open={openSections.moderation}
          onToggle={() => toggleSection('moderation')}
        />

        {openSections.moderation && (
          <div id="sktpr-general-moderation">
        <Row
          title="Auto-approve videos"
          desc="Publish video reviews immediately without moderation."
        >
          <Switch
            id="auto_approve_video_reviews"
            checked={settings.auto_approve_video_reviews}
            onChange={(value) => onChange('auto_approve_video_reviews', value)}
          />
        </Row>
          </div>
        )}
      </div>
    </>
  );
}
