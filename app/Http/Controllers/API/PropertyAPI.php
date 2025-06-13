<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\PropertyModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PropertyAPI extends Controller
{
    public function fetch()
    {
        $property = PropertyModel::all()->map(function ($item) {
            // Corrected line: Use ->photo to match your database column name
            $item->image_url = url('/api/image/' . $item->photo);
            return $item;
        });

        return response()->json($property, 200);
    }

    public function search(Request $request)
    {
        // Get the search query from the request, default to an empty string
        $query = $request->input('q', '');

        // If the query is empty, return an empty result or all properties, your choice
        if (empty($query)) {
            return response()->json([]); // Return empty for a specific search
        }

        // Perform the search on multiple columns
        $properties = PropertyModel::where('title', 'LIKE', "%{$query}%")
            ->orWhere('address', 'LIKE', "%{$query}%")
            ->get();

        return response()->json($properties);
    }

    public function getPropertiesFromUserId($id)
    {
        $property = PropertyModel::where('user_id', '=', $id)->get();
        return response()->json($property, 200);
    }

    public function store(Request $request)
    {
        // 1. Validate the incoming request data
        //    'photo' will now be validated as an image file if present.
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer', // Assuming user_id is required
            'listing_type' => 'nullable|string|max:255',
            'property_type' => 'nullable|string|max:255',
            'bedrooms' => 'nullable|string|max:50',      // Consider 'integer' if it's purely numeric
            'bathrooms' => 'nullable|string|max:50',     // Consider 'integer'
            'address' => 'nullable|string',
            'square_meters' => 'nullable|string|max:50', // Consider 'numeric'
            'facilities' => 'nullable|string',
            'title' => 'nullable|string|max:255',
            'harga' => 'nullable|string|max:100',        // Consider 'numeric' or 'decimal'
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // Max 5MB, adjust as needed. 'photo' is the key from Flutter.
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated(); // Get validated data

        // Prepare data for model creation, excluding the raw photo file from direct insertion
        $dataForModel = $validatedData;
        unset($dataForModel['photo']); // Remove the UploadedFile object from the array to be passed to create()

        // 2. Handle the file upload if a photo was provided
        if ($request->hasFile('photo')) {
            $file = $request->file('photo'); // Retrieve the uploaded file object

            // Store the file in 'storage/app/public/property_photos'
            // Laravel will generate a unique name for the file.
            // Ensure you have run `php artisan storage:link` to make these files publicly accessible.
            $filePath = $file->store('property_photos', 'public');

            // 3. Add the file path (string) to the data for the model
            $dataForModel['photo'] = $filePath; // e.g., "property_photos/randomGeneratedName.jpg"
        } else {
            // Ensure 'photo' is null if no file was uploaded,
            // respecting the 'nullable' database schema.
            $dataForModel['photo'] = null;
        }

        // 4. Create the property record with the (potentially new) photo path
        $property = PropertyModel::create($dataForModel);

        Notification::create([
            'user_id' => $property->user_id,
            'title' => 'Properti Baru Ditambahkan',
            'body' => 'Properti "' . $property->title . '" telah berhasil ditambahkan.',
            'is_read' => false,
        ]);

        // 5. Return the response
        return response()->json($property, 201);
    }

    public function edit($id, Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $property = PropertyModel::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$property) {
            return response()->json(['message' => 'Properti tidak ditemukan atau bukan milik Anda.'], 404);
        }

        return response()->json($property);
    }

    public function update(Request $request, $id)
    {
        // 1. Correctly find the property that belongs to the authenticated user
        $property = PropertyModel::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$property) {
            return response()->json(['message' => 'Properti tidak ditemukan atau bukan milik Anda.'], 404);
        }

        // 2. Validate the request using the exact field names from Flutter
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'harga' => 'required|numeric', // Expects 'harga'
            'property_type' => 'required|string',
            'listing_type' => 'required|string',
            'address' => 'required|string',
            'square_meters' => 'required|numeric', // Expects 'square_meters'
            'bedrooms' => 'required|integer',
            'bathrooms' => 'required|integer',
            'facilities' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        // 3. Create a new array to map the Flutter names to your database column names
        $updateData = [
            'title' => $validated['title'],
            'harga' => $validated['harga'], // Map 'harga' to the 'price' database column
            'property_type' => $validated['property_type'],
            'listing_type' => $validated['listing_type'],
            'address' => $validated['address'],
            'square_meters' => $validated['square_meters'], // Map 'square_meters' to 'land_size' column
            'bedrooms' => $validated['bedrooms'],
            'bathrooms' => $validated['bathrooms'],
            'facilities' => $validated['facilities'],
        ];

        // 4. Handle the photo upload
        if ($request->hasFile('photo')) {
            if ($property->photo && Storage::disk('public')->exists($property->photo)) {
                Storage::disk('public')->delete($property->photo);
            }
            $path = $request->file('photo')->store('properties', 'public');
            $updateData['photo'] = $path;
        }

        // 5. Update the database with the correctly mapped data
        $property->update($updateData);

        return response()->json(['message' => 'Properti berhasil diperbarui.', 'data' => $property]);
    }

    public function destroy($id, Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Baru lanjut jika user valid
        $property = PropertyModel::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$property) {
            return response()->json(['message' => 'Properti tidak ditemukan atau bukan milik Anda.'], 404);
        }

        if ($property->photo && Storage::disk('public')->exists($property->photo)) {
            Storage::disk('public')->delete($property->photo);
        }

        $property->delete();

        return response()->json(['message' => 'Properti berhasil dihapus.'], 200);
    }


    public function getImage($filename)
    {
        $path = storage_path('app/public/property_photos/' . $filename);

        if (!file_exists($path)) {
            return response()->json(['message' => 'Image not found'], 404);
        }

        $file = file_get_contents($path);
        $type = mime_content_type($path);

        return response($file)->header('Content-Type', $type);
    }
}
