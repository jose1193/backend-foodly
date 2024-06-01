<?php

namespace App\Jobs;

use App\Mail\ResetPasswordMail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendMailResetPassword implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email;
    protected $pin;
    /**
     * Create a new job instance.
     */
    public function __construct($email,$pin)
    {
        $this->email = $email;
        $this->pin = $pin;
    }


    /**
     * Execute the job.
     */
    public function handle()
    {
        Mail::to($this->email)->send(new ResetPasswordMail($this->pin));
    }
}
