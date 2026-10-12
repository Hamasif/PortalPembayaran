<?php

namespace Tests\Concerns;

use Illuminate\Testing\TestResponse;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

/**
 * Mock pengguna Google untuk test — tanpa kredensial Google sungguhan.
 */
trait FakesGoogleUser
{
    /**
     * @param  array{name?: string, email?: string, verified?: bool, id?: string}  $overrides
     */
    private function fakeGoogleUser(array $overrides = []): SocialiteUser
    {
        $data = array_merge([
            'name' => 'Mahasiswa Uji Coba',
            'email' => 'mahasiswa001@student.universitas.ac.id',
            'verified' => true,
            'id' => 'google-'.uniqid(),
            'avatar' => 'https://lh3.googleusercontent.com/a/ACg8oc-fake-profile=s96-c',
        ], $overrides);

        $googleUser = new SocialiteUser;
        $googleUser->id = $data['id'];
        $googleUser->name = $data['name'];
        $googleUser->email = $data['email'];
        $googleUser->avatar = $data['avatar'];
        $googleUser->user = [
            'sub' => $data['id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'email_verified' => $data['verified'],
        ];

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturnSelf();
        Socialite::shouldReceive('user')
            ->andReturn($googleUser);

        return $googleUser;
    }

    private function callbackGoogleLogin(array $overrides = []): TestResponse
    {
        $this->fakeGoogleUser($overrides);

        return $this->get(route('login.google.callback'));
    }
}
