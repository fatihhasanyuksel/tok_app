<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use App\Models\Teacher;
use PHPUnit\Framework\Attributes\Test;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guest_is_redirected_to_login(): void
    {
        $this->get(route('reflections.index'))
             ->assertRedirect(route('login'));
    }

    #[Test]
    public function teacher_can_login_and_is_redirected_to_reflections(): void
    {
        $t = Teacher::create([
            'name'     => 'Hasan',
            'email'    => 'hasan@example.com',
            'password' => Hash::make('Password123!'),
            'active'   => 1,
            'is_admin' => 1,
        ]);

        $this->post(route('teacher.login.submit'), [
                'email'    => $t->email,
                'password' => 'Password123!',
            ])
            ->assertRedirect(route('reflections.index'));

        // After redirect, reflections page should load for the same session
        $this->get(route('reflections.index'))
             ->assertOk()
             ->assertSee('Reflections');
    }

    #[Test]
    public function inactive_teacher_is_rejected(): void
    {
        // No factory needed — create directly
        $t = Teacher::create([
            'name'     => 'Inactive T',
            'email'    => 'inactive@example.com',
            'password' => Hash::make('Password123!'),
            'active'   => 0,
            'is_admin' => 0,
        ]);

        $this->post(route('teacher.login.submit'), [
                'email'    => $t->email,
                'password' => 'Password123!',
            ])
            ->assertSessionHasErrors(); // Controller returns back()->withErrors(...)
    }
}