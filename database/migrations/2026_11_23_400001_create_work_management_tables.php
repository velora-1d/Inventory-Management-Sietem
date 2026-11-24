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
        if (! Schema::hasTable('order_workflows')) {
            Schema::create('order_workflows', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('workflow_steps')) {
            Schema::create('workflow_steps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_workflow_id')->constrained('order_workflows')->cascadeOnDelete();
                $table->integer('step_order');
                $table->string('name');
                $table->string('code');
                $table->string('department');
                $table->string('default_role');
                $table->integer('sla_minutes')->default(30);
                $table->unsignedBigInteger('depends_on_step_id')->nullable();
                $table->boolean('is_public_milestone')->default(false);
                $table->string('public_milestone_title')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('order_tasks')) {
            Schema::create('order_tasks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id')->index();
                $table->foreignId('workflow_step_id')->nullable()->constrained('workflow_steps')->nullOnDelete();
                $table->string('title');
                $table->string('code')->nullable();
                $table->string('status', 30)->default('pending');
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->string('department', 50);
                $table->string('priority', 20)->default('normal');
                $table->timestamp('due_at')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('task_assignments')) {
            Schema::create('task_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_task_id')->constrained('order_tasks')->cascadeOnDelete();
                $table->foreignId('from_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('to_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('reason')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('task_dependencies')) {
            Schema::create('task_dependencies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_task_id')->constrained('order_tasks')->cascadeOnDelete();
                $table->foreignId('depends_on_task_id')->constrained('order_tasks')->cascadeOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('task_histories')) {
            Schema::create('task_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_task_id')->constrained('order_tasks')->cascadeOnDelete();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('from_status', 30)->nullable();
                $table->string('to_status', 30);
                $table->text('note')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('task_comments')) {
            Schema::create('task_comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_task_id')->constrained('order_tasks')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users');
                $table->text('body');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('task_attachments')) {
            Schema::create('task_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_task_id')->constrained('order_tasks')->cascadeOnDelete();
                $table->foreignId('uploaded_by')->constrained('users');
                $table->string('disk', 30)->default('local');
                $table->string('path');
                $table->string('original_name');
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('size_bytes')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_attachments');
        Schema::dropIfExists('task_comments');
        Schema::dropIfExists('task_histories');
        Schema::dropIfExists('task_dependencies');
        Schema::dropIfExists('task_assignments');
        Schema::dropIfExists('order_tasks');
        Schema::dropIfExists('workflow_steps');
        Schema::dropIfExists('order_workflows');
    }
};
