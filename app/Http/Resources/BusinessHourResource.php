<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
class BusinessHourResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            
                "day_{$this->day}" => [
                    'open_a' => $this->formatTime($this->open_a),
                    'close_a' => $this->formatTime($this->close_a),
                    'open_b' => $this->formatTime($this->open_b),
                    'close_b' => $this->formatTime($this->close_b),
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
    return $time ? \Carbon\Carbon::createFromFormat('H:i:s', $time)->format('Hi') : null;
    //return Carbon::createFromFormat('H:i:s', $time)->format('Hi');
}

}
