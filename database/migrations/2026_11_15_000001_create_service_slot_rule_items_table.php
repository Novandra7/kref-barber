<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_slot_rule_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_slot_rule_id')
                ->constrained('service_slot_rules')
                ->cascadeOnDelete();
            $table->foreignId('service_id')
                ->nullable()
                ->constrained('services')
                ->cascadeOnDelete();
            $table->string('category')->nullable();
            $table->timestamps();
        });

        $now = now();
        $hasTriggerType = Schema::hasColumn('service_slot_rules', 'trigger_type');
        $hasServiceId = Schema::hasColumn('service_slot_rules', 'service_id');
        $hasCategory = Schema::hasColumn('service_slot_rules', 'category');

        if ($hasServiceId || $hasCategory) {
            $existingRules = DB::table('service_slot_rules')->get();

            foreach ($existingRules as $rule) {
                $triggerType = $hasTriggerType ? ($rule->trigger_type ?? null) : null;
                $category = $hasCategory ? ($rule->category ?? null) : null;
                $serviceId = $hasServiceId ? ($rule->service_id ?? null) : null;

                // Migrasi rule lama ke struktur item kombinasi tanpa trigger_type
                if ($triggerType === 'combo' && $category) {
                    DB::table('service_slot_rules')
                        ->where('id', $rule->id)
                        ->update(['name' => 'Kombinasi ' . $category . ' + Haircut (Semua Barber)']);

                    DB::table('service_slot_rule_items')->insert([
                        [
                            'service_slot_rule_id' => $rule->id,
                            'service_id'           => null,
                            'category'             => $category,
                            'created_at'           => $now,
                            'updated_at'           => $now,
                        ],
                        [
                            'service_slot_rule_id' => $rule->id,
                            'service_id'           => null,
                            'category'             => 'Haircut',
                            'created_at'           => $now,
                            'updated_at'           => $now,
                        ],
                    ]);

                    // Tambahkan juga pasangan dengan Treatment agar perilaku combo chemical tetap utuh
                    $treatmentPayload = [
                        'name'           => 'Kombinasi ' . $category . ' + Treatment (Semua Barber)',
                        'barber_id'      => $rule->barber_id,
                        'service_id'     => null,
                        'category'       => null,
                        'slots_required' => $rule->slots_required,
                        'is_active'      => $rule->is_active,
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ];

                    if ($hasTriggerType) {
                        $treatmentPayload['trigger_type'] = 'standalone';
                    }

                    if (Schema::hasColumn('service_slot_rules', 'description')) {
                        $treatmentPayload['description'] = 'Mengunci ' . $rule->slots_required . ' slot jadwal jika layanan ' . $category . ' digabung dengan Treatment.';
                    }

                    $treatmentRuleId = DB::table('service_slot_rules')->insertGetId($treatmentPayload);

                    DB::table('service_slot_rule_items')->insert([
                        [
                            'service_slot_rule_id' => $treatmentRuleId,
                            'service_id'           => null,
                            'category'             => $category,
                            'created_at'           => $now,
                            'updated_at'           => $now,
                        ],
                        [
                            'service_slot_rule_id' => $treatmentRuleId,
                            'service_id'           => null,
                            'category'             => 'Treatment',
                            'created_at'           => $now,
                            'updated_at'           => $now,
                        ],
                    ]);
                } elseif ($serviceId || $category) {
                    DB::table('service_slot_rule_items')->insert([
                        'service_slot_rule_id' => $rule->id,
                        'service_id'           => $serviceId,
                        'category'             => $category,
                        'created_at'           => $now,
                        'updated_at'           => $now,
                    ]);
                }
            }
        }

        Schema::table('service_slot_rules', function (Blueprint $table) use ($hasServiceId, $hasCategory, $hasTriggerType) {
            if ($hasServiceId) {
                $table->dropConstrainedForeignId('service_id');
            }

            $columnsToDrop = array_values(array_filter([
                $hasCategory ? 'category' : null,
                $hasTriggerType ? 'trigger_type' : null,
            ]));

            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_slot_rules', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->after('barber_id')->constrained('services')->nullOnDelete();
            $table->string('category')->nullable()->after('service_id');
        });

        Schema::dropIfExists('service_slot_rule_items');
    }
};
