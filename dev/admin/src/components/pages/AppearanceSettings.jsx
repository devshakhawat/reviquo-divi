import React, { useState } from 'react';
import {
  Video,
  Camera,
  Mic,
  Play,
  Star,
  MessageSquare,
  Pencil,
  FileVideo,
  Image,
  Upload,
  Ban,
  ChevronDown,
  Clapperboard,
  Palette,
  Square,
  Type,
  Ruler,
  StopCircle,
  PlusCircle,
  BadgeCheck,
} from 'lucide-react';

export const ICON_OPTIONS = [
  { value: 'video-camera', label: 'Video camera', Icon: Video },
  { value: 'camera', label: 'Camera', Icon: Camera },
  { value: 'microphone', label: 'Microphone', Icon: Mic },
  { value: 'play-circle', label: 'Play', Icon: Play },
  { value: 'star', label: 'Star', Icon: Star },
  { value: 'comment', label: 'Comment', Icon: MessageSquare },
  { value: 'pencil', label: 'Pencil', Icon: Pencil },
  { value: 'file-video-o', label: 'Video file', Icon: FileVideo },
  { value: 'picture-o', label: 'Picture', Icon: Image },
  { value: 'upload', label: 'Upload', Icon: Upload },
  { value: 'stop-circle', label: 'Stop', Icon: StopCircle },
  { value: 'plus-circle', label: 'Plus', Icon: PlusCircle },
  { value: 'none', label: 'No icon', Icon: Ban },
];

const FONT_FAMILIES = [
  { value: 'default', label: 'Theme default', stack: '' },
  { value: 'system', label: 'System UI', stack: 'system-ui, -apple-system, Segoe UI, Roboto, sans-serif' },
  { value: 'arial', label: 'Arial', stack: 'Arial, Helvetica, sans-serif' },
  { value: 'helvetica', label: 'Helvetica', stack: 'Helvetica Neue, Helvetica, Arial, sans-serif' },
  { value: 'verdana', label: 'Verdana', stack: 'Verdana, Geneva, sans-serif' },
  { value: 'tahoma', label: 'Tahoma', stack: 'Tahoma, Geneva, sans-serif' },
  { value: 'trebuchet', label: 'Trebuchet MS', stack: 'Trebuchet MS, sans-serif' },
  { value: 'georgia', label: 'Georgia', stack: 'Georgia, Times New Roman, serif' },
  { value: 'times', label: 'Times New Roman', stack: 'Times New Roman, Times, serif' },
  { value: 'courier', label: 'Courier New', stack: 'Courier New, monospace' },
];

const FONT_WEIGHTS = [
  { value: '300', label: 'Light (300)' },
  { value: '400', label: 'Regular (400)' },
  { value: '500', label: 'Medium (500)' },
  { value: '600', label: 'Semi-bold (600)' },
  { value: '700', label: 'Bold (700)' },
  { value: '800', label: 'Extra-bold (800)' },
];

const FONT_STYLES = [
  { value: 'normal', label: 'Normal' },
  { value: 'italic', label: 'Italic' },
];

const BORDER_STYLES = [
  { value: 'none', label: 'None' },
  { value: 'solid', label: 'Solid' },
  { value: 'dashed', label: 'Dashed' },
  { value: 'dotted', label: 'Dotted' },
];

const TEXT_TRANSFORMS = [
  { value: 'uppercase', label: 'UPPERCASE' },
  { value: 'capitalize', label: 'Capitalize' },
  { value: 'lowercase', label: 'lowercase' },
  { value: 'none', label: 'As typed' },
];

const SPACING_FIELDS = [
  { suffix: 'padding_y', label: 'Vertical padding', min: 0, max: 40, unit: 'px' },
  { suffix: 'padding_x', label: 'Horizontal padding', min: 0, max: 80, unit: 'px' },
  { suffix: 'font_size', label: 'Font size', min: 10, max: 24, unit: 'px' },
  { suffix: 'icon_size', label: 'Icon size', min: 10, max: 32, unit: 'px' },
];

