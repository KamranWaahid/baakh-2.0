import React from 'react';
import Logo from '../../../web/components/Logo';

const NotificationPhonePreview = ({
    titleSd = '',
    titleEn = '',
    bodySd = '',
    bodyEn = '',
    ctaSd = '',
    ctaEn = '',
    lang = 'sd',
}) => {
    const isSd = lang === 'sd';
    const title = (isSd ? titleSd : titleEn) || titleSd || titleEn || (isSd ? 'عنوان' : 'Title');
    const body = (isSd ? bodySd : bodyEn) || bodySd || bodyEn || (isSd ? 'اطلاع جو متن هتي نظر ايندو.' : 'Notification copy will appear here.');
    const cta = (isSd ? ctaSd : ctaEn) || ctaSd || ctaEn || (isSd ? 'کوليو' : 'Open');

    return (
        <div className="mx-auto w-full max-w-[320px]">
            <div className="rounded-[2rem] border bg-foreground p-2 shadow-sm">
                <div className="overflow-hidden rounded-[1.6rem] bg-[#f6f4ef]">
                    <div className="flex items-center justify-between px-4 pt-3 text-[10px] text-muted-foreground">
                        <span>9:41</span>
                        <span className="h-3 w-16 rounded-full bg-foreground/80" />
                        <span>100%</span>
                    </div>
                    <div className="flex items-center gap-2 px-4 py-3">
                        <Logo className="h-6 w-6 text-foreground" />
                        <div>
                            <div className="text-sm font-semibold leading-none">Baakh</div>
                            <div className="text-[10px] text-muted-foreground">now</div>
                        </div>
                    </div>
                    <div className="px-4 pb-4">
                        <div className="rounded-xl border bg-background p-3 shadow-sm">
                            <div
                                className={`text-sm font-semibold leading-snug ${isSd ? 'font-arabic text-right' : ''}`}
                                dir={isSd ? 'rtl' : 'ltr'}
                                lang={isSd ? 'sd' : 'en'}
                            >
                                {title}
                            </div>
                            <p
                                className={`mt-2 text-xs leading-relaxed text-muted-foreground ${isSd ? 'font-arabic text-right' : ''}`}
                                dir={isSd ? 'rtl' : 'ltr'}
                                lang={isSd ? 'sd' : 'en'}
                            >
                                {body}
                            </p>
                            <div className="mt-3 flex justify-end">
                                <span
                                    className={`rounded-md bg-primary px-2.5 py-1 text-[11px] font-medium text-primary-foreground ${isSd ? 'font-arabic' : ''}`}
                                    dir={isSd ? 'rtl' : 'ltr'}
                                >
                                    {cta}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default NotificationPhonePreview;
