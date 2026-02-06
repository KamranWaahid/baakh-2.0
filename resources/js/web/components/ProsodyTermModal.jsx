import React from 'react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from "@/components/ui/dialog";
import { Info, BookOpen } from 'lucide-react';
import { Separator } from '@/components/ui/separator';

const ProsodyTermModal = ({ isOpen, onClose, item, lang }) => {
    const isRtl = lang === 'sd';

    if (!item) return null;

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent
                dir={isRtl ? 'rtl' : 'ltr'}
                className="max-w-2xl w-[95vw] md:w-full max-h-[90vh] flex flex-col p-0 overflow-hidden gap-0"
            >
                <DialogHeader className={`p-8 pb-6 border-b bg-gray-50/30 ${isRtl ? 'text-right' : 'text-left'}`}>
                    <div className={`flex items-center gap-4 mb-2 ${isRtl ? 'flex-row' : 'flex-row'}`}>
                        <div className="h-10 w-10 rounded-full bg-white shadow-sm flex items-center justify-center border border-gray-100 shrink-0">
                            <Info className="h-5 w-5 text-gray-400" />
                        </div>
                        <div className={`flex flex-col ${isRtl ? 'items-start' : 'items-start'}`}>
                            <DialogTitle className={`text-2xl font-bold text-gray-900 ${isRtl ? 'font-arabic' : ''}`}>
                                {item.title}
                            </DialogTitle>
                            <span className="text-xs font-medium text-gray-400 uppercase tracking-widest block">
                                {item.subtitle}
                            </span>
                        </div>
                    </div>
                </DialogHeader>

                <div className="flex-1 overflow-y-auto no-scrollbar p-8">
                    <div className={`space-y-8 ${isRtl ? 'text-right' : 'text-left'}`}>
                        {/* Description Section */}
                        <section>
                            <h4 className={`text-sm font-bold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2`}>
                                <BookOpen className="h-4 w-4" />
                                {isRtl ? 'تعارف' : 'Introduction'}
                            </h4>
                            <p className={`text-lg leading-relaxed text-gray-700 ${isRtl ? 'font-arabic' : ''}`}>
                                {item.description}
                            </p>
                        </section>

                        {item.technical_detail && (
                            <>
                                <Separator className="bg-gray-100" />

                                {/* Technical Details Section */}
                                <section className="bg-gray-50 p-6 rounded-2xl border border-gray-100">
                                    <h4 className={`text-sm font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-2`}>
                                        <div className="h-2 w-2 rounded-full bg-black"></div>
                                        {isRtl ? 'فني تفصيل' : 'Technical Information'}
                                    </h4>
                                    <p className={`text-gray-600 leading-relaxed ${isRtl ? 'font-arabic' : ''}`}>
                                        {item.technical_detail}
                                    </p>
                                </section>
                            </>
                        )}
                    </div>
                </div>

                <div className={`p-6 border-t bg-white flex ${isRtl ? 'justify-start' : 'justify-end'}`}>
                    <button
                        onClick={onClose}
                        className="px-6 py-2 bg-black text-white rounded-full text-sm font-bold hover:bg-gray-800 transition-colors"
                    >
                        {isRtl ? 'بند ڪريو' : 'Close'}
                    </button>
                </div>
            </DialogContent>
        </Dialog>
    );
};

export default ProsodyTermModal;