const BUTTON_SECTIONS = [
  {
    key: 'record',
    prefix: 'review_btn_',
    title: 'Record Button',
    icon: Clapperboard,
    description: 'Opens the video recorder on product pages.',
    kind: 'solid',
    hint: '',
    fallbackText: 'Record Video',
  },
  {
    key: 'file',
    prefix: 'file_btn_',
    title: 'Video Upload Button',
    icon: FileVideo,
    description: 'Uploads a pre-recorded video file.',
    kind: 'dashed',
    hint: 'MP4 or WebM',
    fallbackText: 'Upload video',
  },
  {
    key: 'image',
    prefix: 'image_btn_',
    title: 'Photo Upload Button',
    icon: Image,
    description: 'Attaches photos to the review.',
    kind: 'dashed',
    hint: 'up to 5',
    fallbackText: 'Add photos',
  },
];

export function SettingsSection({ title, icon: Icon, description, open, onToggle, children }) {
  return (
    <div className="shrink-0 overflow-hidden rounded-xl border border-border bg-card shadow-soft">
      <button
        type="button"
        aria-expanded={open}
        onClick={onToggle}
        className="flex w-full cursor-pointer items-center justify-between gap-2 border-0 bg-transparent px-5 py-4 text-left transition-colors duration-200 hover:bg-muted/50"
      >
        <span className="flex items-center gap-2.5">
          <span className="flex h-[30px] w-[30px] shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary-strong">
            <Icon size={15} />
          </span>
          <span className="flex flex-col">
            <strong className="text-sm font-semibold tracking-tight text-foreground">{title}</strong>
            <small className="mt-0.5 text-xs font-normal text-muted-foreground">{description}</small>
          </span>
        </span>
        <ChevronDown
          className={`h-4 w-4 shrink-0 text-muted-foreground transition-transform duration-200 ${open ? 'rotate-180' : ''}`}
        />
      </button>
      {open && <div className="border-t border-border px-5 py-6">{children}</div>}
    </div>
  );
}

function FieldGroup({ title, icon: Icon, open, onToggle, children }) {
  return (
    <div className="shrink-0 overflow-hidden rounded-md border border-border">
      <button
        type="button"
        aria-expanded={open}
        onClick={onToggle}
        className="flex w-full cursor-pointer items-center justify-between gap-2 border-0 bg-muted/40 px-4 py-3 text-left transition-colors duration-200 hover:bg-muted/70"
      >
        <span className="flex items-center gap-2 text-sm font-medium leading-none text-foreground">
          <Icon className="h-4 w-4 shrink-0 text-muted-foreground" />
          {title}
        </span>
        <ChevronDown
          className={`h-4 w-4 shrink-0 text-muted-foreground transition-transform duration-200 ${open ? 'rotate-180' : ''}`}
        />
      </button>
      {open && (
        <div className="grid grid-cols-2 gap-x-5 gap-y-4 border-t border-border px-4 py-5">
          {children}
        </div>
      )}
    </div>
  );
}

