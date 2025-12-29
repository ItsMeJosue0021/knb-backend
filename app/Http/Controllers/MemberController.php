<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use App\Services\MemberService;
use App\Services\EmergencyContactService;

class MemberController extends Controller
{
    protected $memberService;
    protected $emergencyContactService;

    public function __construct(MemberService $memberService, EmergencyContactService $emergencyContactService)
    {
        $this->memberService = $memberService;
        $this->emergencyContactService = $emergencyContactService;
    }

    public function index()
    {
        $members = $this->memberService->getAllMembers();
        return response()->json($members, 200);
    }

    public function show($id)
    {
        $member = $this->memberService->findMemberById($id);
        return response()->json($member);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'nick_name' => 'required|string|max:255',
            'address' => 'required|string|max:1000',
            'dob' => 'required|date',
            'civil_status' => 'required|string|max:50',
            'contact_number' => 'required|string|max:15',
            'fb_messenger_account' => 'nullable|string|max:255',

            'contact_person' => 'required|string|max:255',
            'cp_address' => 'required|string|max:500',
            'cp_contact_number' => 'required|string|max:15',
            'cp_fb_messenger_account' => 'nullable|string|max:255',
            'cp_relationship' => 'required|string|max:50',
        ]);


        $memberData = [
            'member_id' => $this->generateMemberId(),
            'first_name' => $request->input('first_name'),
            'middle_name' => $request->input('middle_name'),
            'last_name' => $request->input('last_name'),
            'nick_name' => $request->input('nick_name'),
            'address' => $request->input('address'),
            'dob' => $request->input('dob'),
            'civil_status' => $request->input('civil_status'),
            'contact_number' => $request->input('contact_number'),
            'fb_messenger_account' => $request->input('fb_messenger_account'),
        ];

        $member = $this->memberService->createMember($memberData);
        if (!$member) {
            return response()->json(['message' => 'Failed to create member'], 500);
        }

        $emergencyContactData = [
            'member_id' => $member->id,
            'contact_person' => $request->input('contact_person'),
            'address' => $request->input('cp_address'),
            'contact_number' => $request->input('cp_contact_number'),
            'fb_messenger_account' => $request->input('cp_fb_messenger_account'),
            'relationship' => $request->input('cp_relationship'),
        ];

        $emergencyContact = $this->emergencyContactService->createEmergencyContact($emergencyContactData);
        if (!$emergencyContact) {
            return response()->json(['message' => 'Failed to save contact person'], 500);
        }

        return response()->json(["message" => "Amember has been created"], 201);
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'nick_name' => 'required|string|max:255',
            'address' => 'required|string|max:1000',
            'dob' => 'required|date',
            'civil_status' => 'required|string|max:50',
            'contact_number' => 'required|string|max:15',
            'fb_messenger_account' => 'nullable|string|max:255',

            'contact_person' => 'required|string|max:255',
            'cp_address' => 'required|string|max:500',
            'cp_contact_number' => 'required|string|max:15',
            'cp_fb_messenger_account' => 'nullable|string|max:255',
            'cp_relationship' => 'required|string|max:50',
        ]);


        $memberData = [
            'first_name' => $request->input('first_name'),
            'middle_name' => $request->input('middle_name'),
            'last_name' => $request->input('last_name'),
            'nick_name' => $request->input('nick_name'),
            'address' => $request->input('address'),
            'dob' => $request->input('dob'),
            'civil_status' => $request->input('civil_status'),
            'contact_number' => $request->input('contact_number'),
            'fb_messenger_account' => $request->input('fb_messenger_account'),
        ];

        $member = $this->memberService->updateMember($id, $memberData);
        if (!$member) {
            return response()->json(['message' => 'Failed to create member'], 500);
        }

        $emergencyContactData = [
            'contact_person' => $request->input('contact_person'),
            'address' => $request->input('cp_address'),
            'contact_number' => $request->input('cp_contact_number'),
            'fb_messenger_account' => $request->input('cp_fb_messenger_account'),
            'relationship' => $request->input('cp_relationship'),
        ];

        $existingEmergencyContact = $member->emergencyContact;
        if ($existingEmergencyContact) {
            $emergencyContact = $this->emergencyContactService->updateEmergencyContact($existingEmergencyContact->id, $emergencyContactData);
        } else {
            $emergencyContact = $this->emergencyContactService->createEmergencyContact(array_merge(
                ['member_id' => $member->id],
                $emergencyContactData
            ));
        }
        if (!$emergencyContact) {
            return response()->json(['message' => 'Failed to save contact person'], 500);
        }

        return response()->json(["message" => "Member infor has been updated!"], 200);


    }

    public function destroy($id)
    {
        $this->memberService->deleteMember($id);
        return response()->json(null, 204);
    }

    public function search(Request $request)
    {
        $query = $request->input('search');

        $members = Member::where('first_name', 'like', "%{$query}%")
            ->orWhere('member_id', 'like', "%{$query}%")
            ->orWhere('middle_name', 'like', "%{$query}%")
            ->orWhere('last_name', 'like', "%{$query}%")
            ->orWhere('nick_name', 'like', "%{$query}%")
            ->orWhere('fb_messenger_account', 'like', "%{$query}%")
            ->get()->load('emergencyContact');

        return response()->json($members, 200);
    }

    public function generateMemberId()
    {
        $latestMember = Member::orderBy('id', 'desc')->first();

        if (!$latestMember || !$latestMember->member_id) {
            return 'MEM-0001';
        }

        // Extract numeric part, e.g. "0001" from "MEM-0001"
        $lastNumber = (int) str_replace('MEM-', '', $latestMember->member_id);

        // Increment and pad with zeroes
        $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);

        return 'MEM-' . $newNumber;
    }
}
