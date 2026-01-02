<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ContactInfo;

class ContactInfoController extends Controller
{
    /**
     * Retrieve the single ContactInfo record.
     */
    public function show()
    {
        $info = ContactInfo::first();

        return response()->json($info, 200);
    }

    /**
     * Create or update the ContactInfo record (singleton).
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'telephone_number' => 'nullable|string',
            'phone_number' => 'nullable|string',
            'email_address' => 'nullable|email',
            'physical_address' => 'nullable|string',
        ]);

        $info = ContactInfo::first();

        if ($info) {
            $info->update($validated);
        } else {
            $info = ContactInfo::create($validated);
        }

        return response()->json([
            'message' => 'Contact info saved successfully',
            'data' => $info,
        ], 200);
    }
}
