<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:admin 
                            {--name= : The name of the admin user}
                            {--email= : The email address of the admin user}
                            {--password= : The password for the admin user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an initial admin user account for the application';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('');
        $this->info('╔════════════════════════════════════════╗');
        $this->info('║       Logger Admin Setup Wizard        ║');
        $this->info('╚════════════════════════════════════════╝');
        $this->info('');

        // Check if admin already exists
        $existingAdmin = User::where('role', User::ROLE_ADMIN)->first();
        if ($existingAdmin) {
            if (!$this->confirm('An admin user already exists (' . $existingAdmin->email . '). Do you want to create another one?', false)) {
                $this->info('Setup cancelled.');
                return Command::SUCCESS;
            }
        }

        // Get name
        $name = $this->option('name') ?? $this->ask('Enter admin name');
        
        // Validate name
        $validator = Validator::make(['name' => $name], [
            'name' => 'required|string|min:2|max:255',
        ]);
        
        if ($validator->fails()) {
            $this->error('Invalid name: ' . implode(', ', $validator->errors()->all()));
            return Command::FAILURE;
        }

        // Get email
        $email = $this->option('email') ?? $this->ask('Enter admin email');
        
        // Validate email
        $validator = Validator::make(['email' => $email], [
            'email' => 'required|email|unique:users,email',
        ]);
        
        if ($validator->fails()) {
            $this->error('Invalid email: ' . implode(', ', $validator->errors()->all()));
            return Command::FAILURE;
        }

        // Get password
        $password = $this->option('password') ?? $this->secret('Enter admin password (min 8 characters)');
        
        // Validate password
        $validator = Validator::make(['password' => $password], [
            'password' => 'required|string|min:8',
        ]);
        
        if ($validator->fails()) {
            $this->error('Invalid password: ' . implode(', ', $validator->errors()->all()));
            return Command::FAILURE;
        }

        // Confirm password if entered interactively
        if (!$this->option('password')) {
            $confirmPassword = $this->secret('Confirm password');
            if ($password !== $confirmPassword) {
                $this->error('Passwords do not match.');
                return Command::FAILURE;
            }
        }

        // Create the admin user
        try {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => now(),
            ]);

            $this->info('');
            $this->info('✓ Admin user created successfully!');
            $this->info('');
            $this->table(
                ['Field', 'Value'],
                [
                    ['Name', $user->name],
                    ['Email', $user->email],
                    ['Role', $user->role],
                    ['Created', $user->created_at->format('Y-m-d H:i:s')],
                ]
            );
            $this->info('');
            $this->info('You can now log in at: ' . url('/login'));
            $this->info('');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to create admin user: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
