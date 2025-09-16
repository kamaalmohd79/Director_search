<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('search_results', function (Blueprint $t) {
            $t->id();
            $t->foreignId('search_log_id')->constrained('search_logs')->cascadeOnDelete();

            $t->string('searchedPeopleId')->nullable()->index();
            $t->string('searchedFirstName')->nullable();
            $t->string('searchedSurname')->nullable();
            $t->string('searchedFullName')->nullable();
            $t->string('searchedDOB')->nullable();

            $t->string('peopleId')->nullable()->index();
            $t->string('score')->nullable();
            $t->string('firstName')->nullable();
            $t->string('lastName')->nullable();
            $t->string('status')->nullable();
            $t->string('dateOfBirth')->nullable();

            $t->unsignedInteger('localDirectorNumber')->nullable();
            $t->boolean('isOriginalDirector')->nullable();

            $t->string('companyId')->nullable();
            $t->string('companyName')->nullable();
            $t->string('companyNumber')->nullable()->index();
            $t->string('safeNumber')->nullable();
            $t->string('companyType')->nullable();

            $t->text('address')->nullable();
            $t->string('addressCity')->nullable();
            $t->string('addressPostCode')->nullable()->index();
            $t->string('addressHouseNo')->nullable();

            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_results');
    }
};
