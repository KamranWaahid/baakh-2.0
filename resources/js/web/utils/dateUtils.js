export const formatSindhiDate = (dateStr) => {
    if (!dateStr) return '';

    const months = {
        'january': 'جنوري', 'february': 'فيبروري', 'march': 'مارچ',
        'april': 'اپريل', 'may': 'مئي', 'june': 'جون',
        'july': 'جولائي', 'august': 'آگسٽ', 'september': 'سيپٽمبر',
        'october': 'آڪٽوبر', 'november': 'نومبر', 'december': 'ڊسمبر',
        'jan': 'جنوري', 'feb': 'فيبروري', 'mar': 'مارچ',
        'apr': 'اپريل', 'jun': 'جون', 'jul': 'جولائي',
        'aug': 'آگسٽ', 'sep': 'سيپٽمبر', 'oct': 'آڪٽوبر',
        'nov': 'نومبر', 'dec': 'ڊسمبر'
    };

    // Extract alphanumeric parts
    const parts = dateStr.match(/([a-zA-Z]+|\d+)/g);
    
    if (parts && parts.length >= 3) {
        // Find components
        const year = parts.find(p => p.length === 4 && !isNaN(p));
        const monthStr = parts.find(p => isNaN(p));
        const day = parts.find(p => p !== year && !isNaN(p));

        if (day && monthStr && year) {
            const lowerMonth = monthStr.toLowerCase();
            const sindhiMonth = months[lowerMonth] || monthStr;
            
            // Format: Day Month Year (e.g., 05 جولائي، 2025)
            return `${day} ${sindhiMonth}، ${year}`;
        }
    }

    // Fallback: simple replacement
    let formattedDate = dateStr;
    for (const [en, sd] of Object.entries(months)) {
        const regex = new RegExp(`\\b${en}\\b`, 'gi');
        formattedDate = formattedDate.replace(regex, sd);
    }
    return formattedDate.replace(',', '،');
};
