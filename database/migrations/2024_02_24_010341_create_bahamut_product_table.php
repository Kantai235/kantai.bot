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
        Schema::create('bahamut_product', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('sn');
            $table->string('name');
            $table->string('image');
            $table->string('platform');
            $table->integer('point');
            $table->integer('price');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bahamut_product');
    }
};
