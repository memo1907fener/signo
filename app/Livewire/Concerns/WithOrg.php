<?php

namespace App\Livewire\Concerns;

use App\Models\Organization;

trait WithOrg
{
    protected function org(): Organization
    {
        $org = auth()->user()?->organization;
        abort_unless($org, 403);
        return $org;
    }
}
