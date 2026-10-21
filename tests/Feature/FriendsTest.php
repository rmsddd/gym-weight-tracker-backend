<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Exercise;
use App\Models\Friendship;
use App\Models\PendingRegistration;
use App\Models\User;
use App\Models\Workout;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FriendsTest extends TestCase
{
    use RefreshDatabase;

    private function befriend(User $a, User $b): Friendship
    {
        return Friendship::create(['requester_id' => $a->id, 'addressee_id' => $b->id, 'status' => 'accepted']);
    }

    /** A workout with one exercise and one set, as its owner would have logged it. */
    private function workoutFor(User $user, array $attributes = []): Workout
    {
        $workout = Workout::create([
            'user_id' => $user->id,
            'name' => 'Push Day',
            'date' => '2026-10-21',
            'note' => 'felt weak today',
            'started_at' => now()->subMinutes(30),
            ...$attributes,
        ]);
        $exercise = Exercise::create(['name' => 'Bench Press', 'category_id' => Category::create(['name' => 'Chest'])->id]);
        $workout->workoutExercises()->create(['exercise_id' => $exercise->id])
            ->sets()->create(['weight' => 80, 'reps' => 8]);

        return $workout;
    }

    public function test_social_routes_need_a_login(): void
    {
        $this->getJson('/api/friends')->assertUnauthorized();
        $this->getJson('/api/users/search?q=re')->assertUnauthorized();
        $this->getJson('/api/friend-requests')->assertUnauthorized();
        $this->patchJson('/api/user/username', ['username' => 'remus'])->assertUnauthorized();
    }

    public function test_an_account_without_a_username_chooses_one_once(): void
    {
        $user = User::factory()->create(['username' => null]);
        Sanctum::actingAs($user);

        $this->getJson('/api/friends')->assertForbidden()->assertJsonPath('message', 'Choose a username first.');

        $this->patchJson('/api/user/username', ['username' => '  Remus.D '])
            ->assertOk()
            ->assertJsonPath('username', 'remus.d');

        $this->getJson('/api/friends')->assertOk();
        $this->patchJson('/api/user/username', ['username' => 'someone'])->assertForbidden();
        $this->assertSame('remus.d', $user->fresh()->username);
    }

    public function test_username_rules(): void
    {
        User::factory()->create(['username' => 'taken']);
        Sanctum::actingAs(User::factory()->create(['username' => null]));

        $this->patchJson('/api/user/username', ['username' => 'TAKEN'])
            ->assertUnprocessable()->assertJsonPath('errors.username.0', 'This username is taken.');
        $this->patchJson('/api/user/username', ['username' => 'ab'])->assertUnprocessable();
        $this->patchJson('/api/user/username', ['username' => str_repeat('a', 21)])->assertUnprocessable();
        $this->patchJson('/api/user/username', ['username' => 'no spaces'])
            ->assertUnprocessable()->assertJsonPath('errors.username.0', 'Use only letters, digits, dots and underscores.');
        $this->patchJson('/api/user/username', ['username' => 'lift_er.99'])->assertOk();
    }

    public function test_the_display_name_can_change(): void
    {
        $user = User::factory()->create(['name' => 'Old', 'username' => 'remus']);
        Sanctum::actingAs($user);

        $this->patchJson('/api/user/name', ['name' => 'Remus D'])->assertOk()->assertJsonPath('name', 'Remus D');
        $this->patchJson('/api/user/name', ['name' => ''])->assertUnprocessable();
        $this->assertSame('remus', $user->fresh()->username);
    }

    public function test_search_finds_usernames_prefix_first_without_emails(): void
    {
        $me = User::factory()->create(['username' => 'remus']);
        User::factory()->create(['username' => 'aremi']);
        User::factory()->create(['username' => 'remington']);
        User::factory()->create(['username' => 'bob']);
        User::factory()->create(['username' => null, 'name' => 'Rem Without Username']);
        Sanctum::actingAs($me);

        $response = $this->getJson('/api/users/search?q=REM')->assertOk();

        $this->assertSame(['remington', 'aremi'], array_column($response->json('data'), 'username'));
        $this->assertArrayNotHasKey('email', $response->json('data.0'));
        $this->getJson('/api/users/search?q=r')->assertUnprocessable();
    }

    public function test_search_says_where_the_viewer_stands(): void
    {
        $me = User::factory()->create(['username' => 'me']);
        $sent = User::factory()->create(['username' => 'sent_x']);
        $asked = User::factory()->create(['username' => 'asked_x']);
        $friend = User::factory()->create(['username' => 'friend_x']);
        Friendship::create(['requester_id' => $me->id, 'addressee_id' => $sent->id]);
        Friendship::create(['requester_id' => $asked->id, 'addressee_id' => $me->id]);
        $this->befriend($friend, $me);
        Sanctum::actingAs($me);

        $states = collect($this->getJson('/api/users/search?q=_x')->json('data'))->pluck('friendship', 'username');

        $this->assertSame(['asked_x' => 'incoming', 'friend_x' => 'friends', 'sent_x' => 'outgoing'], $states->sortKeys()->all());
    }

    public function test_a_request_is_sent_accepted_and_both_become_friends(): void
    {
        $me = User::factory()->create(['username' => 'me']);
        $other = User::factory()->create(['username' => 'other']);

        Sanctum::actingAs($me);
        $id = $this->postJson('/api/friend-requests', ['user_id' => $other->id])->assertCreated()->json('data.id');
        $this->postJson('/api/friend-requests', ['user_id' => $other->id])
            ->assertUnprocessable()->assertJsonPath('errors.user_id.0', 'You already sent a request.');
        $this->postJson('/api/friend-requests', ['user_id' => $me->id])->assertUnprocessable();
        $this->getJson('/api/friend-requests')->assertJsonPath('data.outgoing.0.user.username', 'other');
        // The requester cannot accept their own request
        $this->postJson("/api/friend-requests/$id/accept")->assertForbidden();

        Sanctum::actingAs($other);
        $this->getJson('/api/friend-requests')
            ->assertJsonPath('data.incoming.0.user.username', 'me')
            ->assertJsonMissingPath('data.incoming.0.user.email');
        $this->postJson("/api/friend-requests/$id/accept")->assertNoContent();

        $this->getJson('/api/friends')->assertJsonPath('data.0.username', 'me');
        Sanctum::actingAs($me);
        $this->getJson('/api/friends')->assertJsonPath('data.0.username', 'other');
        $this->getJson('/api/friend-requests')->assertJsonCount(0, 'data.outgoing');
    }

    public function test_asking_someone_who_already_asked_you_accepts_their_request(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        Friendship::create(['requester_id' => $other->id, 'addressee_id' => $me->id]);
        Sanctum::actingAs($me);

        $this->postJson('/api/friend-requests', ['user_id' => $other->id])
            ->assertCreated()->assertJsonPath('data.status', 'accepted');
        $this->assertDatabaseCount('friendships', 1);
    }

    public function test_requests_can_be_declined_or_cancelled_and_friends_removed(): void
    {
        [$me, $a, $b, $c, $stranger] = User::factory()->count(5)->create()->all();
        $incoming = Friendship::create(['requester_id' => $a->id, 'addressee_id' => $me->id]);
        $outgoing = Friendship::create(['requester_id' => $me->id, 'addressee_id' => $b->id]);
        $this->befriend($me, $c);

        Sanctum::actingAs($stranger);
        $this->deleteJson("/api/friend-requests/{$incoming->id}")->assertForbidden();

        Sanctum::actingAs($me);
        $this->deleteJson("/api/friend-requests/{$incoming->id}")->assertNoContent();
        $this->deleteJson("/api/friend-requests/{$outgoing->id}")->assertNoContent();
        $this->deleteJson("/api/friends/{$stranger->username}")->assertNotFound();
        $this->deleteJson("/api/friends/{$c->username}")->assertNoContent();

        $this->assertDatabaseCount('friendships', 0);
    }

    public function test_only_friends_see_a_users_workouts_and_never_the_note(): void
    {
        $me = User::factory()->create();
        $friend = User::factory()->create();
        $stranger = User::factory()->create();
        $this->befriend($me, $friend);
        $this->workoutFor($friend, ['completed_at' => now(), 'finished_at' => now()]);
        $this->workoutFor($stranger);
        Sanctum::actingAs($me);

        $this->getJson("/api/friends/{$stranger->username}")->assertForbidden();

        $response = $this->getJson("/api/friends/{$friend->username}")
            ->assertOk()
            ->assertJsonPath('data.online', false)
            ->assertJsonPath('data.live_workout', null)
            ->assertJsonPath('data.history.0.exercises.0.sets.0.weight', 80)
            ->assertJsonMissingPath('data.user.email');
        $this->assertStringNotContainsString('felt weak today', $response->getContent());
    }

    public function test_a_friend_is_online_until_they_press_finish(): void
    {
        $me = User::factory()->create();
        $friend = User::factory()->create();
        $this->befriend($me, $friend);
        $workout = $this->workoutFor($friend);
        $older = $this->workoutFor($friend, ['completed_at' => now(), 'started_at' => now()->subDay()]);
        Sanctum::actingAs($me);

        $this->getJson('/api/friends')
            ->assertJsonPath('data.0.online', true)
            ->assertJsonPath('data.0.live_workout.name', 'Push Day');
        $this->getJson("/api/friends/{$friend->username}")
            ->assertJsonPath('data.live_workout.id', $workout->id)
            ->assertJsonPath('data.live_workout.exercises.0.sets.0.reps', 8)
            ->assertJsonPath('data.history.0.id', $older->id)
            ->assertJsonCount(1, 'data.history');

        // Stop keeps them online; resume restarts the time friends see
        Sanctum::actingAs($friend);
        $this->postJson("/api/workouts/{$workout->id}/finish")->assertOk();
        Sanctum::actingAs($me);
        $this->getJson('/api/friends')->assertJsonPath('data.0.online', true);

        Sanctum::actingAs($friend);
        $this->postJson("/api/workouts/{$workout->id}/resume")->assertOk();
        $this->assertNotNull($workout->fresh()->resumed_at);

        $this->postJson("/api/workouts/{$workout->id}/complete")->assertOk();
        Sanctum::actingAs($me);
        $this->getJson('/api/friends')->assertJsonPath('data.0.online', false)->assertJsonPath('data.0.live_workout', null);
        $this->getJson("/api/friends/{$friend->username}")->assertJsonCount(2, 'data.history');
    }

    public function test_the_friend_list_puts_online_friends_first(): void
    {
        $me = User::factory()->create();
        $anna = User::factory()->create(['name' => 'Anna']);
        $zack = User::factory()->create(['name' => 'Zack']);
        $this->befriend($me, $anna);
        $this->befriend($zack, $me);
        $this->workoutFor($zack);
        Sanctum::actingAs($me);

        $this->assertSame(['Zack', 'Anna'], array_column($this->getJson('/api/friends')->json('data'), 'name'));
    }

    public function test_a_friend_cannot_change_your_workouts(): void
    {
        $me = User::factory()->create();
        $friend = User::factory()->create();
        $this->befriend($me, $friend);
        $workout = $this->workoutFor($friend);
        Sanctum::actingAs($me);

        $this->getJson("/api/workouts/{$workout->id}")->assertForbidden();
        $this->deleteJson("/api/workouts/{$workout->id}")->assertForbidden();
        $this->postJson("/api/workouts/{$workout->id}/complete")->assertForbidden();
    }

    public function test_signing_up_keeps_the_username_unless_someone_took_it_first(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Lifter', 'username' => 'Lifter', 'email' => 'a@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertStatus(202);
        $this->assertDatabaseHas('pending_registrations', ['email' => 'a@example.com', 'username' => 'lifter']);

        $service = app(RegistrationService::class);
        $pending = PendingRegistration::first();
        User::factory()->create(['username' => 'lifter']);

        $this->assertTrue($service->confirm($pending->id, sha1('a@example.com')));
        $this->assertNull(User::where('email', 'a@example.com')->value('username'));
    }
}
