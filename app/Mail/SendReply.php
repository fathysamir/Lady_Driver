<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendReply extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $reply;
    public $subject;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($name,$reply,$subject)
    {
        $this->name = $name;
        $this->reply = $reply;
        $this->subject = $subject;
      
    }


  /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.contactus_reply')
                    ->subject('Lady Driver - ' . $this->subject)
                    ->with([
                        'name' => $this->name,
                        'reply' => $this->reply,
                       
                    ]);
    }
}
