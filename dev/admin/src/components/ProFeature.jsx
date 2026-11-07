import { Crown, ExternalLink, LockKeyhole } from 'lucide-react';

export function ProBadge({ className = '' }) {
  return (
    <span
      className={`inline-flex items-center gap-1 rounded-full border border-primary/20 bg-primary/10 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-primary-strong ${className}`}
    >
      <Crown size={11} aria-hidden="true" />
      Pro
    </span>
  );
}

export function ProFeatureGate({ title, description, upgradeUrl }) {
  return (
    <section className="rounded-xl border border-border bg-card px-6 py-12 text-center shadow-soft">
      <span className="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary-strong">
        <LockKeyhole size={22} aria-hidden="true" />
      </span>
      <ProBadge className="mt-4" />
      <h2 className="m-0 mt-3 text-xl font-semibold tracking-tight text-foreground">{title}</h2>
      <p className="mx-auto mb-0 mt-2 max-w-lg text-sm leading-6 text-muted-foreground">
        {description}
      </p>
      {upgradeUrl ? (
        <a
          className="sktpr-react-button mt-6 min-h-11 no-underline"
          href={upgradeUrl}
          aria-label={`Upgrade to unlock ${title}`}
        >
          Upgrade to Pro
          <ExternalLink size={15} aria-hidden="true" />
        </a>
      ) : null}
    </section>
  );
}
