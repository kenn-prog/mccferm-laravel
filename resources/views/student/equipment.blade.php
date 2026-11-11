@extends('layouts.app')
@section('content')
@php $cats = $items->pluck('category')->unique()->values(); $total=$items->sum('total_quantity'); $avail=$items->sum('available_quantity'); $borrowableItems=$items->where('status','available')->where('available_quantity','>',0); $selectedBorrowableItem=$borrowableItems->firstWhere('id',old('equipment_id')) ?? $borrowableItems->first(); @endphp
<div class="page-header">
  <div class="page-header-content">
    <div class="eyebrow">Inventory</div>
    <h1>Equipment</h1>
    <p class="sub">Check availability and request to borrow.</p>
  </div>
  <button class="btn btn-icon" type="button" data-toggle-target="borrow-dialog" aria-haspopup="dialog" aria-expanded="false" aria-controls="borrow-dialog">
    <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
    Borrow equipment
  </button>
</div>
<div class="chips"><button type="button" class="chip on" data-filter="all">All</button>@foreach($cats as $c)<button type="button" class="chip" data-filter="{{ strtolower($c) }}">{{ $c }}</button>@endforeach</div>
<div class="split">
  <div class="equipment-grid">
    @foreach($items as $e)
      @php $ok = $e->available_quantity>0 && $e->status==='available'; $g = strtolower($e->category); @endphp
      <div class="equipment-card" data-cat="{{ $g }}">
        <div class="card-header" style="background:linear-gradient(135deg,{{ in_array($g,['av']) ? '#9c27b0,#ce93d8' : (in_array($g,['sports']) ? '#ff6f00,#ffb74d' : (in_array($g,['furniture']) ? '#0097a7,#4dd0e1' : '#1c4fa8,#4aa8ff')) }})">
          <svg viewBox="0 0 24 24"><path d="m12 3 9 5-9 5-9-5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/></svg>
        </div>
        <div class="card-body">
          <div class="card-title">{{ $e->name }}</div>
          <div class="card-subtitle">
            <svg viewBox="0 0 24 24" style="width:14px;height:14px"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
            {{ $e->category }} · {{ $e->available_quantity }} of {{ $e->total_quantity }} available
          </div>
          <div class="card-footer">
            <span class="badge {{ $ok ? 'available' : 'unavailable' }}">{{ $ok ? 'Available' : 'Unavailable' }}</span>
            @if($ok)<button type="button" class="btn s" data-open-selected-dialog aria-haspopup="dialog" aria-expanded="false" aria-controls="borrow-dialog" onclick="pick('equipment_id','{{ $e->id }}','borrow-dialog'); updateBorrowQuantityLimit()">Borrow</button>@endif
          </div>
        </div>
      </div>
    @endforeach
  </div>
  <div class="enhanced-card">
    <div class="cardhead"><h3>Equipment Summary</h3></div>
    <div class="enhanced-item">
      <div class="item-icon">
        <svg viewBox="0 0 24 24"><path d="m12 3 9 5-9 5-9-5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/></svg>
      </div>
      <div class="item-content">
        <div class="item-title">{{ $total }}</div>
        <div class="item-subtitle">Total items</div>
      </div>
    </div>
    <div class="enhanced-item">
      <div class="item-icon">
        <svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
      </div>
      <div class="item-content">
        <div class="item-title">{{ $avail }}</div>
        <div class="item-subtitle">Available</div>
      </div>
    </div>
    <div class="enhanced-item">
      <div class="item-icon">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
      </div>
      <div class="item-content">
        <div class="item-title">{{ $total-$avail }}</div>
        <div class="item-subtitle">Borrowed</div>
      </div>
    </div>
  </div>
</div>
<dialog class="card add-form" id="borrow-dialog">
  <div class="add-form-header"><h3>Borrowing request</h3><button class="btn s l" type="button" data-close-dialog>Cancel</button></div>
  <form method="post" action="{{ request()->boolean('request_followup') ? '/borrow?request_followup=1' : '/borrow' }}">@csrf
    <div class="row">
      <div><label for="borrow-equipment">Equipment</label><select id="borrow-equipment" name="equipment_id" required>@forelse($borrowableItems as $e)<option value="{{ $e->id }}" data-available="{{ $e->available_quantity }}" @selected(old('equipment_id')==$e->id)>{{ $e->name }}</option>@empty<option value="" disabled selected>No equipment currently available</option>@endforelse</select></div>
      <div><label for="borrow-quantity" id="borrow-quantity-label">Quantity ({{ $selectedBorrowableItem?->available_quantity ?? 0 }} available)</label><input id="borrow-quantity" type="number" name="quantity" min="1" max="{{ $selectedBorrowableItem?->available_quantity ?? 0 }}" value="{{ old('quantity',1) }}" required></div>
      <div><label>Borrow date</label><input type="date" name="borrow_date" data-school-open-date min="{{ today()->toDateString() }}" value="{{ old('borrow_date') }}" required><small>School is closed on Sundays.</small></div>
      <div><label>Pickup time (8:00 AM–8:00 PM)</label><input type="time" name="borrow_time" min="08:00" max="20:00" value="{{ old('borrow_time') }}" required></div>
      <div><label>Expected return</label><input type="date" name="expected_return" data-school-open-date min="{{ old('borrow_date') }}" max="{{ old('borrow_date') ? \Illuminate\Support\Carbon::parse(old('borrow_date'))->addDays(5)->toDateString() : '' }}" value="{{ old('expected_return') }}" required><small style="display:block;color:var(--mute);margin-top:-8px">Maximum 5 days after the borrow date. School is closed on Sundays.</small></div>
      <div><label>Return time (8:00 AM–8:00 PM)</label><input type="time" name="expected_return_time" min="08:00" max="20:00" value="{{ old('expected_return_time') }}" required></div>
    </div>
    <label>Purpose</label><input name="purpose" required>
    <button class="btn">Submit request</button>
  </form>
</dialog>
@if($errors->any())<script>document.getElementById('borrow-dialog').showModal();</script>@endif
<script>
const borrowEquipmentInput = document.getElementById('borrow-equipment');
const borrowQuantityInput = document.getElementById('borrow-quantity');
const borrowQuantityLabel = document.getElementById('borrow-quantity-label');
function updateBorrowQuantityLimit() {
  const available = Number(borrowEquipmentInput.selectedOptions[0]?.dataset.available || 0);
  borrowQuantityInput.max = available;
  borrowQuantityLabel.textContent = 'Quantity (' + available + ' available)';
  if (Number(borrowQuantityInput.value) > available) borrowQuantityInput.value = String(available);
}
borrowEquipmentInput.addEventListener('change', updateBorrowQuantityLimit);
updateBorrowQuantityLimit();
const borrowDateInput = document.querySelector('#borrow-dialog [name="borrow_date"]');
const expectedReturnInput = document.querySelector('#borrow-dialog [name="expected_return"]');
function updateReturnDateLimits() {
  const borrowDate = borrowDateInput.value;
  expectedReturnInput.min = borrowDate;
  if (!borrowDate) {
    expectedReturnInput.removeAttribute('max');
    return;
  }
  const latestDate = new Date(borrowDate + 'T00:00:00');
  latestDate.setDate(latestDate.getDate() + 5);
  expectedReturnInput.max = [
    latestDate.getFullYear(),
    String(latestDate.getMonth() + 1).padStart(2, '0'),
    String(latestDate.getDate()).padStart(2, '0')
  ].join('-');
}
borrowDateInput.addEventListener('change', updateReturnDateLimits);
updateReturnDateLimits();
</script>
@endsection
