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
import { Star } from 'lucide-react';

const FeedbackModal = ({ trigger, isRtl = false }) => {
    const [rating, setRating] = React.useState(0);
    const [submitted, setSubmitted] = React.useState(false);

    const [loading, setLoading] = React.useState(false);

    // API Submit Logic
    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        const message = e.target.message.value;

        try {
            await import('../../admin/api/axios').then(module => {
                const api = module.default;
                return api.post('/api/v1/feedback', {
                    message,
                    rating
                });
            });
            setSubmitted(true);
        } catch (error) {
            console.error('Feedback failed:', error);
            // Optionally show error state
        } finally {
            setLoading(false);
        }
    };

    return (
        <Dialog>
            <DialogTrigger asChild>
                {trigger}
            </DialogTrigger>
            <DialogContent className="sm:max-w-md bg-white p-8 md:p-12 shadow-xl border-0 font-sans">
                <DialogHeader className="mb-6">
                    <DialogTitle className={`text-center font-serif text-3xl font-medium tracking-tight ${isRtl ? 'font-arabic' : ''}`}>
                        {submitted ? (isRtl ? 'مهرباني!' : 'Thank you!') : (isRtl ? 'توهان جي راءِ' : 'Your Feedback')}
                    </DialogTitle>
                </DialogHeader>

                {submitted ? (
                    <div className="text-center py-8">
                        <p className="text-lg text-gray-600 mb-6">
                            {isRtl ? 'توهان جي راءِ اسان لاءِ اهم آهي. اسان ان تي عمل ڪنداسين.' : 'Your feedback helps us improve Baakh. We appreciate your input.'}
                        </p>
                        <Button onClick={() => setSubmitted(false)} variant="outline" className="rounded-full px-8">
                            {isRtl ? 'بند ڪريو' : 'Close'}
                        </Button>
                    </div>
                ) : (
                    <form onSubmit={handleSubmit} className="flex flex-col gap-6">
                        <div className="flex justify-center gap-2 mb-2">
                            {[1, 2, 3, 4, 5].map((star) => (
                                <button
                                    key={star}
                                    type="button"
                                    onClick={() => setRating(star)}
                                    className="focus:outline-none transition-transform hover:scale-110"
                                >
                                    <Star
                                        className={`h-8 w-8 ${star <= rating ? 'fill-yellow-500 text-yellow-500' : 'text-gray-300'}`}
                                        strokeWidth={1.5}
                                    />
                                </button>
                            ))}
                        </div>

                        <Textarea
                            name="message"
                            placeholder={isRtl ? 'پنهنجي راءِ هتي لکو...' : 'Tell us about your experience...'}
                            className={`min-h-[120px] resize-none bg-gray-50 border-gray-200 focus:border-black focus:ring-black rounded-xl p-4 text-base ${isRtl ? 'text-right font-arabic' : ''}`}
                            required
                        />

                        <Button type="submit" className="rounded-full h-12 bg-black hover:bg-gray-800 text-white font-medium text-base mt-2">
                            {isRtl ? 'موڪليو' : 'Submit Feedback'}
                        </Button>

                        <p className="text-center text-xs text-gray-400 mt-2">
                            {isRtl ? 'توهان جي ڊيٽا محفوظ آهي.' : 'Your feedback differs from support requests.'}
                        </p>
                    </form>
                )}
            </DialogContent>
        </Dialog>
    );
};

export default FeedbackModal;
