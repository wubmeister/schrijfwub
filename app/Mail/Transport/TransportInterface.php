<?php

namespace App\Mail\Transport;

use App\Mail\Mail;

interface TransportInterface
{
    /**
     * This method should send the passed mail to all it's defined addresses
     *
     * @param App\Mail\Mail $mail The mail object
     */
    public function send(Mail $mail);
}
