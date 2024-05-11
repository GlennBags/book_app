<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // volumeInfo ->
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->string('authors', 2056);
            $table->string('title');
            $table->string('subtitle');
            $table->string('description', 2056);
            $table->string('categories'); // array
            $table->string('canonicalVolumeLink');
            $table->string('infoLink');
            $table->string('previewLink');
            $table->string('imageLinks_thumbnail'); // imageLinks->thumbnail
            $table->string('publishedDate');
            $table->bigInteger('isbn_10')->nullable();
            $table->bigInteger('isbn_13')->nullable();
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
        Schema::dropIfExists('listings');
    }
};
