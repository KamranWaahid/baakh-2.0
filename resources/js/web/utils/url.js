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

    // Ensure path starts with a slash
    const cleanPath = path.startsWith('/') ? path : `/${path}`;

    // Special handling for assets and storage
    // If it already says it's in assets/ or storage/, just return it
    if (cleanPath.startsWith('/assets/') || cleanPath.startsWith('/storage/')) {
        return cleanPath;
    }

    // Default to assets if it doesn't look like a storage path
    // But many paths in the DB might just be "images/poets/..."
    // Based on DB check, they are already like "assets/images/poets/..."
    return cleanPath;
};
