import React, { useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { createRoot } from 'react-dom/client';
import {
    BrowserRouter,
    Routes,
    Route,
    Navigate,
    Link,
    NavLink,
    useParams,
    useNavigate,
    useLocation,
    Outlet,
} from 'react-router-dom';
import { useQueries, useQuery } from '@tanstack/react-query';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { ExternalLink, ArrowLeft, Search as SearchIcon, Menu, X } from 'lucide-react';
import api from '@/admin/api/axios';
import '../../css/app.css';
import './lyrics.css';

const cfg = window.__BAAKH_LYRICS__ || {
    mainSiteUrl: 'https://baakh.com',
    lyricsSiteUrl: 'https://lyrics.baakh.com',
    locale: 'sd',
};

const queryClient = new QueryClient({
    defaultOptions: { queries: { staleTime: 60_000, retry: 1 } },
});

const t = (lang, sd, en) => (lang === 'en' ? en : sd);

function mediaSrc(url) {
    if (!url) return null;
    if (/^https?:\/\//i.test(url) || url.startsWith('/')) return url;
    return `/${url}`;
}

function primaryCredit(item) {
    return item.singer?.name || item.band?.name || '—';
}

function collabPath(lang, collab) {
    return collab.type === 'band'
        ? `/${lang}/band/${collab.slug}`
        : `/${lang}/artist/${collab.slug}`;
}

function collabRoleLabel(lang, role) {
    const r = role || 'feat';
    if (r === 'with') return t(lang, 'سان', 'with');
    if (r === 'collab') return t(lang, 'گڏ', 'collab');
    return t(lang, 'فيچر', 'feat');
}

function extraCollaborators(song) {
    const skip = new Set();
    if (song.singer?.id) skip.add(`singer:${song.singer.id}`);
    if (song.band?.id) skip.add(`band:${song.band.id}`);
    return (song.collaborators || []).filter((c) => !skip.has(`${c.type}:${c.id}`));
}

function youtubeId(url) {
    if (!url) return null;
    const m = String(url).match(/(?:youtu\.be\/|v=|embed\/)([A-Za-z0-9_-]{6,})/);
    return m?.[1] || null;
}

const LISTEN_PLATFORMS = [
    {
        key: 'youtube',
        label: 'YouTube',
        className: 'is-youtube',
        icon: (
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path
                    fill="currentColor"
                    d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.5 12 3.5 12 3.5s-7.5 0-9.4.6A3 3 0 0 0 .5 6.2 31.5 31.5 0 0 0 0 12a31.5 31.5 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.6 9.4.6 9.4.6s7.5 0 9.4-.6a3 3 0 0 0 2.1-2.1A31.5 31.5 0 0 0 24 12a31.5 31.5 0 0 0-.5-5.8ZM9.75 15.5v-7l6.5 3.5-6.5 3.5Z"
                />
            </svg>
        ),
    },
    {
        key: 'spotify',
        label: 'Spotify',
        className: 'is-spotify',
        icon: (
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path
                    fill="currentColor"
                    d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.6 0 12 0Zm5.5 17.3a.75.75 0 0 1-1.03.25c-2.82-1.72-6.38-2.11-10.57-1.16a.75.75 0 1 1-.33-1.46c4.54-1.03 8.48-.59 11.68 1.34a.75.75 0 0 1 .25 1.03Zm1.4-3.1a.94.94 0 0 1-1.29.31c-3.23-1.98-8.15-2.56-11.97-1.4a.94.94 0 1 1-.55-1.8c4.3-1.3 9.68-.67 13.5 1.62a.94.94 0 0 1 .31 1.27Zm.12-3.23c-3.87-2.3-10.26-2.51-13.96-1.39a1.13 1.13 0 1 1-.65-2.16c4.24-1.28 11.28-1.03 15.72 1.61a1.13 1.13 0 1 1-1.11 1.94Z"
                />
            </svg>
        ),
    },
    {
        key: 'deezer',
        label: 'Deezer',
        className: 'is-deezer',
        icon: (
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path fill="#ef5466" d="M0 15.5h3.2V20H0z" />
                <path fill="#fdbb2c" d="M4.2 13h3.2v7H4.2z" />
                <path fill="#81be41" d="M8.4 10.5H11.6v9.5H8.4z" />
                <path fill="#24d5ee" d="M12.6 8H15.8v12h-3.2z" />
                <path fill="#a238ff" d="M16.8 10.5H20v9.5h-3.2z" />
                <path fill="#ef5466" d="M20.8 13H24v7h-3.2z" />
            </svg>
        ),
    },
];

function resolveListenLinks(entity = {}) {
    const links = entity.listen_links || {};
    const out = {
        youtube: links.youtube || null,
        spotify: links.spotify || null,
        deezer: links.deezer || null,
    };
    if (!out.youtube && youtubeId(entity.music_url)) {
        out.youtube = entity.music_url;
    }
    return LISTEN_PLATFORMS
        .map((p) => ({ ...p, href: out[p.key] }))
        .filter((p) => !!p.href);
}

function ListenLinks({ entity, lang }) {
    const items = resolveListenLinks(entity);
    if (items.length === 0) return null;

    return (
        <section className="bol-listen" aria-label={t(lang, 'ٻڌو', 'Listen')}>
            <h2 className="bol-listen-title">{t(lang, 'ٻڌو', 'Listen')}</h2>
            <ul className="bol-listen-row">
                {items.map((item) => (
                    <li key={item.key}>
                        <a
                            className={`bol-listen-link ${item.className}`}
                            href={item.href}
                            target="_blank"
                            rel="noreferrer"
                        >
                            <span className="bol-listen-badge" aria-hidden>
                                {item.icon}
                            </span>
                            <span className="bol-listen-label">{item.label}</span>
                        </a>
                    </li>
                ))}
            </ul>
        </section>
    );
}

function useLang() {
    const { lang } = useParams();
    return lang === 'en' ? 'en' : 'sd';
}

function LanguageShell() {
    const { lang } = useParams();
    if (lang !== 'sd' && lang !== 'en') return <Navigate to="/sd" replace />;
    return <Outlet />;
}

function WindowFrame({ title, children, className = '', bodyClassName = '' }) {
    return (
        <section className={`bol-window ${className}`.trim()} aria-label={title || undefined}>
            <div className={`bol-window-body ${bodyClassName}`.trim()}>{children}</div>
        </section>
    );
}

function BioPreview({ text, lang, title }) {
    const [open, setOpen] = useState(false);
    const full = String(text || '').trim();
    const limit = 140;
    const needsExpand = full.length > limit;
    const preview = needsExpand
        ? `${full.slice(0, limit).replace(/\s+\S*$/, '').trim()}…`
        : full;
    const dialogTitle = title || t(lang, 'تعارف', 'About');

    useEffect(() => {
        if (!open) return undefined;
        const onKey = (e) => {
            if (e.key === 'Escape') setOpen(false);
        };
        const prevOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        document.addEventListener('keydown', onKey);
        return () => {
            document.body.style.overflow = prevOverflow;
            document.removeEventListener('keydown', onKey);
        };
    }, [open]);

    if (!full) return null;

    return (
        <div className="bol-bio-wrap">
            <p className="bol-bio" dir="rtl" lang="sd">{preview}</p>
            {needsExpand && (
                <button
                    type="button"
                    className="bol-btn bol-bio-more"
                    onClick={() => setOpen(true)}
                >
                    {t(lang, 'وڌيڪ پڙهو', 'Read more')}
                </button>
            )}
            {open && createPortal(
                <div className="bol-bio-modal" role="presentation">
                    <button
                        type="button"
                        className="bol-search-scrim"
                        aria-label={t(lang, 'بند ڪريو', 'Close')}
                        onClick={() => setOpen(false)}
                    />
                    <div
                        className="bol-bio-dialog"
                        role="dialog"
                        aria-modal="true"
                        aria-label={dialogTitle}
                    >
                        <div className="bol-bio-dialog-head">
                            <h2 dir="rtl" lang="sd">{dialogTitle}</h2>
                            <button
                                type="button"
                                className="bol-btn"
                                onClick={() => setOpen(false)}
                            >
                                {t(lang, 'بند', 'Close')}
                            </button>
                        </div>
                        <p className="bol-bio bol-bio-full" dir="rtl" lang="sd">{full}</p>
                    </div>
                </div>,
                document.body,
            )}
        </div>
    );
}

function Cover({ src, monogram = 'ٻ' }) {
    return (
        <div className="bol-cover">
            {src ? <img src={mediaSrc(src)} alt="" loading="lazy" /> : (
                <div className="bol-cover-fallback" aria-hidden>{monogram}</div>
            )}
        </div>
    );
}

function SearchCommand({ lang, className = '' }) {
    const navigate = useNavigate();
    const [q, setQ] = useState('');
    const [open, setOpen] = useState(false);
    const [active, setActive] = useState(0);
    const inputRef = useRef(null);
    const dialogRef = useRef(null);
    const isMac = typeof navigator !== 'undefined'
        && /Mac|iPhone|iPad|iPod/.test(navigator.platform || navigator.userAgent || '');

    const { data, isFetching } = useQuery({
        queryKey: ['bol-search', lang, q],
        enabled: open && q.trim().length >= 1,
        queryFn: async () => (await api.get('/api/v1/lyrics', {
            params: { lang, search: q.trim(), per_page: 8 },
        })).data?.data || [],
    });

    const results = data || [];
    const placeholder = t(lang, 'ڪنهن گيت جي ڳولا…', 'Find a song or verse…');

    const close = () => {
        setOpen(false);
        setQ('');
        setActive(0);
    };

    useEffect(() => {
        const onKey = (e) => {
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                setOpen(true);
            }
        };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, []);

    useEffect(() => {
        if (!open) return undefined;
        const id = window.requestAnimationFrame(() => inputRef.current?.focus());
        const prevOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        return () => {
            window.cancelAnimationFrame(id);
            document.body.style.overflow = prevOverflow;
        };
    }, [open]);

    useEffect(() => setActive(0), [results.length, q]);

    const go = (item) => {
        close();
        navigate(`/${lang}/song/${item.slug}`);
    };

    const onKeyDown = (e) => {
        if (e.key === 'Escape') {
            e.preventDefault();
            close();
            return;
        }
        if (results.length === 0) return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setActive((i) => (i + 1) % results.length);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setActive((i) => (i - 1 + results.length) % results.length);
        } else if (e.key === 'Enter' && results[active]) {
            e.preventDefault();
            go(results[active]);
        }
    };

    const shortcut = isMac ? '⌘K' : 'Ctrl K';

    const popup = open ? createPortal(
        <div
            className={`bol-search-modal ${lang === 'sd' ? 'is-rtl' : 'is-ltr'}`}
            role="presentation"
            dir={lang === 'sd' ? 'rtl' : 'ltr'}
            lang={lang === 'sd' ? 'sd' : 'en'}
        >
            <button
                type="button"
                className="bol-search-scrim"
                aria-label={t(lang, 'ڳولا بند ڪريو', 'Close search')}
                onClick={close}
            />
            <div
                className="bol-search-dialog"
                role="dialog"
                aria-modal="true"
                aria-label={t(lang, 'ڳولا', 'Search')}
                ref={dialogRef}
            >
                <div className={`bol-search bol-search-modal-field ${className}`.trim()}>
                    <SearchIcon className="h-4 w-4 opacity-60" aria-hidden />
                    <input
                        ref={inputRef}
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                        onKeyDown={onKeyDown}
                        placeholder={placeholder}
                        dir={lang === 'sd' ? 'rtl' : 'ltr'}
                        lang={lang === 'sd' ? 'sd' : 'en'}
                        role="combobox"
                        aria-expanded={q.trim().length > 0}
                        aria-autocomplete="list"
                    />
                    <kbd className="bol-search-kbd">{shortcut}</kbd>
                </div>
                <div className="bol-search-panel is-modal" role="listbox">
                    {q.trim().length === 0 && (
                        <div
                            className="bol-search-item is-hint"
                            dir={lang === 'sd' ? 'rtl' : 'ltr'}
                            lang={lang === 'sd' ? 'sd' : 'en'}
                        >
                            {t(lang, 'گيت يا فنڪار لکو…', 'Type a song or artist…')}
                        </div>
                    )}
                    {q.trim().length > 0 && isFetching && (
                        <div
                            className="bol-search-item"
                            dir={lang === 'sd' ? 'rtl' : 'ltr'}
                            lang={lang === 'sd' ? 'sd' : 'en'}
                        >
                            {t(lang, 'ڳولھي رهيو آهي…', 'Searching…')}
                        </div>
                    )}
                    {q.trim().length > 0 && !isFetching && results.length === 0 && (
                        <div
                            className="bol-search-item"
                            dir={lang === 'sd' ? 'rtl' : 'ltr'}
                            lang={lang === 'sd' ? 'sd' : 'en'}
                        >
                            {t(lang, 'ڪو نتيجو نه مليو.', 'No matches.')}
                        </div>
                    )}
                    {results.map((item, i) => (
                        <button
                            key={item.id}
                            type="button"
                            role="option"
                            aria-selected={i === active}
                            className={`bol-search-item ${i === active ? 'is-active' : ''}`}
                            onMouseEnter={() => setActive(i)}
                            onClick={() => go(item)}
                        >
                            <strong dir="rtl" lang="sd">{item.title}</strong>
                            <span dir="rtl" lang="sd">{item.singer?.name || '—'}</span>
                        </button>
                    ))}
                </div>
            </div>
        </div>,
        document.body,
    ) : null;

    return (
        <>
            <button
                type="button"
                className={`bol-search bol-search-trigger ${className}`.trim()}
                onClick={() => setOpen(true)}
                aria-haspopup="dialog"
                aria-expanded={open}
                aria-label={t(lang, 'ڳولا', 'Search')}
            >
                <SearchIcon className="bol-search-trigger-icon h-4 w-4" aria-hidden />
                <span className="bol-search-trigger-label" dir={lang === 'sd' ? 'rtl' : 'ltr'} lang={lang === 'sd' ? 'sd' : undefined}>
                    {placeholder}
                </span>
                <kbd className="bol-search-kbd">{shortcut}</kbd>
            </button>
            {popup}
        </>
    );
}

function DiscoverySidebar({ lang, onNavigate }) {
    const { data: artists } = useQuery({
        queryKey: ['bol-side-artists', lang],
        queryFn: async () => (await api.get('/api/v1/singers', {
            params: { lang, per_page: 8 },
        })).data?.data || [],
    });

    const { data: bands } = useQuery({
        queryKey: ['bol-side-bands', lang],
        queryFn: async () => {
            const featured = (await api.get('/api/v1/bands', {
                params: { lang, featured: 1, per_page: 8 },
            })).data?.data || [];
            if (featured.length) return featured;
            return (await api.get('/api/v1/bands', {
                params: { lang, per_page: 8 },
            })).data?.data || [];
        },
    });

    const { data: genres } = useQuery({
        queryKey: ['bol-side-genres', lang],
        queryFn: async () => (await api.get('/api/v1/lyrics-genres', {
            params: { lang },
        })).data?.data || [],
    });

    const sideBands = (bands?.length ? bands : []).slice(0, 6);

    return (
        <aside id="bol-sidebar" className="bol-sidebar" aria-label={t(lang, 'ڳولا', 'Discover')}>
            <div className="bol-side-block">
                <div className="bol-side-label">{t(lang, 'نمايان فنڪار', 'Featured artists')}</div>
                {(artists || []).slice(0, 6).map((a) => (
                    <NavLink
                        key={a.id}
                        to={`/${lang}/artist/${a.slug}`}
                        className="bol-side-artist"
                        onClick={onNavigate}
                    >
                        <div className="bol-side-avatar">
                            {a.pic ? <img src={mediaSrc(a.pic)} alt="" /> : (a.name || 'ٻ').slice(0, 1)}
                        </div>
                        <span>
                            <strong dir="rtl" lang="sd">{a.name}</strong>
                            <span>{a.lyrics_count || 0} {t(lang, 'گيت', 'songs')}</span>
                        </span>
                    </NavLink>
                ))}
                <Link className="bol-side-more" to={`/${lang}/artists`} onClick={onNavigate}>
                    {t(lang, 'سڀ فنڪار ←', 'Browse all artists →')}
                </Link>
            </div>

            {sideBands.length > 0 && (
                <div className="bol-side-block">
                    <div className="bol-side-label">{t(lang, 'نمايان بينڊ', 'Featured bands')}</div>
                    {sideBands.map((b) => (
                        <NavLink
                            key={b.id}
                            to={`/${lang}/band/${b.slug}`}
                            className="bol-side-artist"
                            onClick={onNavigate}
                        >
                            <div className="bol-side-avatar">
                                {b.pic ? <img src={mediaSrc(b.pic)} alt="" /> : (b.name || 'ٻ').slice(0, 1)}
                            </div>
                            <span>
                                <strong dir="rtl" lang="sd">{b.name}</strong>
                                <span>{b.lyrics_count || 0} {t(lang, 'گيت', 'songs')}</span>
                            </span>
                        </NavLink>
                    ))}
                    <Link className="bol-side-more" to={`/${lang}/bands`} onClick={onNavigate}>
                        {t(lang, 'سڀ بينڊ ←', 'Browse all bands →')}
                    </Link>
                </div>
            )}

            <div className="bol-side-block">
                <div className="bol-side-label">{t(lang, 'صنفون', 'Genres')}</div>
                {(genres || []).map((g) => (
                    <NavLink
                        key={g.id}
                        to={`/${lang}/genre/${g.slug}`}
                        className="bol-side-genre is-plain"
                        onClick={onNavigate}
                    >
                        <span>
                            <strong dir={lang === 'sd' ? 'rtl' : 'ltr'} lang={lang}>{g.name}</strong>
                            <span>{g.lyrics_count || 0} {t(lang, 'گيت', 'songs')}</span>
                        </span>
                    </NavLink>
                ))}
                <Link className="bol-side-more" to={`/${lang}/genres`} onClick={onNavigate}>
                    {t(lang, 'سڀ صنفون ←', 'All genres →')}
                </Link>
            </div>
        </aside>
    );
}

function SiteHeader({ sidebarOpen, onToggleSidebar }) {
    const lang = useLang();
    const location = useLocation();
    const other = lang === 'sd' ? 'en' : 'sd';
    const otherPath = location.pathname.replace(/^\/(sd|en)/, `/${other}`) || `/${other}`;

    return (
        <header className="bol-topnav">
            <div className="bol-brand-cluster">
                <button
                    type="button"
                    className="bol-menu-btn"
                    aria-expanded={sidebarOpen}
                    aria-controls="bol-sidebar"
                    aria-label={sidebarOpen
                        ? t(lang, 'سائڊبار بند ڪريو', 'Close sidebar')
                        : t(lang, 'سائڊبار کوليو', 'Open sidebar')}
                    onClick={onToggleSidebar}
                >
                    {sidebarOpen ? <X className="h-5 w-5" aria-hidden /> : <Menu className="h-5 w-5" aria-hidden />}
                </button>
                <Link to={`/${lang}`} className="bol-brand">
                    <span className="bol-brand-mark" lang="sd" dir="rtl">ٻ</span>
                    <strong dir="rtl" lang="sd">ٻول</strong>
                </Link>
            </div>
            <div className="bol-topnav-search">
                <SearchCommand lang={lang} />
            </div>
            <nav className="bol-top-links" aria-label={t(lang, 'مکيه مينيو', 'Primary')}>
                <NavLink to={`/${lang}/artists`}>{t(lang, 'فنڪار', 'Artists')}</NavLink>
                <NavLink to={`/${lang}/bands`}>{t(lang, 'بينڊ', 'Bands')}</NavLink>
                <NavLink to={`/${lang}/genres`}>{t(lang, 'صنفون', 'Genres')}</NavLink>
                <Link className="bol-lang-link" to={otherPath}>
                    {other === 'sd' ? 'سنڌي' : t(lang, 'انگريزي', 'EN')}
                </Link>
                <a href={cfg.mainSiteUrl} target="_blank" rel="noreferrer">
                    {t(lang, 'باک آرڪائيوَ', 'Baakh Archive')}
                    <ExternalLink className="h-3 w-3 inline ms-1" aria-hidden />
                </a>
            </nav>
        </header>
    );
}

function LyricsShell() {
    const lang = useLang();
    const isRtl = lang === 'sd';
    const dir = isRtl ? 'rtl' : 'ltr';
    const location = useLocation();
    const [sidebarOpen, setSidebarOpen] = useState(false);

    useEffect(() => {
        const root = document.documentElement;
        root.setAttribute('lang', lang);
        root.setAttribute('dir', dir);
        document.body.classList.toggle('is-rtl', isRtl);
        document.body.classList.toggle('is-ltr', !isRtl);
        return () => document.body.classList.remove('is-rtl', 'is-ltr');
    }, [lang, dir, isRtl]);

    useEffect(() => {
        setSidebarOpen(false);
    }, [location.pathname]);

    useEffect(() => {
        const mq = window.matchMedia('(min-width: 960px)');
        const onChange = () => {
            if (mq.matches) setSidebarOpen(false);
        };
        mq.addEventListener('change', onChange);
        return () => mq.removeEventListener('change', onChange);
    }, []);

    useEffect(() => {
        if (!sidebarOpen) return undefined;
        const onKey = (e) => {
            if (e.key === 'Escape') setSidebarOpen(false);
        };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, [sidebarOpen]);

    return (
        <div className={`lyrics-app ${dir}${sidebarOpen ? ' is-sidebar-open' : ''}`} dir={dir} lang={lang}>
            <SiteHeader
                sidebarOpen={sidebarOpen}
                onToggleSidebar={() => setSidebarOpen((v) => !v)}
            />
            <div className="bol-body">
                <DiscoverySidebar lang={lang} onNavigate={() => setSidebarOpen(false)} />
                <div className="bol-main">
                    <Outlet />
                </div>
            </div>
            {sidebarOpen && (
                <button
                    type="button"
                    className="bol-sidebar-scrim"
                    aria-label={t(lang, 'سائڊبار بند ڪريو', 'Close sidebar')}
                    onClick={() => setSidebarOpen(false)}
                />
            )}
        </div>
    );
}

function SongCard({ item, lang }) {
    return (
        <Link to={`/${lang}/song/${item.slug}`} className="bol-song">
            <div className="bol-song-inner">
                <Cover src={item.cover} monogram={(item.title || 'ٻ').slice(0, 1)} />
                <h3 dir="rtl" lang="sd">{item.title}</h3>
                <p dir="rtl" lang="sd">{primaryCredit(item)}</p>
            </div>
        </Link>
    );
}

function ArtistCard({ artist, lang }) {
    return (
        <Link to={`/${lang}/artist/${artist.slug}`} className="bol-artist-avatar">
            <div className="bol-avatar-round" aria-hidden>
                {artist.pic ? (
                    <img src={mediaSrc(artist.pic)} alt="" loading="lazy" />
                ) : (
                    <span>{(artist.name || 'ٻ').slice(0, 1)}</span>
                )}
            </div>
            <span className="bol-artist-avatar-name" dir="rtl" lang="sd">{artist.name}</span>
        </Link>
    );
}

function BandCard({ band, lang }) {
    return (
        <Link to={`/${lang}/band/${band.slug}`} className="bol-song bol-band-card">
            <div className="bol-song-inner">
                <Cover src={band.pic} monogram={(band.name || 'ٻ').slice(0, 1)} />
                <h3 dir="rtl" lang="sd">{band.name}</h3>
                {band.tagline ? (
                    <p dir="rtl" lang="sd">{band.tagline}</p>
                ) : null}
            </div>
        </Link>
    );
}

function FeaturedCard({ song, lang }) {
    if (!song) return null;
    const poet = (song.parts || []).map((p) => p.poet_name).find(Boolean);

    return (
        <Link to={`/${lang}/song/${song.slug}`} className="bol-featured-card">
            <div className="bol-featured-card-inner">
                <Cover src={song.cover} monogram={(song.title || 'ٻ').slice(0, 1)} />
                <h3 dir="rtl" lang="sd">{song.title}</h3>
                <p className="bol-featured-meta" dir="rtl" lang="sd">
                    {primaryCredit(song)}
                    {poet ? ` · ${poet}` : ''}
                </p>
            </div>
        </Link>
    );
}

function GenreShelf({ genre, lang }) {
    const { data, isLoading } = useQuery({
        queryKey: ['bol-shelf', genre.slug, lang],
        queryFn: async () => (await api.get('/api/v1/lyrics', {
            params: { lang, genre: genre.slug, per_page: 8 },
        })).data?.data || [],
    });

    const songs = data || [];
    if (!isLoading && songs.length === 0) return null;

    return (
        <section className="bol-shelf bol-animate">
            <div className="bol-shelf-head">
                <h2 className="bol-shelf-title">
                    {genre.name}
                </h2>
                <Link className="bol-shelf-link" to={`/${lang}/genre/${genre.slug}`}>
                    {t(lang, 'سڀ', 'See all')}
                </Link>
            </div>
            {isLoading ? <div className="bol-skeleton" /> : (
                <div className="bol-rail">
                    {songs.map((item) => (
                        <SongCard key={item.id} item={item} lang={lang} />
                    ))}
                </div>
            )}
        </section>
    );
}

function HomePage() {
    const lang = useLang();

    const { data: featuredList } = useQuery({
        queryKey: ['bol-featured-list', lang],
        queryFn: async () => (await api.get('/api/v1/lyrics', {
            params: { lang, featured: 1, per_page: 8 },
        })).data?.data || [],
    });

    const { data: recent } = useQuery({
        queryKey: ['bol-recent', lang],
        queryFn: async () => (await api.get('/api/v1/lyrics', {
            params: { lang, per_page: 8 },
        })).data?.data || [],
    });

    const { data: genres } = useQuery({
        queryKey: ['bol-genres-home', lang],
        queryFn: async () => (await api.get('/api/v1/lyrics-genres', { params: { lang } })).data?.data || [],
    });

    const { data: feedArtists } = useQuery({
        queryKey: ['bol-feed-artists', lang],
        queryFn: async () => {
            const featured = (await api.get('/api/v1/singers', {
                params: { lang, featured: 1, per_page: 12 },
            })).data?.data || [];
            if (featured.length >= 4) return featured;
            return (await api.get('/api/v1/singers', {
                params: { lang, per_page: 12 },
            })).data?.data || [];
        },
    });

    const { data: feedBands } = useQuery({
        queryKey: ['bol-feed-bands', lang],
        queryFn: async () => {
            const featured = (await api.get('/api/v1/bands', {
                params: { lang, featured: 1, per_page: 12 },
            })).data?.data || [];
            if (featured.length >= 3) return featured;
            return (await api.get('/api/v1/bands', {
                params: { lang, per_page: 12 },
            })).data?.data || [];
        },
    });

    const featuredSlugs = (featuredList || []).map((s) => s.slug);
    const featuredDetails = useQueries({
        queries: featuredSlugs.map((slug) => ({
            queryKey: ['bol-featured', slug, lang],
            queryFn: async () => (await api.get(`/api/v1/lyrics/${slug}`, { params: { lang } })).data,
            enabled: !!slug,
        })),
    });

    const featuredSongs = featuredDetails.map((q) => q.data).filter(Boolean);
    const featuredIds = new Set(featuredSongs.map((s) => s.id));
    const featuredFallback = [
        ...featuredSongs,
        ...((recent || []).filter((s) => !featuredIds.has(s.id))),
    ].slice(0, 4);

    // Interleave shelf types so the feed doesn't stack similar blocks.
    const genreList = genres || [];
    const artists = feedArtists || [];
    const bands = feedBands || [];
    const recentSongs = recent || [];

    const feedBlocks = [
        featuredFallback.length > 0 && {
            key: 'featured',
            node: (
                <section className="bol-shelf">
                    <div className="bol-shelf-head">
                        <h2 className="bol-shelf-title">{t(lang, 'نمايان', 'Featured')}</h2>
                    </div>
                    <div className="bol-featured-row">
                        {featuredFallback.map((song) => (
                            <FeaturedCard
                                key={song.id || song.slug}
                                song={song}
                                lang={lang}
                            />
                        ))}
                    </div>
                </section>
            ),
        },
        artists.length > 0 && {
            key: 'artists',
            node: (
                <section className="bol-shelf bol-animate">
                    <div className="bol-shelf-head">
                        <h2 className="bol-shelf-title">{t(lang, 'فنڪار', 'Artists')}</h2>
                        <Link className="bol-shelf-link" to={`/${lang}/artists`}>
                            {t(lang, 'سڀ', 'See all')}
                        </Link>
                    </div>
                    <div className="bol-rail bol-artist-rail">
                        {artists.map((a) => (
                            <ArtistCard key={a.id} artist={a} lang={lang} />
                        ))}
                    </div>
                </section>
            ),
        },
        genreList[0] && {
            key: `genre-${genreList[0].id}`,
            node: <GenreShelf genre={genreList[0]} lang={lang} />,
        },
        bands.length > 0 && {
            key: 'bands',
            node: (
                <section className="bol-shelf bol-animate">
                    <div className="bol-shelf-head">
                        <h2 className="bol-shelf-title">{t(lang, 'بينڊ', 'Bands')}</h2>
                        <Link className="bol-shelf-link" to={`/${lang}/bands`}>
                            {t(lang, 'سڀ', 'See all')}
                        </Link>
                    </div>
                    <div className="bol-rail bol-band-rail">
                        {bands.map((b) => (
                            <BandCard key={b.id} band={b} lang={lang} />
                        ))}
                    </div>
                </section>
            ),
        },
        ...genreList.slice(1, 3).map((genre) => ({
            key: `genre-${genre.id}`,
            node: <GenreShelf genre={genre} lang={lang} />,
        })),
        recentSongs.length > 0 && {
            key: 'recent',
            node: (
                <section className="bol-shelf bol-animate">
                    <div className="bol-shelf-head">
                        <h2 className="bol-shelf-title">{t(lang, 'تازا شامل ٿيل', 'Recently added')}</h2>
                    </div>
                    <div className="bol-rail">
                        {recentSongs.map((item) => (
                            <SongCard key={item.id} item={item} lang={lang} />
                        ))}
                    </div>
                </section>
            ),
        },
        ...genreList.slice(3).map((genre) => ({
            key: `genre-${genre.id}`,
            node: <GenreShelf genre={genre} lang={lang} />,
        })),
    ].filter(Boolean);

    return (
        <div className="bol-home">
            {feedBlocks.map((block) => (
                <React.Fragment key={block.key}>{block.node}</React.Fragment>
            ))}
        </div>
    );
}

function ArtistsPage() {
    const lang = useLang();
    const [q, setQ] = useState('');
    const { data, isLoading, isError } = useQuery({
        queryKey: ['bol-artists', lang, q],
        queryFn: async () => (await api.get('/api/v1/singers', {
            params: { lang, search: q || undefined, per_page: 48 },
        })).data,
    });
    const artists = data?.data || [];

    return (
        <div className="bol-section bol-animate">
            <div className="bol-section-head">
                <h2>{t(lang, 'فنڪار', 'Artists')}</h2>
            </div>
            <div className="bol-search" style={{ maxWidth: 420 }}>
                <SearchIcon className="h-4 w-4 opacity-60" aria-hidden />
                <input
                    value={q}
                    onChange={(e) => setQ(e.target.value)}
                    placeholder={t(lang, 'فنڪار ڳوليو…', 'Filter artists…')}
                    dir={lang === 'sd' ? 'rtl' : 'ltr'}
                    lang={lang === 'sd' ? 'sd' : undefined}
                />
            </div>
            {isLoading && <div className="bol-skeleton" />}
            {isError && <p className="bol-error">{t(lang, 'فنڪار لوڊ نه ٿي سگهيا.', 'Could not load artists.')}</p>}
            <div className="bol-artists">
                {artists.map((a) => <ArtistCard key={a.id} artist={a} lang={lang} />)}
            </div>
        </div>
    );
}

function BandsPage() {
    const lang = useLang();
    const [q, setQ] = useState('');
    const { data, isLoading, isError } = useQuery({
        queryKey: ['bol-bands', lang, q],
        queryFn: async () => (await api.get('/api/v1/bands', {
            params: { lang, search: q || undefined, per_page: 48 },
        })).data,
    });
    const bands = data?.data || [];

    return (
        <div className="bol-section bol-animate">
            <div className="bol-section-head">
                <h2>{t(lang, 'بينڊ', 'Bands')}</h2>
            </div>
            <div className="bol-search" style={{ maxWidth: 420 }}>
                <SearchIcon className="h-4 w-4 opacity-60" aria-hidden />
                <input
                    value={q}
                    onChange={(e) => setQ(e.target.value)}
                    placeholder={t(lang, 'بينڊ ڳوليو…', 'Filter bands…')}
                    dir={lang === 'sd' ? 'rtl' : 'ltr'}
                    lang={lang === 'sd' ? 'sd' : undefined}
                />
            </div>
            {isLoading && <div className="bol-skeleton" />}
            {isError && <p className="bol-error">{t(lang, 'بينڊ لوڊ نه ٿي سگهيا.', 'Could not load bands.')}</p>}
            <div className="bol-song-board">
                {bands.map((b) => <BandCard key={b.id} band={b} lang={lang} />)}
            </div>
        </div>
    );
}

function GenresPage() {
    const lang = useLang();
    const { data, isLoading } = useQuery({
        queryKey: ['bol-genres-page', lang],
        queryFn: async () => (await api.get('/api/v1/lyrics-genres', { params: { lang } })).data?.data || [],
    });

    return (
        <div className="bol-section bol-animate">
            <div className="bol-section-head">
                <h2>{t(lang, 'صنفون', 'Genres')}</h2>
            </div>
            {isLoading && <div className="bol-skeleton" />}
            <div className="bol-genre-grid">
                {(data || []).map((g) => (
                    <Link key={g.id} to={`/${lang}/genre/${g.slug}`} className="bol-genre-tile">
                        <strong dir={lang === 'sd' ? 'rtl' : 'ltr'} lang={lang}>{g.name}</strong>
                        <span>{g.lyrics_count || 0} {t(lang, 'گيت', 'songs')}</span>
                    </Link>
                ))}
            </div>
        </div>
    );
}

function GenrePage() {
    const lang = useLang();
    const { slug } = useParams();
    const navigate = useNavigate();

    const { data: genre, isLoading: gLoading, isError: gError } = useQuery({
        queryKey: ['bol-genre', slug, lang],
        queryFn: async () => (await api.get(`/api/v1/lyrics-genres/${slug}`, { params: { lang } })).data,
    });

    const { data: songs, isLoading } = useQuery({
        queryKey: ['bol-genre-songs', slug, lang],
        enabled: !!slug,
        queryFn: async () => (await api.get('/api/v1/lyrics', {
            params: { lang, genre: slug, per_page: 48 },
        })).data,
    });

    if (gLoading) return <div className="bol-skeleton" />;
    if (gError || !genre) return <p className="bol-error">{t(lang, 'صنف نه ملي.', 'Genre not found.')}</p>;

    return (
        <div className="bol-section bol-animate">
            <button type="button" className="bol-btn bol-back" onClick={() => navigate(-1)}>
                <ArrowLeft className="bol-back-icon h-4 w-4" aria-hidden />
                {t(lang, 'واپس', 'Back')}
            </button>
            <div className="bol-section-head">
                <h2>{genre.name}</h2>
                <span className="bol-meta-label">{genre.lyrics_count || 0} {t(lang, 'گيت', 'songs')}</span>
            </div>
            {isLoading && <div className="bol-skeleton" />}
            <div className="bol-song-board">
                {(songs?.data || []).map((item) => (
                    <SongCard key={item.id} item={item} lang={lang} />
                ))}
            </div>
            {!isLoading && (songs?.data || []).length === 0 && (
                <p className="bol-empty">{t(lang, 'هن صنف ۾ اڃا گيت نه آهن.', 'No songs in this genre yet.')}</p>
            )}
        </div>
    );
}

function ArtistPage() {
    const lang = useLang();
    const { slug } = useParams();
    const navigate = useNavigate();

    const { data: artist, isLoading, isError } = useQuery({
        queryKey: ['bol-artist', slug, lang],
        queryFn: async () => (await api.get(`/api/v1/singers/${slug}`, { params: { lang } })).data,
    });

    const { data: songs } = useQuery({
        queryKey: ['bol-artist-songs', slug, lang],
        queryFn: async () => (await api.get(`/api/v1/singers/${slug}/lyrics`, {
            params: { lang, per_page: 48 },
        })).data,
    });

    if (isLoading) return <div className="bol-skeleton" />;
    if (isError || !artist) return <p className="bol-error">{t(lang, 'فنڪار نه مليو.', 'Artist not found.')}</p>;

    return (
        <div className="bol-artist-page bol-animate">
            <button type="button" className="bol-btn bol-back" onClick={() => navigate(-1)}>
                <ArrowLeft className="bol-back-icon h-4 w-4" aria-hidden />
                {t(lang, 'واپس', 'Back')}
            </button>
            <WindowFrame title={t(lang, 'فنڪار', 'Artist')}>
                <div className="bol-artist-hero">
                    <div className="bol-portrait">
                        {artist.pic ? <img src={mediaSrc(artist.pic)} alt="" /> : (
                            <div className="bol-cover-fallback">{(artist.name || 'ٻ').slice(0, 1)}</div>
                        )}
                    </div>
                    <div className="bol-artist-meta">
                        <h1 dir="rtl" lang="sd">{artist.name}</h1>
                        {artist.tagline && <p className="bol-lead" dir="rtl" lang="sd">{artist.tagline}</p>}
                        {artist.bio && (
                            <BioPreview
                                text={artist.bio}
                                lang={lang}
                                title={artist.name || t(lang, 'تعارف', 'About')}
                            />
                        )}
                        <p className="bol-fileinfo">
                            {artist.lyrics_count || songs?.data?.length || 0}
                            {' '}
                            {t(lang, 'گيت', 'songs')}
                        </p>
                        {(artist.bands || []).length > 0 && (
                            <p className="bol-quiet-links" dir="rtl" lang="sd">
                                {(artist.bands || []).map((b, i) => (
                                    <span key={b.id}>
                                        {i > 0 && <span aria-hidden> · </span>}
                                        <Link to={`/${lang}/band/${b.slug}`}>{b.name}</Link>
                                    </span>
                                ))}
                            </p>
                        )}
                    </div>
                </div>
            </WindowFrame>
            <ListenLinks entity={artist} lang={lang} />
            <section className="bol-section">
                <div className="bol-section-head">
                    <h2>{t(lang, 'گيت', 'Songs')}</h2>
                </div>
                <div className="bol-song-board">
                    {(songs?.data || []).map((item) => (
                        <SongCard key={item.id} item={item} lang={lang} />
                    ))}
                </div>
            </section>
        </div>
    );
}

function BandPage() {
    const lang = useLang();
    const { slug } = useParams();
    const navigate = useNavigate();

    const { data: band, isLoading, isError } = useQuery({
        queryKey: ['bol-band', slug, lang],
        queryFn: async () => (await api.get(`/api/v1/bands/${slug}`, { params: { lang } })).data,
    });

    const { data: songs } = useQuery({
        queryKey: ['bol-band-songs', slug, lang],
        queryFn: async () => (await api.get(`/api/v1/bands/${slug}/lyrics`, {
            params: { lang, per_page: 48 },
        })).data,
    });

    if (isLoading) return <div className="bol-skeleton" />;
    if (isError || !band) return <p className="bol-error">{t(lang, 'بينڊ نه مليو.', 'Band not found.')}</p>;

    const members = band.members || [];

    return (
        <div className="bol-artist-page bol-animate">
            <button type="button" className="bol-btn bol-back" onClick={() => navigate(-1)}>
                <ArrowLeft className="bol-back-icon h-4 w-4" aria-hidden />
                {t(lang, 'واپس', 'Back')}
            </button>
            <WindowFrame title={t(lang, 'بينڊ', 'Band')}>
                <div className="bol-artist-hero">
                    <div className="bol-portrait">
                        {band.pic ? <img src={mediaSrc(band.pic)} alt="" /> : (
                            <div className="bol-cover-fallback">{(band.name || 'ٻ').slice(0, 1)}</div>
                        )}
                    </div>
                    <div className="bol-artist-meta">
                        <h1 dir="rtl" lang="sd">{band.name}</h1>
                        {band.tagline && <p className="bol-lead" dir="rtl" lang="sd">{band.tagline}</p>}
                        {band.bio && (
                            <BioPreview
                                text={band.bio}
                                lang={lang}
                                title={band.name || t(lang, 'تعارف', 'About')}
                            />
                        )}
                        <p className="bol-fileinfo">
                            {band.lyrics_count || songs?.data?.length || 0}
                            {' '}
                            {t(lang, 'گيت', 'songs')}
                            {band.members_count > 0 && (
                                <>
                                    {' · '}
                                    {band.members_count}
                                    {' '}
                                    {t(lang, 'ميمبر', 'members')}
                                </>
                            )}
                        </p>
                    </div>
                </div>
            </WindowFrame>
            {members.length > 0 && (
                <section className="bol-section">
                    <div className="bol-section-head">
                        <h2>{t(lang, 'ميمبر', 'Members')}</h2>
                    </div>
                    <div className="bol-artists">
                        {members.map((m) => (
                            <Link
                                key={m.id}
                                to={`/${lang}/artist/${m.slug}`}
                                className="bol-artist-avatar"
                            >
                                <div className="bol-avatar-round" aria-hidden>
                                    {m.pic ? (
                                        <img src={mediaSrc(m.pic)} alt="" loading="lazy" />
                                    ) : (
                                        <span>{(m.name || 'ٻ').slice(0, 1)}</span>
                                    )}
                                </div>
                                <span className="bol-artist-avatar-name" dir="rtl" lang="sd">
                                    {m.name}
                                    {m.role ? (
                                        <span className="bol-member-role">{m.role}</span>
                                    ) : null}
                                </span>
                            </Link>
                        ))}
                    </div>
                </section>
            )}
            <ListenLinks entity={band} lang={lang} />
            <section className="bol-section">
                <div className="bol-section-head">
                    <h2>{t(lang, 'گيت', 'Songs')}</h2>
                </div>
                <div className="bol-song-board">
                    {(songs?.data || []).map((item) => (
                        <SongCard key={item.id} item={item} lang={lang} />
                    ))}
                </div>
            </section>
        </div>
    );
}

function MusicPlayer({ song, lang }) {
    const yt = youtubeId(song.music_url);
    if (!song.music_url) return null;

    if (yt) {
        return (
            <WindowFrame title={t(lang, 'ٻڌو', 'Listen')} className="bol-player" bodyClassName="is-flush">
                <iframe
                    title={song.music_title || song.title}
                    src={`https://www.youtube.com/embed/${yt}`}
                    allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowFullScreen
                />
                <div className="bol-player-body">
                    <a className="bol-song-genre" href={song.music_url} target="_blank" rel="noreferrer">
                        {song.music_title || t(lang, 'يوٽيوب تي ٻڌو', 'Listen on YouTube')}
                        <ExternalLink className="h-3.5 w-3.5 inline ms-1" aria-hidden />
                    </a>
                </div>
            </WindowFrame>
        );
    }

    if (song.music_type === 'audio' || /\.(mp3|m4a|ogg|wav)(\?|$)/i.test(song.music_url)) {
        return (
            <WindowFrame title={t(lang, 'آڊيو', 'Audio')} className="bol-player">
                <audio controls preload="none" src={song.music_url} />
            </WindowFrame>
        );
    }

    return (
        <a className="bol-btn" href={song.music_url} target="_blank" rel="noreferrer">
            {t(lang, 'موسيقي کوليو', 'Open music')}
        </a>
    );
}

function lyricLines(text) {
    return String(text || '')
        .replace(/\r\n/g, '\n')
        .split('\n')
        .map((line) => line.trim())
        .filter(Boolean);
}

const SECTION_LABELS = {
    intro: { sd: 'افتتاح', en: 'Intro' },
    verse_1: { sd: 'بند 1', en: 'Verse 1' },
    verse_2: { sd: 'بند 2', en: 'Verse 2' },
    verse_3: { sd: 'بند 3', en: 'Verse 3' },
    verse_4: { sd: 'بند 4', en: 'Verse 4' },
    pre_chorus: { sd: 'پري-مُکڙو', en: 'Pre-Chorus' },
    chorus: { sd: 'مُکڙو', en: 'Chorus' },
    post_chorus: { sd: 'پوسٽ-مُکڙو', en: 'Post-Chorus' },
    bridge: { sd: 'برج', en: 'Bridge' },
    instrumental: { sd: 'موسيقي', en: 'Instrumental' },
    interlude: { sd: 'انٽرليود', en: 'Interlude' },
    solo: { sd: 'سولو', en: 'Solo' },
    spoken: { sd: 'ڳالھ', en: 'Spoken' },
    outro: { sd: 'آخري', en: 'Outro' },
    other: { sd: 'ٻيو', en: 'Other' },
};

function sectionTagLabel(section, lang) {
    if (!section) return null;
    const meta = SECTION_LABELS[section];
    if (!meta) return section.replace(/_/g, ' ');
    return lang === 'en' ? meta.en : meta.sd;
}

function SectionTag({ section, lang }) {
    const label = sectionTagLabel(section, lang);
    if (!label) return null;
    return (
        <span className="bol-section-tag" dir={lang === 'en' ? 'ltr' : 'rtl'} lang={lang === 'sd' ? 'sd' : undefined}>
            [{label}]
        </span>
    );
}

function LyricsTimeline({ parts, lang }) {
    const list = parts || [];
    if (list.length === 0) {
        return <p className="bol-empty">{t(lang, 'هن گيت جا ٻول اڃا شامل نه ڪيا ويا آهن.', 'No lyrics yet.')}</p>;
    }

    return (
        <section className="bol-section bol-lyrics-flow">
            <div className="bol-timeline">
                {list.map((part, index) => {
                    const sd = part.text_sd;
                    const roman = part.text_roman;
                    if (!sd && !roman && part.kind !== 'music') return null;

                    if (part.kind === 'music') {
                        return (
                            <section
                                key={part.id || index}
                                className="bol-part is-music"
                                aria-label={t(lang, 'موسيقي', 'Music')}
                            >
                                <SectionTag section={part.section || 'instrumental'} lang={lang} />
                                <div className="bol-music-marks" aria-hidden>
                                    <span>♪</span>
                                    <span>♫</span>
                                    <span>♪</span>
                                    <span>♩</span>
                                </div>
                            </section>
                        );
                    }

                    const primary = lang === 'en'
                        ? (roman || sd)
                        : (sd || null);
                    if (!primary) return null;

                    const useRoman = lang === 'en' && !!roman;
                    const dir = useRoman ? 'ltr' : 'rtl';
                    const textLang = useRoman ? undefined : 'sd';
                    const lines = lyricLines(primary);

                    return (
                        <section key={part.id || index} className={`bol-part is-${part.kind}`}>
                            <SectionTag section={part.section} lang={lang} />
                            {lines.map((line, li) => (
                                <p
                                    key={`${part.id || index}-${li}`}
                                    className="bol-part-text"
                                    dir={dir}
                                    lang={textLang}
                                >
                                    {line}
                                </p>
                            ))}
                        </section>
                    );
                })}
            </div>
        </section>
    );
}

function FullPoetrySection({ poetry, lang }) {
    if (!poetry?.couplets?.length) return null;

    const lines = poetry.couplets.flatMap((c, i) => {
        const text = lang === 'en' ? (c.text_roman || c.text_sd) : c.text_sd;
        if (!text) return [];
        const useRoman = lang === 'en' && !!c.text_roman;
        return lyricLines(text).map((line, li) => ({
            key: `${c.id || i}-${li}`,
            text: line,
            dir: useRoman ? 'ltr' : 'rtl',
            textLang: useRoman ? undefined : 'sd',
        }));
    });

    if (lines.length === 0) return null;

    return (
        <section className="bol-section bol-poetry bol-lyrics-flow">
            <div className="bol-poetry-couplets">
                {lines.map((line) => (
                    <p
                        key={line.key}
                        className="bol-part-text bol-poetry-line"
                        dir={line.dir}
                        lang={line.textLang}
                    >
                        {line.text}
                    </p>
                ))}
            </div>
        </section>
    );
}

function SongByline({ song, lang }) {
    const collabs = extraCollaborators(song);
    const items = [];

    if (song.singer) {
        items.push({
            key: 'singer',
            node: (
                <Link to={`/${lang}/artist/${song.singer.slug}`}>
                    {song.singer.name}
                </Link>
            ),
        });
    }
    if (song.band) {
        items.push({
            key: 'band',
            node: (
                <Link to={`/${lang}/band/${song.band.slug}`}>
                    {song.band.name}
                </Link>
            ),
        });
    }
    if (song.genre) {
        items.push({
            key: 'genre',
            node: (
                <Link to={`/${lang}/genre/${song.genre.slug}`}>
                    {song.genre.name}
                </Link>
            ),
        });
    }
    collabs.forEach((c) => {
        items.push({
            key: `${c.type}-${c.id}`,
            node: (
                <>
                    <span className="bol-collab-role">{collabRoleLabel(lang, c.role)} </span>
                    <Link to={collabPath(lang, c)}>{c.name}</Link>
                </>
            ),
        });
    });

    if (items.length === 0) return null;

    return (
        <p className="bol-song-byline" dir="rtl" lang="sd">
            {items.map((item, i) => (
                <React.Fragment key={item.key}>
                    {i > 0 && <span aria-hidden> · </span>}
                    {item.node}
                </React.Fragment>
            ))}
        </p>
    );
}

function SongPage() {
    const lang = useLang();
    const { slug } = useParams();
    const navigate = useNavigate();

    const { data: song, isLoading, isError } = useQuery({
        queryKey: ['bol-song', slug, lang],
        queryFn: async () => (await api.get(`/api/v1/lyrics/${slug}`, { params: { lang } })).data,
    });

    if (isLoading) return <div className="bol-skeleton" />;
    if (isError || !song) return <p className="bol-error">{t(lang, 'گيت نه مليو.', 'Song not found.')}</p>;

    return (
        <article className="bol-song-page bol-animate">
            <button type="button" className="bol-btn bol-back" onClick={() => navigate(-1)}>
                <ArrowLeft className="bol-back-icon h-4 w-4" aria-hidden />
                {t(lang, 'واپس', 'Back')}
            </button>
            <header className="bol-song-top">
                <div className="bol-song-cover-col">
                    <Cover src={song.cover} monogram={(song.title || 'ٻ').slice(0, 1)} />
                </div>
                <div className="bol-song-meta">
                    <h1 dir="rtl" lang="sd">{song.title}</h1>
                    <SongByline song={song} lang={lang} />
                </div>
            </header>
            <ListenLinks entity={song} lang={lang} />
            <MusicPlayer song={song} lang={lang} />
            <LyricsTimeline parts={song.parts} lang={lang} />
            <FullPoetrySection poetry={song.poetry} lang={lang} />
        </article>
    );
}

function App() {
    const basename = window.location.pathname.startsWith('/lyrics-site') ? '/lyrics-site' : '';

    return (
        <QueryClientProvider client={queryClient}>
            <BrowserRouter basename={basename}>
                <Routes>
                    <Route path="/" element={<Navigate to={`/${cfg.locale || 'sd'}`} replace />} />
                    <Route path="/:lang" element={<LanguageShell />}>
                        <Route element={<LyricsShell />}>
                            <Route index element={<HomePage />} />
                            <Route path="artists" element={<ArtistsPage />} />
                            <Route path="artist/:slug" element={<ArtistPage />} />
                            <Route path="bands" element={<BandsPage />} />
                            <Route path="band/:slug" element={<BandPage />} />
                            <Route path="genres" element={<GenresPage />} />
                            <Route path="genre/:slug" element={<GenrePage />} />
                            <Route path="song/:slug" element={<SongPage />} />
                        </Route>
                    </Route>
                    <Route path="*" element={<Navigate to="/sd" replace />} />
                </Routes>
            </BrowserRouter>
        </QueryClientProvider>
    );
}

createRoot(document.getElementById('lyrics-root')).render(<App />);
