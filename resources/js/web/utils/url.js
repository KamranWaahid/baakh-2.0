/**
 * Resolves an image path into a full URL.
 * Handles absolute URLs, assets paths, and storage paths with proper fallbacks.
 * 
 * @param {string|null|undefined} path - The image path or URL
 * @param {'poet'|'user'|'post'|null} type - Type of image for fallback
 * @returns {string} - The resolved URL
 */
export const getImageUrl = (path, type = null) => {
    if (!path) {
        return '/assets/images/logo/logo.svg';
    }

    // If it's already a full URL, return it
    if (path.startsWith('http')) {
        return path;
    }

    const trimmedPath = path.replace(/^\/+/, '');

    // Keep known rooted asset/storage paths as-is
    if (
        trimmedPath.startsWith('assets/') ||
        trimmedPath.startsWith('storage/') ||
        trimmedPath.startsWith('images/')
    ) {
        return `/${trimmedPath}`;
    }

    // Legacy poet/user values are often bare filenames in DB.
    if (type === 'poet' && !trimmedPath.includes('/')) {
        return `/assets/images/poets/${trimmedPath}`;
    }
    if (type === 'user' && !trimmedPath.includes('/')) {
        return `/assets/images/users/${trimmedPath}`;
    }

    return `/${trimmedPath}`;
};

export const getImageFallback = (type = null) => {
    if (type === 'poet' || type === 'user') {
        return '/assets/images/logo/logo.svg';
    }

    return '/assets/images/logo/logo.svg';
};

export const handleImageError = (event, type = null) => {
    const fallback = getImageFallback(type);

    // Prevent infinite loops when fallback itself fails.
    event.currentTarget.onerror = null;
    event.currentTarget.src = fallback;
};
