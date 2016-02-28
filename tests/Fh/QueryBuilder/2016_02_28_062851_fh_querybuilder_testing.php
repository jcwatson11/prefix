<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FhQuerybuilderTesting extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('Table')) {
            Schema::create('Table', function (Blueprint $table) {
                $table->increments('TestId');
                $table->bigInteger('StatusId')->length(10)->nullable();
                $table->string('FirstName', 20)->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('ChildTable')) {
            Schema::create('ChildTable', function (Blueprint $table) {
                $table->increments('ChildId');
                $table->boolean('IncludeInPrint')->default(true);
                $table->string('Caption', 150);
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        Schema::dropIfExists('Table');
        Schema::dropIfExists('ChildTable');
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }
}
