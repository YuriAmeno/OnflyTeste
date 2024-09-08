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
        Schema::table('tasks', function (Blueprint $table) {
            Schema::dropIfExists('tasks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            Schema::create('tasks', function (Blueprint $table) {
                $table->id();
                $table->string('description');
                $table->integer('value');
                $table->unsignedBigInteger('user_id'); // Adicione outras colunas conforme necessário
                $table->timestamps();
            });
        });
    }
};
