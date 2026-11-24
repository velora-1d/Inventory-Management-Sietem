<?php

namespace Database\Seeders;

use App\Models\OrderWorkflow;
use App\Models\WorkflowStep;
use Illuminate\Database\Seeder;

class WorkflowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $workflow = OrderWorkflow::updateOrCreate(
            ['code' => 'default_fulfillment'],
            [
                'name' => 'Standard Order Fulfillment',
                'is_default' => true,
                'is_active' => true,
                'description' => 'Default sequential fulfillment workflow: Picking, QC, Packing & Label, and Ready to Ship.',
            ]
        );

        $picking = WorkflowStep::updateOrCreate(
            [
                'order_workflow_id' => $workflow->id,
                'code' => 'picking',
            ],
            [
                'step_order' => 1,
                'name' => 'Picking Barang',
                'department' => 'Warehouse',
                'default_role' => 'warehouse',
                'sla_minutes' => 30,
                'depends_on_step_id' => null,
                'is_public_milestone' => true,
                'public_milestone_title' => 'Pesanan sedang diproses',
            ]
        );

        $qc = WorkflowStep::updateOrCreate(
            [
                'order_workflow_id' => $workflow->id,
                'code' => 'qc',
            ],
            [
                'step_order' => 2,
                'name' => 'Quality Control',
                'department' => 'QC & Packing',
                'default_role' => 'qc_packing',
                'sla_minutes' => 20,
                'depends_on_step_id' => $picking->id,
                'is_public_milestone' => false,
                'public_milestone_title' => null,
            ]
        );

        $packing = WorkflowStep::updateOrCreate(
            [
                'order_workflow_id' => $workflow->id,
                'code' => 'packing',
            ],
            [
                'step_order' => 3,
                'name' => 'Packing & Label',
                'department' => 'QC & Packing',
                'default_role' => 'qc_packing',
                'sla_minutes' => 25,
                'depends_on_step_id' => $qc->id,
                'is_public_milestone' => true,
                'public_milestone_title' => 'Pesanan dikemas',
            ]
        );

        WorkflowStep::updateOrCreate(
            [
                'order_workflow_id' => $workflow->id,
                'code' => 'ready_to_ship',
            ],
            [
                'step_order' => 4,
                'name' => 'Ready to Ship',
                'department' => 'Shipping',
                'default_role' => 'shipping',
                'sla_minutes' => 15,
                'depends_on_step_id' => $packing->id,
                'is_public_milestone' => true,
                'public_milestone_title' => 'Pesanan siap dikirim',
            ]
        );
    }
}
