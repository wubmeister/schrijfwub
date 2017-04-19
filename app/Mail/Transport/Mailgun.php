<?php

namespace App\Mail\Transport;

use App\Mail\Mail;

class Mailgun implements TransportInterface
{
    /**
     * The API key
     * @var string
     */
    protected $apiKey;

    /**
     * The domain
     * @var string
     */
    protected $domain;

    /**
     * Sets the API key
     *
     * @param string $key The API key
     * @return static $this For chaining
     */
    public function setApiKey($key)
    {
        $this->apiKey = $key;
        return $this;
    }

    /**
     * Sets the somain
     *
     * @param string $domain The domain
     * @return static $this For chaining
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * Constructs an instance with a domain and API key
     *
     * @param string $domain The domain
     * @param string $apiKey The API key
     */
    public function __construct($domain = null, $apiKey = null)
    {
        $this->domain = $domain;
        $this->apiKey = $apiKey;
    }

    /**
     * This method should send the passed mail to all it's defined addresses
     *
     * @param App\Mail\Mail $mail The mail object
     */
    public function send(Mail $mail)
    {
        if (!$this->apiKey) {
            throw new \Exception("Mailgun: no API key was set");
        }
        if (!$this->domain) {
            throw new \Exception("Mailgun: no domain was set");
        }

        $ch = curl_init('https://api.mailgun.net/v3/' . $this->domain . '/messages');
        $postData = [
            'from' => $mail->getFrom(),
            'to' => implode(', ', $mail->getTo()),
            'subject' => $mail->getSubject(),
            'text' => $mail->getBody()
        ];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_USERPWD => 'api:' . $this->apiKey
        ]);

        $json = curl_exec($ch);
        $result = json_decode($json, true);

        if (!isset($result['id'])) {
            throw new \Exception("Mailgun failed: {$result['message']}");
        }
    }
}
