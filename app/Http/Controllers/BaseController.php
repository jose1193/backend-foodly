<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as Controller;
use Illuminate\Support\Facades\Cache;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;

class BaseController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    
    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendResponse($result, $message)
    {
        $response = [
            'success' => true,
            'data'    => $result,
            'message' => $message,
        ];
  
        return response()->json($response, 200);
    }
  
    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendError($error, $errorMessages = [], $code = 404)
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];
  
        if(!empty($errorMessages)){
            $response['data'] = $errorMessages;
        }
  
        return response()->json($response, $code);
    }


    protected function getCachedData(string $key, int $minutes, callable $callback)
    {
        return Cache::remember($key, $minutes, $callback);
    }

    public function updateCache(string $cacheKey, int $cacheTime, callable $dataCallback): void
    {
        // Obtén los datos usando el callback y almacénalos en la caché
        $data = $dataCallback();
        $this->putCachedData($cacheKey, $data, $cacheTime);
    }


    protected function putCachedData(string $key, $data, int $minutes): void
    {
        Cache::put($key, $data, now()->addMinutes($minutes));
    }

   public function refreshCache(string $cacheKey, int $cacheTime, callable $dataCallback): void
    {
        // Invalida la caché existente
        $this->invalidateCache($cacheKey);

        // Actualiza la caché con los nuevos datos
        $this->updateCache($cacheKey, $cacheTime, $dataCallback);
    }

    protected function invalidateCache(string $key): void
    {
        Cache::forget($key);
    }


}