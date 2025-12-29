<?php

namespace App\Services;

use App\Models\MembershipRequest;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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
        $user_id = auth()->id();

        $payload = [
            'user_id' => $user_id,
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
        return DB::transaction(function () use ($id) {
            $request = MembershipRequest::findOrFail($id);
            $request->update(['status' => 'approved']);

            $user = User::with(['profile', 'member'])->find($request->user_id);

            if ($user) {
                $member = $user->member;

                // Create or update a member record for this user
                if (!$member) {
                    $member = Member::create($this->buildMemberPayload($user));
                } else {
                    $member->update([
                        'status' => 'approved',
                    ]);
                }

                // Notify the user that their membership request was approved
                if (!empty($user->email)) {
                    Mail::send(
                        'emails.membership.approved',
                        [
                            'first_name' => $user->first_name ?? 'there',
                            'member_number' => $member->member_number ?? $member->member_id ?? 'your membership',
                        ],
                        function ($msg) use ($user) {
                            $msg->to($user->email)
                                ->subject('Membership Request Approved');
                        }
                    );
                }
            }

            return $request;
        });
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

        $user = User::find($request->user_id);

        if ($user && !empty($user->email)) {
            Mail::send(
                'emails.membership.rejected',
                [
                    'first_name' => $user->first_name ?? 'there',
                ],
                function ($msg) use ($user) {
                    $msg->to($user->email)
                        ->subject('Membership Request Update');
                }
            );
        }

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
     * Get the latest membership request for a given user.
     * @param int $userId
     * @return MembershipRequest|null
     */
    public function getByUser(int $userId): ?MembershipRequest
    {
        return MembershipRequest::where('user_id', $userId)
            ->latest()
            ->first();
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

    /**
     * Build a member payload from the user's available information.
     * @param User $user
     * @return array<string, mixed>
     */
    private function buildMemberPayload(User $user): array
    {
        $addressParts = array_filter([
            $user->block ? 'Block ' . $user->block : null,
            $user->lot ? 'Lot ' . $user->lot : null,
            $user->steet,
            $user->dubdivision,
            $user->baranggy,
            $user->city,
            $user->province,
        ]);

        return [
            'member_number' => $this->generateMemberNumber(),
            'first_name' => $user->first_name ?? 'Member',
            'middle_name' => $user->middle_name ?? null,
            'last_name' => $user->last_name ?? 'Member',
            'nick_name' => $user->username ?? null,
            'address' => $addressParts ? implode(', ', $addressParts) : 'Not provided',
            'dob' => optional($user->profile)->dob ?? now()->toDateString(),
            'civil_status' => 'unknown',
            'contact_number' => $user->contact_number ?? 'N/A',
            'fb_messenger_account' => null,
            'status' => 'approved',
            'user_id' => $user->id,
        ];
    }

    /**
     * Generate the next member number.
     * @return string
     */
    private function generateMemberNumber(): string
    {
        $latest = Member::orderByDesc('id')->value('member_number')
            ?? Member::orderByDesc('id')->value('member_id');

        if (!$latest) {
            return 'MEM-0001';
        }

        $lastNumber = (int) str_replace('MEM-', '', $latest);
        $next = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);

        return 'MEM-' . $next;
    }
}
