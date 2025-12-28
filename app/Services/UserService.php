<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UserService {

    /**
     * Summary of getAllUsers
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    public function getAllUsers(?string $searchTerm = null) {
        return User::where('is_archived', false)
            ->when($searchTerm, function ($query, $searchTerm) {
                $like = '%' . $searchTerm . '%';
                $query->where(function ($innerQuery) use ($like) {
                    $innerQuery->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('username', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            })
            ->latest()
            ->get();
    }

    /**
     * Summary of getLoggedInUser
     * @param Request $request
     * @return array{address: array{barangay: mixed, block: mixed, city: mixed, code: mixed, lot: mixed, province: mixed, street: mixed, subdivision: mixed, contactNumber: mixed, email: mixed, firstName: mixed, fullName: string, id: mixed, image: mixed, lastName: mixed, middleName: mixed, role: mixed, username: mixed}}
     */
    public function getLoggedInUser(Request $request) {

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

        return $user;
    }

    /**
     * Summary of getArchivedUsers
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    public function getArchivedUsers(?string $searchTerm = null) {
        return User::where('is_archived', true)
            ->when($searchTerm, function ($query, $searchTerm) {
                $like = '%' . $searchTerm . '%';
                $query->where(function ($innerQuery) use ($like) {
                    $innerQuery->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('username', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            })
            ->latest()
            ->get();
    }

    /**
     * Summary of restore
     * @param int $userId
     * @return User
     */
    public function restore(int $userId) {
        $user = User::where('is_archived', true)->findOrFail($userId);
        $user->is_archived = false;
        $user->save();

        return $user;
    }

    /**
     * Summary of delete
     * @param int $userId
     * @return void
     */
    public function delete(int $userId) {
        $user = User::findOrFail($userId);

        if (!empty($user->email)) {
            $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'there';

            Mail::send(
                'emails.users.archived',
                ['name' => $name],
                function ($message) use ($user) {
                    $message->to($user->email)
                        ->subject('Your Account Has Been Archived');
                }
            );
        }

        $user->is_archived = true;
        $user->save();
    }
}
