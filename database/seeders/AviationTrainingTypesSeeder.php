<?php

// database/seeders/AviationTrainingTypesSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TrainingType;
use Illuminate\Support\Facades\DB;

class AviationTrainingTypesSeeder extends Seeder
{
    /**
     * Seed aviation-specific training types for GAPURA ANGKASA
     * Based on MPGA Excel structure and aviation industry requirements
     */
    public function run(): void
    {
        $this->command->info('🛫 Seeding Aviation Training Types for GAPURA ANGKASA...');

        DB::beginTransaction();

        try {
            // Check if training types already exist
            $existingCount = TrainingType::count();
            if ($existingCount > 0) {
                $this->command->warn("   ⚠️  Found {$existingCount} existing training types");
                $this->command->info('   ✅ Skipping duplicates, only adding new ones');
            }

            // Define aviation training categories and types
            $trainingTypes = $this->getAviationTrainingTypes();
            $totalCreated = 0;
            $skipped = 0;

            foreach ($trainingTypes as $category => $types) {
                $this->command->info("   📚 Processing {$category} training types...");

                foreach ($types as $typeData) {
                    // Check if training type already exists
                    $existing = TrainingType::where('name', $typeData['name'])->first();

                    if ($existing) {
                        $this->command->info("      ⏭️  Skipped: {$typeData['name']} (already exists)");
                        $skipped++;
                        continue;
                    }

                    $trainingType = TrainingType::create([
                        'name' => $typeData['name'],
                        'category' => $category,
                        'description' => $typeData['description'],
                        'validity_period' => $typeData['validity_period'],
                        'is_mandatory' => $typeData['is_mandatory'],
                        'is_active' => true,
                        'compliance_level' => $typeData['compliance_level'],
                        'training_provider_default' => $typeData['provider'] ?? null,
                        'cost_estimate' => $typeData['cost'] ?? null,
                        'requirements' => $typeData['requirements'] ?? null,
                        'renewal_required' => $typeData['renewal_required'] ?? true,
                        'notification_days' => $typeData['notification_days'] ?? 30,
                        'created_by' => 'System Seeder',
                    ]);

                    $this->command->info("      ✓ Created: {$trainingType->name}");
                    $totalCreated++;
                }
            }

            DB::commit();

            $totalTypes = TrainingType::count();
            $mandatoryTypes = TrainingType::where('is_mandatory', true)->count();

            $this->command->info('');
            $this->command->info('🎯 AVIATION TRAINING TYPES SETUP COMPLETE!');
            $this->command->info("   📊 Total Training Types: {$totalTypes}");
            $this->command->info("   ➕ New Types Created: {$totalCreated}");
            $this->command->info("   ⏭️  Types Skipped: {$skipped}");
            $this->command->info("   ⚠️  Mandatory Types: {$mandatoryTypes}");
            $this->command->info('   🛫 Aviation compliance requirements loaded');
            $this->command->newLine();

            // Show breakdown by category
            $this->showCategoryBreakdown();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Error seeding training types: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Show breakdown by category
     */
    private function showCategoryBreakdown()
    {
        $this->command->info('📋 TRAINING TYPES BY CATEGORY:');

        $categories = TrainingType::select('category')
                                ->selectRaw('COUNT(*) as count')
                                ->selectRaw('SUM(CASE WHEN is_mandatory = 1 THEN 1 ELSE 0 END) as mandatory_count')
                                ->groupBy('category')
                                ->orderBy('category')
                                ->get();

        foreach ($categories as $category) {
            $this->command->info("   • {$category->category}: {$category->count} types ({$category->mandatory_count} mandatory)");
        }

        $this->command->newLine();
    }

    /**
     * Get aviation-specific training types for GAPURA ANGKASA
     * Based on Indonesian aviation regulations and MPGA requirements
     */
    private function getAviationTrainingTypes()
    {
        return [
            'SAFETY' => [
                [
                    'name' => 'Dangerous Goods Handling (DGR)',
                    'description' => 'IATA Dangerous Goods Regulations training for safe handling of hazardous materials',
                    'validity_period' => 24,
                    'is_mandatory' => true,
                    'compliance_level' => 'CRITICAL',
                    'provider' => 'IATA Authorized Training Center',
                    'cost' => 2500000,
                    'requirements' => ['Valid ID', 'Basic English proficiency'],
                    'notification_days' => 60,
                ],
                [
                    'name' => 'Aviation Security (AVSEC)',
                    'description' => 'Aviation security training as per ICAO Annex 17 standards',
                    'validity_period' => 36,
                    'is_mandatory' => true,
                    'compliance_level' => 'CRITICAL',
                    'provider' => 'Civil Aviation Security',
                    'cost' => 1500000,
                    'notification_days' => 90,
                ],
                [
                    'name' => 'Ground Safety Training',
                    'description' => 'Airport ground operation safety procedures and protocols',
                    'validity_period' => 12,
                    'is_mandatory' => true,
                    'compliance_level' => 'HIGH',
                    'cost' => 800000,
                ],
                [
                    'name' => 'Fire Safety & Emergency Response',
                    'description' => 'Airport fire safety and emergency response procedures',
                    'validity_period' => 12,
                    'is_mandatory' => true,
                    'compliance_level' => 'CRITICAL',
                    'cost' => 1000000,
                ],
                [
                    'name' => 'Workplace Safety (K3)',
                    'description' => 'Occupational Health and Safety (K3) compliance training',
                    'validity_period' => 12,
                    'is_mandatory' => true,
                    'compliance_level' => 'HIGH',
                    'cost' => 500000,
                ],
            ],

            'OPERATIONAL' => [
                [
                    'name' => 'Ground Handling Operations',
                    'description' => 'Comprehensive ground handling procedures for aircraft servicing',
                    'validity_period' => 24,
                    'is_mandatory' => true,
                    'compliance_level' => 'HIGH',
                    'cost' => 1800000,
                ],
                [
                    'name' => 'Passenger Service Training',
                    'description' => 'Customer service excellence for airport passenger services',
                    'validity_period' => 18,
                    'is_mandatory' => false,
                    'compliance_level' => 'MEDIUM',
                    'cost' => 1200000,
                ],
                [
                    'name' => 'Baggage Handling Procedures',
                    'description' => 'Proper baggage handling and tracking procedures',
                    'validity_period' => 18,
                    'is_mandatory' => true,
                    'compliance_level' => 'HIGH',
                    'cost' => 900000,
                ],
                [
                    'name' => 'Aircraft Marshalling',
                    'description' => 'Aircraft ground movement and marshalling procedures',
                    'validity_period' => 24,
                    'is_mandatory' => true,
                    'compliance_level' => 'HIGH',
                    'cost' => 1500000,
                ],
                [
                    'name' => 'Cargo Operations',
                    'description' => 'Air cargo handling and documentation procedures',
                    'validity_period' => 24,
                    'is_mandatory' => false,
                    'compliance_level' => 'MEDIUM',
                    'cost' => 1600000,
                ],
            ],

            'TECHNICAL' => [
                [
                    'name' => 'Ground Support Equipment (GSE)',
                    'description' => 'Operation and maintenance of ground support equipment',
                    'validity_period' => 36,
                    'is_mandatory' => true,
                    'compliance_level' => 'HIGH',
                    'cost' => 2000000,
                ],
                [
                    'name' => 'Airfield Maintenance',
                    'description' => 'Airport infrastructure and runway maintenance procedures',
                    'validity_period' => 24,
                    'is_mandatory' => false,
                    'compliance_level' => 'MEDIUM',
                    'cost' => 1800000,
                ],
                [
                    'name' => 'Aircraft Refueling Operations',
                    'description' => 'Safe aircraft refueling procedures and fuel quality control',
                    'validity_period' => 24,
                    'is_mandatory' => true,
                    'compliance_level' => 'CRITICAL',
                    'cost' => 2200000,
                    'notification_days' => 60,
                ],
                [
                    'name' => 'Communication Systems',
                    'description' => 'Airport communication systems operation and maintenance',
                    'validity_period' => 36,
                    'is_mandatory' => false,
                    'compliance_level' => 'MEDIUM',
                    'cost' => 1700000,
                ],
            ],

            'REGULATORY' => [
                [
                    'name' => 'DGCA Regulations Compliance',
                    'description' => 'Indonesian Civil Aviation Regulations (CASR) compliance training',
                    'validity_period' => 24,
                    'is_mandatory' => true,
                    'compliance_level' => 'CRITICAL',
                    'provider' => 'Directorate General of Civil Aviation',
                    'cost' => 1200000,
                    'notification_days' => 90,
                ],
                [
                    'name' => 'ICAO Standards & Recommended Practices',
                    'description' => 'International Civil Aviation Organization standards compliance',
                    'validity_period' => 36,
                    'is_mandatory' => true,
                    'compliance_level' => 'HIGH',
                    'cost' => 1500000,
                ],
                [
                    'name' => 'Quality Management System (SMS)',
                    'description' => 'Safety Management System implementation and compliance',
                    'validity_period' => 24,
                    'is_mandatory' => true,
                    'compliance_level' => 'HIGH',
                    'cost' => 1800000,
                ],
                [
                    'name' => 'Environmental Compliance',
                    'description' => 'Airport environmental regulations and waste management',
                    'validity_period' => 24,
                    'is_mandatory' => true,
                    'compliance_level' => 'MEDIUM',
                    'cost' => 1000000,
                ],
            ],

            'MANAGEMENT' => [
                [
                    'name' => 'Airport Operations Management',
                    'description' => 'Comprehensive airport operations management training',
                    'validity_period' => 36,
                    'is_mandatory' => false,
                    'compliance_level' => 'MEDIUM',
                    'cost' => 3000000,
                ],
                [
                    'name' => 'Crisis Management',
                    'description' => 'Airport crisis and emergency management procedures',
                    'validity_period' => 24,
                    'is_mandatory' => true,
                    'compliance_level' => 'HIGH',
                    'cost' => 2500000,
                ],
                [
                    'name' => 'Leadership in Aviation',
                    'description' => 'Leadership development for aviation industry professionals',
                    'validity_period' => 36,
                    'is_mandatory' => false,
                    'compliance_level' => 'LOW',
                    'cost' => 2800000,
                ],
                [
                    'name' => 'Resource Management',
                    'description' => 'Human and technical resource management in aviation',
                    'validity_period' => 24,
                    'is_mandatory' => false,
                    'compliance_level' => 'MEDIUM',
                    'cost' => 2200000,
                ],
            ],

            'SPECIALIZED' => [
                [
                    'name' => 'VIP & Special Flight Operations',
                    'description' => 'Special handling procedures for VIP and charter flights',
                    'validity_period' => 24,
                    'is_mandatory' => false,
                    'compliance_level' => 'MEDIUM',
                    'cost' => 2000000,
                ],
                [
                    'name' => 'Medical Emergency Response',
                    'description' => 'Airport medical emergency response and first aid',
                    'validity_period' => 12,
                    'is_mandatory' => true,
                    'compliance_level' => 'HIGH',
                    'cost' => 800000,
                ],
                [
                    'name' => 'Wildlife Hazard Management',
                    'description' => 'Airport wildlife control and bird strike prevention',
                    'validity_period' => 24,
                    'is_mandatory' => false,
                    'compliance_level' => 'MEDIUM',
                    'cost' => 1500000,
                ],
                [
                    'name' => 'Meteorology for Aviation',
                    'description' => 'Weather observation and reporting for aviation operations',
                    'validity_period' => 36,
                    'is_mandatory' => false,
                    'compliance_level' => 'MEDIUM',
                    'cost' => 1800000,
                ],
            ],

            'RECURRENT' => [
                [
                    'name' => 'Annual Safety Refresher',
                    'description' => 'Annual safety training refresher for all airport personnel',
                    'validity_period' => 12,
                    'is_mandatory' => true,
                    'compliance_level' => 'CRITICAL',
                    'cost' => 500000,
                    'renewal_required' => true,
                    'notification_days' => 30,
                ],
                [
                    'name' => 'Security Awareness Update',
                    'description' => 'Regular security awareness and threat assessment update',
                    'validity_period' => 12,
                    'is_mandatory' => true,
                    'compliance_level' => 'HIGH',
                    'cost' => 400000,
                ],
                [
                    'name' => 'Emergency Drill Participation',
                    'description' => 'Mandatory participation in airport emergency drills',
                    'validity_period' => 6,
                    'is_mandatory' => true,
                    'compliance_level' => 'HIGH',
                    'cost' => 200000,
                    'notification_days' => 15,
                ],
                [
                    'name' => 'Competency Assessment',
                    'description' => 'Regular competency assessment and skill evaluation',
                    'validity_period' => 12,
                    'is_mandatory' => true,
                    'compliance_level' => 'HIGH',
                    'cost' => 600000,
                ],
            ],
        ];
    }
}
