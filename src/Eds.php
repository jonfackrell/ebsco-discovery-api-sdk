<?php

namespace JonFackrell\Eds;

use Illuminate\Support\Facades\Http;
use JonFackrell\Eds\Models\EdsApi;

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

    public function search($options)
    {
    }

    public function retrieve($id)
    {
        [$database, $an] = explode('|', $id);

        $response = Http::withHeaders($this->headers)->post(
            $this->baseUri.'edsapi/rest/Retrieve',
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
        } else {
            return;
        }
    }

    public function info()
    {
        $response = Http::withHeaders($this->headers)->get($this->baseUri . 'edsapi/rest/Info');

        if ($response->ok()) {
            //return $response->json();
            $index = EdsApi::first();
            $index->info = $response->json();
            $index->save();
            return $index->info;
        } elseif ($response->status() == 400) {
            session()->forget('session_token');
            $this->getSessionToken();
            return $this->info();
        } else {
            return null;
        }
    }

    public function citations($database, $an, $styles = [])
    {
        if (empty($styles)) {
            $styles = config('ebsco-discovery.citation_styles');
        }

        $response = Http::withHeaders($this->headers)->get(
            $this->baseUri . 'edsapi/rest/CitationStyles',
            [
                'dbid' => $database,
                'an' => $an,
                'styles' => implode(',', $styles),
            ]
        );

        if ($response->ok()) {
            return $response->json()['Citations'];
        } elseif ($response->status() == 400) {
            session()->forget('session_token');
            $this->getSessionToken();
            return $this->citations($database, $an, $styles);
        } else {
            return null;
        }
    }

    public function export($database, $an, $format = ['ris'])
    {
        $response = Http::withHeaders($this->headers)->get(
            $this->baseUri . 'edsapi/rest/ExportFormat',
            [
                'dbid' => $database,
                'an' => $an,
                'format' => implode(',', $format),
            ]
        );

        if ($response->ok()) {
            return $response->json();
        } elseif ($response->status() == 400) {
            session()->forget('session_token');
            $this->getSessionToken();
            return $this->export($database, $an, $format);
        } else {
            return null;
        }
    }

    private function getAuthToken()
    {
        $index = EdsApi::first();
        if ($index->auth_token_expires_at > now()) {
            $authToken = $index->auth_token;
            $authTimeout = $index->auth_token_expires_at;
        } else {
            $response = Http::withHeaders($this->headers)->post($this->baseUri.'authservice/rest/UIDAuth', [
                'UserId' => $this->userid,
                'Password' => $this->password,
            ]);
            $authToken = $response->json()['AuthToken'];
            $authTimeout = $response->json()['AuthTimeout'];
            $index->update([
                'auth_token' => $authToken,
                'auth_token_expires_at' => now()->addSeconds($authTimeout),
            ]);
        }

        $this->authToken = $authToken;
        $this->authTimeout = $authTimeout;
        $this->headers['x-authenticationToken'] = $authToken;
    }

    private function getSessionToken()
    {
        if (session('session_token')) {
            $sessionToken = session('session_token');
        } else {
            $response = Http::withHeaders($this->headers)->post($this->baseUri.'edsapi/rest/createsession', [
                'Profile' => $this->profile,
                'Org' => $this->org,
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
