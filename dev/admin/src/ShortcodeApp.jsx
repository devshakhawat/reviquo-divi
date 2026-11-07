import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
  ArrowLeft,
  BookOpen,
  Check,
  ChevronDown,
  ChevronLeft,
  CircleHelp,
  Clapperboard,
  Clipboard,
  Columns3,
  Copy,
  Crown,
  FilePlus2,
  GalleryHorizontal,
  GitCommitVertical,
  Images,
  PanelLeft,
  Quote,
  Rows3,
  LayoutGrid,
  List,
  ListCollapse,
  Loader2,
  Maximize2,
  Minimize2,
  MessagesSquare,
  Monitor,
  MoveHorizontal,
  Pencil,
  Plus,
  Save,
  Search,
  Smartphone,
  Star,
  Tablet,
  Trash2,
  X,
} from 'lucide-react';

const config = window.sktpr_plugin || {};
const licensing = config.licensing || {};
const isPro = Boolean(licensing.is_pro);

const fallbackDefaults = {
  source: 'all',
  product_ids: [],
  layout: 'list',
  per_page: 6,
  order: 'newest',
  show_summary: true,
  show_filters: true,
  show_product: true,
  show_review_text: true,
  show_avatar: true,
  show_verified: true,
  show_media: true,
  show_photos: true,
  show_videos: true,
  columns: 3,
  tablet_columns: 2,
  mobile_columns: 1,
  slider_autoplay: false,
  slider_delay: 5,
  slider_arrows: true,
  slider_dots: true,
  gap: 20,
  radius: 10,
  background_color: '#ffffff',
  text_color: '#17201e',
  accent_color: '#ff694d',
  star_color: '#ffb627',
};

async function request(action, values = {}, method = 'POST', signal) {
  const data = new FormData();
  data.append('action', action);
  data.append('_wpnonce', config.nonce || '');
  Object.entries(values).forEach(([key, value]) => data.append(key, value));

  let url = config.ajax_url;
  const options = { method, signal };
  if (method === 'GET') {
    url += `?${new URLSearchParams([...data.entries()]).toString()}`;
  } else {
    options.body = data;
  }

  const response = await fetch(url, options);
  const result = await response.json();
  if (!response.ok || !result.success) {
    throw new Error(result?.data?.message || 'The request could not be completed.');
  }
  return result.data;
}

function TopNavigation({ onSave, saving = false, section = '', showResources = false }) {
  const navigation = config.navigation || {};

  return (
    <header className="sktpr-sc-topbar">
      <a className="sktpr-sc-brand" href={navigation.shortcodes || '#'}>
        <span><Star size={20} /></span>
        <strong>ReviQuo</strong>
      </a>
      {section && (
        <div className="sktpr-sc-breadcrumb" aria-label="Current section">
          <span aria-hidden="true">/</span>
          <strong>{section}</strong>
        </div>
      )}
      {showResources && (
        <div className="sktpr-sc-resource-links">
          {navigation.docs ? (
            <a href={navigation.docs} target="_blank" rel="noreferrer">
              <BookOpen size={17} />
              Docs
            </a>
          ) : null}
          {navigation.help ? (
            <a href={navigation.help} target="_blank" rel="noreferrer">
              <CircleHelp size={17} />
              Help
            </a>
          ) : null}
        </div>
      )}
      {onSave && (
        <div className="sktpr-sc-header-actions">
          <a className="sktpr-react-button sktpr-react-button--secondary no-underline" href={navigation.create || '#'}>
            <Plus size={16} />
            Add new
          </a>
          <button type="button" className="sktpr-react-button sktpr-sc-save-button" onClick={onSave} disabled={saving}>
            <Save size={16} />
            {saving ? 'Saving…' : 'Save changes'}
          </button>
        </div>
      )}
    </header>
  );
}

function Toast({ notice, onDismiss }) {
  useEffect(() => {
    if (!notice) return undefined;
    const timer = window.setTimeout(onDismiss, 4000);
    return () => window.clearTimeout(timer);
  }, [notice, onDismiss]);

  if (!notice) return null;
  return (
    <div className="sktpr-toast-wrap">
      <div className={`sktpr-toast ${notice.type === 'error' ? 'sktpr-toast--error' : 'sktpr-toast--success'}`} role={notice.type === 'error' ? 'alert' : 'status'}>
        <span className="sktpr-toast-icon"><Check size={18} /></span>
        <div className="sktpr-toast-body"><strong>{notice.type === 'error' ? 'Action failed' : 'Done'}</strong><p>{notice.message}</p></div>
        <button type="button" onClick={onDismiss} aria-label="Dismiss notification"><X size={14} /></button>
      </div>
    </div>
  );
}

