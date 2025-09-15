<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('milestone_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('assignee_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('tasks')->onDelete('cascade');

            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', [
                'todo', 'in_progress', 'in_review', 'blocked', 'completed', 'cancelled'
            ])->default('todo');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('type', ['task', 'bug', 'feature', 'epic'])->default('task');

            // Time tracking
            $table->integer('estimated_hours')->nullable();
            $table->integer('actual_hours')->nullable();
            $table->time('time_slot')->nullable(); // For daily scheduling

            // Dates
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Organization
            $table->json('tags')->nullable();
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->decimal('progress', 5, 2)->default(0);

            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['milestone_id', 'status']);
            $table->index(['assignee_id', 'status']);
            $table->index(['created_by']);
            $table->index(['parent_id']);
            $table->index(['priority', 'status']);
            $table->index(['due_date']);
            $table->index(['time_slot']);
            $table->fullText(['title', 'description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
