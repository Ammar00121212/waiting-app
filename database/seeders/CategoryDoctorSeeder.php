<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Doctor;
use Illuminate\Database\Seeder;

class CategoryDoctorSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'General',
            'Surgeon',
            'Dentist',
            'ENT',
            'Cardiologist',
        ];

        $categoryIds = [];
        foreach ($categories as $name) {
            $category = Category::query()->updateOrCreate(
                ['name' => $name],
                ['description' => null, 'is_active' => true]
            );
            $categoryIds[$name] = (int) $category->id;
        }

        $doctors = [
            ['name' => 'Dr. Amina Hassan', 'email' => 'amina@waitingapp.com', 'category' => 'General', 'availability' => 'Mon–Fri, 9:00–15:00'],
            ['name' => 'Dr. Omar Khalid', 'email' => 'omar@waitingapp.com', 'category' => 'Surgeon', 'availability' => 'Mon–Thu, 10:00–14:00'],
            ['name' => 'Dr. Lina Farouk', 'email' => 'lina@waitingapp.com', 'category' => 'Dentist', 'availability' => 'Sun–Thu, 9:00–13:00'],
            ['name' => 'Dr. Youssef Nabil', 'email' => 'youssef@waitingapp.com', 'category' => 'ENT', 'availability' => 'Tue–Sat, 11:00–16:00'],
            ['name' => 'Dr. Sara Mahmoud', 'email' => 'sara@waitingapp.com', 'category' => 'Cardiologist', 'availability' => 'Mon–Wed, 9:00–12:00'],
        ];

        foreach ($doctors as $doc) {
            Doctor::query()->updateOrCreate(
                ['email' => $doc['email']],
                [
                    'category_id' => $categoryIds[$doc['category']],
                    'name' => $doc['name'],
                    'phone' => null,
                    'room_number' => null,
                    'is_active' => true,
                    'availability' => $doc['availability'],
                ]
            );
        }
    }
}

