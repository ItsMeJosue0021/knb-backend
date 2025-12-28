<?php

namespace App\Services;

use App\Models\MembershipRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MembershipService
{

    /**
     * Create a new membership request.
     * @param array $data
     * @return MembershipRequest
     */
    public function create(array $data): MembershipRequest
    {
        $payload = [
            'user_id' => $data['user_id'],
            'status' => $data['status'] ?? 'pending',
            'payment_reference_number' => $data['payment_reference_number'] ?? null,
        ];

        if (isset($data['proof_of_payment'])) {
            $payload['proof_of_payment'] = $this->storeProof($data['proof_of_payment']);
        }

        if (isset($data['proof_of_identity'])) {
            $payload['proof_of_identity'] = $this->storeProof($data['proof_of_identity']);
        }

        return MembershipRequest::create($payload);
    }

    /**
     * Approve a membership request.
     * @param int $id
     * @return MembershipRequest
     */
    public function approve(int $id): MembershipRequest
    {
        $request = MembershipRequest::findOrFail($id);
        $request->update(['status' => 'approved']);

        return $request;
    }


    /**
     * Reject a membership request.
     * @param int $id
     * @return MembershipRequest
     */
    public function reject(int $id): MembershipRequest
    {
        $request = MembershipRequest::findOrFail($id);
        $request->update(['status' => 'rejected']);

        return $request;
    }

    /**
     * Fetch membership requests with optional search on status, reference number, or user info.
     * @param mixed $search
     * @return \Illuminate\Database\Eloquent\Collection<int, MembershipRequest>
     */
    public function getAll(?string $search = null)
    {
        return MembershipRequest::with('user')
            ->when($search, function ($query, $search) {
                $like = '%' . $search . '%';
                $query->where('payment_reference_number', 'like', $like)
                    ->orWhere('status', 'like', $like)
                    ->orWhereHas('user', function ($userQuery) use ($like) {
                        $userQuery->where('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->orWhere('username', 'like', $like);
                    });
            })
            ->latest()
            ->get();
    }

    /**
     * Summary of storeProof
     * @param UploadedFile $file
     * @return bool|string
     */
    private function storeProof(UploadedFile $file): string
    {
        return $file->store('membership/proofs', 'public');
    }
}
