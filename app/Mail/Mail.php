<?php

namespace App\Mail;

/**
 * Class to represent a mail with all of its headers
 */
class Mail
{
    /**
     * The headers
     * @var array
     */
    protected $headers = [];

    /**
     * The return path
     * @var string
     */
    protected $returnPath = '';

    /**
     * The body
     * @var string
     */
    protected $body;

    /**
     * Extracts at least an e-mail address and optionally a name from a string
     *
     * @param string $str The string to extract from
     * @param string $name The name to override the name in the string
     * @return array An array with a key 'email' and optionally a key 'name'
     */
    protected function addrInfo($str, $name = null)
    {
        $result = [ 'email' => $str ];

        if (preg_match('/^([^<]+)<([^>]+)>$/', $str, $match)) {
            $result['email'] = $match[2];
            $result['name'] = $match[1];
        }

        if ($name) {
            $result['name'] = $name;
        }

        return $result;
    }

    /**
     * Formats an address in the form 'User Name <user@domain.com>'
     *
     * @param string $email The e-mail address
     * @param string $name The name
     * @return The formatted address
     */
    protected function formatAddr($email, $name = null)
    {
        return $name ? "{$name} <{$email}>" : $email;
    }

    /**
     * Sets the 'from' address
     *
     * @param string $address The from address, e.g. 'user@domain.com' or 'User Name <user@domain.com>'
     * @param string $name The name of the sender, e.g. 'User Name'
     * @return statis $this for chaining
     */
    public function setFrom($address, $name = null)
    {
        $addrInfo = $this->addrInfo($address, $name);
        if (!$this->returnPath) {
            $this->returnPath = $addrInfo['email'];
        }

        $this->headers['From'] = $this->formatAddr($addrInfo['email'], $addrInfo['name']);

        return $this;
    }

    /**
     * Adds a 'to' address
     *
     * @param string $address The from address, e.g. 'user@domain.com' or 'User Name <user@domain.com>'
     * @param string $name The name of the recipient, e.g. 'User Name'
     * @return statis $this for chaining
     */
    public function addTo($address, $name = null)
    {
        $addrInfo = $this->addrInfo($address, $name);
        if (!isset($this->headers['To'])) {
            $this->headers['To'] = [];
        }
        $this->headers['To'][] = $this->formatAddr($addrInfo['email'], $addrInfo['name']);

        return $this;
    }

    /**
     * Gets the 'to' addresses
     *
     * @param bool $emailOnly Set to TRUE to return only e-mail addresses
     * @return array The 'to' addresses
     */
    public function getTo($emailOnly = false)
    {
        if (!isset($this->headers['To'])) {
            return [];
        }

        if ($emailOnly) {
            $emails = [];
            foreach ($this->headers['To'] as $address) {
                $info = $this->addrInfo($address);
                $emails[] = $info['email'];
            }

            return $emails;
        }

        return $this->headers['To'];
    }

    /**
     * Adds a 'CC' address
     *
     * @param string $address The from address, e.g. 'user@domain.com' or 'User Name <user@domain.com>'
     * @param string $name The name of the recipient, e.g. 'User Name'
     * @return statis $this for chaining
     */
    public function addCC($address, $name = null)
    {
        $addrInfo = $this->addrInfo($address, $name);
        if (!isset($this->headers['CC'])) {
            $this->headers['CC'] = [];
        }
        $this->headers['CC'][] = $this->formatAddr($addrInfo['email'], $addrInfo['name']);

        return $this;
    }

    /**
     * Adds a 'BCC' address
     *
     * @param string $address The from address, e.g. 'user@domain.com' or 'User Name <user@domain.com>'
     * @param string $name The name of the recipient, e.g. 'User Name'
     * @return statis $this for chaining
     */
    public function addBCC($address, $name = null)
    {
        $addrInfo = $this->addrInfo($address, $name);
        if (!isset($this->headers['BCC'])) {
            $this->headers['BCC'] = [];
        }
        $this->headers['BCC'][] = $this->formatAddr($addrInfo['email'], $addrInfo['name']);

        return $this;
    }

    /**
     * Sets the subject of the mail
     *
     * @param string $subject The subject
     * @return static $this for chaining
     */
    public function setSubject($subject)
    {
        $this->headers['Subject'] = $subject;
    }

    /**
     * Sets the body of the mail
     *
     * @param string $body The body
     * @return static $this for chaining
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * Gets the array of headers
     *
     * @return array The headers
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Gets the headers as a string, separated by CRLF's
     *
     * @return string The headers, separated by CRLF's. The string is ended with a CRLF
     */
    public function getHeaderString()
    {
        $str = '';
        foreach ($this->headers as $header => $value) {
            $str .= $header . ': ' . (is_array($value) ? implode(', ', $value) : $value) . "\r\n";
        }
        return $str;
    }

    /**
     * Gets the return path
     *
     * @return string The return path, i.e. a single e-mail address
     */
    public function getReturnPath()
    {
        return $this->returnPath;
    }

    /**
     * Gets the body of the mail
     *
     * @return string The mail body
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Returns the entire mail including headers
     *
     * @return string The mail
     */
    public function __toString()
    {
        $str = $this->getHeaderString() . "\r\n" . $this->body;
        return $str;
    }
}