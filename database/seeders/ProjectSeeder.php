<?php

namespace Database\Seeders;

use App\Models\Log;
use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample projects
        $projects = [
            [
                'name' => 'Main Website',
                'retention_days' => 30,
            ],
            [
                'name' => 'Mobile API',
                'retention_days' => 14,
            ],
            [
                'name' => 'Admin Dashboard',
                'retention_days' => 90,
            ],
            [
                'name' => 'Legacy System',
                'retention_days' => -1, // Infinite retention
            ],
        ];

        foreach ($projects as $projectData) {
            $project = Project::factory()->create($projectData);

            $this->command->info("Created project: {$project->name} (Key: {$project->magic_key})");

            // Create sample logs for each project
            $this->createLogsForProject($project);
        }
    }

    /**
     * Create sample logs for a project.
     */
    protected function createLogsForProject(Project $project): void
    {
        // Create a mix of log levels
        Log::factory()
            ->count(50)
            ->info()
            ->forProject($project)
            ->create();

        Log::factory()
            ->count(30)
            ->debug()
            ->forProject($project)
            ->create();

        Log::factory()
            ->count(15)
            ->error()
            ->forProject($project)
            ->create();

        Log::factory()
            ->count(5)
            ->critical()
            ->forProject($project)
            ->create();

        $this->command->info("  → Created 100 sample logs");
    }
}
