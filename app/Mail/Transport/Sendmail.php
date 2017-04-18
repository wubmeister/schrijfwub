<?php

namespace App\Mail\Transport;

use App\Mail\Mail;

class Sendmail implements TransportInterface
{
    /**
     * This method should send the passed mail to all it's defined addresses
     *
     * @param App\Mail\Mail $mail The mail object
     */
    public function send(Mail $mail)
    {
        $to = $mail->getTo(true);
        foreach ($to as $addr) {
            $this->sendProc($addr, $mail);
        }
    }

    protected function sendProc($to, $mail)
    {
        $cmd = "sendmail -f " . $mail->getReturnPath() . " {$to}";
        $descriptorSpec = [
            0 => [ 'pipe', 'r' ],
            1 => [ 'pipe', 'w' ],
            2 => [ 'pipe', 'w' ]
        ];
        $proc = proc_open($cmd, $descriptorSpec, $pipes);

        if (is_resource($proc)) {
            fwrite($pipes[0], (string)$mail . ".\r\n");

            fclose($pipes[0]);

            $stdout = fgets($pipes[1]);
            $stderr = fgets($pipes[2]);

            fclose($pipes[1]);
            fclose($pipes[2]);

            proc_close($proc);

            if ($stdout || $stderr) {
                throw new \Exception("Sendmail failed: {$stdout} {$stderr}");
            }
        } else {
            throw new \Exception('Something went wrong opening the sendmail process');
        }
    }
}
