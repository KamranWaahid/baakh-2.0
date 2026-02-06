import React from 'react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";

const ReportModal = ({ trigger, isRtl = false, open, onOpenChange, poemId, poetId }) => {
    const [submitted, setSubmitted] = React.useState(false);
    const [loading, setLoading] = React.useState(false);

    // API Submit Logic
    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        const reason = e.target.reason.value;
        const url = window.location.href;

        try {
            await import('../../admin/api/axios').then(module => {
                const api = module.default;
                return api.post('/api/v1/report', {
                    reason,
                    url,
                    poem_id: poemId,
                    poet_id: poetId
                });
            });
            setSubmitted(true);
        } catch (error) {
            console.error('Report failed:', error);
            // Optionally show error state
        } finally {
            setLoading(false);
        }
    };

    const handleClose = () => {
        setSubmitted(false);
        if (onOpenChange) onOpenChange(false);
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            {trigger && (
                <DialogTrigger asChild>
                    {trigger}
                </DialogTrigger>
            )}
            <DialogContent className="sm:max-w-md bg-white p-8 md:p-12 shadow-xl border-0 font-sans">
                <DialogHeader className="mb-6">
                    <DialogTitle className={`text-center font-serif text-3xl font-medium tracking-tight ${isRtl ? 'font-arabic' : ''}`}>
                        {submitted ? (isRtl ? 'رپورٽ ملي وئي!' : 'Report Received!') : (isRtl ? 'رپورٽ ڪريو' : 'Report Issue')}
                    </DialogTitle>
                </DialogHeader>

                {submitted ? (
                    <div className="text-center py-8">
                        <p className="text-lg text-gray-600 mb-6">
                            {isRtl ? 'اسان کي آگاهه ڪرڻ جي مھرباني. اسان جلد ان جو جائزو وٺنداسين.' : 'Thanks for letting us know. We will review this content shortly.'}
                        </p>
                        <Button onClick={handleClose} variant="outline" className="rounded-full px-8">
                            {isRtl ? 'بند ڪريو' : 'Close'}
                        </Button>
                    </div>
                ) : (
                    <form onSubmit={handleSubmit} className="flex flex-col gap-6">
                        <p className="text-sm text-gray-500 text-center">
                            {isRtl
                                ? 'مهرباني ڪري ٻڌايو ته هن مواد ۾ ڇا مسئلو آهي؟'
                                : 'Please describe the issue with this content.'}
                        </p>

                        <Textarea
                            name="reason"
                            placeholder={isRtl ? 'هتي تفصيل لکو...' : 'Tell us what is wrong...'}
                            className={`min-h-[120px] resize-none bg-gray-50 border-gray-200 focus:border-black focus:ring-black rounded-xl p-4 text-base ${isRtl ? 'text-right font-arabic' : ''}`}
                            required
                        />

                        <Button type="submit" disabled={loading} className="rounded-full h-12 bg-red-600 hover:bg-red-700 text-white font-medium text-base mt-2">
                            {loading ? (isRtl ? '...موڪلي رهيو آهي' : 'Sending...') : (isRtl ? 'رپورٽ موڪليو' : 'Submit Report')}
                        </Button>

                        <p className="text-center text-xs text-gray-400 mt-2">
                            {isRtl ? 'توهان جي سڃاڻپ محفوظ رهندي.' : 'Your report is anonymous and safe.'}
                        </p>
                    </form>
                )}
            </DialogContent>
        </Dialog>
    );
};

export default ReportModal;
