<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLoansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'loans',
            function (Blueprint $table) {
                $table->id();
                $table->integer('user_id')->unsigned();
                $table->double('amount', 14, 2);
                $table->unsignedSmallInteger('duration')->default(12);
                $table->float('interest_rate', 5)->default(1.0);
                $table->double('calculated_interest', 14, 2);
                $table->double('other_charges', 8, 2);
                $table->double('total_amount', 14, 2);
                $table->double('total_amount_paid', 14, 2)->default(0.00);
                $table->boolean('status')->default(1);
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
        Schema::dropIfExists('loans');
    }
}
