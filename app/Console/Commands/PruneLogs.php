<?php

namespace App\Console\Commands;

use App\Models\Log;
use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:prune-logs 
                            {--chunk=1000 : The number of records to delete per chunk}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune old log entries based on project retention policies';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $chunkSize = (int) $this->option('chunk');
        $dryRun = $this->option('dry-run');

        $this->info('Starting log pruning...');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No records will be deleted');
        }

        // Get projects with finite retention (retention_days > 0)
        $projects = Project::where('retention_days', '>', 0)->get();

        if ($projects->isEmpty()) {
            $this->info('No projects with retention policies found.');

            return self::SUCCESS;
        }

        $totalDeleted = 0;

        foreach ($projects as $project) {
            $deleted = $this->pruneProjectLogs($project, $chunkSize, $dryRun);
            $totalDeleted += $deleted;
        }

        $this->newLine();
        $action = $dryRun ? 'Would delete' : 'Deleted';
        $this->info("{$action} {$totalDeleted} log entries in total.");

        return self::SUCCESS;
    }

    /**
     * Prune logs for a specific project.
     */
    protected function pruneProjectLogs(Project $project, int $chunkSize, bool $dryRun): int
    {
        $cutoffDate = now()->subDays($project->retention_days);

        $this->line("Processing project: {$project->name} (retention: {$project->retention_days} days)");

        // Count logs to be deleted
        $count = Log::where('project_id', $project->id)
            ->where('created_at', '<', $cutoffDate)
            ->count();

        if ($count === 0) {
            $this->line("  → No logs older than {$project->retention_days} days");

            return 0;
        }

        $action = $dryRun ? 'Would delete' : 'Deleting';
        $this->line("  → {$action} {$count} log entries older than {$cutoffDate->toDateString()}");

        if ($dryRun) {
            return $count;
        }

        // Use chunked deletion to avoid memory issues with high volume
        $deleted = 0;

        do {
            // Delete in chunks using raw SQL for better performance
            $affectedRows = DB::table('logs')
                ->where('project_id', $project->id)
                ->where('created_at', '<', $cutoffDate)
                ->limit($chunkSize)
                ->delete();

            $deleted += $affectedRows;

            if ($affectedRows > 0) {
                $this->output->write('.');
            }

            // Small sleep to prevent database overload on high-volume systems
            usleep(10000); // 10ms

        } while ($affectedRows > 0);

        $this->newLine();
        $this->line("  → Deleted {$deleted} log entries");

        return $deleted;
    }
}
