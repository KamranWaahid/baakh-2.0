/**
 * Resolves an image path into a full URL.
 * Returns empty string when missing so UI can show an icon fallback (not the site logo).
 *
 * @param {string|null|undefined} path - The image path or URL
 * @param {'poet'|'user'|'post'|null} type - Type of image for path resolution
 * @returns {string} - The resolved URL, or '' when unavailable
 */
const isPlaceholderLogo = (path) => {
    if (!path || typeof path !== 'string') return true;
    const normalized = path.replace(/^\/+/, '').toLowerCase();
    return (
        normalized === 'assets/images/logo/logo.svg' ||
        normalized === 'assets/images/logo/logo.png' ||
        normalized.endsWith('/logo/logo.svg') ||
        normalized.endsWith('/logo/logo.png')
    );
};

export const getImageUrl = (path, type = null) => {
    if (!path || isPlaceholderLogo(path)) {
        return '';
    }

    if (typeof path === 'string' && path.startsWith('blob:')) {
        return path;
    }

    // If it's already a full URL, return it
    if (path.startsWith('http')) {
        return path;
    }

    const trimmedPath = path.replace(/^\/+/, '');

    // Keep known rooted asset/storage paths as-is (encode spaces / unicode)
    if (
        trimmedPath.startsWith('assets/') ||
        trimmedPath.startsWith('storage/') ||
        trimmedPath.startsWith('images/') ||
        trimmedPath.startsWith('Images/')
    ) {
        return `/${trimmedPath.split('/').map(encodeURIComponent).join('/')}`;
    }

    // Legacy poet/user values are often bare filenames in DB.
    if (type === 'poet' && !trimmedPath.includes('/')) {
        return `/assets/images/poets/${encodeURIComponent(trimmedPath)}`;
    }
    if (type === 'user' && !trimmedPath.includes('/')) {
        return `/assets/images/users/${encodeURIComponent(trimmedPath)}`;
    }

    return `/${trimmedPath.split('/').map(encodeURIComponent).join('/')}`;
};

export const getImageFallback = (type = null) => {
    // Intentionally empty — consumers should render an icon, not the Baakh logo.
    return '';
};

export const handleImageError = (event, type = null) => {
    const img = event.currentTarget;
    img.onerror = null;
    img.removeAttribute('src');
    img.style.display = 'none';
    img.dataset.broken = '1';
};
