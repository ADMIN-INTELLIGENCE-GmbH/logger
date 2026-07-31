<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectDashboardServerStatsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Render the project dashboard for a project carrying the given server stats.
     */
    protected function dashboardWithStats(array $stats): \Illuminate\Testing\TestResponse
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'is_active' => true,
            'server_stats' => $stats,
            'last_server_stats_at' => now(),
        ]);
        $project->users()->attach($user->id, ['permission' => Project::PERMISSION_EDIT]);

        return $this->actingAs($user)->get(route('projects.dashboard', $project));
    }

    public function test_queue_error_is_surfaced_instead_of_reporting_zero_jobs(): void
    {
        $response = $this->dashboardWithStats([
            'queue' => ['error' => 'Could not fetch queue metrics'],
        ]);

        $response->assertOk();
        $response->assertSee('Could not fetch queue metrics');
        $response->assertDontSee('Jobs Waiting');
    }

    public function test_queue_size_is_still_shown_when_metrics_are_available(): void
    {
        $response = $this->dashboardWithStats([
            'queue' => ['size' => 4, 'connection' => 'redis'],
        ]);

        $response->assertOk();
        $response->assertSee('Jobs Waiting');
        $response->assertSee('redis');
    }

    public function test_database_error_is_surfaced(): void
    {
        $response = $this->dashboardWithStats([
            'database' => ['error' => 'Could not reach database'],
        ]);

        $response->assertOk();
        $response->assertSee('Could not reach database');
    }

    public function test_security_update_count_and_reboot_flag_are_displayed(): void
    {
        $response = $this->dashboardWithStats([
            'updates' => [
                'manager' => 'apt',
                'supported' => true,
                'total_count' => 12,
                'security_count' => 3,
                'reboot_required' => true,
                'packages' => [],
                'truncated' => false,
                'last_refresh' => '2026-07-28T04:12:03+00:00',
                'error' => null,
            ],
        ]);

        $response->assertOk();
        $response->assertSee('Security Updates');
        $response->assertSee('Reboot Required');
        $response->assertSee('apt');
    }

    public function test_pending_update_packages_are_listed(): void
    {
        $response = $this->dashboardWithStats([
            'updates' => [
                'manager' => 'apt',
                'supported' => true,
                'total_count' => 1,
                'security_count' => 1,
                'reboot_required' => false,
                'packages' => [
                    [
                        'name' => 'openssl',
                        'current_version' => '3.0.13-0ubuntu3.4',
                        'available_version' => '3.0.13-0ubuntu3.5',
                        'security' => true,
                    ],
                ],
                'truncated' => false,
                'error' => null,
            ],
        ]);

        $response->assertOk();
        $response->assertSee('openssl');
        $response->assertSee('3.0.13-0ubuntu3.4');
        $response->assertSee('3.0.13-0ubuntu3.5');
    }

    public function test_updates_error_is_surfaced(): void
    {
        $response = $this->dashboardWithStats([
            'updates' => [
                'manager' => 'unknown',
                'supported' => false,
                'error' => 'Package manager not supported',
            ],
        ]);

        $response->assertOk();
        $response->assertSee('Package manager not supported');
    }

    public function test_os_and_host_information_is_displayed(): void
    {
        $response = $this->dashboardWithStats([
            'instance_id' => 'Boxxy',
            'os' => [
                'family' => 'Linux',
                'name' => 'Ubuntu 24.04.1 LTS',
                'version' => '24.04',
                'distro_id' => 'ubuntu',
                'kernel' => '6.8.0-51-generic',
                'architecture' => 'x86_64',
            ],
            'host' => [
                'hostname' => 'web-01',
                'app_url' => 'https://app.example.com',
                'timezone' => 'UTC',
                'locale' => 'en',
                'server_software' => 'nginx/1.24.0',
                'php_sapi' => 'fpm-fcgi',
                'php_extensions' => ['curl', 'json', 'mbstring', 'pdo_mysql', 'redis'],
            ],
        ]);

        $response->assertOk();
        $response->assertSee('Ubuntu 24.04.1 LTS');
        $response->assertSee('6.8.0-51-generic');
        $response->assertSee('x86_64');
        $response->assertSee('web-01');
        $response->assertSee('nginx/1.24.0');
        $response->assertSee('fpm-fcgi');
        $response->assertSee('Boxxy');
    }

    public function test_php_extensions_are_listed(): void
    {
        $response = $this->dashboardWithStats([
            'host' => [
                'hostname' => 'web-01',
                'php_extensions' => ['curl', 'mbstring', 'pdo_mysql'],
            ],
        ]);

        $response->assertOk();
        $response->assertSee('pdo_mysql');
    }

    public function test_per_mount_disk_breakdown_is_displayed(): void
    {
        $response = $this->dashboardWithStats([
            'system' => [
                'disk_space' => [
                    'total' => 269490393088,
                    'free' => 239698132992,
                    'used' => 29792260096,
                    'percent_used' => 11.06,
                    'disks' => [
                        [
                            'path' => '/',
                            'total' => 269490393088,
                            'free' => 239698132992,
                            'used' => 29792260096,
                            'percent_used' => 11.06,
                        ],
                        [
                            'path' => '/boot/efi',
                            'total' => 536870912,
                            'free' => 499122176,
                            'used' => 37748736,
                            'percent_used' => 92.5,
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertSee('/boot/efi');
        $response->assertSee('92.5%');
    }

    public function test_npm_version_is_displayed_in_stack(): void
    {
        $response = $this->dashboardWithStats([
            'system' => [
                'node_version' => 'v24.11.1',
                'npm_version' => '11.6.2',
            ],
        ]);

        $response->assertOk();
        $response->assertSee('11.6.2');
    }

    public function test_non_numeric_size_values_do_not_crash_the_dashboard(): void
    {
        $response = $this->dashboardWithStats([
            'filesize' => ['weird.log' => 'not-a-number'],
            'foldersize' => ['cache' => 'also-bad'],
        ]);

        $response->assertOk();
    }

    public function test_folder_sizes_that_are_all_unreadable_do_not_crash_the_dashboard(): void
    {
        $response = $this->dashboardWithStats([
            'foldersize' => ['upload' => -1, 'email-assets' => -1],
        ]);

        $response->assertOk();
        $response->assertSee('upload');
    }

    public function test_full_shipper_payload_round_trips_from_api_to_dashboard(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['is_active' => true]);
        $project->users()->attach($user->id, ['permission' => Project::PERMISSION_EDIT]);

        $payload = [
            'timestamp' => '2025-12-18T16:53:12+00:00',
            'app_name' => 'ticket',
            'app_env' => 'local',
            'app_debug' => true,
            'instance_id' => 'Boxxy',
            'log_shipper_version' => '1.4.1',
            'system' => [
                'memory_usage' => 33554432,
                'memory_peak' => 33554432,
                'server_memory' => ['total' => 26841100288, 'free' => 23864168448, 'used' => 2976931840, 'percent_used' => 11.09],
                'cpu_usage' => 0.18,
                'php_version' => '8.4.15',
                'laravel_version' => '12.42.0',
                'uptime' => 25162,
                'disk_space' => [
                    'total' => 269490393088,
                    'free' => 239698132992,
                    'used' => 29792260096,
                    'percent_used' => 11.06,
                    'disks' => [
                        ['path' => '/', 'total' => 269490393088, 'free' => 239698132992, 'used' => 29792260096, 'percent_used' => 11.06],
                        ['path' => '/boot/efi', 'total' => 536870912, 'free' => 499122176, 'used' => 37748736, 'percent_used' => 7.03],
                    ],
                ],
                'node_version' => 'v24.11.1',
                'npm_version' => '11.6.2',
                'composer_outdated' => 6,
                'npm_outdated' => 9,
                'composer_audit' => 0,
                'npm_audit' => 0,
            ],
            'queue' => ['error' => 'Could not fetch queue metrics'],
            'database' => ['status' => 'connected', 'latency_ms' => 8.51],
            'cache' => [],
            'filesize' => [],
            'foldersize' => ['upload' => -1, 'cache' => 523732, 'email-assets' => -1],
            'os' => [
                'family' => 'Linux',
                'name' => 'Ubuntu 24.04.1 LTS',
                'version' => '24.04',
                'distro_id' => 'ubuntu',
                'kernel' => '6.8.0-51-generic',
                'architecture' => 'x86_64',
            ],
            'host' => [
                'hostname' => 'web-01',
                'app_url' => 'https://app.example.com',
                'timezone' => 'UTC',
                'locale' => 'en',
                'server_software' => 'nginx/1.24.0',
                'php_sapi' => 'fpm-fcgi',
                'php_extensions' => ['curl', 'json', 'mbstring', 'pdo_mysql', 'redis'],
            ],
            'updates' => [
                'manager' => 'apt',
                'supported' => true,
                'total_count' => 12,
                'security_count' => 3,
                'packages' => [
                    [
                        'name' => 'openssl',
                        'current_version' => '3.0.13-0ubuntu3.4',
                        'available_version' => '3.0.13-0ubuntu3.5',
                        'security' => true,
                    ],
                ],
                'truncated' => false,
                'reboot_required' => true,
                'last_refresh' => '2026-07-28T04:12:03+00:00',
                'error' => null,
            ],
        ];

        $this->postJson(route('api.stats'), $payload, ['X-Project-Key' => $project->magic_key])
            ->assertOk();

        $response = $this->actingAs($user)->get(route('projects.dashboard', $project));

        $response->assertOk();
        $response->assertSee('Boxxy');
        $response->assertSee('Could not fetch queue metrics');
        $response->assertDontSee('Jobs Waiting');
        $response->assertSee('Security Updates');
        $response->assertSee('Reboot Required');
        $response->assertSee('openssl');
        $response->assertSee('Ubuntu 24.04.1 LTS');
        $response->assertSee('6.8.0-51-generic');
        $response->assertSee('web-01');
        $response->assertSee('/boot/efi');
        $response->assertSee('pdo_mysql');
        $response->assertSee('11.6.2');
    }
}
