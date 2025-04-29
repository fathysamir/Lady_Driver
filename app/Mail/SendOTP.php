<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendOTP extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $name;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($otp, $name)
    {
        $this->otp = $otp;
        $this->name = $name;
      
    }


  /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.otp')
                    ->subject('Lady Driver')
                    ->with([
                        'otp' => $this->otp,
                        'name' => $this->name
                    ]);
    }
}
