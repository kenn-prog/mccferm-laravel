<?php
namespace Tests\Feature;
use App\Models\{Equipment,EquipmentItem,Facility,FermsRequest,Reservation,User};
use Illuminate\Foundation\Testing\RefreshDatabase; use Tests\TestCase;
class FermsTest extends TestCase {
  use RefreshDatabase;
  private function u(string $role): User { return User::create(['name'=>ucfirst($role),'email'=>"$role@mcc.edu.ph",'role'=>$role,'is_active'=>true]); }
  public function test_guests_are_redirected_to_login(): void { $this->get('/admin')->assertRedirect('/login'); }
  public function test_student_cannot_open_admin_pages(): void { $this->actingAs($this->u('student'))->get('/admin')->assertForbidden(); }
  public function test_staff_cannot_open_student_pages(): void { $this->actingAs($this->u('staff'))->get('/dashboard')->assertForbidden(); }
  public function test_only_admin_can_change_roles(): void {
    $target=$this->u('student');
    $this->actingAs($this->u('staff'))->post("/admin/users/{$target->id}",['role'=>'admin'])->assertForbidden();
    $this->actingAs($this->u('admin'))->post("/admin/users/{$target->id}",['role'=>'staff','is_active'=>1]);
    $this->assertSame('staff',$target->fresh()->role);
  }
  public function test_conflicting_reservation_is_rejected(): void {
    $f=Facility::create(['name'=>'Hall','location'=>'Main','capacity'=>50]); $s=$this->u('student'); $other=$this->u('organization');
    $q=FermsRequest::create(['user_id'=>$other->id,'type'=>'facility','purpose'=>'x','status'=>'approved']);
    Reservation::create(['request_id'=>$q->id,'facility_id'=>$f->id,'starts_at'=>now()->addDay()->setTime(9,0),'ends_at'=>now()->addDay()->setTime(11,0),'status'=>'approved']);
    $this->actingAs($s)->post('/reservations',['facility_id'=>$f->id,'date'=>now()->addDay()->toDateString(),'start_time'=>'10:00','end_time'=>'12:00','purpose'=>'Meeting'])->assertSessionHasErrors();
    $this->assertSame(1,Reservation::count());
  }
  public function test_equipment_release_and_return_update_stock(): void {
    $eq=Equipment::create(['name'=>'Projector','category'=>'AV','total_quantity'=>5,'available_quantity'=>5]); $s=$this->u('student'); $a=$this->u('staff');
    $this->actingAs($s)->post('/borrow',['equipment_id'=>$eq->id,'quantity'=>2,'borrow_date'=>today()->toDateString(),'expected_return'=>today()->addDay()->toDateString(),'purpose'=>'Class']);
    $q=FermsRequest::firstOrFail();
    $this->actingAs($a)->post("/admin/requests/{$q->id}/decide",['decision'=>'approved']);
    $this->post("/admin/requests/{$q->id}/release"); $this->assertSame(3,$eq->fresh()->available_quantity);
    $tx=\App\Models\Transaction::firstOrFail(); $this->post("/admin/transactions/{$tx->id}/return"); $this->assertSame(5,$eq->fresh()->available_quantity);
  }
  public function test_equipment_return_date_cannot_exceed_five_days_after_borrow_date(): void {
    $eq=Equipment::create(['name'=>'Projector','category'=>'AV','total_quantity'=>5,'available_quantity'=>5]);
    $student=$this->u('student');
    $this->actingAs($student)->post('/borrow',[
      'equipment_id'=>$eq->id,
      'quantity'=>1,
      'borrow_date'=>today()->toDateString(),
      'expected_return'=>today()->addDays(6)->toDateString(),
      'purpose'=>'Class',
    ])->assertSessionHasErrors('expected_return');
    $this->assertSame(0,FermsRequest::count());
  }
  public function test_student_can_edit_own_pending_equipment_request(): void {
    $student=$this->u('student');
    $equipment=Equipment::create(['name'=>'Projector','category'=>'AV','total_quantity'=>5,'available_quantity'=>5]);
    $request=FermsRequest::create(['user_id'=>$student->id,'type'=>'equipment','purpose'=>'Class']);
    EquipmentItem::create(['request_id'=>$request->id,'equipment_id'=>$equipment->id,'quantity'=>1,'borrow_date'=>today(),'expected_return'=>today()->addDay()]);

    $this->actingAs($student)->post("/requests/{$request->id}/edit",[
      'request_id'=>$request->id,
      'equipment_id'=>$equipment->id,
      'quantity'=>2,
      'borrow_date'=>today()->toDateString(),
      'expected_return'=>today()->addDays(2)->toDateString(),
      'purpose'=>'Updated class activity',
    ])->assertStatus(302);

    $this->assertSame('Updated class activity',$request->fresh()->purpose);
    $this->assertSame(2,$request->item->fresh()->quantity);
  }
  public function test_student_can_cancel_own_pending_facility_request(): void {
    $student=$this->u('student');
    $facility=Facility::create(['name'=>'Hall','location'=>'Main','capacity'=>50]);
    $request=FermsRequest::create(['user_id'=>$student->id,'type'=>'facility','purpose'=>'Meeting']);
    $reservation=Reservation::create(['request_id'=>$request->id,'facility_id'=>$facility->id,'starts_at'=>now()->addDay(),'ends_at'=>now()->addDay()->addHour()]);

    $this->actingAs($student)->post("/requests/{$request->id}/cancel")->assertStatus(302);

    $this->assertSame('cancelled',$request->fresh()->status);
    $this->assertSame('cancelled',$reservation->fresh()->status);
  }
  public function test_student_can_edit_own_pending_facility_request(): void {
    $student=$this->u('student');
    $facility=Facility::create(['name'=>'Hall','location'=>'Main','capacity'=>50]);
    $request=FermsRequest::create(['user_id'=>$student->id,'type'=>'facility','purpose'=>'Meeting']);
    $reservation=Reservation::create(['request_id'=>$request->id,'facility_id'=>$facility->id,'starts_at'=>now()->addDays(2)->setTime(9,0),'ends_at'=>now()->addDays(2)->setTime(10,0)]);

    $this->actingAs($student)->post("/requests/{$request->id}/edit",[
      'request_id'=>$request->id,
      'facility_id'=>$facility->id,
      'date'=>today()->addDays(3)->toDateString(),
      'start_time'=>'10:00',
      'end_time'=>'11:00',
      'participants'=>20,
      'purpose'=>'Updated meeting',
      'notes'=>'Updated details',
    ])->assertStatus(302);

    $this->assertSame('Updated meeting',$request->fresh()->purpose);
    $this->assertSame('Updated details',$request->fresh()->notes);
    $this->assertSame(today()->addDays(3)->toDateString(),$reservation->fresh()->starts_at->toDateString());
    $this->assertSame(20,$request->fresh()->participants);
  }
  public function test_student_cannot_edit_another_users_request(): void {
    $owner=$this->u('student');
    $other=$this->u('organization');
    $equipment=Equipment::create(['name'=>'Projector','category'=>'AV','total_quantity'=>5,'available_quantity'=>5]);
    $request=FermsRequest::create(['user_id'=>$owner->id,'type'=>'equipment','purpose'=>'Class']);
    EquipmentItem::create(['request_id'=>$request->id,'equipment_id'=>$equipment->id,'quantity'=>1,'borrow_date'=>today(),'expected_return'=>today()->addDay()]);

    $this->actingAs($other)->post("/requests/{$request->id}/cancel")->assertNotFound();
    $this->assertSame('pending',$request->fresh()->status);
  }
}
