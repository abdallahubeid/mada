<?php

test('the about page renders vision content', function () {
    $this->get(route('marketing.about'))
        ->assertOk()
        ->assertSee('من نحن')
        ->assertSee('حلقة بيانات مغلقة')
        ->assertSee('الموارد البشرية والتوظيف');
});

test('the faq page renders categorised questions from config', function () {
    $this->get(route('marketing.faq'))
        ->assertOk()
        ->assertSee('الأسئلة الشائعة')
        ->assertSee('ما هو نظام Veyra ERP؟')
        ->assertSee('التسعير والفوترة')
        ->assertSee('هل تدعمون التحقق بخطوتين');
});

test('the privacy policy page renders', function () {
    $this->get(route('marketing.privacy'))
        ->assertOk()
        ->assertSee('سياسة الخصوصية')
        ->assertSee('العزل والأمان');
});

test('the terms of service page renders', function () {
    $this->get(route('marketing.terms'))
        ->assertOk()
        ->assertSee('الشروط والأحكام')
        ->assertSee('الاستخدام المقبول');
});
