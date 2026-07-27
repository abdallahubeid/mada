{{-- Pricing page — plans from MarketingContent / DB. --}}
<x-layouts.marketing
    title="الأسعار — Veyra ERP"
    description="قارن خطط Startup و Growth و Enterprise في Veyra ERP. تجربة مجانية دون بطاقة ائتمان، مع خصم على الفوترة السنوية."
>
    <x-marketing.nav />

    <main>
        <x-marketing.page-hero
            eyebrow="الأسعار"
            title="خطط واضحة تناسب كل مرحلة نمو"
            subtitle="ابدأ مجانًا، ثم اختر الخطة التي تناسب حجم فريقك. يمكنك الترقية أو التخفيض في أي وقت."
        />

        <x-marketing.pricing-table
            :compact="false"
            :plans="$plans"
            :currency="$currency"
        />

        <x-marketing.faq-accordion
            :faqs="$faqs"
            title="أسئلة شائعة حول التسعير"
            subtitle="إجابات سريعة عن التجربة المجانية وتغيير الخطط والفوترة."
        />

        <x-marketing.cta-band
            title="جاهز للبدء؟"
            subtitle="فعّل تجربتك المجانية خلال دقائق — بدون بطاقة ائتمان."
        />
    </main>

    <x-marketing.footer />
</x-layouts.marketing>
