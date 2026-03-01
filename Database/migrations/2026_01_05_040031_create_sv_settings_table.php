<?php

use App\Contracts\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class CreateSvSettingsTable
 */
class CreateSvSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sv_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('key')->unique();
            $table->json('value')->nullable();
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
        Schema::dropIfExists('sv_settings');
    }
}
