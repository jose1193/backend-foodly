<?php

namespace App\Http\Controllers;
use App\Http\Controllers\BaseController as BaseController;

use App\Models\AddressLabel;
use App\Http\Resources\AddressLabelResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddressLabelController extends BaseController
{
    protected $cacheKey;
    protected $cacheTime = 720;
    protected $userId;

    /**
     * Apply middleware for Super Admin permissions.
     */
    public function __construct()
    {
        $this->middleware('check.permission:Super Admin')->only([
            'store', 'show', 'update', 'destroy', 'toggleActive'
        ]);

        $this->middleware(function ($request, $next) {
            $this->userId = Auth::id();
            $this->cacheKey = 'address_labels';
            return $next($request);
        });
    }

    /**
     * Display a listing of address labels.
     */
    public function index()
    {
        try {
            // Obtener las etiquetas del caché o de la base de datos
            $labels = $this->getCachedData($this->cacheKey, $this->cacheTime, function () {
                return $this->retrieveAddressLabels();
            });

            return response()->json([
                'labels' => AddressLabelResource::collection($labels)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error retrieving address labels: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while retrieving address labels'], 500);
        }
    }

    private function retrieveAddressLabels()
    {
        return AddressLabel::active()->orderBy('name')->get();
    }

    // Actualización específica de caché 
    private function updateAddressLabelsCache()
    {
        $this->refreshCache($this->cacheKey, $this->cacheTime, function () {
            return $this->retrieveAddressLabels();
        });
    }

    /**
     * Store a newly created address label.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:address_labels,name',
            'description' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        try {
            $label = DB::transaction(function () use ($validated) {
                return AddressLabel::create($validated);
            });

            $this->updateAddressLabelsCache();

            return $this->successfulResponse($label);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('An error occurred while creating address label: ' . $e->getMessage(), [
                'request' => $validated,
            ]);
            return $this->errorResponse('Error creating address label', $e->getMessage());
        }
    }

    /**
     * Display the specified address label.
     */
    public function show($uuid)
    {
        try {
            $cacheKey = "address_label_{$uuid}";

            // Obtener la etiqueta desde el caché o la base de datos
            $label = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($uuid) {
                return AddressLabel::where('uuid', $uuid)->firstOrFail();
            });

            return response()->json(new AddressLabelResource($label), 200);
        } catch (ModelNotFoundException $e) {
            Log::warning("Address label with UUID {$uuid} not found");
            return response()->json(['message' => 'Address label not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error retrieving address label: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while retrieving the address label'], 500);
        }
    }

    /**
     * Update the specified address label.
     */
    public function update(Request $request, $uuid)
    {
        try {
            $cacheKey = "address_label_{$uuid}";

            $label = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($uuid) {
                return AddressLabel::where('uuid', $uuid)->firstOrFail();
            });

            $validated = $request->validate([
                'name' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('address_labels', 'name')->ignore($label->id)
                ],
                'description' => 'nullable|string|max:255',
                'icon' => 'nullable|string|max:50',
                'is_active' => 'boolean',
            ]);

            return DB::transaction(function () use ($label, $validated, $cacheKey) {
                $label->update($validated);

                // Actualiza la caché
                $this->refreshCache($cacheKey, $this->cacheTime, function () use ($label) {
                    return $label;
                });

                $this->updateAddressLabelsCache();

                return $this->successfulResponse($label);
            });

        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Address label not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error updating address label: ' . $e->getMessage());
            return $this->errorResponse('Error updating address label', $e->getMessage());
        }
    }

    /**
     * Remove the specified address label.
     */
    public function destroy($uuid)
    {
        try {
            $cacheKey = "address_label_{$uuid}";

            // Obtener la etiqueta desde el caché o la base de datos
            $label = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($uuid) {
                return AddressLabel::where('uuid', $uuid)->firstOrFail();
            });

            // Check if label is being used by any addresses
            $addressCount = $label->userAddresses()->count();
            
            if ($addressCount > 0) {
                return response()->json([
                    'message' => 'Cannot delete label. It is being used by ' . $addressCount . ' address(es).',
                    'addresses_count' => $addressCount
                ], 409);
            }

            $label->delete();

            // Actualiza la caché
            $this->invalidateCache($cacheKey);
            $this->updateAddressLabelsCache();

            return response()->json([
                'message' => 'Address label deleted successfully'
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Address label not found'], 404);
        } catch (\Exception $e) {
            Log::error('An error occurred while removing the address label: ' . $e->getMessage());
            return $this->errorResponse('Error deleting address label', $e->getMessage());
        }
    }

    /**
     * Toggle the active status of an address label.
     */
    public function toggleActive($uuid)
    {
        try {
            $cacheKey = "address_label_{$uuid}";

            $label = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($uuid) {
                return AddressLabel::where('uuid', $uuid)->firstOrFail();
            });

            return DB::transaction(function () use ($label, $cacheKey) {
                $label->update(['is_active' => !$label->is_active]);

                // Actualiza la caché
                $this->refreshCache($cacheKey, $this->cacheTime, function () use ($label) {
                    return $label;
                });

                $this->updateAddressLabelsCache();

                return $this->successfulResponse($label);
            });

        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Address label not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error updating address label status: ' . $e->getMessage());
            return $this->errorResponse('Error updating address label status', $e->getMessage());
        }
    }

    /**
     * Return successful response with resource.
     */
    private function successfulResponse($label)
    {
        return response()->json(new AddressLabelResource($label), 200);
    }

    /**
     * Return error response.
     */
    private function errorResponse($message = 'An error occurred', $error = null)
    {
        $response = ['error' => $message];
        if ($error) {
            $response['details'] = $error;
        }
        return response()->json($response, 500);
    }
} 