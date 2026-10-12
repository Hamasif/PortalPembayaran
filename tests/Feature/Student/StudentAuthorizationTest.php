<?php

namespace Tests\Feature\Student;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function verifiedStudent(): User
    {
        return User::factory()
            ->student()
            ->has(Student::factory(), 'student')
            ->create();
    }

    public function test_mahasiswa_tidak_dapat_membuka_tagihan_mahasiswa_lain(): void
    {
        $me = $this->verifiedStudent();
        $otherStudent = Student::factory()->create();
        $otherBill = Bill::factory()->for($otherStudent)->create();

        $response = $this->actingAs($me)->get(route('student.bills.show', $otherBill));

        $response->assertNotFound();
    }

    public function test_mahasiswa_tidak_dapat_membuka_bukti_pembayaran_mahasiswa_lain(): void
    {
        $me = $this->verifiedStudent();
        $otherStudent = Student::factory()->create();
        $otherBill = Bill::factory()->for($otherStudent)->create();
        $otherPayment = Payment::factory()->for($otherBill)->success()->create();

        $response = $this->actingAs($me)->get(route('student.payments.receipt', $otherPayment));

        $response->assertNotFound();
    }

    public function test_mahasiswa_tidak_dapat_melihat_transaksi_pending_mahasiswa_lain(): void
    {
        $me = $this->verifiedStudent();
        $otherStudent = Student::factory()->create();
        $otherBill = Bill::factory()->for($otherStudent)->create();
        $otherPayment = Payment::factory()->for($otherBill)->create();

        $response = $this->actingAs($me)->get(route('student.payments.show', $otherPayment));

        $response->assertNotFound();
    }

    public function test_index_tagihan_hanya_menampilkan_tagihan_sendiri(): void
    {
        $me = $this->verifiedStudent();
        $myBill = Bill::factory()->for($me->student)->create();
        Bill::factory()->count(3)->create(); // tagihan mahasiswa lain

        $response = $this->actingAs($me)->get(route('student.bills.index'));

        $response->assertOk();
        $response->assertSee($myBill->bill_number);
    }

    public function test_mahasiswa_tidak_dapat_membuat_pembayaran_untuk_tagihan_orang_lain(): void
    {
        $me = $this->verifiedStudent();
        $otherStudent = Student::factory()->create();
        $otherBill = Bill::factory()->for($otherStudent)->create();

        $response = $this->actingAs($me)->post(route('student.payments.store'), [
            'bill_id' => $otherBill->id,
        ]);

        $response->assertNotFound();
        $this->assertDatabaseMissing('payments', ['bill_id' => $otherBill->id]);
    }

    public function test_akun_belum_verifikasi_dialihkan_keluar_area_tagihan(): void
    {
        $pending = User::factory()->pendingVerification()->create();

        $response = $this->actingAs($pending)->get(route('student.bills.index'));

        $response->assertRedirect(route('verification.complete.form'));
    }
}
