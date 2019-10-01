<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function actingAs(Authenticatable $user, $driver = null)
    {
        $token = auth()->fromUser($user);
        $this->withHeader('Authorization', 'Bearer '.$token);

        return $this;
    }
}
