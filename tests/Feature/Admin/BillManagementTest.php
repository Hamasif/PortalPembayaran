<?php

namespace Tests\Feature\Admin;

use App\Enums\BillStatus;
use App\Models\AcademicTerm;
use App\Models\Bill;
use App\Models\Student;
use App\Models\User;
use App\Services\BillService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Student $student;

    private AcademicTerm $term;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->financeAdmin()->create();
        $this->student = Student::factory()->create(['nim' => '20230001']);
        $this->term = AcademicTerm::factory()->create();
    }

    public function test_admin_dapat_membuat_tagihan(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.bills.store'), [
            'student_id' => $this->student->id,
            'academic_term_id' => $this->term->id,
            'bill_type' => 'ukt',
            'amount' => 5_000_000,
            'due_date' => now()->addMonth()->toDateString(),
            'description' => 'UKT Semester Ganjil',
        ]);

        $response->assertRedirect(route('admin.bills.index'));

        $bill = Bill::where('student_id', $this->student->id)->first();
        $this->assertNotNull($bill);
        $this->assertEquals(5_000_000, $bill->amount);
        $this->assertEquals(BillStatus::Unpaid, $bill->status);
        $this->assertStringStartsWith('BILL-', $bill->bill_number);
    }

    public function test_tagihan_duplikat_pada_periode_sama_ditolak(): void
    {
        Bill::factory()->for($this->student)->for($this->term)->create(['bill_type' => 'ukt']);

        $response = $this->actingAs($this->admin)->post(route('admin.bills.store'), [
            'student_id' => $this->student->id,
            'academic_term_id' => $this->term->id,
            'bill_type' => 'ukt', // jenis sama, periode sama
            'amount' => 3_000_000,
            'due_date' => now()->addMonth()->toDateString(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status_type', 'error');
        $this->assertEquals(1, Bill::where('student_id', $this->student->id)->count());
    }

    public function test_tagihan_jenis_berbeda_pada_periode_sama_diizinkan(): void
    {
        Bill::factory()->for($this->student)->for($this->term)->create(['bill_type' => 'ukt']);

        $this->actingAs($this->admin)->post(route('admin.bills.store'), [
            'student_id' => $this->student->id,
            'academic_term_id' => $this->term->id,
            'bill_type' => 'other',
            'amount' => 500_000,
            'due_date' => now()->addMonth()->toDateString(),
        ]);

        $this->assertEquals(2, Bill::where('student_id', $this->student->id)->count());
    }

    public function test_nominal_nol_atau_negatif_ditolak(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.bills.store'), [
            'student_id' => $this->student->id,
            'academic_term_id' => $this->term->id,
            'bill_type' => 'ukt',
            'amount' => 0,
            'due_date' => now()->addMonth()->toDateString(),
        ]);

        $response->assertSessionHasErrors('amount');
    }

    public function test_tagihan_lunas_dibatalkan_ditolak(): void
    {
        $bill = Bill::factory()->for($this->student)->for($this->term)->paid()->create();

        $this->expectException(\InvalidArgumentException::class);

        app(BillService::class)->cancel($bill, $this->admin, 'alasan');
    }

    public function test_pembatalan_tagihan_tercatat_di_audit_log(): void
    {
        $bill = Bill::factory()->for($this->student)->for($this->term)->create();

        $this->actingAs($this->admin)->post(route('admin.bills.cancel', $bill), [
            'reason' => 'kesalahan input',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $this->admin->id,
            'action' => 'bill.cancelled',
            'entity_id' => $bill->id,
        ]);
        $this->assertEquals(BillStatus::Cancelled, $bill->refresh()->status);
    }

    public function test_filter_tagihan_per_status_dan_periode(): void
    {
        Bill::factory()->for($this->student)->for($this->term)->create(['status' => BillStatus::Paid->value]);
        Bill::factory()->for($this->student)->for($this->term)->create(['bill_type' => 'other']);

        $response = $this->actingAs($this->admin)->get(route('admin.bills.index', ['status' => 'paid']));

        $response->assertOk();
        $this->assertEquals(1, $response->viewData('bills')->count());
    }
}
