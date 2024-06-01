<?php

namespace App\Jobs;

use App\Mail\PasswordResetSuccess;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendMailPasswordResetSuccess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    
    /**
     * Create a new job instance.
     */
    public function __construct(protected object $user )
    {
       
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Mail::to($this->user->email)->send(new PasswordResetSuccess($this->user));
    }
}
