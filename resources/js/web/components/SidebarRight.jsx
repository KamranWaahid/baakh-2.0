import React from 'react';

const SidebarRight = ({ lang }) => {
    const isRtl = lang === 'sd';

    const staffPicks = [
        { title: isRtl ? 'مون ڪيئن پريشان ٿيڻ ڇڏي ڏنو ۽ ٽرمينل سان پيار ڪرڻ سکيو' : 'How I stopped worrying and learned to love the terminal', author: 'Pablo Stanley', date: 'Jan 23' },
        { title: isRtl ? 'مان 2026 ۾ پاڻ کي ٻيهر ايجاد نه ڪري رهيو آهيان' : 'I\'m Not Reinventing Myself in 2026', author: 'Lou Chalmer', date: '3d ago' },
    ];

    const topics = [
        'Programming', 'Writing', 'Sindhi Poetry', 'History', 'Culture', 'Literature', 'Sufism'
    ];

    return (
        <aside className="w-[368px] hidden xl:block shrink-0 border-s border-gray-100 p-8 sticky top-[57px] max-h-[calc(100vh-57px)] overflow-y-auto">
            <section className="mb-10">
                <h3 className="font-bold text-black mb-4">{isRtl ? 'اسٽاف جا چونڊيل' : 'Staff Picks'}</h3>
                <div className="space-y-6">
                    {staffPicks.map((pick, i) => (
                        <div key={i} className="group cursor-pointer">
                            <div className="flex items-center gap-2 mb-1">
                                <div className="h-5 w-5 rounded-full bg-blue-100" />
                                <span className="text-xs font-medium">{pick.author}</span>
                            </div>
                            <h4 className="text-[14px] font-bold leading-snug group-hover:underline">{pick.title}</h4>
                            <span className="text-xs text-gray-500 mt-1 block">{pick.date}</span>
                        </div>
                    ))}
                </div>
                <button className="text-sm text-green-700 hover:text-black mt-6 transition-colors font-medium">
                    {isRtl ? 'مڪمل لسٽ ڏسو' : 'See the full list'}
                </button>
            </section>

            <section className="mb-10">
                <h3 className="font-bold text-black mb-4">{isRtl ? 'تجويز ڪيل موضوع' : 'Recommended topics'}</h3>
                <div className="flex flex-wrap gap-2">
                    {topics.map(topic => (
                        <button key={topic} className="px-4 py-2 bg-gray-100 rounded-full text-sm text-gray-700 hover:bg-gray-200 transition-colors">
                            {topic}
                        </button>
                    ))}
                </div>
                <button className="text-sm text-green-700 hover:text-black mt-6 transition-colors font-medium">
                    {isRtl ? 'وڌيڪ موضوع ڏسو' : 'See more topics'}
                </button>
            </section>

            <section className="sticky bottom-0 bg-white pt-4">
                <div className="flex flex-wrap gap-x-4 gap-y-2 text-xs text-gray-500">
                    <a href="#" className="hover:text-black transition-colors">Help</a>
                    <a href="#" className="hover:text-black transition-colors">Status</a>
                    <a href="#" className="hover:text-black transition-colors">About</a>
                    <a href="#" className="hover:text-black transition-colors">Careers</a>
                    <a href="#" className="hover:text-black transition-colors">Privacy</a>
                    <a href="#" className="hover:text-black transition-colors">Terms</a>
                </div>
            </section>
        </aside>
    );
};

export default SidebarRight;
