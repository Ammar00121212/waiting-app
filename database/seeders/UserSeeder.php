<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultPassword = 'password123';

        User::query()->updateOrCreate(
            ['email' => 'admin@waitingapp.com'],
            [
                'name' => 'Clinic Admin',
                'password' => Hash::make($defaultPassword),
                'is_super_admin' => true,
                'category_id' => null,
            ]
        );

        // Create one department-scoped admin per active department.
        $departments = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        foreach ($departments as $dept) {
            $slug = Str::slug((string) $dept->name);
            $email = $slug ? ($slug.'@waitingapp.com') : ('dept-'.$dept->id.'@waitingapp.com');

            User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $dept->name.' Admin',
                    'password' => Hash::make($defaultPassword),
                    'is_super_admin' => false,
                    'category_id' => (int) $dept->id,
                ]
            );
        }
    }
}
