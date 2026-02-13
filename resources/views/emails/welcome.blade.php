<x-mail::message>
    @if($lang === 'sd')
        <div dir="rtl" style="text-align: right; font-family: 'Lateef', 'Segoe UI', serif;">

            # باک ۾ ڀليڪار، {{ $name }}!

            اسان کي خوشي آهي ته توهان **باک** جو حصو بڻيا آهيو. باک سنڌ جي عظيم شاعريءَ، ادب ۽ ثقافت جو هڪ جديد سنگم آهي.

            توهان جو کاتو ڪاميابيءَ سان کلي چڪو آهي. هاڻي توهان سنڌ جي نامور شاعرن جي شاعري پڙهي سگهو ٿا، ان کي پسند ڪري
            سگهو ٿا ۽ پنهنجي پسنديدہ ڪلام کي محفوظ ڪري سگهو ٿا.

            **توهان جو يوزرنيم:** {{ $username }}

            <x-mail::button :url="config('app.url')">
                باک ڏانهن وڃو
            </x-mail::button>

            اسان کي اميد آهي ته توهان جو هي سفر وڻندڙ هوندو.

            مهرباني،<br>
            **باک ٽيم**
        </div>
    @else

        # Welcome to Baakh, {{ $name }}!

        We are thrilled to have you at **Baakh**—the modern home for Sindhi poetry, literature, and art.

        Your account has been successfully created. You can now explore the works of legendary and contemporary poets, like
        and save your favorite verses.

        **Your Username:** {{ $username }}

        <x-mail::button :url="config('app.url')">
            Visit Baakh
        </x-mail::button>

        We hope you enjoy this literary journey.

        Best Regards,<br>
        **The Baakh Team**
    @endif
</x-mail::message>