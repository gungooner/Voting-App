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
    Schema::create('votes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('poll_id')->constrained()->cascadeOnDelete();
        $table->foreignId('poll_option_id')->constrained()->cascadeOnDelete();
        $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
        $table->string('voter_identifier')->nullable();
        $table->string('ip_address')->nullable();
        $table->timestamps();

        $table->unique(['poll_id', 'user_id']);
        $table->unique(['poll_id', 'voter_identifier']);
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
