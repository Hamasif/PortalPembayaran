<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_dapat_diakses_publik(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Portal Pembayaran Mahasiswa');
        $response->assertSee('Login dengan Google');
    }
}
