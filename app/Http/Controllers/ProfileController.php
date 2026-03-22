<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UpdateProfileInfoRequest;

class ProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function uploadProfilePicture($id, Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = User::findOrFail($id);
        $actor = $request->user();

        if (!$actor || (int) $actor->id !== (int) $user->id) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        if ($user->image) {
            Storage::disk('public')->delete($user->image);
        }

        $path = $request->file('image')->store('profile_pictures', 'public');
        $user->update(['image' => $path]);

        return response()->json([
            'message' => 'Image uploaded successfully',
            'path' => $path,
            'image' => $path,
        ], 200);

    }

    public function update(UpdateProfileInfoRequest $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $actor = $request->user();
        if (!$actor || (int) $actor->id !== (int) $user->id) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        if ($request->has('username')) {
            $requestedUsername = trim((string) $request->input('username'));

            if (
                $requestedUsername !== '' &&
                User::where('username', $requestedUsername)
                    ->where('id', '!=', $user->id)
                    ->exists()
            ) {
                return response()->json([
                    'message' => 'The username has already been taken.',
                    'errors' => [
                        'username' => ['The username has already been taken.'],
                    ],
                ], 422);
            }

            if ($requestedUsername === '') {
                return response()->json([
                    'message' => 'Username is required.',
                    'errors' => [
                        'username' => ['The username field is required.'],
                    ],
                ], 422);
            }
        }

        $payload = $this->collectProfilePayload($request);

        try {
            $user->update($payload);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to update profile.'], 500);
        }

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user->fresh(),
        ], 200);
    }

    public function changePassword(ChangePasswordRequest $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $actor = $request->user();

        if (!$actor || (int) $actor->id !== (int) $user->id) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $currentPassword = $user->password;

        if (!Hash::check($request->oldPassword, $currentPassword)) {
            return response()->json(['error' => 'Current password is incorrect.'], 400);
        }

        try {
            $user->update(['password' => Hash::make($request->newPassword)]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to change password.'], 500);
        }

        return response()->json(['message' => 'Password changed successfully.'], 200);
    }

    private function collectProfilePayload(Request $request): array
    {
        $payload = [];

        $firstName = $this->resolveProfileField($request, ['first_name', 'firstName']);
        if ($firstName !== null) {
            $payload['first_name'] = $firstName;
        }

        $middleName = $this->resolveProfileField($request, ['middle_name', 'middleName']);
        if ($middleName !== null) {
            $payload['middle_name'] = $middleName;
        }

        $lastName = $this->resolveProfileField($request, ['last_name', 'lastName']);
        if ($lastName !== null) {
            $payload['last_name'] = $lastName;
        }

        if ($request->has('username')) {
            $payload['username'] = $request->input('username');
        }
        if ($request->has('email')) {
            $payload['email'] = $request->input('email');
        }

        $contact = $this->resolveProfileField($request, ['contact_number', 'contactNo']);
        if ($contact !== null) {
            $payload['contact_number'] = $contact;
        }

        $block = $this->resolveProfileField($request, ['block']);
        if ($block !== null) {
            $payload['block'] = $block;
        }

        $lot = $this->resolveProfileField($request, ['lot']);
        if ($lot !== null) {
            $payload['lot'] = $lot;
        }

        $street = $this->resolveProfileField($request, ['steet', 'street']);
        if ($street !== null) {
            $payload['steet'] = $street;
        }

        $subdivision = $this->resolveProfileField($request, ['dubdivision', 'subdivision']);
        if ($subdivision !== null) {
            $payload['dubdivision'] = $subdivision;
        }

        $barangay = $this->resolveProfileField($request, ['baranggy', 'barangay']);
        if ($barangay !== null) {
            $payload['baranggy'] = $barangay;
        }

        $city = $this->resolveProfileField($request, ['city']);
        if ($city !== null) {
            $payload['city'] = $city;
        }

        $province = $this->resolveProfileField($request, ['province']);
        if ($province !== null) {
            $payload['province'] = $province;
        }

        return $payload;
    }

    private function resolveProfileField(Request $request, array $keys): ?string
    {
        foreach ($keys as $key) {
            if ($request->has($key)) {
                return $request->input($key);
            }
        }

        return null;
    }
}
