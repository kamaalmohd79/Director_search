use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('search_logs', function (Blueprint $t) {
            $t->id();
            $t->string('first_name')->nullable();
            $t->string('surname');
            $t->string('postcode')->nullable();
            $t->string('ip')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('search_logs'); }
};