export function ButtonStylePanel({ section, settings, onChange }) {
  const { prefix, hint, fallbackText } = section;
  const [previewHovered, setPreviewHovered] = useState(false);
  const [openGroups, setOpenGroups] = useState({
    colors: true,
    border: false,
    typography: false,
    spacing: false,
  });

  const toggleGroup = (key) =>
    setOpenGroups((current) => ({ ...current, [key]: !current[key] }));
  const get = (suffix) => settings[prefix + suffix];
  const set = (suffix, value) => onChange(prefix + suffix, value);

  const buttonText = get('text') || fallbackText;
  const activeIcon = ICON_OPTIONS.find((option) => option.value === get('icon')) || ICON_OPTIONS[0];
  const PreviewIcon = activeIcon.value === 'none' ? null : activeIcon.Icon;
  const fontSize = Number(get('font_size')) || 14;
  const iconSize = Number(get('icon_size')) || 15;
  const activeFamily = FONT_FAMILIES.find((option) => option.value === get('font_family')) || FONT_FAMILIES[0];

  const borderStyle = get('border_style') || 'none';
  const borderWidth = Number(get('border_width')) || 0;
  const hasBorder = borderStyle !== 'none' && borderWidth > 0;
  const borderColor = previewHovered
    ? get('hover_border_color') || get('border_color')
    : get('border_color');

  const previewStyle = {
    backgroundColor: previewHovered ? get('hover_color') || get('color') : get('color'),
    color: previewHovered ? get('hover_txt_color') || get('txt_color') : get('txt_color'),
    padding: `${Number(get('padding_y')) || 0}px ${Number(get('padding_x')) || 0}px`,
    borderRadius: `${Number(get('radius')) || 0}px`,
    fontSize: `${fontSize}px`,
    fontFamily: activeFamily.stack || undefined,
    fontWeight: get('font_weight') || '500',
    fontStyle: get('font_style') || 'normal',
    textTransform: get('text_transform') || 'none',
    border: hasBorder ? `${borderWidth}px ${borderStyle} ${borderColor}` : '0',
  };

  return (
    <div className="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(250px,300px)]">
      <div className="flex flex-col gap-3">
        <FieldGroup
          title="Colors &amp; Content"
          icon={Palette}
          open={openGroups.colors}
          onToggle={() => toggleGroup('colors')}
        >
          <label className="sktpr-field" htmlFor={`${prefix}color`}>
            Background color
            <input
              id={`${prefix}color`}
              className="sktpr-color"
              type="color"
              value={get('color')}
              onChange={(event) => set('color', event.target.value)}
            />
          </label>

          <label className="sktpr-field" htmlFor={`${prefix}txt_color`}>
            Text color
            <input
              id={`${prefix}txt_color`}
              className="sktpr-color"
              type="color"
              value={get('txt_color')}
              onChange={(event) => set('txt_color', event.target.value)}
            />
          </label>

          <label className="sktpr-field" htmlFor={`${prefix}hover_color`}>
            Hover background
            <input
              id={`${prefix}hover_color`}
              className="sktpr-color"
              type="color"
              value={get('hover_color')}
              onChange={(event) => set('hover_color', event.target.value)}
            />
          </label>

          <label className="sktpr-field" htmlFor={`${prefix}hover_txt_color`}>
            Hover text color
            <input
              id={`${prefix}hover_txt_color`}
              className="sktpr-color"
              type="color"
              value={get('hover_txt_color')}
              onChange={(event) => set('hover_txt_color', event.target.value)}
            />
          </label>

          <label className="sktpr-field col-span-full" htmlFor={`${prefix}text`}>
            Button text
            <input
              id={`${prefix}text`}
              className="sktpr-input"
              type="text"
              value={get('text')}
              onChange={(event) => set('text', event.target.value)}
              placeholder={fallbackText}
            />
          </label>

          <div className="sktpr-field col-span-full">
            Button icon
            <div className="grid grid-cols-4 gap-2" role="group" aria-label="Button icon">
              {ICON_OPTIONS.map(({ value, label, Icon }) => {
                const selected = value === activeIcon.value;
                return (
                  <button
                    key={value}
                    type="button"
                    title={label}
                    aria-pressed={selected}
                    onClick={() => set('icon', value)}
                    className={`flex cursor-pointer flex-col items-center gap-1.5 rounded-md border px-2 py-2.5 text-xs font-normal transition-colors duration-200 ${
                      selected
                        ? 'border-primary bg-primary/5 text-primary-strong'
                        : 'border-input bg-background text-muted-foreground hover:bg-muted hover:text-foreground'
                    }`}
                  >
                    <Icon className="h-4 w-4" />
                    {label}
                  </button>
                );
              })}
            </div>
          </div>
        </FieldGroup>

        <FieldGroup
          title="Border"
          icon={Square}
          open={openGroups.border}
          onToggle={() => toggleGroup('border')}
        >
          <label className="sktpr-field" htmlFor={`${prefix}border_style`}>
            Border style
            <select
              id={`${prefix}border_style`}
              className="sktpr-select"
              value={borderStyle}
              onChange={(event) => set('border_style', event.target.value)}
            >
              {BORDER_STYLES.map(({ value, label }) => (
                <option key={value} value={value}>
                  {label}
                </option>
              ))}
            </select>
          </label>

          <label className="sktpr-field" htmlFor={`${prefix}border_width`}>
            <span className="flex items-center justify-between">
              Border width
              <span className="font-normal text-muted-foreground">{borderWidth}px</span>
            </span>
            <input
              id={`${prefix}border_width`}
              className="sktpr-range mt-auto"
              type="range"
              min={0}
              max={10}
              value={borderWidth}
              disabled={borderStyle === 'none'}
              onChange={(event) => set('border_width', Number(event.target.value))}
            />
          </label>

          <label className="sktpr-field col-span-full" htmlFor={`${prefix}radius`}>
            <span className="flex items-center justify-between">
              Border Radius
              <span className="font-normal text-muted-foreground">
                {Number(get('radius')) || 0}px
              </span>
            </span>
            <input
              id={`${prefix}radius`}
              className="sktpr-range"
              type="range"
              min={0}
              max={50}
              value={Number(get('radius')) || 0}
              onChange={(event) => set('radius', Number(event.target.value))}
            />
          </label>

          <label className="sktpr-field" htmlFor={`${prefix}border_color`}>
            Border color
            <input
              id={`${prefix}border_color`}
              className="sktpr-color"
              type="color"
              value={get('border_color')}
              disabled={borderStyle === 'none'}
              onChange={(event) => set('border_color', event.target.value)}
            />
          </label>

          <label className="sktpr-field" htmlFor={`${prefix}hover_border_color`}>
            Hover border color
            <input
              id={`${prefix}hover_border_color`}
              className="sktpr-color"
              type="color"
              value={get('hover_border_color')}
              disabled={borderStyle === 'none'}
              onChange={(event) => set('hover_border_color', event.target.value)}
            />
          </label>
        </FieldGroup>

        <FieldGroup
          title="Typography"
          icon={Type}
          open={openGroups.typography}
          onToggle={() => toggleGroup('typography')}
        >
          <label className="sktpr-field col-span-full" htmlFor={`${prefix}font_family`}>
            Font family
            <select
              id={`${prefix}font_family`}
              className="sktpr-select"
              value={activeFamily.value}
              onChange={(event) => set('font_family', event.target.value)}
            >
              {FONT_FAMILIES.map(({ value, label }) => (
                <option key={value} value={value}>
                  {label}
                </option>
              ))}
            </select>
          </label>

          <label className="sktpr-field" htmlFor={`${prefix}font_weight`}>
            Font weight
            <select
              id={`${prefix}font_weight`}
              className="sktpr-select"
              value={get('font_weight') || '500'}
              onChange={(event) => set('font_weight', event.target.value)}
            >
              {FONT_WEIGHTS.map(({ value, label }) => (
                <option key={value} value={value}>
                  {label}
                </option>
              ))}
            </select>
          </label>

          <label className="sktpr-field" htmlFor={`${prefix}font_style`}>
            Font style
            <select
              id={`${prefix}font_style`}
              className="sktpr-select"
              value={get('font_style') || 'normal'}
              onChange={(event) => set('font_style', event.target.value)}
            >
              {FONT_STYLES.map(({ value, label }) => (
                <option key={value} value={value}>
                  {label}
                </option>
              ))}
            </select>
          </label>

          <label className="sktpr-field col-span-full" htmlFor={`${prefix}text_transform`}>
            Text case
            <select
              id={`${prefix}text_transform`}
              className="sktpr-select"
              value={get('text_transform') || 'none'}
              onChange={(event) => set('text_transform', event.target.value)}
            >
              {TEXT_TRANSFORMS.map(({ value, label }) => (
                <option key={value} value={value}>
                  {label}
                </option>
              ))}
            </select>
          </label>
        </FieldGroup>

        <FieldGroup
          title="Spacing &amp; Size"
          icon={Ruler}
          open={openGroups.spacing}
          onToggle={() => toggleGroup('spacing')}
        >
          {SPACING_FIELDS.map(({ suffix, label, min, max, unit }) => (
            <label className="sktpr-field" htmlFor={prefix + suffix} key={suffix}>
              <span className="flex items-center justify-between">
                {label}
                <span className="font-normal text-muted-foreground">
                  {Number(get(suffix)) || 0}
                  {unit}
                </span>
              </span>
              <input
                id={prefix + suffix}
                className="sktpr-range"
                type="range"
                min={min}
                max={max}
                value={Number(get(suffix)) || 0}
                onChange={(event) => set(suffix, Number(event.target.value))}
              />
            </label>
          ))}
        </FieldGroup>
      </div>

      <aside className="lg:sticky lg:top-6">
        <div className="rounded-md border border-border bg-muted/30 p-4">
          <span className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
            Live preview
          </span>
          <div className="mt-3 flex min-h-[150px] items-center justify-center rounded-md border border-dashed border-border bg-background px-4 py-8">
            <button
              type="button"
              className="inline-flex cursor-pointer items-center gap-1.5 leading-none transition-all duration-200 active:scale-[0.98]"
              style={previewStyle}
              onMouseEnter={() => setPreviewHovered(true)}
              onMouseLeave={() => setPreviewHovered(false)}
            >
              {PreviewIcon && <PreviewIcon style={{ width: iconSize, height: iconSize }} />}
              {buttonText}
              {section.hint && (
                <span className="text-xs font-normal" style={{ opacity: 0.65 }}>
                  {hint}
                </span>
              )}
            </button>
          </div>

          <dl className="m-0 mt-4 grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-xs">
            <dt className="font-medium text-muted-foreground">Background</dt>
            <dd className="m-0 flex items-center gap-1.5 text-foreground">
              <span
                className="h-3 w-3 rounded-sm border border-border"
                style={{ backgroundColor: get('color') }}
              />
              {get('color')?.toUpperCase()}
            </dd>
            <dt className="font-medium text-muted-foreground">Text</dt>
            <dd className="m-0 flex items-center gap-1.5 text-foreground">
              <span
                className="h-3 w-3 rounded-sm border border-border"
                style={{ backgroundColor: get('txt_color') }}
              />
              {get('txt_color')?.toUpperCase()}
            </dd>
            <dt className="font-medium text-muted-foreground">Hover</dt>
            <dd className="m-0 flex items-center gap-1.5 text-foreground">
              <span
                className="h-3 w-3 rounded-sm border border-border"
                style={{ backgroundColor: get('hover_color') }}
              />
              {get('hover_color')?.toUpperCase()}
              <span
                className="ml-1 h-3 w-3 rounded-sm border border-border"
                style={{ backgroundColor: get('hover_txt_color') }}
              />
              {get('hover_txt_color')?.toUpperCase()}
            </dd>
            <dt className="font-medium text-muted-foreground">Border</dt>
            <dd className="m-0 flex items-center gap-1.5 text-foreground">
              {hasBorder ? (
                <>
                  <span
                    className="h-3 w-3 rounded-sm border border-border"
                    style={{ backgroundColor: get('border_color') }}
                  />
                  {`${borderWidth}px ${borderStyle} ${get('border_color')?.toUpperCase()}`}
                </>
              ) : (
                'None'
              )}
            </dd>
            <dt className="font-medium text-muted-foreground">Icon</dt>
            <dd className="m-0 text-foreground">{activeIcon.label}</dd>
            <dt className="font-medium text-muted-foreground">Font</dt>
            <dd className="m-0 text-foreground">
              {activeFamily.label} · {get('font_weight') || '500'}
              {(get('font_style') || 'normal') === 'italic' ? ' · Italic' : ''}
            </dd>
          </dl>
        </div>
      </aside>
    </div>
  );
}

