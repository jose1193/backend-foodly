<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\BusinessResource;
use App\Models\Business;

class ExternalSearchController extends Controller
{
    /**
     * External search API URL
     */
    protected $externalApiUrl = 'https://foodly-api-env.eba-t6i5hcyf.us-east-1.elasticbeanstalk.com/search';

    /**
     * Search businesses using external API service
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'radius' => 'sometimes|numeric|min:1|max:50',
                'voice_text' => 'sometimes|string',
            ]);

            // Forward the request to the external API
            $response = Http::post($this->externalApiUrl, [
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'radius' => $validated['radius'] ?? 5,
                'voice_text' => $validated['voice_text'] ?? '',
            ]);

            // Check if the response is successful
            if ($response->successful()) {
                $data = $response->json();

                // Check if the response has the expected structure
                if (isset($data['business']) && is_array($data['business']) && isset($data['success']) && $data['success'] === true) {
                    // Return the business data directly
                    return response()->json([
                        'business' => $data['business'],
                        
                    ], 200);
                }
                
                // Return the raw response if structure doesn't match expectations
                return response()->json($data, 200);
            }

            // Handle error from external API
            return response()->json([
                'success' => false,
                'message' => 'External search service error: ' . $response->body()
            ], $response->status());

        } catch (\Exception $e) {
            Log::error('Error in external search service', [
                'error' => $e->getMessage(),
                'params' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while searching businesses: ' . $e->getMessage()
            ], 500);
        }
    }
} 