<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return response()->json($request->user());
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB Max
            'remove_profile_photo' => 'nullable|boolean' // For removing photo
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $request->only(['name', 'nickname', 'email', 'phone', 'address']);

        if ($request->input('remove_profile_photo') && $user->profile_photo_path) {
            // Delete old photo if it exists
            Storage::disk('public')->delete($user->profile_photo_path);
            $data['profile_photo_path'] = null;
        } elseif ($request->hasFile('profile_photo')) {
            // Delete old photo if it exists and a new one is uploaded
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }
            // Store new photo
            $path = $request->file('profile_photo')->store('profile-photos', 'public');
            $data['profile_photo_path'] = $path;
        }

        $user->update($data);

        // Refresh user model to get updated profile_photo_url
        $user->refresh();

        return response()->json([
            'message' => 'Profile updated successfully!',
            'user' => $user
        ]);
    }
}
