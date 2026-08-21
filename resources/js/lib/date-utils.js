const SINDHI_MONTHS = [
    'جنوري', 'فيبروري', 'مارچ', 'اپريل', 'مئي', 'جون',
    'جولائي', 'آگسٽ', 'سيپٽمبر', 'آڪٽوبر', 'نومبر', 'ڊسمبر'
];

export const formatDate = (dateString, lang = 'en') => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString; // Return original if parse failed

    if (lang === 'sd') {
        const day = date.getDate();
        const month = SINDHI_MONTHS[date.getMonth()];
        const year = date.getFullYear();
        // Return in Sindhi format: Day Month Year (e.g., 01 جنوري 1493)
        return `${day.toString().padStart(2, '0')} ${month} ${year}`;
    }

    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

/**
 * Localize stored period date_range strings (English AD copy) for sd vs en.
 * Sindhi literary dates use ع after the year (عيسوي), matching archive body copy.
 */
export const formatPeriodDateRange = (dateRange, lang = 'en') => {
    const raw = String(dateRange || '').trim();
    if (!raw || lang !== 'sd') {
        return raw;
    }

    let s = raw;
    s = s.replace(/Pre-(\d+)\s*(?:A\.?\s*D\.?)/gi, '$1ع کان اڳ');
    s = s.replace(/\bc\.\s*/gi, 'لڳ ڀڳ ');
    s = s.replace(/\bcirca\s*/gi, 'لڳ ڀڳ ');
    s = s.replace(/\bPresent\b/gi, 'اڄ تائين');
    s = s.replace(/\bto\b/gi, '–');
    s = s.replace(/\bA\.?\s*D\.?\b/gi, 'ع');
    s = s.replace(/(\d)\s+ع/g, '$1ع');
    s = s.replace(/\s+–\s+/g, ' – ');
    s = s.replace(/\s+/g, ' ').trim();

    return s;
};

