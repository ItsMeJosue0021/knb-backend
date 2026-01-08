<?php

namespace App\Services;

use App\Models\GoodsDonation;

class GoodsDonationService
{
    public function updateNameOrDescription(int $id, array $data): GoodsDonation
    {
        $donation = GoodsDonation::findOrFail($id);

        $update = [];
        if (array_key_exists('name', $data)) {
            $update['name'] = $data['name'];
        }
        if (array_key_exists('description', $data)) {
            $update['description'] = $data['description'];
        }

        if ($update) {
            $donation->update($update);
        }

        return $donation->fresh();
    }
}
