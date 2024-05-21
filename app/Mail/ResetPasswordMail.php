<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $data;
    protected $viewName;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $viewName = 'emails.admin-mail')
    {
        $this->data = $data;
        $this->viewName = $viewName; // Pass the view name as a parameter
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view($this->viewName)->with(["data" => $this->data]);
    }
}
