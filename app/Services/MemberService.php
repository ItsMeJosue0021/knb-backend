<?php

namespace App\Services;

use App\Repositories\Interfaces\MemberRepositoryInterface;

class MemberService
{
    protected $memberRepository;

    public function __construct(MemberRepositoryInterface $memberRepository)
    {
        $this->memberRepository = $memberRepository;
    }

    /**
     * Summary of getAllMembers
     */
    public function getAllMembers()
    {
        return $this->memberRepository->getAll();
    }

    /**
     * Summary of findMemberById
     * @param mixed $id
     */
    public function findMemberById($id)
    {
        return $this->memberRepository->find($id);
    }

    /**
     * Summary of createMember
     * @param array $data
     */
    public function createMember(array $data)
    {
        return $this->memberRepository->create($data);
    }

    /**
     * Summary of updateMember
     * @param mixed $id
     * @param array $data
     */
    public function updateMember($id, array $data)
    {
        return $this->memberRepository->update($id, $data);
    }

    /**
     * Summary of deleteMember
     * @param mixed $id
     */
    public function deleteMember($id)
    {
        return $this->memberRepository->delete($id);
    }
}
