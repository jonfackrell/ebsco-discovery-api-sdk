<?php


namespace JonFackrell\Eds;

use Illuminate\Support\Facades\Http;

class Eds
{
    private $baseUri;
    private $headers;
    private $userid;
    private $password;
    private $profile;
    private $org;
    private $authToken;
    private $authTimeout;
    private $sessionToken;

    public function __construct()
    {
        $this->userid = env('EDS_USERID');
        $this->password = env('EDS_PASSWORD');
        $this->profile = env('EDS_PROFILE');
        $this->org = env('EDS_ORG');
        $this->baseUri = 'https://eds-api.ebscohost.com/';

        $this->headers = [
            'Content-Type' => 'application/json',
        ];

        $this->getAuthToken();
        $this->getSessionToken();
    }

    public function retrieve($id)
    {
        list($database, $an) = explode('|', $id);

        $response = Http::withHeaders($this->headers)->post(
            $this->baseUri . 'edsapi/rest/Retrieve',
            [
                'DbId' => $database,
                'An' => $an,
                'EbookPreferredFormat' => 'ebook-epub',
            ]
        );

        if ($response->ok()) {
            return $response->json()['Record'];
        } elseif ($response->status() == 400) {
            session()->forget('session_token');
            $this->getSessionToken();
            return $this->retrieve($id);
        } else{
            return;
        }
    }

    private function getAuthToken()
    {
        // Need to fix this
        /*$index = Index::where('name', 'EDS')->first();
        if ($index->auth_token_expires_at > now()) {
            $authToken = $index->auth_token;
            $authTimeout = $index->auth_token_expires_at;
        } else {*/
            $response = Http::withHeaders($this->headers)->post($this->baseUri . 'authservice/rest/UIDAuth', [
                'UserId' => $this->userid,
                'Password' => $this->password,
            ]);
            $authToken = $response->json()['AuthToken'];
            $authTimeout = $response->json()['AuthTimeout'];
            /*$index->update([
                'auth_token' => $authToken,
                'auth_token_expires_at' => now()->addSeconds($authTimeout),
            ]);*/
        /*}*/

        $this->authToken = $authToken;
        $this->authTimeout = $authTimeout;
        $this->headers['x-authenticationToken'] = $authToken;
    }

    private function getSessionToken()
    {
        if (session('session_token')) {
            $sessionToken = session('session_token');
        } else {
            $response = Http::withHeaders($this->headers)->post($this->baseUri . 'edsapi/rest/createsession', [
                'Profile' => $this->profile,
                'Org' => $this->org
            ]);

            if ($response->ok()) {
                $sessionToken = $response->json()['SessionToken'];
                session(['session_token' => $sessionToken]);
            }
        }

        $this->sessionToken = $sessionToken;
        $this->headers['x-sessionToken'] = $sessionToken;
    }

    public function viewAuthToken()
    {
        return $this->authToken;
    }

    public function viewSessionToken()
    {
        return $this->sessionToken;
    }
}