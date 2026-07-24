<?php

test('a missing route renders the custom 404 page', function () {
    $this->get('/this-route-does-not-exist')
        ->assertNotFound()
        ->assertSee('الصفحة غير موجودة')
        ->assertSee('العودة للرئيسية')
        ->assertSee('العودة للخلف');
});

test('the 404 page links back to the landing page', function () {
    $this->get('/this-route-does-not-exist')
        ->assertSee(route('landing'), false);
});
