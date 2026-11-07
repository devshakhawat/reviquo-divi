import React, { useState, useEffect, useCallback } from 'react';
import { CheckCircle2, XCircle, X, TriangleAlert } from 'lucide-react';
import DashboardHeader from './components/pages/DashboardHeader';
import DashboardSidebar from './components/pages/DashboardSidebar';
import GeneralSettings from './components/pages/GeneralSettings';
import StorefrontSettings from './components/pages/StorefrontSettings';
import AppearanceSettings from './components/pages/AppearanceSettings';
import RecordingModalSettings from './components/pages/RecordingModalSettings';
import EmailReminderSettings from './components/pages/EmailReminderSettings';
import ReviewRewardsSettings from './components/pages/ReviewRewardsSettings';

const defaults = {
  enable_video_btn: true,
  show_file_uploader: true,
  show_image_uploader: true,
  required_video_recording: false,
  required_file_upload: false,
  required_image_upload: false,
  auto_approve_video_reviews: false,
  enable_gdpr_consent: false,
  show_rating_summary: true,
  show_review_filters: true,
  enable_rich_snippets: true,
  show_verified_badge: true,
  verified_badge_size: 12,
  verified_badge_bg_color: '#E6F2EA',
  verified_badge_text_color: '#2F7552',
  gdpr_consent_text:
    'I consent to my name, review, and any uploaded photos or videos being stored and displayed publicly on this site.',
  video_duration: 2,
  review_btn_color: '#FF694D',
  review_btn_txt_color: '#FFFFFF',
  review_btn_hover_color: '#EF4F31',
  review_btn_hover_txt_color: '#FFFFFF',
  review_btn_border_style: 'none',
  review_btn_border_width: 0,
  review_btn_border_color: '#FF694D',
  review_btn_hover_border_color: '#EF4F31',
  review_btn_text: 'Record Video',
  review_btn_icon: 'video-camera',
  review_btn_padding_y: 12,
  review_btn_padding_x: 20,
  review_btn_radius: 3,
  review_btn_font_size: 14,
  review_btn_icon_size: 15,
  review_btn_font_family: 'default',
  review_btn_font_weight: '500',
  review_btn_font_style: 'normal',
  review_btn_text_transform: 'uppercase',
  file_btn_color: '#F7F5EF',
  file_btn_txt_color: '#17201E',
  file_btn_hover_color: '#EEF4EF',
  file_btn_hover_txt_color: '#17201E',
  file_btn_border_style: 'dashed',
  file_btn_border_width: 1,
  file_btn_border_color: '#DFE2DA',
  file_btn_hover_border_color: '#FF694D',
  file_btn_text: 'Upload video',
  file_btn_icon: 'file-video-o',
  file_btn_padding_y: 10,
  file_btn_padding_x: 18,
  file_btn_radius: 6,
  file_btn_font_size: 14,
  file_btn_icon_size: 16,
  file_btn_font_family: 'default',
  file_btn_font_weight: '500',
  file_btn_font_style: 'normal',
  file_btn_text_transform: 'none',
  image_btn_color: '#F7F5EF',
  image_btn_txt_color: '#17201E',
  image_btn_hover_color: '#EEF4EF',
  image_btn_hover_txt_color: '#17201E',
  image_btn_border_style: 'dashed',
  image_btn_border_width: 1,
  image_btn_border_color: '#DFE2DA',
  image_btn_hover_border_color: '#FF694D',
  image_btn_text: 'Add photos',
  image_btn_icon: 'camera',
  image_btn_padding_y: 10,
  image_btn_padding_x: 18,
  image_btn_radius: 6,
  image_btn_font_size: 14,
  image_btn_icon_size: 16,
  image_btn_font_family: 'default',
  image_btn_font_weight: '500',
  image_btn_font_style: 'normal',
  image_btn_text_transform: 'none',
  modal_bg_color: '#FFFFFF',
  modal_radius: 7,
  modal_overlay_color: '#000000',
  modal_overlay_opacity: 40,
  modal_title_text: 'Record Review',
  modal_title_color: '#17201E',
  modal_title_icon: 'video-camera',
  modal_start_btn_color: '#FF694D',
  modal_start_btn_txt_color: '#FFFFFF',
  modal_start_btn_hover_color: '#EF4F31',
  modal_start_btn_hover_txt_color: '#FFFFFF',
  modal_start_btn_border_style: 'none',
  modal_start_btn_border_width: 0,
  modal_start_btn_border_color: '#FF694D',
  modal_start_btn_hover_border_color: '#EF4F31',
  modal_start_btn_text: 'Start Recording',
  modal_start_btn_icon: 'video-camera',
  modal_start_btn_padding_y: 10,
  modal_start_btn_padding_x: 20,
  modal_start_btn_radius: 3,
  modal_start_btn_font_size: 12,
  modal_start_btn_icon_size: 12,
  modal_start_btn_font_family: 'default',
  modal_start_btn_font_weight: '400',
  modal_start_btn_font_style: 'normal',
  modal_start_btn_text_transform: 'uppercase',
  modal_stop_btn_color: '#C6272A',
  modal_stop_btn_txt_color: '#FFFFFF',
  modal_stop_btn_hover_color: '#A81E21',
  modal_stop_btn_hover_txt_color: '#FFFFFF',
  modal_stop_btn_border_style: 'none',
  modal_stop_btn_border_width: 0,
  modal_stop_btn_border_color: '#C6272A',
  modal_stop_btn_hover_border_color: '#C6272A',
  modal_stop_btn_text: 'Stop Recording',
  modal_stop_btn_icon: 'stop-circle',
  modal_stop_btn_padding_y: 10,
  modal_stop_btn_padding_x: 20,
  modal_stop_btn_radius: 3,
  modal_stop_btn_font_size: 12,
  modal_stop_btn_icon_size: 12,
  modal_stop_btn_font_family: 'default',
  modal_stop_btn_font_weight: '400',
  modal_stop_btn_font_style: 'normal',
  modal_stop_btn_text_transform: 'uppercase',
  modal_add_btn_color: '#80C6A6',
  modal_add_btn_txt_color: '#17201E',
  modal_add_btn_hover_color: '#2F7552',
  modal_add_btn_hover_txt_color: '#FFFFFF',
  modal_add_btn_border_style: 'none',
  modal_add_btn_border_width: 0,
  modal_add_btn_border_color: '#80C6A6',
  modal_add_btn_hover_border_color: '#2F7552',
  modal_add_btn_text: 'Add this video',
  modal_add_btn_icon: 'plus-circle',
  modal_add_btn_padding_y: 10,
  modal_add_btn_padding_x: 20,
  modal_add_btn_radius: 3,
  modal_add_btn_font_size: 12,
  modal_add_btn_icon_size: 12,
  modal_add_btn_font_family: 'default',
  modal_add_btn_font_weight: '400',
  modal_add_btn_font_style: 'normal',
  modal_add_btn_text_transform: 'none',
  button_position: 'after_review_form',
};

