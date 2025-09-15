<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Milestone;
use App\Models\Task;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create demo users
        $owner = User::create([
            'name' => 'John Doe',
            'email' => 'admin@example.com',
            'username' => 'johndoe',
            'password' => Hash::make('password'),
            'timezone' => 'UTC',
        ]);

        $projectManager = User::create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'username' => 'janesmith',
            'password' => Hash::make('password'),
            'timezone' => 'UTC',
        ]);

        $developer1 = User::create([
            'name' => 'Mike Johnson',
            'email' => 'mike@example.com',
            'username' => 'mikejohnson',
            'password' => Hash::make('password'),
            'timezone' => 'UTC',
        ]);

        $developer2 = User::create([
            'name' => 'Sarah Wilson',
            'email' => 'sarah@example.com',
            'username' => 'sarahwilson',
            'password' => Hash::make('password'),
            'timezone' => 'UTC',
        ]);

        $designer = User::create([
            'name' => 'Alex Chen',
            'email' => 'alex@example.com',
            'username' => 'alexchen',
            'password' => Hash::make('password'),
            'timezone' => 'UTC',
        ]);

        // Create demo organization
        $organization = Organization::create([
            'name' => 'Acme Corporation',
            'slug' => 'acme-corp',
            'description' => 'A leading technology company focused on innovative solutions.',
            'plan' => 'premium',
            'max_users' => 100,
            'max_projects' => 50,
            'trial_ends_at' => now()->addDays(30),
        ]);

        // Add users to organization
        $organization->addUser($owner, 'owner');
        $organization->addUser($projectManager, 'project_manager');
        $organization->addUser($developer1, 'member');
        $organization->addUser($developer2, 'member');
        $organization->addUser($designer, 'member');

        // Create demo projects
        $webProject = Project::create([
            'organization_id' => $organization->id,
            'owner_id' => $owner->id,
            'name' => 'Company Website Redesign',
            'code' => 'ACM-001',
            'description' => 'Complete redesign of the company website with modern UI/UX and improved performance.',
            'color' => '#3b82f6',
            'status' => 'active',
            'priority' => 'high',
            'start_date' => now()->subDays(30),
            'end_date' => now()->addDays(60),
            'deadline' => now()->addDays(45),
            'budget' => 25000.00,
        ]);

        $mobileProject = Project::create([
            'organization_id' => $organization->id,
            'owner_id' => $projectManager->id,
            'name' => 'Mobile App Development',
            'code' => 'ACM-002',
            'description' => 'Native mobile application for iOS and Android platforms.',
            'color' => '#10b981',
            'status' => 'planning',
            'priority' => 'medium',
            'start_date' => now()->addDays(15),
            'end_date' => now()->addDays(120),
            'deadline' => now()->addDays(100),
            'budget' => 50000.00,
        ]);

        $apiProject = Project::create([
            'organization_id' => $organization->id,
            'owner_id' => $developer1->id,
            'name' => 'API Integration Platform',
            'code' => 'ACM-003',
            'description' => 'Develop a comprehensive API platform for third-party integrations.',
            'color' => '#f59e0b',
            'status' => 'active',
            'priority' => 'critical',
            'start_date' => now()->subDays(15),
            'end_date' => now()->addDays(75),
            'deadline' => now()->addDays(60),
            'budget' => 35000.00,
        ]);

        // Add team members to projects
        $webProject->addTeamMember($projectManager, 'manager');
        $webProject->addTeamMember($developer1, 'member');
        $webProject->addTeamMember($designer, 'member');

        $mobileProject->addTeamMember($owner, 'member');
        $mobileProject->addTeamMember($developer1, 'manager');
        $mobileProject->addTeamMember($developer2, 'member');

        $apiProject->addTeamMember($projectManager, 'member');
        $apiProject->addTeamMember($developer2, 'member');

        // Create milestones for web project
        $webMilestone1 = Milestone::create([
            'project_id' => $webProject->id,
            'name' => 'Design Phase Completion',
            'description' => 'Complete all UI/UX designs and wireframes',
            'status' => 'completed',
            'start_date' => now()->subDays(30),
            'due_date' => now()->subDays(15),
            'completed_at' => now()->subDays(12),
            'sort_order' => 1,
            'progress' => 100,
        ]);

        $webMilestone2 = Milestone::create([
            'project_id' => $webProject->id,
            'name' => 'Frontend Development',
            'description' => 'Implement responsive frontend with React',
            'status' => 'active',
            'start_date' => now()->subDays(15),
            'due_date' => now()->addDays(15),
            'sort_order' => 2,
            'progress' => 65,
        ]);

        $webMilestone3 = Milestone::create([
            'project_id' => $webProject->id,
            'name' => 'Backend Integration',
            'description' => 'Connect frontend with backend APIs',
            'status' => 'planning',
            'start_date' => now()->addDays(10),
            'due_date' => now()->addDays(35),
            'sort_order' => 3,
            'progress' => 0,
        ]);

        // Create milestones for API project
        $apiMilestone1 = Milestone::create([
            'project_id' => $apiProject->id,
            'name' => 'Core API Development',
            'description' => 'Build the foundational API infrastructure',
            'status' => 'active',
            'start_date' => now()->subDays(15),
            'due_date' => now()->addDays(20),
            'sort_order' => 1,
            'progress' => 45,
        ]);

        // Create tasks for web project
        $this->createWebProjectTasks($webProject, $webMilestone1, $webMilestone2, $webMilestone3, [
            'designer' => $designer,
            'developer1' => $developer1,
            'pm' => $projectManager
        ]);

        // Create tasks for mobile project
        $this->createMobileProjectTasks($mobileProject, [
            'owner' => $owner,
            'developer1' => $developer1,
            'developer2' => $developer2,
            'pm' => $projectManager
        ]);

        // Create tasks for API project
        $this->createApiProjectTasks($apiProject, $apiMilestone1, [
            'developer1' => $developer1,
            'developer2' => $developer2,
            'pm' => $projectManager
        ]);

        $this->command->info('Demo data seeded successfully!');
        $this->command->info('Login credentials:');
        $this->command->info('Email: admin@example.com | Password: password (Owner)');
        $this->command->info('Email: jane@example.com | Password: password (Project Manager)');
        $this->command->info('Email: mike@example.com | Password: password (Developer)');
    }

    /**
     * Create tasks for web project.
     */
    private function createWebProjectTasks($project, $milestone1, $milestone2, $milestone3, $users)
    {
        // Milestone 1 tasks (completed)
        Task::create([
            'project_id' => $project->id,
            'milestone_id' => $milestone1->id,
            'assignee_id' => $users['designer']->id,
            'created_by' => $users['pm']->id,
            'title' => 'Create wireframes for main pages',
            'description' => 'Design wireframes for homepage, about, services, and contact pages',
            'status' => 'completed',
            'priority' => 'high',
            'type' => 'task',
            'estimated_hours' => 16,
            'actual_hours' => 14,
            'start_date' => now()->subDays(30),
            'due_date' => now()->subDays(25),
            'completed_at' => now()->subDays(24),
            'tags' => json_encode(['design', 'wireframes', 'ux']),
            'sort_order' => 1,
            'progress' => 100,
        ]);

        Task::create([
            'project_id' => $project->id,
            'milestone_id' => $milestone1->id,
            'assignee_id' => $users['designer']->id,
            'created_by' => $users['pm']->id,
            'title' => 'Design UI mockups',
            'description' => 'Create high-fidelity UI mockups based on approved wireframes',
            'status' => 'completed',
            'priority' => 'high',
            'type' => 'task',
            'estimated_hours' => 24,
            'actual_hours' => 28,
            'start_date' => now()->subDays(25),
            'due_date' => now()->subDays(18),
            'completed_at' => now()->subDays(16),
            'tags' => json_encode(['design', 'ui', 'mockups']),
            'sort_order' => 2,
            'progress' => 100,
        ]);

        // Milestone 2 tasks (in progress)
        Task::create([
            'project_id' => $project->id,
            'milestone_id' => $milestone2->id,
            'assignee_id' => $users['developer1']->id,
            'created_by' => $users['pm']->id,
            'title' => 'Set up React project structure',
            'description' => 'Initialize React project with necessary dependencies and folder structure',
            'status' => 'completed',
            'priority' => 'high',
            'type' => 'task',
            'estimated_hours' => 4,
            'actual_hours' => 3,
            'start_date' => now()->subDays(15),
            'due_date' => now()->subDays(14),
            'completed_at' => now()->subDays(13),
            'tags' => json_encode(['frontend', 'react', 'setup']),
            'sort_order' => 3,
            'progress' => 100,
        ]);

        Task::create([
            'project_id' => $project->id,
            'milestone_id' => $milestone2->id,
            'assignee_id' => $users['developer1']->id,
            'created_by' => $users['pm']->id,
            'title' => 'Implement responsive navigation',
            'description' => 'Create responsive navigation component with mobile menu',
            'status' => 'in_progress',
            'priority' => 'high',
            'type' => 'feature',
            'estimated_hours' => 12,
            'actual_hours' => 8,
            'start_date' => now()->subDays(12),
            'due_date' => now()->addDays(2),
            'tags' => json_encode(['frontend', 'navigation', 'responsive']),
            'sort_order' => 4,
            'progress' => 65,
        ]);

        Task::create([
            'project_id' => $project->id,
            'milestone_id' => $milestone2->id,
            'assignee_id' => $users['developer1']->id,
            'created_by' => $users['pm']->id,
            'title' => 'Build homepage components',
            'description' => 'Implement hero section, features, testimonials, and CTA components',
            'status' => 'todo',
            'priority' => 'medium',
            'type' => 'feature',
            'estimated_hours' => 20,
            'start_date' => now()->addDays(1),
            'due_date' => now()->addDays(10),
            'tags' => json_encode(['frontend', 'homepage', 'components']),
            'sort_order' => 5,
            'progress' => 0,
        ]);

        // Milestone 3 tasks (planned)
        Task::create([
            'project_id' => $project->id,
            'milestone_id' => $milestone3->id,
            'assignee_id' => $users['developer1']->id,
            'created_by' => $users['pm']->id,
            'title' => 'Connect contact form to API',
            'description' => 'Integrate contact form with backend email service',
            'status' => 'todo',
            'priority' => 'medium',
            'type' => 'task',
            'estimated_hours' => 6,
            'start_date' => now()->addDays(15),
            'due_date' => now()->addDays(20),
            'tags' => json_encode(['backend', 'api', 'contact']),
            'sort_order' => 6,
            'progress' => 0,
        ]);

        // Overdue task
        Task::create([
            'project_id' => $project->id,
            'assignee_id' => $users['designer']->id,
            'created_by' => $users['pm']->id,
            'title' => 'Update brand guidelines document',
            'description' => 'Update the brand guidelines to reflect new design decisions',
            'status' => 'in_progress',
            'priority' => 'low',
            'type' => 'task',
            'estimated_hours' => 4,
            'start_date' => now()->subDays(10),
            'due_date' => now()->subDays(2), // Overdue
            'tags' => json_encode(['documentation', 'branding']),
            'sort_order' => 7,
            'progress' => 50,
        ]);
    }

    /**
     * Create tasks for mobile project.
     */
    private function createMobileProjectTasks($project, $users)
    {
        Task::create([
            'project_id' => $project->id,
            'assignee_id' => $users['pm']->id,
            'created_by' => $users['owner']->id,
            'title' => 'Define app requirements',
            'description' => 'Document functional and non-functional requirements for the mobile app',
            'status' => 'in_progress',
            'priority' => 'critical',
            'type' => 'task',
            'estimated_hours' => 16,
            'actual_hours' => 12,
            'start_date' => now()->subDays(5),
            'due_date' => now()->addDays(5),
            'tags' => json_encode(['planning', 'requirements', 'documentation']),
            'sort_order' => 1,
            'progress' => 75,
        ]);

        Task::create([
            'project_id' => $project->id,
            'assignee_id' => $users['developer1']->id,
            'created_by' => $users['pm']->id,
            'title' => 'Research React Native vs Flutter',
            'description' => 'Evaluate and recommend the best framework for cross-platform development',
            'status' => 'todo',
            'priority' => 'high',
            'type' => 'task',
            'estimated_hours' => 8,
            'start_date' => now()->addDays(3),
            'due_date' => now()->addDays(10),
            'tags' => json_encode(['research', 'mobile', 'framework']),
            'sort_order' => 2,
            'progress' => 0,
        ]);
    }

    /**
     * Create tasks for API project.
     */
    private function createApiProjectTasks($project, $milestone1, $users)
    {
        Task::create([
            'project_id' => $project->id,
            'milestone_id' => $milestone1->id,
            'assignee_id' => $users['developer1']->id,
            'created_by' => $users['pm']->id,
            'title' => 'Design API architecture',
            'description' => 'Create comprehensive API design and architecture documentation',
            'status' => 'completed',
            'priority' => 'critical',
            'type' => 'task',
            'estimated_hours' => 20,
            'actual_hours' => 22,
            'start_date' => now()->subDays(15),
            'due_date' => now()->subDays(10),
            'completed_at' => now()->subDays(8),
            'tags' => json_encode(['api', 'architecture', 'design']),
            'sort_order' => 1,
            'progress' => 100,
        ]);

        Task::create([
            'project_id' => $project->id,
            'milestone_id' => $milestone1->id,
            'assignee_id' => $users['developer2']->id,
            'created_by' => $users['developer1']->id,
            'title' => 'Implement authentication endpoints',
            'description' => 'Build JWT-based authentication system with login/register/refresh endpoints',
            'status' => 'in_progress',
            'priority' => 'critical',
            'type' => 'feature',
            'estimated_hours' => 24,
            'actual_hours' => 16,
            'start_date' => now()->subDays(8),
            'due_date' => now()->addDays(5),
            'tags' => json_encode(['api', 'authentication', 'jwt']),
            'sort_order' => 2,
            'progress' => 70,
        ]);

        Task::create([
            'project_id' => $project->id,
            'milestone_id' => $milestone1->id,
            'assignee_id' => $users['developer1']->id,
            'created_by' => $users['pm']->id,
            'title' => 'Set up rate limiting',
            'description' => 'Implement rate limiting for API endpoints to prevent abuse',
            'status' => 'todo',
            'priority' => 'high',
            'type' => 'task',
            'estimated_hours' => 8,
            'start_date' => now()->addDays(2),
            'due_date' => now()->addDays(12),
            'tags' => json_encode(['api', 'security', 'rate-limiting']),
            'sort_order' => 3,
            'progress' => 0,
        ]);

        // Create a blocked task
        Task::create([
            'project_id' => $project->id,
            'milestone_id' => $milestone1->id,
            'assignee_id' => $users['developer2']->id,
            'created_by' => $users['pm']->id,
            'title' => 'Database optimization',
            'description' => 'Optimize database queries and implement caching strategies',
            'status' => 'blocked',
            'priority' => 'medium',
            'type' => 'task',
            'estimated_hours' => 12,
            'start_date' => now()->subDays(3),
            'due_date' => now()->addDays(15),
            'tags' => json_encode(['database', 'optimization', 'caching']),
            'notes' => 'Blocked pending database migration approval from infrastructure team',
            'sort_order' => 4,
            'progress' => 0,
        ]);
    }
}
