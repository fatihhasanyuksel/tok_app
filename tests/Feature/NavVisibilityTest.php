<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use App\Models\Teacher;
use PHPUnit\Framework\Attributes\Test;

class NavVisibilityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function regular_teacher_sees_basic_actions(): void
    {
        $t = Teacher::create([
            'name'     => 'T',
            'email'    => 't@example.com',
            'password' => Hash::make('Password123!'),
            'active'   => 1,
            'is_admin' => 0,
        ]);

        $this->withSession(['teacher_id' => $t->id])
             ->get(route('reflections.index'))
             ->assertOk()
             ->assertSee('New Reflection')
             ->assertSee('Students')
             ->assertDontSee('Manage Teachers')   // admin only
             ->assertDontSee('Transfer');         // admin only
    }

    #[Test]
    public function admin_sees_admin_actions(): void
    {
        $t = Teacher::create([
            'name'     => 'Admin',
            'email'    => 'a@example.com',
            'password' => Hash::make('Password123!'),
            'active'   => 1,
            'is_admin' => 1,
        ]);

        $this->withSession(['teacher_id' => $t->id])
             ->get(route('reflections.index'))
             ->assertOk()
             ->assertSee('Manage Teachers')
             ->assertSee('Transfer');
    }
}