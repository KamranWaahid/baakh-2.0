export const formatSindhiDate = (dateStr) => {
    if (!dateStr) return '';

    const months = {
        'Jan': 'جنوري',
        'Feb': 'فيبروري',
        'Mar': 'مارچ',
        'Apr': 'اپريل',
        'May': 'مئي',
        'Jun': 'جون',
        'Jul': 'جولائي',
        'Aug': 'آگسٽ',
        'Sep': 'سيپٽمبر',
        'Oct': 'آڪٽوبر',
        'Nov': 'نومبر',
        'Dec': 'ڊسمبر'
    };

    // Expected format: "Jul 05, 2025"
    const match = dateStr.match(/^([A-Za-z]{3})\s+(\d{1,2}),\s+(\d{4})$/);
    if (!match) return dateStr;

    const [_, monthName, day, year] = match;
    const sindhiMonth = months[monthName] || monthName;

    return `${day} ${sindhiMonth}، ${year}`;
};
