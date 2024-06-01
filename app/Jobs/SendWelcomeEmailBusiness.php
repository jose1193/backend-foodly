<?php

namespace App\Jobs;

use App\Mail\WelcomeMailBusiness;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Facades\Mail;

class SendWelcomeEmailBusiness implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $business;

    /**
     * Create a new job instance.
     */
    public function __construct($user, $business)
    {
        $this->user = $user;
        $this->business = $business;
    }


    /**
     * Execute the job.
     */
    public function handle()
    {
        Mail::to($this->user->email)->send(new WelcomeMailBusiness($this->user, $this->business));
    }
}