function VerifiedBadgeStylePanel({ settings, onChange }) {
  const size = Number(settings.verified_badge_size) || 12;
  const previewStyle = {
    backgroundColor: settings.verified_badge_bg_color,
    color: settings.verified_badge_text_color,
    fontSize: `${size}px`,
  };

  return (
    <div className="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(250px,300px)]">
      <div className="grid grid-cols-2 gap-5">
        <label className="sktpr-field" htmlFor="verified_badge_bg_color">
          Background color
          <input
            id="verified_badge_bg_color"
            className="sktpr-color"
            type="color"
            value={settings.verified_badge_bg_color}
            onChange={(event) => onChange('verified_badge_bg_color', event.target.value)}
          />
        </label>
        <label className="sktpr-field" htmlFor="verified_badge_text_color">
          Text and icon color
          <input
            id="verified_badge_text_color"
            className="sktpr-color"
            type="color"
            value={settings.verified_badge_text_color}
            onChange={(event) => onChange('verified_badge_text_color', event.target.value)}
          />
        </label>
        <label className="sktpr-field col-span-full" htmlFor="verified_badge_size">
          <span className="flex items-center justify-between">
            Badge size
            <span className="font-normal text-muted-foreground">{size}px</span>
          </span>
          <input
            id="verified_badge_size"
            className="sktpr-range"
            type="range"
            min="10"
            max="24"
            value={size}
            onChange={(event) => onChange('verified_badge_size', Number(event.target.value))}
          />
          <small>The icon, height, and spacing scale with the label.</small>
        </label>
      </div>
      <aside>
        <div className="rounded-md border border-border bg-muted/30 p-4">
          <span className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
            Live preview
          </span>
          <div className="mt-3 flex min-h-[110px] items-center justify-center rounded-md border border-dashed border-border bg-background px-4 py-8">
            <span
              className="inline-flex h-[2.25em] items-center gap-[0.5em] whitespace-nowrap rounded-full pl-[0.5833em] pr-[0.8333em] font-semibold tracking-[0.01em]"
              style={previewStyle}
            >
              <BadgeCheck className="h-[1.0833em] w-[1.0833em]" />
              Verified Customer
            </span>
          </div>
        </div>
      </aside>
    </div>
  );
}

export default function AppearanceSettings({ settings, onChange }) {
  const [openSections, setOpenSections] = useState({
    record: true,
    file: false,
    image: false,
    badge: false,
  });

  const toggleSection = (key) =>
    setOpenSections((current) => ({ ...current, [key]: !current[key] }));

  return (
    <>
      <div className="mb-5">
        <h2 className="m-0 mb-1 text-xl font-semibold tracking-tight text-foreground">
          Appearance settings
        </h2>
        <p className="m-0 text-sm text-muted-foreground">
          Style the review buttons shown on your storefront.
        </p>
      </div>

      <div className="flex flex-col gap-4">
        {BUTTON_SECTIONS.map((section) => (
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
        <SettingsSection
          title="Verified Customer Badge"
          icon={BadgeCheck}
          description="Style the verification label shown beside confirmed buyers."
          open={openSections.badge}
          onToggle={() => toggleSection('badge')}
        >
          <VerifiedBadgeStylePanel settings={settings} onChange={onChange} />
        </SettingsSection>
      </div>
    </>
  );
}
