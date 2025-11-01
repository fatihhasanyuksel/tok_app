<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use App\Models\Teacher;
use App\Models\Reflection;
use PHPUnit\Framework\Attributes\Test;

class ReflectionCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function loginTeacher(array $overrides = []): Teacher
    {
        $t = Teacher::create(array_merge([
            'name'     => 'Teach',
            'email'    => 'teach@example.com',
            'password' => Hash::make('Password123!'),
            'active'   => 1,
            'is_admin' => 0,
        ], $overrides));

        // Simulate logged-in teacher via session
        $this->withSession(['teacher_id' => $t->id]);

        return $t;
    }

    #[Test]
    public function can_create_edit_and_delete_a_reflection(): void
    {
        $teacher = $this->loginTeacher();

        // Create reflection
        $this->post(route('reflections.store'), [
            'title' => 'My First',
            'body'  => 'Hello world',
        ])->assertRedirect(route('reflections.index'));

        $this->assertDatabaseHas('reflections', [
            'teacher_id' => $teacher->id,
            'title'      => 'My First',
            'status'     => 'draft',
        ]);

        $reflection = Reflection::first();

        // Edit page
        $this->get(route('reflections.edit', $reflection))
             ->assertOk()
             ->assertSee('Edit Reflection');

        // Update reflection
        $this->put(route('reflections.update', $reflection), [
            'title' => 'Updated Title',
            'body'  => 'Updated body',
        ])->assertRedirect(route('reflections.index'));

        $this->assertDatabaseHas('reflections', [
            'id'    => $reflection->id,
            'title' => 'Updated Title',
        ]);

        // Delete reflection
        $this->delete(route('reflections.destroy', $reflection))
             ->assertRedirect(route('reflections.index'));

        $this->assertDatabaseMissing('reflections', ['id' => $reflection->id]);
    }
}