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
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('workspace_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role')->default('agent');
            $table->index('role');
        });

        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('summary');
            $table->text('description');
            $table->string('category');
            $table->unsignedInteger('price_cents');
            $table->char('currency', 3)->default('USD');
            $table->string('status');
            $table->string('location')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'slug']);
            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'category']);
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->text('message');
            $table->string('status');
            $table->string('source')->default('mcp');
            $table->string('idempotency_key')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'idempotency_key']);
            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'email']);
        });

        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->text('body')->nullable();
            $table->timestamps();

            $table->index(['lead_id', 'id']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained()->restrictOnDelete();
            $table->foreignId('lead_id')->constrained()->restrictOnDelete();
            $table->foreignId('placed_by')->constrained('users')->restrictOnDelete();
            $table->string('number')->unique();
            $table->unsignedInteger('amount_cents');
            $table->char('currency', 3);
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamp('confirmed_at');
            $table->timestamps();

            $table->index(['workspace_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
        Schema::dropIfExists('lead_activities');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('listings');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workspace_id');
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });

        Schema::dropIfExists('workspaces');
    }
};
