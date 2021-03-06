<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('posts', function (Blueprint $table) {
        $table->string('title', 255)->change();
        $table->longText('content')->nullable()->change();
        $table->string('img')->nullable() ->commnet('博客/微博图片,多图已逗号分隔');
      });
      Schema::table('reply', function (Blueprint $table) {
        $table->dropColumn('t_uid');
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::table('posts', function (Blueprint $table) {
       $table->dropColumn('img');
      });
    }
}
