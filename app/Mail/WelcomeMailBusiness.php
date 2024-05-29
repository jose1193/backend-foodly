<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMailBusiness extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $user;
    public $business;
    
    public function __construct($user,$business)
    {
        $this->user = $user;
        $this->business = $business;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome Mail Business',
        );
    }

    /**
     * Get the message content definition.
     */
    public function build()
    {
        return $this->subject('Welcome to Foodly! Your door to the gastronomic world')
                    ->view('emails.welcome_business');
    }
}
