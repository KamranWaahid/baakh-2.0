export const SITE_TITLE_EN = 'Baakh - Archive of Sindhi Poetry';
export const SITE_TITLE_SD = 'باک - سنڌي شاعريءَ جو آرڪائيو';

export function brandName(isRtl) {
    return isRtl ? 'باک' : 'Baakh';
}

export function siteTitle(isRtl) {
    return isRtl ? SITE_TITLE_SD : SITE_TITLE_EN;
}

export function withBrand(title, isRtl) {
    if (!title) {
        return siteTitle(isRtl);
    }

    const brand = brandName(isRtl);
    if (title === brand || title.endsWith(` | ${brand}`) || title.endsWith(` - ${brand}`)) {
        return title;
    }

    return `${title} | ${brand}`;
}

export function poetDocumentTitle(poetName, isRtl) {
    if (!poetName) {
        return null;
    }

    return isRtl
        ? `${poetName} جي شاعري: غزل، بيت ۽ ڪلام | باک`
        : `${poetName} Poetry: Ghazals, Couplets & Works | Baakh`;
}

export function poemDocumentTitle({ title, poetName, category }, isRtl) {
    if (!title) {
        return null;
    }

    const brand = brandName(isRtl);
    const parts = [poetName, category].filter(Boolean).join(' ');
    const meaning = isRtl ? 'متن ۽ مطلب' : 'Lyrics & Meaning';
    const body = parts ? `${parts} - ${title} ${meaning}` : `${title} ${meaning}`;

    if (body.endsWith(` | ${brand}`)) {
        return body;
    }

    return `${body} | ${brand}`;
}

/**
 * Titles for listing/static routes. Returns null for entity pages so the
 * server-rendered <title> (and later the page itself) is left alone.
 */
export function listingDocumentTitle(pathname, isRtl) {
    const rest = pathname.replace(/^\/(en|sd)(?=\/|$)/, '') || '/';

    if (rest.startsWith('/poet/') || rest.startsWith('/topic/') || rest.startsWith('/tag/')) {
        return null;
    }

    const titles = isRtl
        ? {
            '/': SITE_TITLE_SD,
            '/poets': 'شاعر',
            '/poetry': 'شاعري',
            '/couplets': 'بيت',
            '/genre': 'ادبي صنفون',
            '/period': 'ادبي دور',
            '/prosody': 'علم عروض ۽ ڇند وديا',
            '/explore': 'موضوعن جي ڳولا',
            '/about': 'باک بابت',
            '/contact': 'رابطو',
            '/privacy': 'رازداري',
            '/terms': 'شرط',
            '/help': 'مدد',
            '/status': 'سائيٽ جي حالت',
            '/profile': 'پروفائل',
            '/settings': 'سيٽنگون',
        }
        : {
            '/': SITE_TITLE_EN,
            '/poets': 'Poets',
            '/poetry': 'Poetry',
            '/couplets': 'Couplets',
            '/genre': 'Poetic Genres',
            '/period': 'Literary Periods',
            '/prosody': 'Sindhi Prosody',
            '/explore': 'Explore topics',
            '/about': 'About Baakh',
            '/contact': 'Contact',
            '/privacy': 'Privacy',
            '/terms': 'Terms',
            '/help': 'Help',
            '/status': 'Status',
            '/profile': 'Profile',
            '/settings': 'Settings',
        };

    if (Object.prototype.hasOwnProperty.call(titles, rest)) {
        return rest === '/' ? titles[rest] : withBrand(titles[rest], isRtl);
    }

    // Category feeds and other entity-like routes keep the server <title>.
    return null;
}
