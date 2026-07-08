<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\OrganisationChart;
use App\Models\OrganisationChartNode;
use App\Models\Province;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganisationChartTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;
    private Role $managerRole;
    private Role $officerRole;
    private Role $viewerRole;
    private Province $northern;
    private Province $luapula;
    private District $kasama;
    private District $mansa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['name' => 'Admin', 'code' => 'ADMIN', 'is_active' => true]);
        $this->managerRole = Role::create(['name' => 'HR Manager', 'code' => 'HRM', 'is_active' => true]);
        $this->officerRole = Role::create(['name' => 'HR Officer', 'code' => 'HRO', 'is_active' => true]);
        $this->viewerRole = Role::create(['name' => 'Viewer', 'code' => 'VIEWER', 'is_active' => true]);

        $this->northern = Province::create(['name' => 'Northern', 'code' => 'NOR', 'is_active' => true]);
        $this->luapula = Province::create(['name' => 'Luapula', 'code' => 'LUA', 'is_active' => true]);

        $this->kasama = District::create(['province_id' => $this->northern->id, 'name' => 'Kasama', 'code' => 'NOR-KAS', 'is_active' => true]);
        $this->mansa = District::create(['province_id' => $this->luapula->id, 'name' => 'Mansa', 'code' => 'LUA-MAN', 'is_active' => true]);
    }

    public function test_unauthenticated_users_cannot_access_reporting_structure(): void
    {
        $this->get(route('employees.reporting-structure'))->assertRedirect('/login');
    }

    public function test_organisation_chart_sidebar_route_lists_project_charts(): void
    {
        $admin = $this->user($this->adminRole);
        $chart = OrganisationChart::create([
            'title' => 'USAID Action HIV Project Management Overview',
            'status' => OrganisationChart::STATUS_PUBLISHED,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('organisation-chart.index'))
            ->assertOk()
            ->assertSee('Organisation Chart')
            ->assertSee($chart->title);
    }

    public function test_admin_can_create_edit_and_view_clickable_project_chart_boxes(): void
    {
        $admin = $this->user($this->adminRole);
        $project = Project::create(['name' => 'USAID Action HIV', 'code' => 'USAID-AHIV', 'is_active' => true]);
        $employee = $this->employee(['employee_no' => '22866', 'first_name' => 'Paul', 'last_name' => 'Chinyemba']);

        $response = $this->actingAs($admin)
            ->post(route('organisation-chart.store'), [
                'title' => 'RTCZ USAID Action HIV Project Management Overview',
                'project_id' => $project->id,
                'status' => OrganisationChart::STATUS_DRAFT,
                'effective_date' => '2026-06-01',
                'description' => 'Project leadership structure.',
            ]);

        $chart = OrganisationChart::firstOrFail();
        $response->assertRedirect(route('organisation-chart.edit', $chart));

        $this->actingAs($admin)
            ->put(route('organisation-chart.update', $chart), [
                'title' => $chart->title,
                'project_id' => $project->id,
                'status' => OrganisationChart::STATUS_PUBLISHED,
                'effective_date' => '2026-06-01',
                'description' => 'Project leadership structure.',
                'nodes' => [
                    [
                        'label' => 'Chief of Party',
                        'subtitle' => 'USAID ACTION HIV Project',
                        'node_type' => OrganisationChartNode::TYPE_KEY_POSITION,
                        'planned_positions' => 1,
                        'employee_id' => $employee->id,
                        'sort_order' => 1,
                    ],
                ],
            ])
            ->assertRedirect(route('organisation-chart.show', $chart));

        $this->assertDatabaseHas('organisation_chart_nodes', [
            'organisation_chart_id' => $chart->id,
            'label' => 'Chief of Party',
            'employee_id' => $employee->id,
        ]);

        $this->actingAs($admin)
            ->get(route('organisation-chart.show', $chart))
            ->assertOk()
            ->assertSee('Download PNG')
            ->assertSee('data-download-org-chart-png', false)
            ->assertSee('rtcz-usaid-action-hiv-project-management-overview-org-chart.png', false)
            ->assertSee('dataset.orgChartExportSurface', false)
            ->assertSee('requestAnimationFrame', false)
            ->assertSee('html-to-image', false)
            ->assertDontSee('formal-org-legend', false)
            ->assertSee('Chief of Party')
            ->assertSee($employee->full_name)
            ->assertSee(route('employees.show', $employee), false);
    }

    public function test_hr_officer_can_view_but_cannot_create_organisation_charts(): void
    {
        $officer = $this->user($this->officerRole, $this->northern);
        $chart = OrganisationChart::create([
            'title' => 'Internal Project Chart',
            'status' => OrganisationChart::STATUS_PUBLISHED,
        ]);

        $this->actingAs($officer)
            ->get(route('organisation-chart.show', $chart))
            ->assertOk()
            ->assertSee($chart->title);

        $this->actingAs($officer)
            ->get(route('organisation-chart.create'))
            ->assertForbidden();
    }

    public function test_hr_manager_can_manage_organisation_charts(): void
    {
        $manager = $this->user($this->managerRole);
        $chart = OrganisationChart::create([
            'title' => 'USAID Action HIV Project Management Overview',
            'status' => OrganisationChart::STATUS_DRAFT,
            'created_by' => $manager->id,
            'updated_by' => $manager->id,
        ]);
        $node = $chart->nodes()->create([
            'label' => 'Chief of Party',
            'node_type' => OrganisationChartNode::TYPE_KEY_POSITION,
            'sort_order' => 1,
        ]);

        $this->actingAs($manager)
            ->get(route('organisation-chart.create'))
            ->assertOk();

        $this->actingAs($manager)
            ->get(route('organisation-chart.edit', $chart))
            ->assertOk();

        $this->actingAs($manager)
            ->get(route('organisation-chart.designer', $chart))
            ->assertOk();

        $this->actingAs($manager)
            ->post(route('organisation-chart.nodes.duplicate', [$chart, $node]))
            ->assertRedirect();
    }

    public function test_hr_officer_and_viewer_have_read_only_organisation_chart_access(): void
    {
        $chart = OrganisationChart::create([
            'title' => 'USAID Action HIV Project Management Overview',
            'status' => OrganisationChart::STATUS_PUBLISHED,
        ]);
        $node = $chart->nodes()->create([
            'label' => 'Chief of Party',
            'node_type' => OrganisationChartNode::TYPE_KEY_POSITION,
            'sort_order' => 1,
        ]);

        foreach ([$this->user($this->officerRole, $this->northern), $this->user($this->viewerRole, $this->northern)] as $user) {
            $this->actingAs($user)
                ->get(route('organisation-chart.index'))
                ->assertOk()
                ->assertSee($chart->title)
                ->assertDontSee('New Chart')
                ->assertDontSee(route('organisation-chart.archived'), false)
                ->assertDontSee(route('organisation-chart.archive', $chart), false);

            $this->actingAs($user)
                ->get(route('organisation-chart.show', $chart))
                ->assertOk()
                ->assertSee($chart->title)
                ->assertSee('Download PNG')
                ->assertSee('data-download-org-chart-png', false)
                ->assertDontSee('Open Designer')
                ->assertDontSee('Edit Chart')
                ->assertDontSee('Edit Box')
                ->assertDontSee('Duplicate Box')
                ->assertDontSee('Delete Box');

            $this->actingAs($user)
                ->get(route('organisation-chart.create'))
                ->assertForbidden();

            $this->actingAs($user)
                ->get(route('organisation-chart.edit', $chart))
                ->assertForbidden();

            $this->actingAs($user)
                ->get(route('organisation-chart.designer', $chart))
                ->assertForbidden();

            $this->actingAs($user)
                ->put(route('organisation-chart.update', $chart), [
                    'title' => $chart->title,
                    'status' => OrganisationChart::STATUS_PUBLISHED,
                    'nodes' => [],
                ])
                ->assertForbidden();

            $this->actingAs($user)
                ->patch(route('organisation-chart.layout.update', $chart), [
                    'nodes' => [
                        ['id' => $node->id, 'parent_id' => null, 'sort_order' => 1],
                    ],
                ])
                ->assertForbidden();

            $this->actingAs($user)
                ->post(route('organisation-chart.nodes.duplicate', [$chart, $node]))
                ->assertForbidden();

            $this->actingAs($user)
                ->delete(route('organisation-chart.nodes.destroy', [$chart, $node]))
                ->assertForbidden();

            $this->actingAs($user)
                ->patch(route('organisation-chart.archive', $chart))
                ->assertForbidden();
        }
    }

    public function test_organisation_chart_box_linked_employee_field_is_searchable(): void
    {
        $admin = $this->user($this->adminRole);
        $jobTitle = JobTitle::create(['name' => 'Chief of Party', 'code' => 'COP', 'is_active' => true]);
        $employee = $this->employee([
            'employee_no' => '22866',
            'first_name' => 'Paul',
            'last_name' => 'Chinyemba',
            'job_title_id' => $jobTitle->id,
        ]);
        $chart = OrganisationChart::create([
            'title' => 'USAID Action HIV Project Management Overview',
            'status' => OrganisationChart::STATUS_DRAFT,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $chart->nodes()->create([
            'label' => 'Chief of Party',
            'node_type' => OrganisationChartNode::TYPE_KEY_POSITION,
            'employee_id' => $employee->id,
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('organisation-chart.edit', $chart))
            ->assertOk()
            ->assertSee('data-org-employee-picker', false)
            ->assertSee('Search employee number, name, job title, or location')
            ->assertSee('22866 - Paul Chinyemba')
            ->assertSee('Chief of Party');
    }

    public function test_organisation_chart_box_validation_uses_friendly_messages(): void
    {
        $admin = $this->user($this->adminRole);
        $chart = OrganisationChart::create([
            'title' => 'USAID Action HIV Project Management Overview',
            'status' => OrganisationChart::STATUS_DRAFT,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->from(route('organisation-chart.edit', $chart))
            ->put(route('organisation-chart.update', $chart), [
                'title' => $chart->title,
                'status' => OrganisationChart::STATUS_DRAFT,
                'nodes' => [
                    [
                        'label' => '',
                        'node_type' => OrganisationChartNode::TYPE_SUPPORT_UNIT,
                        'sort_order' => 1,
                    ],
                ],
            ])
            ->assertRedirect(route('organisation-chart.edit', $chart))
            ->assertSessionHasErrors([
                'nodes.0.label' => 'Box label is required.',
            ]);
    }

    public function test_organisation_chart_edit_page_includes_drag_and_drop_controls(): void
    {
        $admin = $this->user($this->adminRole);
        $chart = OrganisationChart::create([
            'title' => 'USAID Action HIV Project Management Overview',
            'status' => OrganisationChart::STATUS_DRAFT,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $chart->nodes()->create([
            'label' => 'Chief of Party',
            'node_type' => OrganisationChartNode::TYPE_KEY_POSITION,
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('organisation-chart.edit', $chart))
            ->assertOk()
            ->assertSee('data-org-node-drag-handle', false)
            ->assertSee('Drop a saved box here to make it top level.')
            ->assertSee('Drag saved boxes onto another saved box');
    }

    public function test_saved_chart_boxes_are_collapsed_on_edit_page(): void
    {
        $admin = $this->user($this->adminRole);
        $chart = OrganisationChart::create([
            'title' => 'USAID Action HIV Project Management Overview',
            'status' => OrganisationChart::STATUS_DRAFT,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $chart->nodes()->create([
            'label' => 'Chief of Party',
            'node_type' => OrganisationChartNode::TYPE_KEY_POSITION,
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('organisation-chart.edit', $chart))
            ->assertOk()
            ->assertSee('Show Details')
            ->assertSee('Reports to: Top level')
            ->assertSee('No linked employee')
            ->assertSee('openNodeFromHash', false)
            ->assertSee('window.location.hash', false);
    }

    public function test_chart_box_click_opens_action_options_instead_of_direct_navigation(): void
    {
        $admin = $this->user($this->adminRole);
        $employee = $this->employee(['employee_no' => '22866', 'first_name' => 'Paul', 'last_name' => 'Chinyemba']);
        $chart = OrganisationChart::create([
            'title' => 'USAID Action HIV Project Management Overview',
            'status' => OrganisationChart::STATUS_PUBLISHED,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $node = $chart->nodes()->create([
            'label' => 'Chief of Party',
            'node_type' => OrganisationChartNode::TYPE_KEY_POSITION,
            'employee_id' => $employee->id,
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('organisation-chart.show', $chart))
            ->assertOk()
            ->assertSee('data-org-node-action', false)
            ->assertSee('View Employee Profile')
            ->assertSee('Edit Box')
            ->assertSee('Duplicate Box')
            ->assertSee('Delete Box')
            ->assertSee(route('employees.show', $employee), false)
            ->assertSee(route('organisation-chart.nodes.duplicate', [$chart, $node]), false)
            ->assertSee(route('organisation-chart.nodes.destroy', [$chart, $node]), false);
    }

    public function test_admin_can_duplicate_chart_box_without_copying_linked_employee(): void
    {
        $admin = $this->user($this->adminRole);
        $employee = $this->employee(['employee_no' => '22866', 'first_name' => 'Paul', 'last_name' => 'Chinyemba']);
        $chart = OrganisationChart::create([
            'title' => 'USAID Action HIV Project Management Overview',
            'status' => OrganisationChart::STATUS_DRAFT,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $node = $chart->nodes()->create([
            'label' => 'Chief of Party',
            'node_type' => OrganisationChartNode::TYPE_KEY_POSITION,
            'employee_id' => $employee->id,
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)
            ->post(route('organisation-chart.nodes.duplicate', [$chart, $node]))
            ->assertRedirect(route('organisation-chart.edit', $chart).'#node-'.OrganisationChartNode::latest('id')->first()->id);

        $copy = $chart->nodes()->where('label', 'Chief of Party Copy')->firstOrFail();

        $this->assertNull($copy->employee_id);
        $this->assertSame($node->parent_id, $copy->parent_id);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'organisation_chart_box_duplicated',
            'user_id' => $admin->id,
        ]);
    }

    public function test_exact_duplicate_chart_boxes_are_rejected(): void
    {
        $admin = $this->user($this->adminRole);
        $chart = OrganisationChart::create([
            'title' => 'USAID Action HIV Project Management Overview',
            'status' => OrganisationChart::STATUS_DRAFT,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->from(route('organisation-chart.edit', $chart))
            ->put(route('organisation-chart.update', $chart), [
                'title' => $chart->title,
                'status' => OrganisationChart::STATUS_DRAFT,
                'nodes' => [
                    [
                        'label' => 'Chief of Party',
                        'node_type' => OrganisationChartNode::TYPE_KEY_POSITION,
                        'sort_order' => 1,
                    ],
                    [
                        'label' => 'Chief of Party',
                        'node_type' => OrganisationChartNode::TYPE_KEY_POSITION,
                        'sort_order' => 2,
                    ],
                ],
            ])
            ->assertRedirect(route('organisation-chart.edit', $chart))
            ->assertSessionHasErrors([
                'nodes.1.label' => 'This chart already has an identical box. Change the label, linked employee, or reporting position before saving.',
            ]);
    }

    public function test_designer_layout_can_update_parent_and_sort_order(): void
    {
        $admin = $this->user($this->adminRole);
        $chart = OrganisationChart::create([
            'title' => 'USAID Action HIV Project Management Overview',
            'status' => OrganisationChart::STATUS_DRAFT,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $chief = $chart->nodes()->create([
            'label' => 'Chief of Party',
            'node_type' => OrganisationChartNode::TYPE_KEY_POSITION,
            'sort_order' => 1,
        ]);
        $technical = $chart->nodes()->create([
            'label' => 'Technical Director',
            'node_type' => OrganisationChartNode::TYPE_KEY_POSITION,
            'sort_order' => 2,
        ]);

        $this->actingAs($admin)
            ->get(route('organisation-chart.designer', $chart))
            ->assertOk()
            ->assertSee('Organisation Chart Designer')
            ->assertSee('Save Layout')
            ->assertSee('Edit Box Details')
            ->assertSee('data-designer-card', false)
            ->assertSee('data-designer-duplicate', false)
            ->assertSee(route('organisation-chart.nodes.destroy', [$chart, $chief]), false)
            ->assertSee('Duplicate')
            ->assertSee('Drop under')
            ->assertSee('Drop beside')
            ->assertDontSee('Designer Tools')
            ->assertDontSee('Box Types');

        $this->actingAs($admin)
            ->patch(route('organisation-chart.layout.update', $chart), [
                'nodes' => [
                    ['id' => $chief->id, 'parent_id' => null, 'sort_order' => 1],
                    ['id' => $technical->id, 'parent_id' => $chief->id, 'sort_order' => 1],
                ],
            ])
            ->assertRedirect(route('organisation-chart.designer', $chart));

        $this->assertDatabaseHas('organisation_chart_nodes', [
            'id' => $technical->id,
            'parent_id' => $chief->id,
            'sort_order' => 1,
        ]);
    }

    public function test_admin_can_duplicate_chart_box_from_designer(): void
    {
        $admin = $this->user($this->adminRole);
        $employee = $this->employee(['employee_no' => '22866', 'first_name' => 'Paul', 'last_name' => 'Chinyemba']);
        $chart = OrganisationChart::create([
            'title' => 'USAID Action HIV Project Management Overview',
            'status' => OrganisationChart::STATUS_DRAFT,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $node = $chart->nodes()->create([
            'label' => 'Chief of Party',
            'node_type' => OrganisationChartNode::TYPE_KEY_POSITION,
            'employee_id' => $employee->id,
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)
            ->post(route('organisation-chart.nodes.duplicate', [$chart, $node]), [
                'redirect_to' => 'designer',
            ])
            ->assertRedirect(route('organisation-chart.designer', $chart).'#node-'.OrganisationChartNode::latest('id')->first()->id);

        $copy = $chart->nodes()->where('label', 'Chief of Party Copy')->firstOrFail();

        $this->assertNull($copy->employee_id);
        $this->assertSame($node->parent_id, $copy->parent_id);
    }

    public function test_admin_can_delete_chart_box_and_keep_child_boxes(): void
    {
        $admin = $this->user($this->adminRole);
        $chart = OrganisationChart::create([
            'title' => 'USAID Action HIV Project Management Overview',
            'status' => OrganisationChart::STATUS_DRAFT,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $parent = $chart->nodes()->create([
            'label' => 'Chief of Party',
            'node_type' => OrganisationChartNode::TYPE_KEY_POSITION,
            'sort_order' => 1,
        ]);
        $child = $chart->nodes()->create([
            'label' => 'Technical Director',
            'parent_id' => $parent->id,
            'node_type' => OrganisationChartNode::TYPE_KEY_POSITION,
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)
            ->delete(route('organisation-chart.nodes.destroy', [$chart, $parent]))
            ->assertRedirect(route('organisation-chart.show', $chart));

        $this->assertDatabaseMissing('organisation_chart_nodes', [
            'id' => $parent->id,
        ]);
        $this->assertDatabaseHas('organisation_chart_nodes', [
            'id' => $child->id,
            'parent_id' => null,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'organisation_chart_box_deleted',
            'user_id' => $admin->id,
        ]);
    }

    public function test_admin_can_view_reporting_structure(): void
    {
        $admin = $this->user($this->adminRole);
        $supervisor = $this->employee(['employee_no' => 'SUP-001', 'first_name' => 'Mary', 'last_name' => 'Banda']);
        $employee = $this->employee([
            'employee_no' => 'EMP-001',
            'first_name' => 'Grace',
            'last_name' => 'Mwansa',
            'supervisor_employee_id' => $supervisor->id,
            'supervisor_name' => $supervisor->full_name,
        ]);

        $this->actingAs($admin)
            ->get(route('employees.reporting-structure', ['province_id' => $this->northern->id]))
            ->assertOk()
            ->assertSee('Reporting Structure')
            ->assertSee($supervisor->full_name)
            ->assertSee($employee->full_name);
    }

    public function test_reporting_structure_waits_for_search_or_filter_before_showing_employees(): void
    {
        $admin = $this->user($this->adminRole);
        $employee = $this->employee(['employee_no' => 'EMP-001', 'first_name' => 'Grace', 'last_name' => 'Mwansa']);

        $this->actingAs($admin)
            ->get(route('employees.reporting-structure'))
            ->assertOk()
            ->assertSee('Search or apply a filter to view the reporting structure.')
            ->assertDontSee($employee->employee_no);
    }

    public function test_hr_officer_only_sees_assigned_province_in_reporting_structure(): void
    {
        $officer = $this->user($this->officerRole, $this->northern);

        $this->employee(['employee_no' => 'NOR-001', 'first_name' => 'Northern', 'province_id' => $this->northern->id, 'district_id' => $this->kasama->id]);
        $this->employee(['employee_no' => 'LUA-001', 'first_name' => 'Luapula', 'province_id' => $this->luapula->id, 'district_id' => $this->mansa->id]);

        $this->actingAs($officer)
            ->get(route('employees.reporting-structure', ['province_id' => $this->northern->id]))
            ->assertOk()
            ->assertSee('NOR-001')
            ->assertDontSee('LUA-001');
    }

    public function test_employee_can_be_updated_with_linked_supervisor(): void
    {
        $admin = $this->user($this->adminRole);
        $jobTitle = JobTitle::create(['name' => 'HR Manager', 'code' => 'HRM', 'is_active' => true]);
        $supervisor = $this->employee(['employee_no' => 'SUP-001', 'first_name' => 'Mary', 'last_name' => 'Banda', 'job_title_id' => $jobTitle->id]);
        $employee = $this->employee(['employee_no' => 'EMP-001', 'first_name' => 'Grace', 'last_name' => 'Mwansa']);

        $this->actingAs($admin)
            ->put(route('employees.update', $employee), [
                'employee_no' => $employee->employee_no,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'province_id' => $employee->province_id,
                'district_id' => $employee->district_id,
                'supervisor_employee_id' => $supervisor->id,
                'supervisor_name' => '',
            ])
            ->assertRedirect(route('employees.show', $employee));

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'supervisor_employee_id' => $supervisor->id,
            'supervisor_name' => $supervisor->full_name,
        ]);
    }

    public function test_employee_cannot_be_their_own_supervisor(): void
    {
        $admin = $this->user($this->adminRole);
        $employee = $this->employee(['employee_no' => 'EMP-001']);

        $this->actingAs($admin)
            ->from(route('employees.edit', $employee))
            ->put(route('employees.update', $employee), [
                'employee_no' => $employee->employee_no,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'province_id' => $employee->province_id,
                'district_id' => $employee->district_id,
                'supervisor_employee_id' => $employee->id,
                'supervisor_name' => '',
            ])
            ->assertRedirect(route('employees.edit', $employee))
            ->assertSessionHasErrors('supervisor_employee_id');
    }

    public function test_admin_can_auto_link_line_managers_by_employee_number(): void
    {
        $admin = $this->user($this->adminRole);
        $lineManager = $this->employee([
            'employee_no' => '22866',
            'first_name' => 'Paul',
            'last_name' => 'Chinyemba',
        ]);
        $employee = $this->employee([
            'employee_no' => 'EMP-001',
            'first_name' => 'Ithamar',
            'last_name' => 'Nyangu',
            'supervisor_name' => '22866 - Mr P Chinyemba',
            'supervisor_employee_id' => null,
        ]);
        $unknown = $this->employee([
            'employee_no' => 'EMP-002',
            'first_name' => 'Unknown',
            'last_name' => 'Manager',
            'supervisor_name' => '99999 - Missing Person',
            'supervisor_employee_id' => null,
        ]);

        $this->actingAs($admin)
            ->from(route('employees.reporting-structure', ['province_id' => $this->northern->id]))
            ->post(route('employees.reporting-structure.link-line-managers'))
            ->assertRedirect(route('employees.reporting-structure', ['province_id' => $this->northern->id]))
            ->assertSessionHas('success');

        $this->assertSame($lineManager->id, $employee->fresh()->supervisor_employee_id);
        $this->assertSame('Paul Chinyemba', $employee->fresh()->supervisor_name);
        $this->assertNull($unknown->fresh()->supervisor_employee_id);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'line_managers_auto_linked',
            'user_id' => $admin->id,
        ]);
    }

    private function user(Role $role, ?Province $province = null): User
    {
        return User::factory()->create([
            'role_id' => $role->id,
            'province_id' => $province?->id,
            'is_active' => true,
        ]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function employee(array $attributes = []): Employee
    {
        return Employee::create(array_merge([
            'employee_no' => fake()->unique()->bothify('EMP-###'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'province_id' => $this->northern->id,
            'district_id' => $this->kasama->id,
        ], $attributes));
    }
}
