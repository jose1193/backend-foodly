<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;


class BranchHourResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
         return [
       
        'day' =>  $this->day,
        'open' => [
            'day' => $this->day,
            'time' => $this->formatTime($this->open),
        ],
        'close' => [
            'day' => $this->day,
            'time' => $this->formatTime($this->close),
        ],
       
    ];
    }

    private function getDayName(int $day)
    {
        $days = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday'
        ];

        return $days[$day] ?? 'Unknown';
    }

    private function formatTime(string $time)
{
    return Carbon::createFromFormat('H:i:s', $time)->format('Hi');
}

}
