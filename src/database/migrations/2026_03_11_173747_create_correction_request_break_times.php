<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCorrectionRequestBreakTimes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('correction_request_break_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('correction_request_id')->constrained();
            $table->unsignedInteger('break_index');
            $table->datetime('requested_in_at')->nullable();
            $table->datetime('requested_out_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('correction_request_break_times');
    }
}
