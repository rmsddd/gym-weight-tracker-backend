<?php

namespace Database\Seeders;

use App\Models\Exercise;
use App\Models\Friendship;
use App\Models\User;
use App\Models\Workout;
use App\Services\WorkoutService;
use Illuminate\Database\Seeder;

/**
 * Local test data for Friends, never for production: a test account to log in with, three friends
 * (one training, one stopped, one offline) and one friend request waiting for it.
 * Run again to reset it: php artisan db:seed --class=DemoFriendsSeeder
 */
class DemoFriendsSeeder extends Seeder
{
    // Log in to the local app with these
    public const EMAIL = 'tester@demo.local';
    public const PASSWORD = 'password';

    public function run(): void
    {
        $me = $this->user('tester', 'Tester Demo', 'dino', self::EMAIL);
        $andrei = $this->user('andrei.p', 'Andrei Pop', 'lion');
        $ioana = $this->user('ioana_m', 'Ioana M.', 'dog');
        $mihai = $this->user('mihai.d', 'Mihai Dobre', 'bear');
        $bianca = $this->user('bianca', 'Bianca R.', 'tiger');

        Friendship::involving($me->id)->delete();
        foreach ([$andrei, $ioana, $mihai] as $friend) {
            Friendship::create(['requester_id' => $friend->id, 'addressee_id' => $me->id, 'status' => 'accepted']);
        }
        Friendship::create(['requester_id' => $bianca->id, 'addressee_id' => $me->id]);

        $service = app(WorkoutService::class);
        Workout::whereIn('user_id', [$andrei->id, $ioana->id, $mihai->id])->each(fn ($w) => $service->delete($w));

        $bench = Exercise::where('name', 'like', '%Bench Press%')->first() ?? Exercise::first();
        $rdl = Exercise::where('name', 'like', 'Romanian%')->first() ?? Exercise::skip(1)->first();
        $day = fn (int $daysAgo) => now()->subDays($daysAgo);

        $this->workout($andrei, 'Push Day', $bench, [[80, 8], [82.5, 8]], ['started_at' => now()->subMinutes(47)]);
        $this->workout($ioana, 'Legs', $rdl, [[70, 10]], ['started_at' => now()->subMinutes(80), 'finished_at' => now()->subMinutes(15)]);
        $this->workout($andrei, 'Legs', $rdl, [[100, 5]], $this->done($day(2), 55));
        $this->workout($mihai, 'Pull Day', $rdl, [[60, 10], [65, 8]], $this->done($day(1), 60));
    }

    private function user(string $username, string $name, string $avatar, ?string $email = null): User
    {
        $user = User::firstOrCreate(['email' => $email ?? "$username@demo.local"], [
            'name' => $name,
            'password' => self::PASSWORD,
            'avatar' => $avatar,
            'email_verified_at' => now(),
        ]);
        $user->forceFill(['username' => $username])->save();

        return $user;
    }

    private function done($startedAt, int $minutes): array
    {
        $end = $startedAt->copy()->addMinutes($minutes);

        return [
            'date' => $startedAt->toDateString(),
            'started_at' => $startedAt,
            'finished_at' => $end,
            'completed_at' => $end,
            'duration_minutes' => $minutes,
        ];
    }

    private function workout(User $user, string $name, Exercise $exercise, array $sets, array $attributes): void
    {
        $workout = Workout::create(['user_id' => $user->id, 'name' => $name, 'date' => now()->toDateString(), ...$attributes]);
        $workoutExercise = $workout->workoutExercises()->create(['exercise_id' => $exercise->id, 'position' => 0]);
        foreach ($sets as [$weight, $reps]) {
            $workoutExercise->sets()->create(['weight' => $weight, 'reps' => $reps]);
        }
    }
}
