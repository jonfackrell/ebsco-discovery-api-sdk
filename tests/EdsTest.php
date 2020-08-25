<?php

namespace JonFackrell\Eds\Tests;

use Illuminate\Support\Facades\Http;
use JonFackrell\Eds\Eds;
use Orchestra\Testbench\TestCase;

class EdsTest extends TestCase
{
    public function it_can_request_auth_token()
    {
        Http::fake();

        Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post('https://eds-api.ebscohost.com/'.'authservice/rest/UIDAuth', [
            'UserId' => env('EDS_USERID'),
            'Password' => env('EDS_PASSWORD'),
        ]);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Content-Type', 'application/json') &&
                   $request->url() == 'https://eds-api.ebscohost.com/'.'authservice/rest/UIDAuth' &&
                   $request['UserId'] == env('EDS_USERID') &&
                   $request['Password'] == env('EDS_PASSWORD');
        });
    }

    public function it_can_request_session_token()
    {
        Http::fake();

        Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-authenticationToken' => 'AuthToken',
        ])->post('https://eds-api.ebscohost.com/'.'edsapi/rest/createsession', [
            'Profile' => env('EDS_PROFILE'),
            'Org' => env('EDS_ORG'),
        ]);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Content-Type', 'application/json') &&
                   $request->hasHeader('x-authenticationToken', 'AuthToken') &&
                   $request->url() == 'https://eds-api.ebscohost.com/'.'edsapi/rest/createsession' &&
                   $request['Profile'] == env('EDS_PROFILE') &&
                   $request['Org'] == env('EDS_ORG');
        });
    }

    /** @test */
    public function it_can_create_auth_and_session_tokens()
    {
        Http::fakeSequence()
                    ->push('{"AuthToken": "TestToken", "AuthTimeout": 1800}', 200)
                    ->push('{"SessionToken": "TestToken"}', 200);

        $eds = new Eds();

        $this->assertNotEmpty($eds->viewAuthToken());
        $this->assertNotEmpty($eds->viewSessionToken());
    }

    /** @test */
    public function it_can_get_existing_session_token()
    {
        Http::fakeSequence()
            ->push('{"AuthToken": "TestToken", "AuthTimeout": 1800}', 200);

        session(['session_token' => 'TestToken']);

        $eds = new Eds();

        $this->assertNotEmpty($eds->viewSessionToken());
    }

    /** @test */
    public function it_can_retrieve_item()
    {
        Http::fakeSequence()
                    ->push('{"AuthToken": "TestToken", "AuthTimeout": 1800}', 200)
                    ->push('{"SessionToken": "TestToken"}', 200)
                    ->push('{"Record": {"": ""}}', 200);

        $eds = new Eds();

        $this->assertNotEmpty($eds->retrieve('TestDatabase|TestAn'));
    }

    /** @test */
    public function it_returns_null_when_cannot_retrieve_item()
    {
        Http::fakeSequence()
                    ->push('{"AuthToken": "TestToken", "AuthTimeout": 1800}', 200)
                    ->push('{"SessionToken": "TestToken"}', 200)
                    ->push('{"Record": {"": ""}}', 500);

        $eds = new Eds();

        $this->assertEmpty($eds->retrieve('TestDatabase|TestAn'));
    }

    /** @test */
    public function it_can_retrieve_item_when_session_expired()
    {
        Http::fakeSequence()
                    ->push('{"AuthToken": "TestToken", "AuthTimeout": 1800}', 200)
                    ->push('{"SessionToken": "TestToken"}', 200)
                    ->push('{"Record": {"": ""}}', 400)
                    ->push('{"SessionToken": "TestToken"}', 200)
                    ->push('{"Record": {"": ""}}', 200);

        $eds = new Eds();

        $this->assertNotEmpty($eds->retrieve('TestDatabase|TestAn'));
    }
}
