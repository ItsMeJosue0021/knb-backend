<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Psy\Exception\Exception;
use App\Services\UserService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Barryvdh\DomPDF\Facade\Pdf;

class AuthController extends Controller
{

    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
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

        $user = User::where('email', $request->email)
            ->where('is_archived', false)
            ->first();

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
     * Summary of user
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function user(Request $request)
    {
        try {
            $user = $this->userService->getLoggedInUser($request);
            return response(['user' => $user], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => "Unable to fetch user's data.",
            ], 500);
        }
    }

    /**
     * Summary of users
     * @return \Illuminate\Http\Response
     */
    public function users(Request $request)
    {
        $searchTerm = $request->input('serach', $request->input('search'));

        return response([
            'users' => $this->userService->getAllUsers($searchTerm)
        ], 200);
    }

    /**
     * Returns all archived users.
     */
    public function archivedUsers(Request $request)
    {
        $searchTerm = $request->input('serach', $request->input('search'));

        return response([
            'users' => $this->userService->getArchivedUsers($searchTerm)
        ], 200);
    }

    /**
     * Streams a PDF listing of all active users.
     */
    public function printUsers()
    {
        try {
            $users = $this->userService->getAllUsers()->load('role');

            $pdf = Pdf::loadView('users.report', [
                'users' => $users,
                'generatedAt' => now(),
            ])->setPaper('a4', 'landscape');

            return $pdf->stream('users.pdf');
        } catch (Exception $e) {
            Log::error('Failed to generate users PDF', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Unable to generate users PDF.',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|min:6',
        ]);

        if ($request->filled('password')) {
            if (!Hash::check($request->password, $user->password)) {
                $validatedData['password'] = Hash::make($request->password);
            } else {
                unset($validatedData['password']);
            }
        } else {
            unset($validatedData['password']);
        }

        $user->update($validatedData);

        return response()->json(['message' => 'User updated successfully', 'user' => $user]);
    }

    /**
     * Summary of destroy
     * @param mixed $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $this->userService->delete($id);
            return response()->json([
                'message' => 'User deleted successfully'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restores an archived user.
     *
     * @param mixed $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore($id)
    {
        try {
            $user = $this->userService->restore($id);

            return response()->json([
                'message' => 'User restored successfully',
                'user' => $user,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
