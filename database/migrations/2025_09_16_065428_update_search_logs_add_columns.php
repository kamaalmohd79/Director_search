<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('search_logs', function (Blueprint $t) {
            if (!Schema::hasColumn('search_logs', 'first_name')) {
                $t->string('first_name')->nullable()->after('id');
            }
            if (!Schema::hasColumn('search_logs', 'surname')) {
                $t->string('surname')->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('search_logs', 'postcode')) {
                $t->string('postcode')->nullable()->after('surname');
            }
            if (!Schema::hasColumn('search_logs', 'ip')) {
                $t->string('ip')->nullable()->after('postcode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('search_logs', function (Blueprint $t) {
            if (Schema::hasColumn('search_logs', 'first_name')) $t->dropColumn('first_name');
            if (Schema::hasColumn('search_logs', 'surname'))    $t->dropColumn('surname');
            if (Schema::hasColumn('search_logs', 'postcode'))   $t->dropColumn('postcode');
            if (Schema::hasColumn('search_logs', 'ip'))         $t->dropColumn('ip');
        });
    }
};
