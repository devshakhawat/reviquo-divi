import React, { useState } from 'react';
import { Video, StopCircle, PlusCircle, Layout } from 'lucide-react';
import { SettingsSection, ButtonStylePanel, ICON_OPTIONS } from './AppearanceSettings';

const MODAL_BUTTON_SECTIONS = [
  {
    key: 'start',
    prefix: 'modal_start_btn_',
    title: 'Start Recording Button',
    icon: Video,
    description: 'Begins the webcam recording.',
    kind: 'solid',
    hint: '',
    fallbackText: 'Start Recording',
  },
  {
    key: 'stop',
    prefix: 'modal_stop_btn_',
    title: 'Stop Recording Button',
    icon: StopCircle,
    description: 'Ends the recording.',
    kind: 'solid',
    hint: '',
    fallbackText: 'Stop Recording',
  },
  {
    key: 'add',
    prefix: 'modal_add_btn_',
    title: 'Add Video Button',
    icon: PlusCircle,
    description: 'Attaches the recording to the review.',
    kind: 'solid',
    hint: '',
    fallbackText: 'Add this video',
  },
];

export default function RecordingModalSettings({ settings, onChange }) {
  const [openSections, setOpenSections] = useState({
    design: true,
    start: false,
    stop: false,
    add: false,
  });

  const toggleSection = (key) =>
    setOpenSections((current) => ({ ...current, [key]: !current[key] }));

  const overlayOpacity = Number(settings.modal_overlay_opacity ?? 40);
  const titleIcon =
    ICON_OPTIONS.find((option) => option.value === (settings.modal_title_icon ?? 'video-camera')) ||
    ICON_OPTIONS[0];
  const TitleIcon = titleIcon.value === 'none' ? null : titleIcon.Icon;

  return (
    <>
      <div className="mb-5">
        <h2 className="m-0 mb-1 text-xl font-semibold tracking-tight text-foreground">
          Recording modal
        </h2>
        <p className="m-0 text-sm text-muted-foreground">
          Style the popup customers use to record a video review.
        </p>
      </div>

      <div className="flex flex-col gap-4">
        <SettingsSection
          title="Modal Design"
          icon={Layout}
          description="The popup window, backdrop, and title."
          open={openSections.design}
          onToggle={() => toggleSection('design')}
        >
          <div className="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(250px,300px)]">
            <div className="grid grid-cols-2 gap-x-5 gap-y-4">
            <label className="sktpr-field" htmlFor="modal_title_text">
              Title text
              <input
                id="modal_title_text"
                className="sktpr-input"
                type="text"
                value={settings.modal_title_text ?? 'Record Review'}
                onChange={(event) => onChange('modal_title_text', event.target.value)}
              />
            </label>

            <label className="sktpr-field" htmlFor="modal_title_icon">
              Title icon
              <select
                id="modal_title_icon"
                className="sktpr-select"
                value={settings.modal_title_icon ?? 'video-camera'}
                onChange={(event) => onChange('modal_title_icon', event.target.value)}
              >
                {ICON_OPTIONS.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>
            </label>

            <label className="sktpr-field" htmlFor="modal_title_color">
              Title color
              <input
                id="modal_title_color"
                className="sktpr-color"
                type="color"
                value={settings.modal_title_color || '#17201E'}
                onChange={(event) => onChange('modal_title_color', event.target.value)}
              />
            </label>

            <label className="sktpr-field" htmlFor="modal_bg_color">
              Modal background
              <input
                id="modal_bg_color"
                className="sktpr-color"
                type="color"
                value={settings.modal_bg_color ?? '#FFFFFF'}
                onChange={(event) => onChange('modal_bg_color', event.target.value)}
              />
            </label>

            <label className="sktpr-field" htmlFor="modal_radius">
              <span className="flex items-center justify-between">
                Corner radius
                <span className="font-normal text-muted-foreground">
                  {Number(settings.modal_radius ?? 7)}px
                </span>
              </span>
              <input
                id="modal_radius"
                className="sktpr-range mt-auto"
                type="range"
                min="0"
                max="30"
                value={Number(settings.modal_radius ?? 7)}
                onChange={(event) => onChange('modal_radius', Number(event.target.value))}
              />
            </label>

            <label className="sktpr-field" htmlFor="modal_overlay_color">
              Backdrop color
              <input
                id="modal_overlay_color"
                className="sktpr-color"
                type="color"
                value={settings.modal_overlay_color ?? '#000000'}
                onChange={(event) => onChange('modal_overlay_color', event.target.value)}
              />
            </label>

            <label className="sktpr-field" htmlFor="modal_overlay_opacity">
              <span className="flex items-center justify-between">
                Backdrop opacity
                <span className="font-normal text-muted-foreground">{overlayOpacity}%</span>
              </span>
              <input
                id="modal_overlay_opacity"
                className="sktpr-range mt-auto"
                type="range"
                min="0"
                max="95"
                value={overlayOpacity}
                onChange={(event) => onChange('modal_overlay_opacity', Number(event.target.value))}
              />
            </label>
            </div>

            <aside className="lg:sticky lg:top-6">
              <div className="rounded-md border border-border bg-muted/30 p-4">
                <span className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                  Live preview
                </span>
                <div
                  className="mt-3 flex min-h-[150px] items-center justify-center rounded-md border border-dashed border-border px-4 py-8"
                  style={{
                    backgroundColor: `${settings.modal_overlay_color ?? '#000000'}${Math.round(
                      (overlayOpacity / 100) * 255
                    )
                      .toString(16)
                      .padStart(2, '0')}`,
                  }}
                >
                  <div
                    className="w-full max-w-[200px] px-4 py-4 text-center shadow-lg"
                    style={{
                      backgroundColor: settings.modal_bg_color ?? '#FFFFFF',
                      borderRadius: `${Number(settings.modal_radius ?? 7)}px`,
                    }}
                  >
                    <strong
                      className="flex items-center justify-center gap-2 text-sm font-medium"
                      style={{ color: settings.modal_title_color || undefined }}
                    >
                      {TitleIcon && <TitleIcon className="h-4 w-4 shrink-0" />}
                      {settings.modal_title_text || 'Record Review'}
                    </strong>
                    <p className="m-0 mt-2 text-xs text-muted-foreground">Recorder popup</p>
                  </div>
                </div>
              </div>
            </aside>
          </div>
        </SettingsSection>

        {MODAL_BUTTON_SECTIONS.map((section) => (
          <SettingsSection
            key={section.key}
            title={section.title}
            icon={section.icon}
            description={section.description}
            open={openSections[section.key]}
            onToggle={() => toggleSection(section.key)}
          >
            <ButtonStylePanel section={section} settings={settings} onChange={onChange} />
          </SettingsSection>
        ))}
      </div>
    </>
  );
}
