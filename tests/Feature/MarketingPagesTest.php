<?php

/**
 * Smoke coverage for the public marketing pages (docs/MARKETING.md §5.5).
 */
test('the features page renders successfully', function () {
    $this->get(route('marketing.features'))
        ->assertOk()
        ->assertSee('المميزات')
        ->assertSee('أمان متعدد المستأجرين')
        ->assertSee('ابدأ التجربة المجانية');
});

test('the solutions page renders industry sections with anchors', function () {
    $this->get(route('marketing.solutions'))
        ->assertOk()
        ->assertSee('الحلول')
        ->assertSee('الجهات الحكومية')
        ->assertSee('id="government"', false)
        ->assertSee('المنظمات غير الربحية');
});

test('the pricing page renders plan tiers from the shared catalog', function () {
    $this->get(route('marketing.pricing'))
        ->assertOk()
        ->assertSee('الأسعار')
        ->assertSee('الأساسية', false)
        ->assertSee('النمو', false)
        ->assertSee('Enterprise')
        ->assertSee('الأكثر طلباً');
});

test('the security page renders compliance pillars', function () {
    $this->get(route('marketing.security'))
        ->assertOk()
        ->assertSee('الأمان والامتثال')
        ->assertSee('عزل بيانات متعدد المستأجرين')
        ->assertSee('تحقق بخطوتين')
        ->assertSee('بانتظار التحقق');
});
