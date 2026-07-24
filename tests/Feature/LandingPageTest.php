<?php

test('the landing page renders with hero and pricing content', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Veyra')
        ->assertSee('ابدأ التجربة المجانية')
        ->assertSee('Growth')
        ->assertSee('الأكثر طلباً');
});

test('the landing page CTAs point to the expected destinations', function () {
    $this->get('/')
        ->assertSee(route('login'), false)
        ->assertSee('/register', false);
});
