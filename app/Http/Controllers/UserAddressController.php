<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserAddressResource;
use App\Models\UserAddress;
use App\Models\AddressLabel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserAddressController extends Controller
{
    /**
     * Display a listing of the user's addresses.
     */
    public function index()
    {
        $user = Auth::user();
        $addresses = $user->addresses()->with('addressLabel')->orderBy('principal', 'desc')->get();
        
        return response()->json([
            'addresses' => UserAddressResource::collection($addresses)
        ], 200);
    }

    /**
     * Store a newly created address.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'address' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                'country' => 'required|string|max:255',
                'zip_code' => 'required|string|max:20',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'address_label_id' => 'required|exists:address_labels,id',
                'principal' => 'boolean',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'hint' => 'Make sure you are sending "address_label_id" (not "label_id") with a valid address label ID'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $validated['user_id'] = $user->id;

            // Check if user already has an address with this label
            $existingAddress = $user->addresses()->where('address_label_id', $validated['address_label_id'])->first();
            if ($existingAddress) {
                $label = AddressLabel::find($validated['address_label_id']);
                return response()->json([
                    'message' => 'You already have an address with label: ' . $label->name,
                    'existing_address' => new UserAddressResource($existingAddress)
                ], 409);
            }

            // If this is the first address, make it principal
            if ($user->addresses()->count() === 0) {
                $validated['principal'] = true;
            }

            $address = UserAddress::create($validated);
            $address->load('addressLabel');

            DB::commit();

            return response()->json(new UserAddressResource($address), 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error creating address',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified address.
     */
    public function show($uuid)
    {
        $user = Auth::user();
        $address = $user->addresses()->with('addressLabel')->where('uuid', $uuid)->first();

        if (!$address) {
            return response()->json(['message' => 'Address not found'], 404);
        }

        return response()->json(new UserAddressResource($address), 200);
    }

    /**
     * Update the specified address.
     */
    public function update(Request $request, $uuid)
    {
        try {
            $validated = $request->validate([
                'address' => 'sometimes|required|string|max:255',
                'city' => 'sometimes|required|string|max:255',
                'country' => 'sometimes|required|string|max:255',
                'zip_code' => 'sometimes|required|string|max:20',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'address_label_id' => 'sometimes|required|exists:address_labels,id',
                'principal' => 'boolean',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'hint' => 'Make sure you are sending "address_label_id" (not "label_id") with a valid address label ID'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $address = $user->addresses()->where('uuid', $uuid)->first();

            if (!$address) {
                return response()->json(['message' => 'Address not found'], 404);
            }

            // Check if trying to change to a label that already exists for this user
            if (isset($validated['address_label_id']) && $validated['address_label_id'] != $address->address_label_id) {
                $existingAddress = $user->addresses()
                    ->where('address_label_id', $validated['address_label_id'])
                    ->where('id', '!=', $address->id)
                    ->first();
                    
                if ($existingAddress) {
                    $label = AddressLabel::find($validated['address_label_id']);
                    return response()->json([
                        'message' => 'You already have an address with label: ' . $label->name,
                        'existing_address' => new UserAddressResource($existingAddress)
                    ], 409);
                }
            }

            // If user has only one address, it must be principal
            $totalAddresses = $user->addresses()->count();
            if ($totalAddresses === 1) {
                // Force principal to true if it's the only address (silently, no error)
                $validated['principal'] = true;
            } else {
                // If user has multiple addresses and is setting current principal to false
                if (isset($validated['principal']) && $validated['principal'] === false && $address->principal) {
                    // Find another address to make principal
                    $nextPrincipal = $user->addresses()
                        ->where('id', '!=', $address->id)
                        ->first();
                    
                    if ($nextPrincipal) {
                        $nextPrincipal->update(['principal' => true]);
                    }
                }
            }

            $address->update($validated);
            $address->load('addressLabel');

            DB::commit();

            return response()->json(new UserAddressResource($address), 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error updating address',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified address.
     */
    public function destroy($uuid)
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $address = $user->addresses()->where('uuid', $uuid)->first();

            if (!$address) {
                return response()->json(['message' => 'Address not found'], 404);
            }

            // If deleting principal address, make another one principal
            if ($address->principal) {
                $nextAddress = $user->addresses()
                    ->where('id', '!=', $address->id)
                    ->first();
                
                if ($nextAddress) {
                    $nextAddress->update(['principal' => true]);
                }
            }

            $address->delete();

            DB::commit();

            return response()->json([
                'message' => 'Address deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error deleting address',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set an address as principal.
     */
    public function setPrincipal($uuid)
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $address = $user->addresses()->where('uuid', $uuid)->first();

            if (!$address) {
                return response()->json(['message' => 'Address not found'], 404);
            }

            $address->update(['principal' => true]);
            $address->load('addressLabel');

            DB::commit();

            return response()->json(new UserAddressResource($address), 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error updating principal address',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 