const SINDHI_MONTHS = [
    'جنوري', 'فيبروري', 'مارچ', 'اپريل', 'مئي', 'جون',
    'جولائي', 'آگسٽ', 'سيپٽمبر', 'آڪٽوبر', 'نومبر', 'ڊسمبر'
];

export const formatDate = (dateString, lang = 'en') => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);

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
