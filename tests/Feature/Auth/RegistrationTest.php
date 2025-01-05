<?php

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'nama_user' => 'Test User',
        'NIP' => '123456789',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated(); // Pastikan pengguna terautentikasi
    $response->assertRedirect(route('dashboard')); // Pastikan diarahkan ke dashboard
});
