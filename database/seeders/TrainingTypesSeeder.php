<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\TrainingType;

class TrainingTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seed training types based on Excel analysis from MPGA Training Record
     *
     * Based on actual data from: Trainning_record_MPGA AGUSTUS 2025.xlsx
     * 5 Training types with specific durations and certificate formats
     */
    public function run(): void
    {
        $this->command->info('🎓 Seeding GAPURA training types from MPGA Excel analysis...');

        // Clear existing data
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('training_types')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $trainingTypes = [
            [
                'name' => 'PAX & BAGGAGE HANDLING',
                'code' => 'PAX_BAGGAGE_HANDLING',
                'description' => 'Passenger and Baggage Handling Training - Covers all aspects of passenger service, baggage handling procedures, check-in processes, and customer service standards in aviation environment.',
                'duration_months' => 36,
                'certificate_format' => 'GLC/OPR-{number}/{month}/{year}',
                'requires_background_check' => true,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'SAFETY TRAINING (SMS)',
                'code' => 'SAFETY_TRAINING_SMS',
                'description' => 'Safety Management System Training - Comprehensive safety training covering SMS procedures, protocols, hazard identification, risk assessment, and safety reporting in aviation operations.',
                'duration_months' => 36,
                'certificate_format' => 'GLC/OPR-{number}/{month}/{year}',
                'requires_background_check' => true,
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'HUMAN FACTOR',
                'code' => 'HUMAN_FACTOR',
                'description' => 'Human Factor Training - Understanding human factors in aviation operations, human performance limitations, error management, communication skills, and teamwork in aviation safety.',
                'duration_months' => 36,
                'certificate_format' => 'GLC/OPR-{number}/{month}/{year}',
                'requires_background_check' => true,
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'DANGEROUS GOODS AWARENESS',
                'code' => 'DANGEROUS_GOODS_AWARENESS',
                'description' => 'Dangerous Goods Awareness Training - Recognition, identification, and proper handling of dangerous goods in aviation. Covers IATA regulations, classification, packaging, and emergency procedures.',
                'duration_months' => 24,
                'certificate_format' => 'GLC/OPR-{number}/{month}/{year}',
                'requires_background_check' => true,
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'AVIATION SECURITY AWARENESS',
                'code' => 'AVIATION_SECURITY_AWARENESS',
                'description' => 'Aviation Security Awareness Training - Security procedures, threat awareness, access control, screening procedures, and emergency response protocols in aviation security.',
                'duration_months' => 12,
                'certificate_format' => 'GLC/GM/OPR-{number}/{month}/{year}',
                'requires_background_check' => true,
                'is_active' => true,
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Insert training types
        foreach ($trainingTypes as $trainingType) {
            TrainingType::create($trainingType);
        }

        $this->command->info('✅ Training types seeded successfully!');
        $this->command->info('📊 Total training types created: ' . count($trainingTypes));
        $this->command->newLine();

        $this->command->info('🎯 TRAINING TYPES INSTALLED:');
        foreach ($trainingTypes as $index => $type) {
            $this->command->info(sprintf(
                '   %d. %s (%d months) - %s',
                $index + 1,
                $type['name'],
                $type['duration_months'],
                $type['code']
            ));
        }

        $this->command->newLine();
        $this->command->info('📋 CERTIFICATE FORMATS:');
        $this->command->info('   • PAX, Safety, Human Factor, Dangerous Goods: GLC/OPR-{number}/{month}/{year}');
        $this->command->info('   • Aviation Security: GLC/GM/OPR-{number}/{month}/{year}');

        $this->command->newLine();
        $this->command->info('🔧 FEATURES ENABLED:');
        $this->command->info('   ✅ Background check requirements');
        $this->command->info('   ✅ Auto-expiry tracking');
        $this->command->info('   ✅ Certificate format validation');
        $this->command->info('   ✅ Duration-based compliance monitoring');

        $this->command->newLine();
        $this->command->info('🚀 Ready for Excel import and training record management!');
    }
}
