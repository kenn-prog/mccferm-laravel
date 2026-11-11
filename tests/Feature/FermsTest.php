<?php
namespace Tests\Feature;
use App\Models\{Equipment,EquipmentItem,Facility,FermsRequest,Notice,Reservation,Transaction,User};
use Illuminate\Foundation\Testing\RefreshDatabase; use Tests\TestCase;
class FermsTest extends TestCase {
  use RefreshDatabase;
  private function u(string $role): User { return User::create(['name'=>ucfirst($role),'email'=>"$role@mcc.edu.ph",'role'=>$role,'is_active'=>true]); }
  public function test_guests_are_redirected_to_login(): void { $this->get('/admin')->assertRedirect('/login'); }
  public function test_student_cannot_open_admin_pages(): void { $this->actingAs($this->u('student'))->get('/admin')->assertForbidden(); }
  public function test_staff_cannot_open_student_pages(): void { $this->actingAs($this->u('staff'))->get('/dashboard')->assertForbidden(); }
  public function test_staff_and_admin_can_send_return_reminders_to_student_and_organization_borrowers(): void {
    $equipment=Equipment::create(['name'=>'Projector','category'=>'AV','total_quantity'=>5,'available_quantity'=>3]);
    $student=null;

    foreach (['student','organization'] as $role) {
      $borrower=$this->u($role);
      if($role==='student') $student=$borrower;
      $transaction=Transaction::create([
        'equipment_id'=>$equipment->id,'user_id'=>$borrower->id,'quantity'=>1,
        'borrow_date'=>today(),'expected_return'=>today()->addDay(),'expected_return_time'=>'15:30','status'=>'borrowed',
      ]);

      $this->actingAs($this->u($role==='student'?'staff':'admin'))
        ->get('/admin/return-reminders')->assertOk()->assertSee('Return reminders')->assertSee($borrower->name)
        ->assertSee($role==='student'?'Student':'Organization');
      $this->post("/admin/transactions/{$transaction->id}/remind")
        ->assertRedirect('/admin/return-reminders')->assertSessionHas('ok');
      $this->assertDatabaseHas('notifications',[
        'user_id'=>$borrower->id,'title'=>'Equipment return reminder','type'=>'warning',
      ]);
      $notice=Notice::where('user_id',$borrower->id)->firstOrFail();
      $this->assertStringContainsString('Projector',$notice->message);
      $this->assertStringContainsString(today()->addDay()->format('M d, Y').' at 3:30 PM',$notice->message);

      $transaction->update(['status'=>'returned']);
      $this->post("/admin/transactions/{$transaction->id}/remind")->assertStatus(422);
    }

    $this->actingAs($student)->get('/admin/return-reminders')->assertForbidden();
  }
  public function test_only_admin_can_change_roles(): void {
    $target=$this->u('student');
    $this->actingAs($this->u('staff'))->post("/admin/users/{$target->id}",['role'=>'admin'])->assertForbidden();
    $this->actingAs($this->u('admin'))->post("/admin/users/{$target->id}",['role'=>'staff','is_active'=>1]);
    $this->assertSame('staff',$target->fresh()->role);
  }
  public function test_phone_number_is_required_for_student_and_admin_profiles(): void {
    foreach (['student'=>'/profile','staff'=>'/admin/profile'] as $role=>$path) {
      $user=$this->u($role);
      $this->actingAs($user)->get($path)->assertOk()
        ->assertSee('Phone Number')
        ->assertSee('name="phone"',false)
        ->assertSee('name="phone" value="" placeholder="09XX XXX XXXX" required',false);

      $this->post($path,['name'=>$user->name,'phone'=>''])->assertSessionHasErrors('phone');
      $this->post($path,['name'=>$user->name,'phone'=>'+639123456789'])
        ->assertRedirect($path)
        ->assertSessionHasNoErrors();
      $this->assertSame('+639123456789',$user->fresh()->phone);
    }
  }
  public function test_validation_errors_are_shown_in_a_dismissible_popup(): void {
    $student=$this->u('student');

    $this->actingAs($student)->post('/profile',['name'=>$student->name,'phone'=>''])
      ->assertSessionHasErrors('phone');

    $this->get('/profile')->assertOk()
      ->assertSee('<dialog class="confirm-dialog error-dialog" id="error-dialog"',false)
      ->assertSee('Something went wrong')
      ->assertSee('The phone field is required.')
      ->assertSee('data-close-error',false);
  }
  public function test_phone_backfill_only_fills_missing_numbers(): void {
    $missing=$this->u('student');
    $missing->update(['phone'=>null]);
    $blank=$this->u('organization');
    $blank->update(['phone'=>'']);
    $existing=$this->u('staff');
    $existing->update(['phone'=>'+639123456789']);

    $backfill=require database_path('migrations/2026_11_10_000002_fill_missing_user_phone_numbers.php');
    $backfill->up();
    $correction=require database_path('migrations/2026_11_10_000003_correct_sample_phone_number_format.php');
    $correction->up();
    $uniqueness=require database_path('migrations/2026_11_10_000004_make_sample_phone_numbers_unique.php');
    $uniqueness->up();

    $this->assertMatchesRegularExpression('/^09\d{2} \d{3} \d{4}$/',$missing->fresh()->phone);
    $this->assertMatchesRegularExpression('/^09\d{2} \d{3} \d{4}$/',$blank->fresh()->phone);
    $this->assertNotSame($missing->fresh()->phone,$blank->fresh()->phone);
    $this->assertSame('+639123456789',$existing->fresh()->phone);
  }
  public function test_rejected_and_cancelled_requests_are_shown_in_reports_not_request_queue(): void {
    $staff=$this->u('staff');
    $student=$this->u('student');
    $equipment=Equipment::create(['name'=>'Projector','category'=>'AV','total_quantity'=>5,'available_quantity'=>5]);

    foreach (['rejected','cancelled','released','returned','pending'] as $status) {
      $request=FermsRequest::create(['user_id'=>$student->id,'type'=>'equipment','purpose'=>ucfirst($status).' request','status'=>$status]);
      EquipmentItem::create(['request_id'=>$request->id,'equipment_id'=>$equipment->id,'quantity'=>1,'borrow_date'=>today(),'expected_return'=>today()->addDay()]);
    }

    $this->actingAs($staff)->get('/admin/requests')->assertOk()
      ->assertSee('Pending request')
      ->assertSee('id="request-decision-dialog"',false)
      ->assertSee('data-request-decision="approved"',false)
      ->assertSee('data-request-decision="rejected"',false)
      ->assertSee('Are you sure you want to')
      ->assertDontSee('Rejected request')
      ->assertDontSee('Cancelled request')
      ->assertDontSee('Released request')
      ->assertDontSee('Returned request')
      ->assertDontSee('rejected</option>')
      ->assertDontSee('cancelled</option>')
      ->assertDontSee('released</option>')
      ->assertDontSee('returned</option>');

    $this->get('/admin/requests?status=rejected')->assertOk()
      ->assertDontSee('Rejected request');
    $this->get('/admin/reports')->assertOk()
      ->assertSee('Rejected, Cancelled & Completed Requests',false)
      ->assertSee('Rejected request')
      ->assertSee('Cancelled request')
      ->assertSee('Released request')
      ->assertSee('Returned request');
  }
  public function test_conflicting_reservation_is_rejected(): void {
    $f=Facility::create(['name'=>'Hall','location'=>'Main','capacity'=>50]); $s=$this->u('student'); $other=$this->u('organization');
    $q=FermsRequest::create(['user_id'=>$other->id,'type'=>'facility','purpose'=>'x','status'=>'approved']);
    Reservation::create(['request_id'=>$q->id,'facility_id'=>$f->id,'starts_at'=>now()->addDay()->setTime(9,0),'ends_at'=>now()->addDay()->setTime(11,0),'status'=>'approved']);
    $this->actingAs($s)->post('/reservations',['facility_id'=>$f->id,'date'=>now()->addDay()->toDateString(),'start_time'=>'10:00','end_time'=>'12:00','purpose'=>'Meeting'])->assertSessionHasErrors();
    $this->assertSame(1,Reservation::count());
  }
  public function test_facility_and_equipment_times_are_limited_to_eight_am_through_eight_pm(): void {
    $student=$this->u('student');
    $facility=Facility::create(['name'=>'Hall','location'=>'Main','capacity'=>50]);
    $equipment=Equipment::create(['name'=>'Projector','category'=>'AV','total_quantity'=>5,'available_quantity'=>5]);
    $date=today()->addDay()->toDateString();

    $this->actingAs($student)->post('/reservations',[
      'facility_id'=>$facility->id,'date'=>$date,'start_time'=>'07:59','end_time'=>'08:30','purpose'=>'Early request',
    ])->assertSessionHasErrors('start_time');
    $this->post('/reservations',[
      'facility_id'=>$facility->id,'date'=>$date,'start_time'=>'19:00','end_time'=>'20:01','purpose'=>'Late request',
    ])->assertSessionHasErrors('end_time');
    $this->post('/reservations',[
      'facility_id'=>$facility->id,'date'=>$date,'start_time'=>'08:00','end_time'=>'20:00','purpose'=>'Valid request',
    ])->assertRedirect('/requests');

    $this->post('/borrow',[
      'equipment_id'=>$equipment->id,'quantity'=>1,'borrow_date'=>$date,'borrow_time'=>'07:59',
      'expected_return'=>$date,'expected_return_time'=>'09:00','purpose'=>'Early pickup',
    ])->assertSessionHasErrors('borrow_time');
    $this->post('/borrow',[
      'equipment_id'=>$equipment->id,'quantity'=>1,'borrow_date'=>$date,'borrow_time'=>'09:00',
      'expected_return'=>$date,'expected_return_time'=>'20:01','purpose'=>'Late return',
    ])->assertSessionHasErrors('expected_return_time');
    $this->post('/borrow',[
      'equipment_id'=>$equipment->id,'quantity'=>1,'borrow_date'=>$date,'borrow_time'=>'10:00',
      'expected_return'=>$date,'expected_return_time'=>'10:00','purpose'=>'Return time order',
    ])->assertSessionHasErrors('expected_return_time');
    $this->post('/borrow',[
      'equipment_id'=>$equipment->id,'quantity'=>1,'borrow_date'=>$date,'borrow_time'=>'08:00',
      'expected_return'=>$date,'expected_return_time'=>'20:00','purpose'=>'Valid pickup',
    ])->assertRedirect('/requests');

    $item=EquipmentItem::latest('id')->firstOrFail();
    $this->assertSame('08:00',$item->borrow_time);
    $this->assertSame('20:00',$item->expected_return_time);
  }
  public function test_facility_and_equipment_requests_reject_sundays_on_create_and_edit(): void {
    $student=$this->u('student');
    $facility=Facility::create(['name'=>'Hall','location'=>'Main','capacity'=>50]);
    $equipment=Equipment::create(['name'=>'Projector','category'=>'AV','total_quantity'=>5,'available_quantity'=>5]);
    $sunday=today()->next('Sunday');
    $saturday=$sunday->copy()->subDay();
    $monday=$sunday->copy()->addDay();

    $this->actingAs($student)->get('/facilities')->assertOk()
      ->assertSee('data-school-open-date',false)
      ->assertSee('School is closed on Sundays.');
    $this->get('/equipment')->assertOk()
      ->assertSee('data-school-open-date',false);

    $this->post('/reservations',[
      'facility_id'=>$facility->id,'date'=>$sunday->toDateString(),'start_time'=>'09:00','end_time'=>'10:00','purpose'=>'Sunday event',
    ])->assertSessionHasErrors('date');
    $this->post('/borrow',[
      'equipment_id'=>$equipment->id,'quantity'=>1,'borrow_date'=>$sunday->toDateString(),'borrow_time'=>'09:00',
      'expected_return'=>$monday->toDateString(),'expected_return_time'=>'10:00','purpose'=>'Sunday pickup',
    ])->assertSessionHasErrors('borrow_date');
    $this->post('/borrow',[
      'equipment_id'=>$equipment->id,'quantity'=>1,'borrow_date'=>$saturday->toDateString(),'borrow_time'=>'09:00',
      'expected_return'=>$sunday->toDateString(),'expected_return_time'=>'10:00','purpose'=>'Sunday return',
    ])->assertSessionHasErrors('expected_return');
    $this->assertSame(0,FermsRequest::count());

    $facilityRequest=FermsRequest::create(['user_id'=>$student->id,'type'=>'facility','purpose'=>'Edit facility']);
    Reservation::create(['request_id'=>$facilityRequest->id,'facility_id'=>$facility->id,'starts_at'=>$monday->copy()->addDay()->setTime(9,0),'ends_at'=>$monday->copy()->addDay()->setTime(10,0)]);
    $this->post("/requests/{$facilityRequest->id}/edit",[
      'request_id'=>$facilityRequest->id,'facility_id'=>$facility->id,'date'=>$sunday->toDateString(),
      'start_time'=>'09:00','end_time'=>'10:00','purpose'=>'Edit facility',
    ])->assertSessionHasErrors('date');

    $equipmentRequest=FermsRequest::create(['user_id'=>$student->id,'type'=>'equipment','purpose'=>'Edit equipment']);
    EquipmentItem::create(['request_id'=>$equipmentRequest->id,'equipment_id'=>$equipment->id,'quantity'=>1,'borrow_date'=>$monday,'borrow_time'=>'09:00','expected_return'=>$monday->copy()->addDay(),'expected_return_time'=>'10:00']);
    $this->post("/requests/{$equipmentRequest->id}/edit",[
      'request_id'=>$equipmentRequest->id,'equipment_id'=>$equipment->id,'quantity'=>1,
      'borrow_date'=>$saturday->toDateString(),'borrow_time'=>'09:00',
      'expected_return'=>$sunday->toDateString(),'expected_return_time'=>'10:00','purpose'=>'Edit equipment',
    ])->assertSessionHasErrors('expected_return');
    $this->post("/requests/{$equipmentRequest->id}/edit",[
      'request_id'=>$equipmentRequest->id,'equipment_id'=>$equipment->id,'quantity'=>1,
      'borrow_date'=>$sunday->toDateString(),'borrow_time'=>'09:00',
      'expected_return'=>$monday->toDateString(),'expected_return_time'=>'10:00','purpose'=>'Edit equipment',
    ])->assertSessionHasErrors('borrow_date');
  }
  public function test_equipment_release_and_return_update_stock(): void {
    $eq=Equipment::create(['name'=>'Projector','category'=>'AV','total_quantity'=>5,'available_quantity'=>5]); $s=$this->u('student'); $a=$this->u('staff');
    $this->actingAs($s)->post('/borrow',['equipment_id'=>$eq->id,'quantity'=>2,'borrow_date'=>today()->toDateString(),'borrow_time'=>'09:00','expected_return'=>today()->addDay()->toDateString(),'expected_return_time'=>'10:00','purpose'=>'Class']);
    $q=FermsRequest::firstOrFail();
    $this->actingAs($a)->post("/admin/requests/{$q->id}/decide",['decision'=>'approved']);
    $this->post("/admin/requests/{$q->id}/release"); $this->assertSame(3,$eq->fresh()->available_quantity);
    $tx=\App\Models\Transaction::firstOrFail(); $this->assertSame('09:00',$tx->borrow_time); $this->assertSame('10:00',$tx->expected_return_time);
    $this->post("/admin/transactions/{$tx->id}/return"); $this->assertSame(5,$eq->fresh()->available_quantity);
  }
  public function test_equipment_return_date_cannot_exceed_five_days_after_borrow_date(): void {
    $eq=Equipment::create(['name'=>'Projector','category'=>'AV','total_quantity'=>5,'available_quantity'=>5]);
    $student=$this->u('student');
    $this->actingAs($student)->post('/borrow',[
      'equipment_id'=>$eq->id,
      'quantity'=>1,
      'borrow_date'=>today()->toDateString(),
      'borrow_time'=>'09:00',
      'expected_return'=>today()->addDays(6)->toDateString(),
      'expected_return_time'=>'10:00',
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
      ->assertSee('max="3"',false)
      ->assertSee('name="borrow_time" min="08:00" max="20:00"',false)
      ->assertSee('name="expected_return_time" min="08:00" max="20:00"',false);

    $this->post('/borrow',[
      'equipment_id'=>$equipment->id,
      'quantity'=>4,
      'borrow_date'=>today()->toDateString(),
      'borrow_time'=>'09:00',
      'expected_return'=>today()->addDay()->toDateString(),
      'expected_return_time'=>'10:00',
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
      'borrow_time'=>'09:00',
      'expected_return'=>today()->addDays(2)->toDateString(),
      'expected_return_time'=>'10:00',
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
      'borrow_time'=>'09:00',
      'expected_return'=>today()->addDays(2)->toDateString(),
      'expected_return_time'=>'10:00',
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
      'borrow_time'=>'09:00',
      'expected_return'=>today()->addDays(2)->toDateString(),
      'expected_return_time'=>'10:00',
      'purpose'=>'Updated class activity',
    ])->assertStatus(302);

    $this->assertSame('Updated class activity',$request->fresh()->purpose);
    $this->assertSame(2,$request->item->fresh()->quantity);
    $this->assertSame('09:00',$request->item->fresh()->borrow_time);
    $this->assertSame('10:00',$request->item->fresh()->expected_return_time);
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