function ProductPicker({ value, onChange, multiple = false, label, showSearchIcon = true }) {
  const [query, setQuery] = useState('');
  const [options, setOptions] = useState([]);
  const [selected, setSelected] = useState([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (!value || (Array.isArray(value) && !value.length)) {
      setSelected([]);
      return;
    }
    const ids = Array.isArray(value) ? value : [value];
    request('sktpr_shortcode_products', { ids: ids.join(',') }, 'GET')
      .then((data) => setSelected(data.items || []))
      .catch(() => setSelected([]));
  }, [JSON.stringify(value)]);

  useEffect(() => {
    const timer = window.setTimeout(() => {
      setLoading(true);
      request('sktpr_shortcode_products', { search: query }, 'GET')
        .then((data) => setOptions(data.items || []))
        .catch(() => setOptions([]))
        .finally(() => setLoading(false));
    }, 250);
    return () => window.clearTimeout(timer);
  }, [query]);

  const choose = (product) => {
    if (multiple) {
      if (!selected.some((item) => item.id === product.id)) {
        const next = [...selected, product];
        setSelected(next);
        onChange(next.map((item) => item.id));
      }
    } else {
      setSelected([product]);
      onChange(product.id);
    }
    setQuery('');
  };

  const remove = (id) => {
    const next = selected.filter((item) => item.id !== id);
    setSelected(next);
    onChange(multiple ? next.map((item) => item.id) : 0);
  };

  return (
    <div className="sktpr-sc-form-row sktpr-sc-form-row--picker">
      <label className="sktpr-sc-row-label">{label}:</label>
      <div className="sktpr-sc-row-control sktpr-sc-picker">
        <span className="relative block">
          {showSearchIcon && <Search className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />}
          <input className={`sktpr-sc-control ${showSearchIcon ? 'pl-9' : ''}`} value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Search products…" aria-label={label} />
          {loading && <Loader2 className="absolute right-3 top-3 h-4 w-4 animate-spin text-muted-foreground" />}
        </span>
        {query && (
          <div className="sktpr-sc-picker-results">
            {options.length ? options.map((product) => (
              <button type="button" key={product.id} onClick={() => choose(product)}>{product.name}<small>#{product.id}</small></button>
            )) : !loading && <p>No products found.</p>}
          </div>
        )}
        {!!selected.length && (
          <div className="sktpr-sc-chips">
            {selected.map((product) => (
              <span key={product.id}>{product.name}<button type="button" onClick={() => remove(product.id)} aria-label={`Remove ${product.name}`}><X size={12} /></button></span>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}

function SettingRow({ label, children, className = '' }) {
  return (
    <div className={`sktpr-sc-form-row ${className}`}>
      <span className="sktpr-sc-row-label">{label}:</span>
      <div className="sktpr-sc-row-control">{children}</div>
    </div>
  );
}

function ShortcodeProBadge() {
  return (
    <span className="sktpr-sc-pro-badge">
      <Crown size={11} aria-hidden="true" />
      Pro
    </span>
  );
}

function SettingToggle({ label, checked, onChange, disabled = false }) {
  return (
    <SettingRow label={label}>
      <label className={`sktpr-sc-compact-toggle ${disabled ? 'is-disabled' : ''}`}>
        <input type="checkbox" checked={checked} disabled={disabled} onChange={(event) => onChange(event.target.checked)} />
        <i aria-hidden="true" />
        <span>{checked ? 'ON' : 'OFF'}</span>
      </label>
    </SettingRow>
  );
}

function ConfirmDialog({ name, count = 1, bulk = false, busy, onCancel, onConfirm }) {
  return (
    <div className="sktpr-modal-overlay" role="presentation" onClick={busy ? undefined : onCancel}>
      <div className="sktpr-modal" role="alertdialog" aria-modal="true" aria-labelledby="sktpr-delete-title" onClick={(event) => event.stopPropagation()}>
        <span className="sktpr-modal-icon"><Trash2 size={20} /></span>
        <h4 id="sktpr-delete-title">{bulk ? `Delete ${count} selected ${count === 1 ? 'shortcode' : 'shortcodes'}?` : `Delete “${name}”?`}</h4>
        <p>{bulk ? 'This moves the selected configurations to the WordPress trash. Pages using their shortcodes will stop displaying reviews.' : 'This moves the saved configuration to the WordPress trash. Pages using its shortcode will stop displaying reviews.'}</p>
        <div className="sktpr-modal-actions">
          <button type="button" className="sktpr-react-button sktpr-react-button--secondary" onClick={onCancel} disabled={busy}>Cancel</button>
          <button type="button" className="sktpr-react-button sktpr-react-button--destructive" onClick={onConfirm} disabled={busy}>{busy ? 'Deleting…' : 'Delete'}</button>
        </div>
      </div>
    </div>
  );
}

function ShortcodeList({ items, loading, onCreate, onEdit, onRefresh, notify }) {
  const [deleting, setDeleting] = useState(null);
  const [bulkDeleteOpen, setBulkDeleteOpen] = useState(false);
  const [busy, setBusy] = useState(false);
  const [copied, setCopied] = useState(0);
  const [searchQuery, setSearchQuery] = useState('');
  const [sourceFilter, setSourceFilter] = useState('any');
  const [layoutFilter, setLayoutFilter] = useState('any');
  const [selectedIds, setSelectedIds] = useState([]);
  const [page, setPage] = useState(1);
  const pageSize = 10;

  const sourceLabels = {
    all: 'all',
    current: 'current',
    selected: 'selected',
  };

  const layoutLabels = {
    gallery: 'media gallery',
    compact: 'compact list',
    chat: 'chat bubbles',
  };

  const filteredItems = items.filter((item) => {
    const matchesSearch = item.name.toLowerCase().includes(searchQuery.trim().toLowerCase())
      || item.shortcode.toLowerCase().includes(searchQuery.trim().toLowerCase());
    const matchesSource = sourceFilter === 'any' || item.source === sourceFilter;
    const matchesLayout = layoutFilter === 'any' || item.layout === layoutFilter;
    return matchesSearch && matchesSource && matchesLayout;
  });
  const totalPages = Math.max(1, Math.ceil(filteredItems.length / pageSize));
  const currentPage = Math.min(page, totalPages);
  const pageStart = (currentPage - 1) * pageSize;
  const visibleItems = filteredItems.slice(pageStart, pageStart + pageSize);
  const visibleIds = visibleItems.map((item) => item.id);
  const allVisibleSelected = visibleIds.length > 0 && visibleIds.every((id) => selectedIds.includes(id));

  useEffect(() => {
    setPage(1);
  }, [searchQuery, sourceFilter, layoutFilter]);

  useEffect(() => {
    setSelectedIds((current) => current.filter((id) => items.some((item) => item.id === id)));
  }, [items]);

  const toggleVisibleSelection = () => {
    setSelectedIds((current) => (
      allVisibleSelected
        ? current.filter((id) => !visibleIds.includes(id))
        : [...new Set([...current, ...visibleIds])]
    ));
  };

  const toggleSelection = (id) => {
    setSelectedIds((current) => (
      current.includes(id) ? current.filter((selectedId) => selectedId !== id) : [...current, id]
    ));
  };

  const copy = async (item) => {
    await navigator.clipboard.writeText(item.shortcode);
    setCopied(item.id);
    window.setTimeout(() => setCopied(0), 1600);
  };

  const duplicate = async (item) => {
    setBusy(true);
    try {
      const data = await request('sktpr_shortcode_duplicate', { id: item.id });
      notify('success', data.message);
      onRefresh();
    } catch (error) {
      notify('error', error.message);
    } finally {
      setBusy(false);
    }
  };

  const remove = async () => {
    setBusy(true);
    try {
      const data = await request('sktpr_shortcode_delete', { id: deleting.id });
      notify('success', data.message);
      setDeleting(null);
      onRefresh();
    } catch (error) {
      notify('error', error.message);
    } finally {
      setBusy(false);
    }
  };

  const removeSelected = async () => {
    if (busy || !selectedIds.length) return;
    setBusy(true);
    try {
      const data = await request('sktpr_shortcode_bulk_delete', { ids: JSON.stringify(selectedIds) });
      notify('success', data.message);
      setSelectedIds([]);
      setBulkDeleteOpen(false);
      onRefresh();
    } catch (error) {
      notify('error', error.message);
      onRefresh();
    } finally {
      setBusy(false);
    }
  };

  return (
    <>
      <TopNavigation section="Shortcodes" showResources />
      <div className="sktpr-sc-shell">
        <header className="sktpr-sc-page-head">
          <div><h1>Review Shortcodes</h1><p>Create reusable WooCommerce review displays with a live preview.</p></div>
          <button type="button" className="sktpr-react-button" onClick={onCreate}><Plus size={16} /> Create New</button>
        </header>
        <div className="sktpr-sc-list-card">
          {loading ? (
            <div className="sktpr-sc-empty"><Loader2 className="animate-spin" /><p>Loading shortcodes…</p></div>
          ) : !items.length ? (
            <div className="sktpr-sc-empty"><FilePlus2 size={34} /><h2>Create your first review shortcode</h2><p>Choose review sources, display options, and styling while viewing the result.</p><button type="button" className="sktpr-react-button" onClick={onCreate}>Create Shortcode</button></div>
          ) : (
            <>
              <div className="sktpr-sc-list-toolbar">
                <label className="sktpr-sc-list-search">
                  <Search size={17} aria-hidden="true" />
                  <input type="search" value={searchQuery} onChange={(event) => setSearchQuery(event.target.value)} placeholder="Search shortcodes…" aria-label="Search shortcodes" />
                </label>
                <label className="sktpr-sc-list-select">
                  <select value={sourceFilter} onChange={(event) => setSourceFilter(event.target.value)} aria-label="Filter by source">
                    <option value="any">All sources</option>
                    <option value="current">Current product</option>
                    <option value="selected">Selected products</option>
                    <option value="all">All products</option>
                  </select>
                  <ChevronDown size={16} aria-hidden="true" />
                </label>
                <label className="sktpr-sc-list-select">
                  <select value={layoutFilter} onChange={(event) => setLayoutFilter(event.target.value)} aria-label="Filter by layout">
                    <option value="any">All layouts</option>
                    <option value="list">List</option>
                    <option value="grid">Grid</option>
                    <option value="masonry">Masonry</option>
                    <option value="slider">Slider</option>
                    <option value="gallery">Media Gallery</option>
                    <option value="testimonial">Testimonial</option>
                    <option value="spotlight">Spotlight</option>
                    <option value="compact">Compact List</option>
                    <option value="reels">Reels</option>
                    <option value="marquee">Marquee</option>
                    <option value="timeline">Timeline</option>
                    <option value="accordion">Accordion</option>
                    <option value="chat">Chat Bubbles</option>
                  </select>
                  <ChevronDown size={16} aria-hidden="true" />
                </label>
                {selectedIds.length ? (
                  <div className="sktpr-sc-bulk-actions">
                    <strong>{selectedIds.length} selected</strong>
                    <button type="button" onClick={() => setBulkDeleteOpen(true)} disabled={busy}>
                      <Trash2 size={16} />
                      Delete
                    </button>
                  </div>
                ) : (
                  <strong className="sktpr-sc-list-count">{filteredItems.length} {filteredItems.length === 1 ? 'shortcode' : 'shortcodes'}</strong>
                )}
              </div>
              <div className="sktpr-sc-table-wrap">
                <table className="sktpr-sc-table">
                  <thead>
                    <tr>
                      <th className="sktpr-sc-check-column">
                        <input type="checkbox" checked={allVisibleSelected} onChange={toggleVisibleSelection} aria-label="Select all visible shortcodes" />
                      </th>
                      <th>Name</th><th>Shortcode</th><th>Source</th><th>Layout</th><th>Updated</th><th className="text-right">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {visibleItems.length ? visibleItems.map((item) => (
                      <tr key={item.id}>
                        <td className="sktpr-sc-check-column">
                          <input type="checkbox" checked={selectedIds.includes(item.id)} onChange={() => toggleSelection(item.id)} aria-label={`Select ${item.name}`} />
                        </td>
                        <td><button type="button" className="sktpr-sc-name" onClick={() => onEdit(item)}>{item.name}</button></td>
                        <td><button type="button" className="sktpr-sc-code" onClick={() => copy(item)}>{item.shortcode}{copied === item.id ? <Check size={15} /> : <Clipboard size={15} />}</button></td>
                        <td><span className={`sktpr-sc-source-badge is-${item.source}`}>{sourceLabels[item.source] || item.source}</span></td>
                        <td>
                          <span className="sktpr-sc-layout-badge">
                            {item.layout === 'grid' ? <LayoutGrid size={16} /> : item.layout === 'masonry' ? <Columns3 size={16} /> : item.layout === 'slider' ? <GalleryHorizontal size={16} /> : item.layout === 'gallery' ? <Images size={16} /> : item.layout === 'testimonial' ? <Quote size={16} /> : item.layout === 'spotlight' ? <PanelLeft size={16} /> : item.layout === 'compact' ? <Rows3 size={16} /> : item.layout === 'reels' ? <Clapperboard size={16} /> : item.layout === 'marquee' ? <MoveHorizontal size={16} /> : item.layout === 'timeline' ? <GitCommitVertical size={16} /> : item.layout === 'accordion' ? <ListCollapse size={16} /> : item.layout === 'chat' ? <MessagesSquare size={16} /> : <List size={16} />}
                            {layoutLabels[item.layout] || item.layout}
                          </span>
                        </td>
                        <td className="sktpr-sc-updated">{item.modified}</td>
                        <td><div className="sktpr-sc-row-actions">
                          <button type="button" onClick={() => onEdit(item)} aria-label={`Edit ${item.name}`} title="Edit"><Pencil size={16} /></button>
                          <button type="button" onClick={() => duplicate(item)} disabled={busy} aria-label={`Duplicate ${item.name}`} title="Duplicate"><Copy size={16} /></button>
                          <button type="button" className="sktpr-sc-table-delete" onClick={() => setDeleting(item)} aria-label={`Delete ${item.name}`} title="Delete"><Trash2 size={16} /></button>
                        </div></td>
                      </tr>
                    )) : (
                      <tr><td colSpan="7"><div className="sktpr-sc-no-results"><Search size={22} /><strong>No shortcodes found</strong><span>Try adjusting your search or filters.</span></div></td></tr>
                    )}
                  </tbody>
                </table>
              </div>
              <footer className="sktpr-sc-list-footer">
                <span>
                  {filteredItems.length
                    ? `Showing ${pageStart + 1}–${Math.min(pageStart + pageSize, filteredItems.length)} of ${filteredItems.length}`
                    : 'Showing 0 of 0'}
                </span>
                <div>
                  <button type="button" onClick={() => setPage((current) => Math.max(1, current - 1))} disabled={currentPage === 1}>Previous</button>
                  <button type="button" onClick={() => setPage((current) => Math.min(totalPages, current + 1))} disabled={currentPage === totalPages}>Next</button>
                </div>
              </footer>
            </>
          )}
        </div>
        {deleting && <ConfirmDialog name={deleting.name} busy={busy} onCancel={() => setDeleting(null)} onConfirm={remove} />}
        {bulkDeleteOpen && <ConfirmDialog bulk count={selectedIds.length} busy={busy} onCancel={() => setBulkDeleteOpen(false)} onConfirm={removeSelected} />}
      </div>
    </>
  );
}

function Builder({ item, defaults, onBack, onSaved, notify }) {
  const [id, setId] = useState(item?.id || 0);
  const [name, setName] = useState(item?.name || 'My Review Shortcode');
  const [settings, setSettings] = useState({ ...defaults, ...(item?.settings || {}) });
  const [previewProduct, setPreviewProduct] = useState(item?.settings?.product_ids?.[0] || 0);
  const [preview, setPreview] = useState('');
  const [previewing, setPreviewing] = useState(true);
  const [saving, setSaving] = useState(false);
  const [dirty, setDirty] = useState(false);
  const [confirmDelete, setConfirmDelete] = useState(false);
  const [activeSettingsTab, setActiveSettingsTab] = useState('general');
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
  const [previewDevice, setPreviewDevice] = useState('desktop');
  const [builderFullscreen, setBuilderFullscreen] = useState(false);
  const [builderFallbackExpanded, setBuilderFallbackExpanded] = useState(false);
  const builderRef = useRef(null);

  const update = (key, value) => {
    setSettings((current) => ({ ...current, [key]: value }));
    setDirty(true);
  };

  useEffect(() => {
    const beforeUnload = (event) => {
      if (!dirty) return;
      event.preventDefault();
      event.returnValue = '';
    };
    window.addEventListener('beforeunload', beforeUnload);
    return () => window.removeEventListener('beforeunload', beforeUnload);
  }, [dirty]);

  useEffect(() => {
    const controller = new AbortController();
    const timer = window.setTimeout(() => {
      setPreviewing(true);
      request('sktpr_shortcode_preview', {
        settings: JSON.stringify(settings),
        preview_product_id: previewProduct || 0,
      }, 'POST', controller.signal)
        .then((data) => setPreview(data.html || ''))
        .catch((error) => {
          if (error.name !== 'AbortError') {
            setPreview(`<div class="sktpr-shortcode-empty">${error.message}</div>`);
          }
        })
        .finally(() => setPreviewing(false));
    }, 300);
    return () => {
      window.clearTimeout(timer);
      controller.abort();
    };
  }, [settings, previewProduct]);

  useEffect(() => {
    const onFullscreenChange = () => {
      setBuilderFullscreen(document.fullscreenElement === builderRef.current);
    };
    const onKeyDown = (event) => {
      if (event.key === 'Escape') {
        setBuilderFallbackExpanded(false);
      }
    };

    document.addEventListener('fullscreenchange', onFullscreenChange);
    window.addEventListener('keydown', onKeyDown);
    return () => {
      document.removeEventListener('fullscreenchange', onFullscreenChange);
      window.removeEventListener('keydown', onKeyDown);
    };
  }, []);

  useEffect(() => {
    const fullscreenActive = builderFullscreen || builderFallbackExpanded;
    document.body.classList.toggle('sktpr-sc-fullscreen-is-active', fullscreenActive);

    return () => document.body.classList.remove('sktpr-sc-fullscreen-is-active');
  }, [builderFullscreen, builderFallbackExpanded]);

  const save = async () => {
    if (saving) return;
    setSaving(true);
    try {
      const data = await request('sktpr_shortcode_save', { id, name, settings: JSON.stringify(settings) });
      setId(data.id);
      setSettings(data.settings);
      setDirty(false);
      notify('success', data.message);
      onSaved(data);
    } catch (error) {
      notify('error', error.message);
    } finally {
      setSaving(false);
    }
  };

  const back = () => {
    if (!dirty || window.confirm('Discard your unsaved changes?')) onBack();
  };

  const copy = async () => {
    if (!id) {
      notify('error', 'Save the shortcode before copying it.');
      return;
    }
    await navigator.clipboard.writeText(`[reviquo id="${id}"]`);
    notify('success', 'Shortcode copied.');
  };

  const remove = async () => {
    if (saving) return;
    setSaving(true);
    try {
      const data = await request('sktpr_shortcode_delete', { id });
      notify('success', data.message);
      setConfirmDelete(false);
      onBack();
    } catch (error) {
      notify('error', error.message);
    } finally {
      setSaving(false);
    }
  };

  const toggleBuilderFullscreen = async () => {
    const builder = builderRef.current;
    if (!builder) return;

    if (builderFallbackExpanded) {
      setBuilderFallbackExpanded(false);
      return;
    }

    if (document.fullscreenElement === builder) {
      await document.exitFullscreen();
      return;
    }

    if (builder.requestFullscreen) {
      try {
        await builder.requestFullscreen();
        return;
      } catch (error) {
        setBuilderFallbackExpanded(true);
        return;
      }
    }

    setBuilderFallbackExpanded(true);
  };

  const previewWidths = {
    desktop: '100%',
    tablet: '768px',
    mobile: '375px',
  };

  return (
    <>
      <TopNavigation onSave={save} saving={saving} />
      <div ref={builderRef} className={`sktpr-sc-builder ${builderFallbackExpanded ? 'is-builder-fallback-expanded' : ''}`}>
        <header className="sktpr-sc-builder-head">
          <button type="button" className="sktpr-sc-icon-button" onClick={back} aria-label="Back to shortcodes"><ArrowLeft size={18} /></button>
          <div className="min-w-0 flex-1"><h1>{id ? 'Edit Review Shortcode' : 'Create Review Shortcode'}</h1></div>
          <div className="sktpr-sc-builder-actions">
            {id > 0 && (
              <button type="button" className="sktpr-sc-icon-button text-destructive" onClick={() => setConfirmDelete(true)} aria-label="Delete shortcode" title="Delete shortcode">
                <Trash2 size={17} />
              </button>
            )}
            <button
              type="button"
              className="sktpr-react-button sktpr-react-button--secondary"
              onClick={toggleBuilderFullscreen}
              aria-label={builderFullscreen || builderFallbackExpanded ? 'Exit fullscreen builder' : 'Open fullscreen builder'}
              title={builderFullscreen || builderFallbackExpanded ? 'Exit fullscreen builder' : 'Open fullscreen builder'}
              aria-pressed={builderFullscreen || builderFallbackExpanded}
            >
              {builderFullscreen || builderFallbackExpanded ? <Minimize2 size={16} /> : <Maximize2 size={16} />}
              {builderFullscreen || builderFallbackExpanded ? 'Exit fullscreen' : 'Fullscreen'}
            </button>
          </div>
        </header>

        <div className={`sktpr-sc-builder-grid ${sidebarCollapsed ? 'is-sidebar-collapsed' : ''}`}>
        <div className="sktpr-sc-sidebar">
        <div id="sktpr-sc-settings-controls" className="sktpr-sc-controls">
          <div className="sktpr-sc-settings-panel">
            <div className="sktpr-sc-tabs" role="tablist" aria-label="Shortcode settings">
              {[
                ['general', 'General'],
                ['style', 'Style'],
                ['query', 'Query'],
              ].map(([tab, label]) => (
                <button
                  key={tab}
                  type="button"
                  role="tab"
                  aria-selected={activeSettingsTab === tab}
                  aria-controls={`sktpr-sc-tab-${tab}`}
                  className={activeSettingsTab === tab ? 'is-active' : ''}
                  onClick={() => setActiveSettingsTab(tab)}
                >
                  {label}
                </button>
              ))}
            </div>

            <div id={`sktpr-sc-tab-${activeSettingsTab}`} className="sktpr-sc-tab-panel" role="tabpanel">
              {activeSettingsTab === 'general' && (
                <>
                  <SettingRow label="Shortcode">
                    <div className="sktpr-sc-inline-code">
                      <code>{id ? `[reviquo id="${id}"]` : 'Save to generate'}</code>
                      <button type="button" onClick={copy} disabled={!id}><Clipboard size={14} /> Copy</button>
                    </div>
                  </SettingRow>
                  <SettingRow label="Shortcode Name">
                    <input className="sktpr-sc-control" value={name} onChange={(event) => { setName(event.target.value); setDirty(true); }} />
                  </SettingRow>
                  <SettingRow label="Layout">
                    <select className="sktpr-sc-control" value={settings.layout} onChange={(event) => update('layout', event.target.value)}>
                      <option value="list">List</option>
                      <option value="grid">Grid</option>
                      <option value="masonry">Masonry</option>
                      <option value="slider">Slider</option>
                      <option value="gallery">Media Gallery</option>
                      <option value="testimonial">Testimonial</option>
                      <option value="spotlight">Spotlight</option>
                      <option value="compact">Compact List</option>
                      <option value="reels">Reels</option>
                      <option value="marquee">Marquee</option>
                      <option value="timeline">Timeline</option>
                      <option value="accordion">Accordion</option>
                      <option value="chat">Chat Bubbles</option>
                    </select>
                    {settings.layout === 'gallery' && (
                      <p className="mt-1.5 mb-0 text-xs text-muted-foreground">Shows only reviews with photos or videos, as media tiles that open in the lightbox.</p>
                    )}
                    {settings.layout === 'marquee' && (
                      <p className="mt-1.5 mb-0 text-xs text-muted-foreground">Loops one load of reviews (Reviews Per Load) in an endlessly scrolling strip that pauses on hover. Shown as a static row for visitors who prefer reduced motion.</p>
                    )}
                    {settings.layout === 'reels' && (
                      <p className="mt-1.5 mb-0 text-xs text-muted-foreground">Shows only reviews with videos, as vertical tiles in a scrolling row. Desktop Columns sets how many are in view (4 suits a full-width row).</p>
                    )}
                  </SettingRow>
                  {settings.layout === 'slider' && (
                    <>
                      <SettingToggle label="Autoplay" checked={settings.slider_autoplay} onChange={(value) => update('slider_autoplay', value)} />
                      {settings.slider_autoplay && (
                        <SettingRow label="Autoplay Delay (s)">
                          <div className="sktpr-sc-range-control">
                            <input className="sktpr-sc-number" type="number" min="1" max="20" value={settings.slider_delay} onChange={(event) => update('slider_delay', Number(event.target.value))} />
                            <input type="range" min="1" max="20" value={settings.slider_delay} onChange={(event) => update('slider_delay', Number(event.target.value))} aria-label="Autoplay delay in seconds" />
                          </div>
                        </SettingRow>
                      )}
                      <SettingToggle label="Arrows" checked={settings.slider_arrows} onChange={(value) => update('slider_arrows', value)} />
                      <SettingToggle label="Dots" checked={settings.slider_dots} onChange={(value) => update('slider_dots', value)} />
                    </>
                  )}
                  <SettingToggle label="Rating Summary" checked={settings.show_summary} onChange={(value) => update('show_summary', value)} />
                  <SettingToggle label="Review Filters" checked={settings.show_filters} onChange={(value) => update('show_filters', value)} />
                  <SettingToggle label="Product Name" checked={settings.show_product} onChange={(value) => update('show_product', value)} />
                  <SettingToggle label="Review Text" checked={settings.show_review_text} onChange={(value) => update('show_review_text', value)} />
                  <SettingToggle label="Customer Avatar" checked={settings.show_avatar} onChange={(value) => update('show_avatar', value)} />
                  <SettingToggle
                    label={<span className="sktpr-sc-label-with-badge">Verified Badge {!isPro ? <ShortcodeProBadge /> : null}</span>}
                    checked={settings.show_verified}
                    disabled={!isPro}
                    onChange={(value) => update('show_verified', value)}
                  />
                  <SettingToggle label="Photos" checked={settings.show_photos} onChange={(value) => update('show_photos', value)} />
                  <SettingToggle label="Videos" checked={settings.show_videos} onChange={(value) => update('show_videos', value)} />
                </>
              )}

              {activeSettingsTab === 'style' && (
                <>
                  <SettingRow label="Desktop Columns">
                    <select className="sktpr-sc-control" value={settings.columns} disabled={['list', 'spotlight', 'compact', 'marquee', 'timeline', 'accordion', 'chat'].includes(settings.layout)} onChange={(event) => update('columns', Number(event.target.value))}>
                      {[1, 2, 3, 4].map((value) => <option key={value} value={value}>{value}</option>)}
                    </select>
                  </SettingRow>
                  <SettingRow label="Tablet Columns">
                    <select className="sktpr-sc-control" value={settings.tablet_columns} disabled={['list', 'spotlight', 'compact', 'marquee', 'timeline', 'accordion', 'chat'].includes(settings.layout)} onChange={(event) => update('tablet_columns', Number(event.target.value))}>
                      {[1, 2, 3, 4].map((value) => <option key={value} value={value}>{value}</option>)}
                    </select>
                  </SettingRow>
                  <SettingRow label="Mobile Columns">
                    <select className="sktpr-sc-control" value={settings.mobile_columns} disabled={['list', 'spotlight', 'compact', 'marquee', 'timeline', 'accordion', 'chat'].includes(settings.layout)} onChange={(event) => update('mobile_columns', Number(event.target.value))}>
                      {[1, 2, 3, 4].map((value) => <option key={value} value={value}>{value}</option>)}
                    </select>
                  </SettingRow>
                  <SettingRow label="Card Gap">
                    <div className="sktpr-sc-range-control">
                      <input className="sktpr-sc-number" type="number" min="0" max="48" value={settings.gap} onChange={(event) => update('gap', Number(event.target.value))} />
                      <input type="range" min="0" max="48" value={settings.gap} onChange={(event) => update('gap', Number(event.target.value))} aria-label="Card gap" />
                    </div>
                  </SettingRow>
                  <SettingRow label="Card Radius">
                    <div className="sktpr-sc-range-control">
                      <input className="sktpr-sc-number" type="number" min="0" max="32" value={settings.radius} onChange={(event) => update('radius', Number(event.target.value))} />
                      <input type="range" min="0" max="32" value={settings.radius} onChange={(event) => update('radius', Number(event.target.value))} aria-label="Card radius" />
                    </div>
                  </SettingRow>
                  {[['background_color', 'Background'], ['text_color', 'Text Color'], ['accent_color', 'Accent Color'], ['star_color', 'Star Color']].map(([key, label]) => (
                    <SettingRow label={label} key={key}>
                      <div className="sktpr-sc-color-control">
                        <input type="color" value={settings[key]} onChange={(event) => update(key, event.target.value)} aria-label={label} />
                        <input className="sktpr-sc-control" value={settings[key]} onChange={(event) => update(key, event.target.value)} />
                      </div>
                    </SettingRow>
                  ))}
                </>
              )}

              {activeSettingsTab === 'query' && (
                <>
                  <SettingRow label="Review Source">
                    <select className="sktpr-sc-control" value={settings.source} onChange={(event) => update('source', event.target.value)}>
                      <option value="current">Current product</option>
                      <option value="selected">Selected products</option>
                      <option value="all">All products</option>
                    </select>
                  </SettingRow>
                  {settings.source === 'selected' && <ProductPicker label="Products" multiple value={settings.product_ids} onChange={(value) => update('product_ids', value)} />}
                  <ProductPicker label="Preview Product" value={previewProduct} onChange={setPreviewProduct} showSearchIcon={false} />
                  <SettingRow label="Reviews Per Load">
                    <div className="sktpr-sc-range-control">
                      <input className="sktpr-sc-number" type="number" min="1" max="24" value={settings.per_page} onChange={(event) => update('per_page', Number(event.target.value))} />
                      <input type="range" min="1" max="24" value={settings.per_page} onChange={(event) => update('per_page', Number(event.target.value))} aria-label="Reviews per load" />
                    </div>
                  </SettingRow>
                  <SettingRow label="Order">
                    <select className="sktpr-sc-control" value={settings.order} onChange={(event) => update('order', event.target.value)}>
                      <option value="newest">Newest first</option>
                      <option value="oldest">Oldest first</option>
                      <option value="highest">Highest rated</option>
                      <option value="lowest">Lowest rated</option>
                    </select>
                  </SettingRow>
                </>
              )}
            </div>
          </div>
        </div>
        <button
          type="button"
          className="sktpr-sc-sidebar-toggle"
          onClick={() => setSidebarCollapsed((collapsed) => !collapsed)}
          aria-controls="sktpr-sc-settings-controls"
          aria-expanded={!sidebarCollapsed}
          aria-pressed={sidebarCollapsed}
          aria-label={sidebarCollapsed ? 'Expand settings panel' : 'Collapse settings panel'}
          title={sidebarCollapsed ? 'Expand settings panel' : 'Collapse settings panel'}
        >
          <ChevronLeft size={18} />
        </button>
        </div>

        <aside className="sktpr-sc-preview-workspace">
          <div className="sktpr-sc-preview-toolbar">
            <div className="sktpr-sc-preview-title">
              <h2>Live preview</h2>
              {previewing && <Loader2 className="animate-spin text-primary" size={18} aria-label="Updating preview" />}
            </div>
            <div className="sktpr-sc-preview-actions">
              <div className="sktpr-sc-device-controls" role="group" aria-label="Preview device">
                {[
                  ['desktop', 'Desktop', Monitor],
                  ['tablet', 'Tablet', Tablet],
                  ['mobile', 'Mobile', Smartphone],
                ].map(([device, label, Icon]) => (
                  <button
                    key={device}
                    type="button"
                    title={`${label} preview`}
                    aria-label={`${label} preview`}
                    aria-pressed={previewDevice === device}
                    className={previewDevice === device ? 'is-active' : ''}
                    onClick={() => setPreviewDevice(device)}
                  >
                    <Icon size={19} />
                  </button>
                ))}
              </div>
            </div>
          </div>
          <div className="sktpr-sc-preview-stage">
            <div
              className={`sktpr-sc-device-frame is-${previewDevice}`}
              style={{ width: previewWidths[previewDevice] }}
              data-preview-device={previewDevice}
            >
              <div className={`sktpr-sc-preview-canvas ${previewing ? 'is-loading' : ''}`} dangerouslySetInnerHTML={{ __html: preview }} />
            </div>
          </div>
        </aside>
        </div>
        {confirmDelete && <ConfirmDialog name={name} busy={saving} onCancel={() => setConfirmDelete(false)} onConfirm={remove} />}
      </div>
    </>
  );
}

export default function ShortcodeApp() {
  const initialParams = new URLSearchParams(window.location.search);
  const startsInCreateView = initialParams.get('view') === 'new';
  const initialEditId = initialParams.get('view') === 'edit' ? Number.parseInt(initialParams.get('id'), 10) || 0 : 0;
  const [items, setItems] = useState([]);
  const [defaults, setDefaults] = useState(fallbackDefaults);
  const [loading, setLoading] = useState(true);
  const [editing, setEditing] = useState(startsInCreateView ? false : null);
  const [pendingEditId, setPendingEditId] = useState(initialEditId);
  const [notice, setNotice] = useState(null);

  const notify = useCallback((type, message) => setNotice({ type, message }), []);
  const load = useCallback(() => {
    setLoading(true);
    request('sktpr_shortcode_list')
      .then((data) => { setItems(data.items || []); setDefaults(data.defaults || fallbackDefaults); })
      .catch((error) => notify('error', error.message))
      .finally(() => setLoading(false));
  }, [notify]);

  useEffect(load, [load]);

  useEffect(() => {
    if (loading || !pendingEditId) return;

    const requestedItem = items.find((item) => Number(item.id) === pendingEditId);
    if (requestedItem) {
      setEditing(requestedItem);
    } else {
      notify('error', 'The requested review shortcode could not be found.');
    }
    setPendingEditId(0);
  }, [items, loading, notify, pendingEditId]);

  const openCreate = () => {
    if (config.navigation?.create) {
      window.history.pushState({}, '', config.navigation.create);
    }
    setEditing(false);
  };

  const returnToList = () => {
    if (config.navigation?.shortcodes) {
      window.history.pushState({}, '', config.navigation.shortcodes);
    }
    setEditing(null);
    load();
  };

  return (
    <div className="sktpr-sc-app">
      <Toast notice={notice} onDismiss={() => setNotice(null)} />
      {editing !== null ? (
        <Builder item={editing || null} defaults={defaults} onBack={returnToList} onSaved={(data) => setEditing((current) => ({ ...(current || {}), ...data }))} notify={notify} />
      ) : (
        <ShortcodeList items={items} loading={loading} onCreate={openCreate} onEdit={setEditing} onRefresh={load} notify={notify} />
      )}
    </div>
  );
}
