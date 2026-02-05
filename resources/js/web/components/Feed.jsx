import React from 'react';
import PostCard from './PostCard';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';

const Feed = ({ lang }) => {
    const isRtl = lang === 'sd';

    const posts = [
        {
            title: isRtl ? 'جنوري ۾ مون پڙهيل 3 شاندار ڪتاب' : '3 Brilliant Books I Read In January',
            excerpt: isRtl ? 'جنوري ۾ پڙهڻ لاءِ بهترين ڪتابن جي فهرست...' : 'I discovered something so valuable for all of us that it deserved a description longer than an article.',
            author: 'Tom Addison',
            date: '5d ago',
            readTime: '8 min read',
            category: 'Reading List'
        },
        {
            title: isRtl ? 'هڪ ننڍڙي عادت جيڪي منهنجي دماغ کي نئون ڪيو' : 'The Tiny Habit that Rewired My Brain',
            excerpt: isRtl ? 'ڪيئن هڪ ننڍي تبديلي وڏي نتيجي جو سبب بڻجي سگهي ٿي...' : 'This is how a small daily ritual can transform your productivity and clarity in ways you never expected.',
            author: 'Learning Strategist',
            date: '3d ago',
            readTime: '12 min read',
            category: 'Psychology'
        },
        {
            title: isRtl ? 'شاھ لطيف جي شاعري ۾ فطرت جا نظارا' : 'Nature in the Poetry of Shah Latif',
            excerpt: isRtl ? 'لطيف سائين پنهنجي شاعري ۾ ڪيئن فطرت کي بيان ڪيو آهي...' : 'An exploration of naturalistic elements and symbolism in the immortal verses of Shah Abdul Latif Bhittai.',
            author: 'Baakh Editorial',
            date: 'Feb 1',
            readTime: '15 min read',
            category: 'Sindhi Literature'
        }
    ];

    return (
        <div className="flex-1 max-w-[720px] w-full mx-auto px-4 md:px-8 py-6">
            <div className="sticky top-[65px] bg-white/95 backdrop-blur-sm pt-2 pb-0 z-40 flex items-center gap-6 border-b border-gray-100 mb-8">
                <Button
                    variant="link"
                    className="p-0 h-auto pb-3 rounded-none border-b-2 border-black text-black font-semibold text-sm hover:no-underline"
                >
                    {isRtl ? 'توهان لاءِ' : 'For you'}
                </Button>
                <Button
                    variant="link"
                    className="p-0 h-auto pb-3 rounded-none border-b-2 border-transparent text-gray-500 hover:text-black font-medium text-sm hover:no-underline transition-colors"
                >
                    {isRtl ? 'چونڊيل' : 'Featured'}
                </Button>
            </div>

            <div className="space-y-8">
                {posts.map((post, i) => (
                    <React.Fragment key={i}>
                        <PostCard lang={lang} {...post} />
                        {i < posts.length - 1 && <Separator className="bg-gray-100" />}
                    </React.Fragment>
                ))}
            </div>
        </div>
    );
};

export default Feed;
