<?php
namespace Tests\Feature;
use App\Models\{Equipment,EquipmentItem,Facility,FermsRequest,Reservation,Transaction,User};
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
  public function test_equipment_borrow_quantity_is_limited_to_available_stock(): void {
    $student=$this->u('student');
    $equipment=Equipment::create(['name'=>'Projector','category'=>'AV','total_quantity'=>5,'available_quantity'=>3]);

    $this->actingAs($student)->get('/equipment')->assertOk()
      ->assertSee('<option value="'.$equipment->id.'" data-available="3"',false)
      ->assertSee('>Projector</option>',false)
      ->assertDontSee('Projector (3)',false)
      ->assertSee('Quantity (3 available)')
      ->assertSee('max="3"',false);

    $this->post('/borrow',[
      'equipment_id'=>$equipment->id,
      'quantity'=>4,
      'borrow_date'=>today()->toDateString(),
      'expected_return'=>today()->addDay()->toDateString(),
      'purpose'=>'Class',
    ])->assertSessionHasErrors('quantity');
    $this->assertSame(0,FermsRequest::count());
  }
  public function test_facility_request_offers_equipment_then_shows_staff_letter_reminder(): void {
    $student=$this->u('student');
    $facility=Facility::create(['name'=>'Hall','location'=>'Main','capacity'=>50]);
    $equipment=Equipment::create(['name'=>'Projector','category'=>'AV','total_quantity'=>5,'available_quantity'=>5]);

    $this->actingAs($student)->post('/reservations',[
      'facility_id'=>$facility->id,
      'date'=>today()->addDay()->toDateString(),
      'start_time'=>'09:00',
      'end_time'=>'10:00',
      'purpose'=>'Class activity',
    ])->assertRedirect('/requests')->assertSessionHas('request_followup','equipment');

    $this->get('/requests')->assertOk()
      ->assertSee('Would you also like to borrow equipment?')
      ->assertSee('Please visit the staff office to sign the required letter for your request.')
      ->assertSee('href="/equipment?request_followup=1"',false);

    $this->get('/equipment?request_followup=1')->assertOk()
      ->assertSee('action="/borrow?request_followup=1"',false);
    $this->post('/borrow?request_followup=1',[
      'equipment_id'=>$equipment->id,
      'quantity'=>1,
      'borrow_date'=>today()->addDay()->toDateString(),
      'expected_return'=>today()->addDays(2)->toDateString(),
      'purpose'=>'Class activity',
    ])->assertRedirect('/requests')->assertSessionHas('request_reminder',true);

    $this->get('/requests')->assertOk()
      ->assertSee('Please visit the staff office to sign the required letter for your request.')
      ->assertDontSee('<dialog class="confirm-dialog" id="request-followup-dialog">',false);
  }
  public function test_equipment_request_offers_facility_then_shows_staff_letter_reminder(): void {
    $student=$this->u('student');
    $equipment=Equipment::create(['name'=>'Projector','category'=>'AV','total_quantity'=>5,'available_quantity'=>5]);
    $facility=Facility::create(['name'=>'Hall','location'=>'Main','capacity'=>50]);

    $this->actingAs($student)->post('/borrow',[
      'equipment_id'=>$equipment->id,
      'quantity'=>1,
      'borrow_date'=>today()->addDay()->toDateString(),
      'expected_return'=>today()->addDays(2)->toDateString(),
      'purpose'=>'Class activity',
    ])->assertRedirect('/requests')->assertSessionHas('request_followup','facility');

    $this->get('/requests')->assertOk()
      ->assertSee('Would you also like to reserve a facility?')
      ->assertSee('href="/facilities?request_followup=1"',false);

    $this->get('/facilities?request_followup=1')->assertOk()
      ->assertSee('action="/reservations?request_followup=1"',false);
    $this->post('/reservations?request_followup=1',[
      'facility_id'=>$facility->id,
      'date'=>today()->addDays(2)->toDateString(),
      'start_time'=>'09:00',
      'end_time'=>'10:00',
      'purpose'=>'Class activity',
    ])->assertRedirect('/requests')->assertSessionHas('request_reminder',true);

    $this->get('/requests')->assertOk()
      ->assertSee('Please visit the staff office to sign the required letter for your request.')
      ->assertDontSee('<dialog class="confirm-dialog" id="request-followup-dialog">',false);
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
  public function test_dashboard_see_all_links_open_my_requests(): void {
    $student=$this->u('student');

    $response=$this->actingAs($student)->get('/dashboard')->assertOk();

    $response->assertDontSee('Search this page...')
      ->assertDontSee("document.getElementById('q')");
    $response->assertSee('Upcoming Reservations</h3><a class="link" href="/requests">See all',false);
    $response->assertSee('Borrowed Equipment</h3><a class="link" href="/requests">See all',false);
  }
  public function test_student_and_organization_can_choose_new_request_type_from_dashboard(): void {
    foreach (['student','organization'] as $role) {
      $response=$this->actingAs($this->u($role))->get('/dashboard')->assertOk();

      $response->assertSee('<details class="request-dropdown">',false)
        ->assertSee('<summary class="btn">+ New request</summary>',false)
        ->assertSee('href="/facilities">Facilities',false)
        ->assertSee('href="/equipment">Equipment',false)
        ->assertDontSee('<dialog class="card add-form" id="new-request-dialog">',false);
    }
  }
  public function test_only_pending_requests_show_in_requests_and_processed_requests_show_in_history(): void {
    $student=$this->u('student');
    $facility=Facility::create(['name'=>'Finished Hall','location'=>'Main','capacity'=>50,'status'=>'unavailable']);
    $upcomingFacility=Facility::create(['name'=>'Upcoming Hall','location'=>'Main','capacity'=>50,'status'=>'unavailable']);
    $rejectedFacility=Facility::create(['name'=>'Rejected Hall','location'=>'Main','capacity'=>50,'status'=>'unavailable']);
    $pendingFacility=Facility::create(['name'=>'Pending Hall','location'=>'Main','capacity'=>50]);
    $finishedRequest=FermsRequest::create(['user_id'=>$student->id,'type'=>'facility','purpose'=>'Past event','status'=>'approved']);
    $upcomingRequest=FermsRequest::create(['user_id'=>$student->id,'type'=>'facility','purpose'=>'Future event','status'=>'approved']);
    $rejectedRequest=FermsRequest::create(['user_id'=>$student->id,'type'=>'facility','purpose'=>'Rejected event','status'=>'rejected']);
    $pendingRequest=FermsRequest::create(['user_id'=>$student->id,'type'=>'facility','purpose'=>'Pending event','status'=>'pending']);
    Reservation::create(['request_id'=>$finishedRequest->id,'facility_id'=>$facility->id,'starts_at'=>now()->subDays(2),'ends_at'=>now()->subDay(),'status'=>'approved']);
    Reservation::create(['request_id'=>$upcomingRequest->id,'facility_id'=>$upcomingFacility->id,'starts_at'=>now()->addDay(),'ends_at'=>now()->addDay()->addHour(),'status'=>'approved']);
    Reservation::create(['request_id'=>$rejectedRequest->id,'facility_id'=>$rejectedFacility->id,'starts_at'=>now()->addDays(2),'ends_at'=>now()->addDays(2)->addHour(),'status'=>'rejected']);
    Reservation::create(['request_id'=>$pendingRequest->id,'facility_id'=>$pendingFacility->id,'starts_at'=>now()->addDays(3),'ends_at'=>now()->addDays(3)->addHour(),'status'=>'pending']);

    $returnedEquipment=Equipment::create(['name'=>'Returned Projector','category'=>'AV','total_quantity'=>5,'available_quantity'=>5]);
    $returnedRequest=FermsRequest::create(['user_id'=>$student->id,'type'=>'equipment','purpose'=>'Returned item','status'=>'returned']);
    EquipmentItem::create(['request_id'=>$returnedRequest->id,'equipment_id'=>$returnedEquipment->id,'quantity'=>1,'borrow_date'=>today()->subDays(2),'expected_return'=>today()->subDay()]);
    Transaction::create(['request_id'=>$returnedRequest->id,'equipment_id'=>$returnedEquipment->id,'user_id'=>$student->id,'quantity'=>1,'borrow_date'=>today()->subDays(2),'expected_return'=>today()->subDay(),'actual_return'=>now()->subDay(),'status'=>'returned']);

    $this->actingAs($student)->get('/requests')->assertOk()
      ->assertSee('Pending Hall')
      ->assertDontSee('Finished Hall')
      ->assertDontSee('Upcoming Hall')
      ->assertDontSee('Rejected Hall')
      ->assertDontSee('Returned Projector');

    $this->get('/transactions')->assertOk()
      ->assertSee('History')
      ->assertSee('Finished Hall')
      ->assertSee('Upcoming Hall')
      ->assertSee('Rejected Hall')
      ->assertSee('Returned Projector')
      ->assertDontSee('Pending Hall');
  }
}
