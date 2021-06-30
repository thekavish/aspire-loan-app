<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRepaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'repayments',
            function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('loan_id')->unsigned();
                $table->double('amount', 14, 2);
                $table->double('amount_remaining', 14, 2);
                $table->timestamps();
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('repayments');
    }
}
