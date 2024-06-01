<?php

namespace App\Jobs;
use App\Mail\WelcomeMailBranch;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;


class SendWelcomeEmailBranch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $branch;

    /**
     * Create a new job instance.
     */
    public function __construct($user, $branch)
    {
        $this->user = $user;
        $this->branch = $branch;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Mail::to($this->user->email)->send(new WelcomeMailBranch($this->user, $this->branch));
    }
}
