import { Settings, Store, Palette, Mail, Clapperboard, Gift, ChevronRight } from 'lucide-react';

const sidebarItems = [
  {
    id: 'general',
    label: 'General',
    icon: Settings,
    description: 'Review collection settings',
  },
  {
    id: 'storefront',
    label: 'Storefront',
    icon: Store,
    description: 'Display & privacy',
  },
  {
    id: 'appearance',
    label: 'Appearance',
    icon: Palette,
    description: 'Button styling',
  },
  {
    id: 'recording-modal',
    label: 'Recording Modal',
    icon: Clapperboard,
    description: 'Recorder popup design',
  },
  {
    id: 'email-reminders',
    label: 'Email reminders',
    icon: Mail,
    description: 'Automated review requests',
  },
  {
    id: 'review-rewards',
    label: 'Review rewards',
    icon: Gift,
    description: 'Coupons for reviews',
  },
];

export default function DashboardSidebar({ activeTab, onTabChange, collapsed = false }) {
  return (
    <aside className="sktpr-settings-sidebar bg-card border-r border-border" aria-label="ReviQuo settings">
      <div className={`sktpr-settings-sidebar-nav space-y-2 ${collapsed ? 'p-2' : 'p-4'}`}>
        {sidebarItems.map((item) => {
          const Icon = item.icon;
          const isActive = activeTab === item.id;

          return (
            <button
              key={item.id}
              type="button"
              className={`sktpr-settings-sidebar-button w-full flex items-center gap-3 rounded-md border text-left text-sm cursor-pointer transition-all duration-200 ease-smooth ${
                isActive
                  ? 'bg-primary/10 text-primary-strong border-primary/20'
                  : 'bg-transparent text-foreground border-transparent hover:bg-muted hover:text-foreground'
              } ${collapsed ? 'h-12 justify-center px-0' : 'py-3 px-4'}`}
              onClick={() => onTabChange(item.id)}
              aria-current={isActive ? 'page' : undefined}
            >
              <Icon className="h-4 w-4 shrink-0" />
              {!collapsed && (
                <>
                  <div className="flex-1 min-w-0">
                    <div className="font-medium">{item.label}</div>
                    <div className="sktpr-settings-sidebar-description text-xs font-medium text-muted-foreground">
                      {item.description}
                    </div>
                  </div>
                  <ChevronRight className="sktpr-settings-sidebar-chevron h-4 w-4 shrink-0" />
                </>
              )}
            </button>
          );
        })}
      </div>
    </aside>
  );
}
