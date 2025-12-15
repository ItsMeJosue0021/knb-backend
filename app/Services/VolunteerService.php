<?php

namespace App\Services;

use App\Models\Project;
use App\Models\VolunteerRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class VolunteerService
{

    /**
     * Summary of getAllRequests
     * @return \Illuminate\Database\Eloquent\Collection<int, VolunteerRequest>
     */
    public function getAllRequests()
    {
        return VolunteerRequest::with('project')->latest()->get();
    }

    /**
     * Summary of getRequestsByUserId
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection<int, VolunteerRequest>
     */
    public function getRequestsByUserId (int $userId) {
        return VolunteerRequest::with('project')
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }

    /**
     * Get all approved volunteer requests for a specific project.
     *
     * @param int $projectId
     * @return \Illuminate\Database\Eloquent\Collection<int, VolunteerRequest>
     */
    public function getApprovedByProjectId(int $projectId)
    {
        return VolunteerRequest::with(['project'])
            ->where('project_id', $projectId)
            ->where('status', 'approved')
            ->latest()
            ->get();
    }

    /**
     * Summary of volunter
     * @param array $data
     * @param int $project_id
     * @return VolunteerRequest
     */
    public function volunteer(array $data, int $project_id)
    {
        // Allow optional auth: try API guard first, then default
        $user = auth('sanctum')->user() ?? Auth::user();

        $payload = array_merge($data, [
            'project_id' => $project_id,
            'status' => 'pending',
            'is_user' => false,
            'is_member' => false,
            'user_id' => null,
            'member_number' => null,
        ]);

        if ($user) {
            // Use verified user profile details when available
            $payload['first_name'] = $user->first_name ?? $payload['first_name'];
            $payload['middle_name'] = $user->middle_name ?? $payload['middle_name'] ?? null;
            $payload['last_name'] = $user->last_name ?? $payload['last_name'];
            $payload['contact_number'] = $user->contact_number ?? $payload['contact_number'];
            $payload['email'] = $user->email ?? $payload['email'];

            $payload['is_user'] = true;
            $payload['user_id'] = $user->id;

            $member = $user->member;
            if ($member) {
                $payload['is_member'] = true;
                $payload['member_number'] = $member->member_number ?? null;
            }
        }

        $volunteerRequest = VolunteerRequest::create($payload);

        if (!empty($payload['email'])) {
            $projectName = Project::find($project_id)?->title ?? 'the project';

            Mail::send(
                'emails.volunteer.received',
                [
                    'first_name' => $payload['first_name'],
                    'project_name' => $projectName,
                ],
                function ($msg) use ($payload) {
                    $msg->to($payload['email'])
                        ->subject('Volunteer Request Received');
                }
            );
        }

        return $volunteerRequest;
    }

    /**
     * Summary of approve
     * @param int $requestId
     * @return VolunteerRequest
     */
    public function approve(int $requestId)
    {
        $request = VolunteerRequest::with('project')->findOrFail($requestId);
        $request->update(['status' => 'approved']);

        if (!empty($request->email)) {
            $projectName = $request->project?->title ?? 'the project';

            Mail::send(
                'emails.volunteer.approved',
                [
                    'first_name' => $request->first_name,
                    'project_name' => $projectName,
                ],
                function ($msg) use ($request) {
                    $msg->to($request->email)
                        ->subject('Volunteer Request Approved');
                }
            );
        }

        return $request;
    }

    /**
     * Summary of reject
     * @param int $requestId
     * @return VolunteerRequest
     */
    public function reject(int $requestId)
    {
        $request = VolunteerRequest::with('project')->findOrFail($requestId);
        $request->update(['status' => 'rejected']);

        if (!empty($request->email)) {
            $projectName = $request->project?->title ?? 'the project';

            Mail::send(
                'emails.volunteer.rejected',
                [
                    'first_name' => $request->first_name,
                    'project_name' => $projectName,
                ],
                function ($msg) use ($request) {
                    $msg->to($request->email)
                        ->subject('Volunteer Request Update');
                }
            );
        }

        return $request;
    }

    /**
     * Summary of delete
     * @param int $requestId
     * @return void
     */
    public function delete(int $requestId) {
        VolunteerRequest::findOrFail($requestId)->delete();
    }
}
