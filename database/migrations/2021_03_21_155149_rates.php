<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Rates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->char('charcode', 3)->charset('ascii')->collation('ascii_general_ci');
            $table->unsignedSmallInteger('numcode')->unique();
            $table->string('name');
            $table->string('name_eng');
            $table->primary('charcode');
            $table->index('name');
        });
        Schema::create('rates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('datereq');
            $table->char('charcode')->charset('ascii')->collation('ascii_general_ci');
            $table->unsignedSmallInteger('nominal')->default(1);
            $table->unsignedDecimal('rate', 10, 4);
            $table->foreign('charcode')->references('charcode')->on('currencies');
            $table->unique(['datereq', 'charcode']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('rates');
    }
}
