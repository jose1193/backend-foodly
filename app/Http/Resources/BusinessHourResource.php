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
        // Verifica que $this->collection no sea null antes de usar groupBy()
        if ($this->collection) {
            // Ejemplo de cómo usar groupBy()
            $groupedData = $this->collection->groupBy('day');
            // Continúa con la lógica para formatear los datos según sea necesario
        }

        // Devuelve el arreglo transformado o lo que sea adecuado en tu caso
        return parent::toArray($request);
    


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
