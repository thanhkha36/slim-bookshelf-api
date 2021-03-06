<?php
namespace App\Action;

use Monolog\Logger;

class LoginAction
{
    protected $logger;
    protected $renderer;
    protected $session;
    protected $guzzle;
    protected $settings;
    
    public function __construct(Logger $logger, $renderer, $session, $guzzle, $settings)
    {
        $this->logger = $logger;
        $this->renderer = $renderer;
        $this->session = $session;
        $this->guzzle = $guzzle;
        $this->settings = $settings;
    }

    public function __invoke($request, $response)
    {
        try {
            $data = [
                'grant_type' => 'password',
                'client_id' => $this->settings['client_id'],
                'client_secret' => $this->settings['client_secret'],
                'username' => $request->getParam('username'),
                'password' => $request->getParam('password'),
            ];

            $res = $this->guzzle->post('/token', ['json' => $data]);
        } catch (\GuzzleHttp\Exception\TransferException $e) {
            $this->flash->addMessage('error', "Failed to log in: " . $e->getMessage());
            return $response->withRedirect('/login');
        }


        $data = json_decode($res->getBody(), true);
        $this->session->username = $request->getParam('username');
        $this->session->access_token = $data['access_token'];
        $this->session->expires = strtotime('+' . $data['expires'] . ' seconds');
        $this->session->refresh_token = $data['refresh_token'];

        if ($r = $request->getQueryParam('r')) {
            return $response->withRedirect('/authorise?' . $r);
        }
        return $response->withRedirect('/');
    }
}