function Toast({ notice, onDismiss }) {
  useEffect(() => {
    if (!notice) return undefined;
    const timer = window.setTimeout(onDismiss, 5000);
    return () => window.clearTimeout(timer);
  }, [notice, onDismiss]);

  if (!notice) return null;

  const isSuccess = notice.type === 'success';
  const Icon = isSuccess ? CheckCircle2 : XCircle;

  return (
    <div className="sktpr-toast-wrap">
      <div
        className={`sktpr-toast ${isSuccess ? 'sktpr-toast--success' : 'sktpr-toast--error'}`}
        role={isSuccess ? 'status' : 'alert'}
      >
        <span className="sktpr-toast-icon">
          <Icon size={18} />
        </span>
        <div className="sktpr-toast-body">
          <strong>{isSuccess ? 'Settings saved' : 'Something went wrong'}</strong>
          <p>{notice.message}</p>
        </div>
        <button type="button" onClick={onDismiss} aria-label="Dismiss notification">
          <X size={14} />
        </button>
      </div>
    </div>
  );
}

function ResetConfirmDialog({ open, busy, onCancel, onConfirm }) {
  useEffect(() => {
    if (!open) return undefined;
    const onKey = (event) => {
      if (event.key === 'Escape') onCancel();
    };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [open, onCancel]);

  if (!open) return null;

  return (
    <div className="sktpr-modal-overlay" role="presentation" onClick={busy ? undefined : onCancel}>
      <div
        className="sktpr-modal"
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="sktpr-reset-title"
        aria-describedby="sktpr-reset-desc"
        onClick={(event) => event.stopPropagation()}
      >
        <span className="sktpr-modal-icon">
          <TriangleAlert size={20} />
        </span>
        <h4 id="sktpr-reset-title">Reset all settings?</h4>
        <p id="sktpr-reset-desc">
          This will restore all settings to their defaults. This action cannot be undone.
        </p>
        <div className="sktpr-modal-actions">
          <button
            type="button"
            className="sktpr-react-button sktpr-react-button--secondary"
            onClick={onCancel}
            disabled={busy}
            autoFocus
          >
            Cancel
          </button>
          <button
            type="button"
            className="sktpr-react-button sktpr-react-button--destructive"
            onClick={onConfirm}
            disabled={busy}
          >
            {busy ? 'Resetting…' : 'Reset settings'}
          </button>
        </div>
      </div>
    </div>
  );
}

export default function App() {
  const config = window.sktpr_plugin || {};
  const licensing = config.licensing || {};
  const isPro = Boolean(licensing.is_pro);
  const [settings, setSettings] = useState({ ...defaults, ...(config.settings || {}) });
  const [emailSettings, setEmailSettings] = useState(config.email_settings || {});
  const [rewardSettings, setRewardSettings] = useState(config.reward_settings || {});
  const [activeTab, setActiveTab] = useState('general');
  const [saving, setSaving] = useState(false);
  const [notice, setNotice] = useState(null);
  const [confirmReset, setConfirmReset] = useState(false);

  const dismissNotice = useCallback(() => setNotice(null), []);

  const updateSetting = (key, value) => setSettings((current) => ({ ...current, [key]: value }));
  const updateEmailSetting = (key, value) => setEmailSettings((current) => ({ ...current, [key]: value }));
  const updateRewardSetting = (key, value) => setRewardSettings((current) => ({ ...current, [key]: value }));

  const saveSettings = async () => {
    setSaving(true);
    const data = new FormData();
    data.append('action', 'sktpr_get_review_settings');
    data.append('_wpnonce', config.nonce || '');

    Object.entries(settings).forEach(([key, value]) => {
      if (typeof value !== 'boolean' || value) data.append(key, value);
    });

    try {
      const response = await fetch(config.ajax_url, { method: 'POST', body: data });
      const result = await response.json();

      if (!response.ok || !result.success) {
        throw new Error(result?.data?.status || 'Unable to save settings.');
      }

      setNotice({ type: 'success', message: result.data.status || 'Settings saved successfully.' });
    } catch (error) {
      setNotice({ type: 'error', message: error.message || 'Unable to save settings.' });
    } finally {
      setSaving(false);
    }
  };

  const saveEmailSettings = async () => {
    setSaving(true);
    const data = new FormData();
    data.append('action', 'sktpr_save_email_settings');
    data.append('_wpnonce', config.nonce || '');

    Object.entries(emailSettings).forEach(([key, value]) => {
      if (key === 'rules') {
        data.append(key, JSON.stringify(value));
      } else if (typeof value !== 'boolean' || value) {
        data.append(key, value);
      }
    });

    try {
      const response = await fetch(config.ajax_url, { method: 'POST', body: data });
      const result = await response.json();

      if (!response.ok || !result.success) {
        throw new Error(result?.data?.status || 'Unable to save email reminder settings.');
      }

      setEmailSettings(result.data.settings || emailSettings);
      setNotice({ type: 'success', message: result.data.status || 'Email reminder settings saved successfully.' });
    } catch (error) {
      setNotice({ type: 'error', message: error.message || 'Unable to save email reminder settings.' });
    } finally {
      setSaving(false);
    }
  };

  const saveRewardSettings = async () => {
    setSaving(true);
    const data = new FormData();
    data.append('action', 'sktpr_save_reward_settings');
    data.append('_wpnonce', config.nonce || '');

    Object.entries(rewardSettings).forEach(([key, value]) => {
      if (key === 'tiers') {
        data.append(key, JSON.stringify(value));
      } else if (typeof value !== 'boolean' || value) {
        data.append(key, value ?? '');
      }
    });

    try {
      const response = await fetch(config.ajax_url, { method: 'POST', body: data });
      const result = await response.json();

      if (!response.ok || !result.success) {
        throw new Error(result?.data?.status || 'Unable to save review reward settings.');
      }

      setRewardSettings(result.data.settings || rewardSettings);
      setNotice({ type: 'success', message: result.data.status || 'Review reward settings saved successfully.' });
    } catch (error) {
      setNotice({ type: 'error', message: error.message || 'Unable to save review reward settings.' });
    } finally {
      setSaving(false);
    }
  };

  const resetSettings = async () => {
    setSaving(true);
    const data = new FormData();
    data.append('action', 'sktpr_reset_settings');
    data.append('_wpnonce', config.nonce || '');

    try {
      const response = await fetch(config.ajax_url, { method: 'POST', body: data });
      const result = await response.json();

      if (!response.ok || !result.success) {
        throw new Error(result?.data?.status || 'Unable to reset settings.');
      }

      setNotice({ type: 'success', message: result.data.status || 'Settings reset to defaults.' });
      window.setTimeout(() => window.location.reload(), 700);
    } catch (error) {
      setNotice({ type: 'error', message: error.message || 'Unable to reset settings.' });
    } finally {
      setSaving(false);
      setConfirmReset(false);
    }
  };

  const tabSavers = {
    'email-reminders': saveEmailSettings,
    'review-rewards': saveRewardSettings,
  };
  const saveActiveSettings = tabSavers[activeTab] || saveSettings;
  return (
    <div className="sktpr-settings-app mr-5 mt-5 overflow-hidden rounded-lg border bg-background shadow-soft">
      <Toast notice={notice} onDismiss={dismissNotice} />

      <ResetConfirmDialog
        open={confirmReset}
        busy={saving}
        onCancel={() => setConfirmReset(false)}
        onConfirm={resetSettings}
      />

      <DashboardHeader
        version={config.version}
        saving={saving}
        isPro={isPro}
        isTrial={Boolean(licensing.is_trial)}
        isMarketplace={Boolean(licensing.is_marketplace)}
        upgradeUrl={licensing.upgrade_url || ''}
        accountUrl={licensing.account_url || ''}
        onSave={saveActiveSettings}
        onReset={() => setConfirmReset(true)}
      />

      <div className="sktpr-settings-layout grid grid-cols-[288px_minmax(0,1fr)] min-h-[560px]">
        <DashboardSidebar activeTab={activeTab} onTabChange={setActiveTab} />

        <main className="sktpr-settings-main max-w-[980px] p-6">
          <div key={activeTab} className="animate-tab-in motion-reduce:animate-none">
            {activeTab === 'general' && (
              <GeneralSettings
                settings={settings}
                onChange={updateSetting}
                isPro={isPro}
                upgradeUrl={licensing.upgrade_url || ''}
              />
            )}
            {activeTab === 'storefront' && (
              <StorefrontSettings
                settings={settings}
                onChange={updateSetting}
                isPro={isPro}
                upgradeUrl={licensing.upgrade_url || ''}
              />
            )}
            {activeTab === 'appearance' && <AppearanceSettings settings={settings} onChange={updateSetting} />}
            {activeTab === 'recording-modal' && (
              <RecordingModalSettings settings={settings} onChange={updateSetting} />
            )}
            {activeTab === 'email-reminders' && (
              <EmailReminderSettings
                settings={emailSettings}
                orderStatuses={config.order_statuses || {}}
                previewData={config.email_preview || {}}
                isPro={isPro}
                upgradeUrl={licensing.upgrade_url || ''}
                onChange={updateEmailSetting}
              />
            )}
            {activeTab === 'review-rewards' && (
              <ReviewRewardsSettings
                settings={rewardSettings}
                previewData={config.email_preview || {}}
                currencySymbol={config.currency_symbol || '$'}
                isPro={isPro}
                upgradeUrl={licensing.upgrade_url || ''}
                onChange={updateRewardSetting}
              />
            )}
          </div>
        </main>
      </div>
    </div>
  );
}
