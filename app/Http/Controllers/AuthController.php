<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Registers a new user.
     */
    public function register(Request $request)
    {
        // Validate the request data
        $validated = $request->validate([
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'middleName' => 'nullable|string|max:255',
            'contactNumber' => 'nullable|string|max:20',
            'username' => 'required|string|max:255|unique:users,username',
            'block' => 'nullable|string|max:255',
            'lot' => 'nullable|string|max:255',
            'street' => 'nullable|string|max:255',
            'subdivision' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|same:confirmPassword',
            'confirmPassword' => 'required|string|min:8',
            'code' => 'nullable|string|max:4', // Optional: if used
        ]);

        // Create the user
        $user = User::create([
            'first_name' => $validated['firstName'],
            'last_name' => $validated['lastName'],
            'middle_name' => $validated['middleName'],
            'contact_number' => $validated['contactNumber'],
            'username' => $validated['username'],
            'block' => $validated['block'],
            'lot' => $validated['lot'],
            'steet' => $validated['street'],
            'dubdivision' => $validated['subdivision'],
            'baranggy' => $validated['barangay'],
            'city' => $validated['city'],
            'province' => $validated['province'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => 2,
        ]);

        return response()->json([
            'message' => 'User created successfully.',
            'user' => $user
        ], 201);
    }

    /**
     * Logs the user in.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            Log::info("User login failed: " . ($user->email ?? 'unknown'));
            return response(['message' => 'Invalid credentials',], 401);
        }

        Log::info("User login successful: " . ($user->email ?? 'unknown'));

        $token = $user->createToken('auth_token')->plainTextToken;

        return response([
            'user' => $user->load('role'),
            'access_token' => $token
        ]);
    }

    /**
     * Logs the user out.
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response(['message' => 'Logged out'], 200);
    }

    /**
     * Returns the authenticated user.
     */
    public function user(Request $request)
    {

        $data = $request->user()->load('role');
        $user = [
            'id' => $data->id,
            'firstName' => $data->first_name,
            'middleName' => $data->middle_name,
            'lastName' => $data->last_name,
            'fullName' => $data->first_name . ' ' . $data->middle_name . ' ' . $data->last_name,
            'contactNumber' => $data->contact_number,
            'address' => [
                'block' => $data->block,
                'lot' => $data->lot,
                'street' => $data->steet,
                'subdivision' => $data->dubdivision,
                'barangay' => $data->baranggy,
                'city' => $data->city,
                'province' => $data->province,
                'code' => $data->code,
            ],
            'username' => $data->username,
            'email' => $data->email,
            'role' => $data->role->name,
            'image' => $data->image ?? null,
        ];


        return response(['user' => $user], 200);
    }

    public function users()
    {
        return response(['users' => User::all()], 200);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|min:6',
        ]);

        // Check if a password is provided
        if ($request->filled('password')) {
            // Compare new password with the existing one
            if (!Hash::check($request->password, $user->password)) {
                // If they are different, hash and update it
                $validatedData['password'] = Hash::make($request->password);
            } else {
                // If the same, remove password from the update data
                unset($validatedData['password']);
            }
        } else {
            // If no password is provided, remove it from update data
            unset($validatedData['password']);
        }

        // Update user data
        $user->update($validatedData);

        return response()->json(['message' => 'User updated successfully', 'user' => $user]);
    }

    // Delete user
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

}
