export const formatSindhiDate = (dateStr) => {
    if (!dateStr) return '';

    const months = {
        'January': 'جنوري',
        'February': 'فيبروري',
        'March': 'مارچ',
        'April': 'اپريل',
        'May': 'مئي',
        'June': 'جون',
        'July': 'جولائي',
        'August': 'آگسٽ',
        'September': 'سيپٽمبر',
        'October': 'آڪٽوبر',
        'November': 'نومبر',
        'December': 'ڊسمبر',
        'Jan': 'جنوري',
        'Feb': 'فيبروري',
        'Mar': 'مارچ',
        'Apr': 'اپريل',
        'Jun': 'جون',
        'Jul': 'جولائي',
        'Aug': 'آگسٽ',
        'Sep': 'سيپٽمبر',
        'Oct': 'آڪٽوبر',
        'Nov': 'نومبر',
        'Dec': 'ڊسمبر'
    };

    let formattedDate = dateStr;
    
    // Replace all instances of English month names with Sindhi
    for (const [en, sd] of Object.entries(months)) {
        // Use regex with word boundaries to match exact month strings case-insensitively
        const regex = new RegExp(`\\b${en}\\b`, 'gi');
        formattedDate = formattedDate.replace(regex, sd);
    }

    // Optional: if the date format is exactly "05 جنوري, 2025", we could replace the comma with Sindhi comma '،'
    formattedDate = formattedDate.replace(',', '،');

    return formattedDate;
};
