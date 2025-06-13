<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    public function index()
    {
        $favorites = Favorite::with('property')
            ->where('user_id', Auth::id())
            ->get();

        $formattedFavorites = $favorites->map(function ($favorite) {
            return [
                'id' => $favorite->id,
                'title' => $favorite->property->title,
                'address' => $favorite->property->address,
                'harga' => $favorite->property->harga,
                'image_url' => $favorite->property->photo,
                'bedrooms' => $favorite->property->bedrooms,
                'bathrooms' => $favorite->property->bathrooms,
                'property_type' => $favorite->property->property_type,
            ];
        });

        return response()->json($formattedFavorites);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    // Tambah atau toggle favorit
    public function store(Request $request)
    {
        // 1. Validate the incoming request to ensure the property ID is present.
        $request->validate([
            'property_models_id' => 'required|integer|exists:property_models,id', // Assuming your property table is 'property_models'
        ]);

        $userId = Auth::id();
        $propertyId = $request->property_models_id;

        // 2. Check if the favorite record already exists for this user and property.
        $favorite = Favorite::where('user_id', $userId)
            ->where('property_models_id', $propertyId)
            ->first();

        // 3. Implement the new create/delete logic.
        if ($favorite) {
            // IF IT EXISTS: The user is unfavoriting the item. Delete the record.
            $favorite->delete();

            $message = 'Unfavorited';
            $status = false; // The new status is 'false' (not a favorite)
        } else {
            // IF IT DOES NOT EXIST: The user is favoriting the item. Create a new record.
            Favorite::create([
                'user_id' => $userId,
                'property_models_id' => $propertyId,
                // No 'status' field is needed here if you remove it from your table.
            ]);

            $message = 'Favorited';
            $status = true; // The new status is 'true' (is a favorite)
        }

        // 4. Return a JSON response in the exact same format your Flutter app expects.
        return response()->json([
            'message' => $message,
            'data' => [
                'status' => $status,
            ],
        ], 200); // Explicitly return a 200 OK status.
    }


    /**
     * Display the specified resource.
     */
    public function show(Favorite $favorite)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Favorite $favorite)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Favorite $favorite)
    {
        //
    }

    // Hapus dari favorit (set status = false)
    public function destroy($property_id)
    {
        $user = Auth::user();

        $favorite = Favorite::where('user_id', $user->id)
            ->where('id', $property_id)
            ->first();

        if (!$favorite) {
            return response()->json(['message' => 'Favorite not found'], 404);
        }

        $favorite->delete();

        return response()->json(['message' => 'Removed from favorites'], 200); // Set status 200 secara eksplisit
    }

}
