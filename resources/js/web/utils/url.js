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
        if (type === 'user') return '/assets/images/users/default-avatar.png';
        if (type === 'poet') return '/assets/images/poets/default-poet.png';
        return '/assets/images/site/placeholder.png'; // General fallback
    }

    // If it's already a full URL, return it
    if (path.startsWith('http')) {
        return path;
    }

    // Strip any leading slashes and then add exactly one
    const cleanPath = '/' + path.replace(/^\/+/, '');

    return cleanPath;
};
