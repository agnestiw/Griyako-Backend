<?php

namespace App\Http\Controllers;

use App\Models\PropertyModel;
use Illuminate\Http\Request;

class FavoritePropertyController extends Controller
{
    public function toggle(PropertyModel $property, Request $request)
    {
        $user = $request->user();

        // toggle() is a convenient Laravel method that attaches if not present,
        // and detaches if present.
        $result = $user->favorites()->toggle($property->id);

        $status = count($result['attached']) > 0 ? 'favorited' : 'unfavorited';

        return response()->json([
            'message' => 'Status changed successfully.',
            'status' => $status,
        ]);
    }

    /**
     * Returns a list of the authenticated user's favorite properties.
     */
    public function index(Request $request)
    {
        $favorites = $request->user()->favorites()->get();

        return response()->json($favorites);
    }
}
