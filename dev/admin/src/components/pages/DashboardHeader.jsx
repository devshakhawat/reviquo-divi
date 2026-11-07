import { Crown, KeyRound, Save, RotateCcw, Settings } from 'lucide-react';

export default function DashboardHeader({
  version,
  saving,
  saveDisabled = false,
  isPro = false,
  isTrial = false,
  isMarketplace = false,
  upgradeUrl = '',
  accountUrl = '',
  onSave,
  onReset,
}) {
  return (
    <header className="sktpr-settings-header bg-card border-b border-border px-6 py-4">
      <div className="sktpr-settings-header-inner flex items-center justify-between">
        <div className="sktpr-settings-header-title flex items-center gap-4">
          <div className="flex items-center gap-3">
            <div className="w-8 h-8 rounded-lg bg-gradient-primary flex items-center justify-center">
              <Settings className="h-5 w-5 text-primary-foreground" />
            </div>
            <div>
              <h1 className="m-0 text-xl font-semibold leading-7 text-foreground">ReviQuo</h1>
              <p className="m-0 text-sm text-muted-foreground">Configure your review collection experience.</p>
            </div>
          </div>
          <span className="ml-2 inline-flex items-center rounded-full border border-transparent bg-secondary px-2.5 py-0.5 text-xs font-semibold text-secondary-foreground">
            v{version}
          </span>
          {!isMarketplace ? (
            <span className="inline-flex items-center gap-1 rounded-full border border-primary/20 bg-primary/10 px-2.5 py-0.5 text-xs font-semibold text-primary-strong">
              {isPro ? <Crown size={12} aria-hidden="true" /> : null}
              {isPro ? (isTrial ? 'Pro Trial' : 'Pro') : 'Free'}
            </span>
          ) : null}
        </div>

        <div className="sktpr-settings-header-actions flex items-center gap-3">
          {accountUrl ? (
            <a className="sktpr-react-button sktpr-react-button--secondary no-underline" href={accountUrl}>
              <KeyRound className="h-4 w-4" aria-hidden="true" />
              License
            </a>
          ) : null}
          {!isPro && upgradeUrl ? (
            <a className="sktpr-react-button no-underline" href={upgradeUrl}>
              <Crown className="h-4 w-4" aria-hidden="true" />
              Upgrade
            </a>
          ) : null}
          <button
            type="button"
            className="sktpr-react-button sktpr-react-button--secondary"
            onClick={onReset}
            disabled={saving}
          >
            <RotateCcw className="h-4 w-4" />
            Reset
          </button>
          <button
            type="button"
            className="sktpr-react-button"
            onClick={onSave}
            disabled={saving || saveDisabled}
          >
            <Save className="h-4 w-4" />
            {saving ? 'Saving…' : 'Save Changes'}
          </button>
        </div>
      </div>
    </header>
  );
}
