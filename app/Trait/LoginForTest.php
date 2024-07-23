<?php

namespace App\Trait;

use App\Models\User;

trait LoginForTest
{
    public function loginForTest(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
    }
}
